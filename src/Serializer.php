<?php
declare(strict_types=1);

namespace Itav\Component\Serializer;

use DateTime;
use Exception;
use Itav\Component\Serializer\DocBlock\Parser;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

class Serializer
{
    const MAX_DEPTH = 50;
    const DATE_FORMAT = 'Y-m-d H:i:s';
    const ERROR_MODE_THROW = 1;
    const ERROR_MODE_SILENT = 2;

    private $tokenParser;
    private $datetimeFormat;
    private $errorMode;

    public function __construct(
        Parser $tokenParser,
        string $datetimeFormat = self::DATE_FORMAT,
        int $errorMode = self::ERROR_MODE_SILENT
    ) {
        $this->tokenParser = $tokenParser;
        $this->datetimeFormat = $datetimeFormat;
        $this->errorMode = $errorMode;
    }

    /**
     * @param string $json
     * @param string $class
     * @param null $dst
     * @param bool $useSetter
     * @return array|null
     * @throws SerializerException
     * @throws ReflectionException
     */
    public function unserialize(string $json, string $class, $dst = null, bool $useSetter = false)
    {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $msg = json_last_error_msg();
            return $this->handleError("Invalid json string: Error: {$msg}", null);
        }
        return $this->denormalize($data, $class, $dst, $useSetter);
    }

    /**
     * @param $obj
     * @param bool $withNull
     * @param bool $camelCase
     * @param bool $useGetter
     * @return null|string
     * @throws SerializerException
     * @throws ReflectionException
     */
    public function serialize($obj, bool $withNull = false, bool $camelCase = false, bool $useGetter = false): ?string
    {
        $data = $this->normalize($obj, $withNull, $camelCase, $useGetter);
        if (null === $data) {
            return null;
        }
        $json = json_encode($data);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $msg = json_last_error_msg();
            return $this->handleError("Invalid array when trying encode to json. Error: {$msg}", null);
        }

        return $json;
    }

    /**
     * @param $obj
     * @param bool $withNull
     * @param bool $camelCase
     * @param bool $useGetter
     * @param int $rec
     * @return array
     * @throws SerializerException
     * @throws ReflectionException
     */
    public function normalize(
        $obj,
        bool $withNull = false,
        bool $camelCase = false,
        bool $useGetter = false,
        int $rec = self::MAX_DEPTH
    ): array {
        if (null === $obj || is_scalar($obj) || $rec <= 0) {
            return $this->handleError("Invalid argument for normalize fnc. (Possible mac recursion exceeded.)", []);
        }

        if (is_array($obj)) {
            return $this->normalizeRecursive($obj, $withNull, $camelCase, $useGetter, $rec);
        }

        $result = [];
        $reflection = new ReflectionClass($obj);
        $properties = $reflection->getProperties();
        foreach ($properties as $property) {
            $value = $this->getPropertyValue($obj, $property, $withNull, $camelCase, $useGetter, $rec);

            if (!$withNull && null !== $value) {
                $result[$camelCase ? Tools::uncamelize($property->getName()) : $property->getName()] = $value;
            } elseif ($withNull) {
                $result[$camelCase ? Tools::uncamelize($property->getName()) : $property->getName()] = $value;
            }
        }
        return $result;
    }

    /**
     * @param array $data
     * @param string $className
     * @param null $obj
     * @param bool $useSetter
     * @param int $rec
     * @return array|null
     * @throws SerializerException
     * @throws ReflectionException
     */
    public function denormalize(
        array $data,
        string $className,
        $obj = null,
        bool $useSetter = false,
        int $rec = self::MAX_DEPTH
    ) {
        if ($rec <= 0) {
            return $this->handleError('Invalid argument for denormalize fnc. Max recursion exceeded.', null);
        }

        $isCollection = Tools::isCollection($className);
        $obj = $this->prepareObject($className, $obj);
        if (null === $obj) {
            return null;
        }

        if ($isCollection) {
            return $this->denormalizeRecursive($data, $className, $useSetter, $rec);
        }

        foreach ($data as $key => $value) {
            $prop = Tools::checkPropExist($className, (string)$key);
            if ($prop) {
                $this->setPropertyValue($obj, $value, $prop, $useSetter, $rec);
            }
        }
        return $obj;
    }

    /**
     * @param array $data
     * @param string $className
     * @param bool $useSetter
     * @param int $rec
     * @return array|mixed|null
     * @throws SerializerException
     * @throws ReflectionException
     */
    public function factory(
        array $data,
        string $className,
        bool $useSetter = false,
        int $rec = self::MAX_DEPTH
    ) {
        if ($rec <= 0) {
            return $this->handleError('Invalid argument for denormalize fnc. Max recursion exceeded.', null);
        }

        $isCollection = Tools::isCollection($className);
        if ($isCollection) {
            return $this->factoryRecursive($data, $className, $useSetter, $rec);
        }

        if (!class_exists($className)) {
            return $this->handleError("Class {$className} not exists.", null);
        }

        $rc = new ReflectionClass($className);
        $con = $rc->getConstructor();
        if (!$con) {
            return $this->denormalize($data, $className, null, $useSetter, $rec);
        }

        $params = [];
        $args = $con->getParameters();
        $reqCnt = $con->getNumberOfRequiredParameters();
        $passCnt = count($data);

        if ($passCnt < $reqCnt) {
            return $this->handleError("Arguments count is not enough.", null);
        }

        $isNumeric = Tools::isNumericArray($data);

        foreach ($args as $arg) {
            if ($arg->isCallable()) {
                return $this->handleError("Feature of creating with callable is not supported.", null);
            }

            if ($arg->isVariadic()) {
                return $this->handleError("Feature of creating with variadic is not supported.", null);
            }

            $paramName = $isNumeric ? $arg->getPosition() : $arg->getName();
            if (!$isNumeric && !array_key_exists($paramName, $data)) {
                $paramName = Tools::uncamelize($paramName);
                if (!array_key_exists($paramName, $data)) {
                    if (!$arg->isDefaultValueAvailable()) {
                        $this->handleError("Parameter not found", null, true);
                    }
                }
            }

            if ($isNumeric && !array_key_exists($paramName, $data)) {
                if (!$arg->isDefaultValueAvailable()) {
                    $this->handleError("Parameter not found", null, true);
                }
            }

            $val = array_key_exists($paramName, $data) ? $data[$paramName] : $arg->getDefaultValue();

            if ($arg->hasType()) {
                $type = $arg->getType();
                if ($type->isBuiltin()) {
                    $val = $this->getBuiltInValue($type->getName(), $val);
                    if (null === $val && !$type->allowsNull()) {
                        $this->handleError("Null passed for not null-able argument", null, true);
                    }
                } else {
                    if (!class_exists($type->getName())) {
                        $this->handleError("Null passed for not null-able argument", null, true);
                    }
                    if (null === $val && !$type->allowsNull()) {
                        $this->handleError("Null passed for not null-able argument", null, true);
                    }
                    if (null !== $val && !$type->allowsNull()) {
                        if (!is_a($val, $type->getName())) {
                            if (is_array($val)) {
                                $rec--;
                                $val = $this->factory($val, $type->getName(), $useSetter, $rec);
                                $rec++;
                            } else {
                                $this->handleError("Invalid argument type provided", null, true);
                            }
                        }
                    }
                }
            }
            $params[$arg->getPosition()] = $val;
        }

        if (count($params) < $reqCnt) {
            return $this->handleError("Arguments count is not enough.", null);
        }
        return new $className(...$params);
    }

    /**
     * @param $items
     * @param bool $withNull
     * @param bool $camelCase
     * @param bool $useGetter
     * @param int $rec
     * @return array
     * @throws SerializerException
     * @throws ReflectionException
     */
    private function normalizeRecursive($items, bool $withNull, bool $camelCase, bool $useGetter, int &$rec): array
    {
        $result = [];
        $rec--;
        if (is_object($items)) {
            $result = $this->normalize($items, $withNull, $camelCase, $useGetter, $rec);
        }

        if (!is_array($items)) {
            $rec++;
            return $result;
        }

        foreach ($items as $k => $item) {
            switch (true) {
                case null === $item && $withNull:
                    $result[$k] = null;
                    break;
                case is_scalar($item):
                    $result[$k] = $item;
                    break;
                case is_array($item):
                    $result[$k] = $this->normalizeRecursive($item, $withNull, $camelCase, $useGetter, $rec);
                    break;
                case $item instanceof DateTime:
                    $result[$k] = $item->format($this->datetimeFormat);
                    break;
                case is_object($item):
                    $result[$k] = $this->normalize($item, $withNull, $camelCase, $useGetter, $rec);
                    break;
            }
        }

        $rec++;
        return $result;
    }

    /**
     * @param $obj
     * @param ReflectionProperty $rp
     * @param bool $withNull
     * @param bool $camelCase
     * @param bool $useGetter
     * @param int $rec
     * @return array|mixed|null|string
     * @throws SerializerException
     * @throws ReflectionException
     */
    private function getPropertyValue(
        $obj,
        ReflectionProperty $rp,
        bool $withNull,
        bool $camelCase,
        bool $useGetter,
        int &$rec
    ) {
        if ($useGetter && $getter = Tools::genGetter($rp->getDeclaringClass()->name, $rp->getName())) {
            $value = $obj->$getter();
        } else {
            $rp->setAccessible(true);
            $value = $rp->getValue($obj);
        }

        switch (true) {
            case is_scalar($value):
                break;
            case null === $value:
                $value = null;
                break;
            case $value instanceof DateTime:
                $value = $value->format($this->datetimeFormat);
                break;
            case is_object($value) || is_array($value):
                $value = $this->normalizeRecursive($value, $withNull, $camelCase, $useGetter, $rec);
                break;
            default:
                $info = print_r($value, true);
                $value = $this->handleError("Not recognized type of value of Property: Value: {$info}", null);
        }
        return $value;
    }

    /**
     * @param string $className
     * @param null $obj
     * @return null
     * @throws SerializerException
     * @throws ReflectionException
     */
    private function prepareObject(string $className, $obj = null)
    {
        if (!class_exists($className)) {
            return $this->handleError("Class {$className} not exists.", null);
        }
        if (null === $obj) {
            $rc = new ReflectionClass($className);
            $con = $rc->getConstructor();
            if ($con && $con->getNumberOfRequiredParameters() > 0) {
                return $rc->newInstanceWithoutConstructor();
            }
            return new $className;
        }
        if (!($obj instanceof $className)) {
            return $this->handleError('Serializer Error. Destination object does not match to class.', null);
        }
        return $obj;
    }

    /**
     * @param array $data
     * @param string $className
     * @param bool $useSetter
     * @param int $rec
     * @return array
     * @throws SerializerException
     * @throws ReflectionException
     */
    private function denormalizeRecursive(
        array $data,
        string $className,
        bool $useSetter = false,
        int &$rec = self::MAX_DEPTH
    ) {
        $result = [];
        $rec--;
        foreach ($data as $key => $item) {
            $result[$key] = $this->denormalize($item, $className, null, $useSetter, $rec);
        }
        $rec++;
        return $result;
    }

    /**
     * @param array $data
     * @param string $className
     * @param bool $useSetter
     * @param int $rec
     * @return array
     * @throws SerializerException
     * @throws ReflectionException
     */
    private function factoryRecursive(
        array $data,
        string $className,
        bool $useSetter = false,
        int &$rec = self::MAX_DEPTH
    ) {
        $result = [];
        $rec--;
        foreach ($data as $key => $item) {
            $result[$key] = $this->factory($item, $className, $useSetter, $rec);
        }
        $rec++;
        return $result;
    }

    /**
     * @param $obj
     * @param $value
     * @param string $prop
     * @param bool $useSetter
     * @param int $rec
     * @throws SerializerException
     * @throws ReflectionException
     */
    private function setPropertyValue($obj, $value, string $prop, bool $useSetter, int &$rec): void
    {
        $rp = new ReflectionProperty($obj, $prop);
        $docInfo = $this->tokenParser->parseDoc($rp);
        switch (true) {
            case $useSetter && $setter = Tools::genSetter($rp->getDeclaringClass()->name, $prop):
                $rm = new ReflectionMethod($obj, $setter);
                $rm->setAccessible(true);
                $rm->invoke($obj, $value);
                return;
            case null === $value:
                break;
            case $docInfo && $docInfo->isBuiltIn():
                $value = $this->getBuiltInValue($docInfo->className(), $value);
                break;
            case is_array($value) && $docInfo:
                if ($docInfo->isArray()) {
                    $value = $this->denormalizeRecursive($value, $docInfo->className(), $useSetter, $rec);
                } else {
                    $rec--;
                    $value = $this->denormalize($value, $docInfo->className(), null, $useSetter, $rec);
                    $rec++;
                }
                break;
        }

        $rp->setAccessible(true);
        $rp->setValue($obj, $value);
    }

    /**
     * @param $type
     * @param $val
     * @return mixed
     * @throws SerializerException
     */
    private function getBuiltInValue(string $type, $val)
    {
        if ('int' === $type || 'integer' === $type) {
            $val = filter_var($val, FILTER_VALIDATE_INT);
            return false !== $val ? $val : null;
        }

        if ('float' === $type || 'double' === $type) {
            $val = filter_var($val, FILTER_VALIDATE_FLOAT);
            return false !== $val ? $val : null;
        }

        if ('bool' === $type || 'boolean' === $type) {
            return (bool)$val;
        }

        if ('DateTime' === ltrim($type, '\\')) {
            try {
                $intVal = filter_var($val, FILTER_VALIDATE_INT);
                return $intVal ? (new DateTime())->setTimestamp($intVal) : new DateTime($val);
            } catch (Exception $e) {
                $val = $this->handleError("Could not create DateTime object. Msg: {$e->getMessage()}", null);
            }
        }
        return $val;
    }

    /**
     * @param string $msg
     * @param $return
     * @param bool $forceThrow
     * @return mixed
     * @throws SerializerException
     */
    private function handleError(string $msg, $return, $forceThrow = false)
    {
        if (self::ERROR_MODE_THROW === $this->errorMode || $forceThrow) {
            throw new SerializerException($msg);
        }
        return $return;
    }
}
