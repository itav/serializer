<?php

namespace Itav\Component\Serializer;

class Serializer
{

    /**
     * Max recursion no.
     */
    const MAX_REC = 100;

    /**
     * @var int
     */
    private $rec = 0;
    /**
     * @var TokenParser
     */
    private $tokenParser;

    /**
     * Serializer constructor.
     */
    public function __construct()
    {
        $this->tokenParser = new TokenParser();
    }

    /**
     * @param array $src
     * @param string $class
     * @param mixed $dst
     * @param bool $useSetter
     * @return mixed
     * @throws SerializerException
     */
    public function denormalize($src, $class, $dst = null, $useSetter = false)
    {
        return $this->unserialize($src, $class, $dst, $useSetter);
    }

    /**
     * @param array $src
     * @param string $class
     * @param mixed $dst
     * @param bool $useSetter
     * @return mixed
     * @throws SerializerException
     */
    public function unserialize($src, $class, $dst = null, $useSetter = false)
    {
        if ($this->rec++ > self::MAX_REC) {
            return null;
        }
        $classArray = false;
        if (strpos($class, '[]')) {
            $class = str_replace('[]', '', $class);
            $classArray = true;
        }

        if (!$src) {
            //Nie wyrzucaj wyjątku kiedy puste dane wejściowe - zwracaj nowy obiekt klasy z 2 parametru.
            $src = [];
            //throw new SerializerException('Serializer Error. Source data is empty or null.');
        }

        if (!$class && !class_exists($class)) {

            throw new SerializerException('Serializer Error. Class specified as 2 parameter does not exist');
        }

        if ($dst) {
            if (!($dst instanceof $class)) {
                throw new SerializerException('Serializer Error. Destination object does not match to class specified as 2 parameter.');
            }
        } else {

            $dst = new $class;
        }

        if (!is_array($src)) {
            if (is_object($src)) {
                //TODO array map or walk recursive
                $src = (array)$src;
            } else {
                //Nie wyrzucaj wyjątku kiedy danych wejsciowych nie sie przekonwerowac na tablice - zwracaj nowy pusty obiekt klasy z 2 parametru.
                //throw new SerializerException('Serializer Error. Src is not array and can not be decoded to an array.');
                $src = [];
            }
        }

        if ($classArray) {
            if (is_array($src)) {
                $dstArr = [];
                foreach ($src as $id => $recV) {
                    $dstArr[$id] = $this->unserialize($recV, $class, null, $useSetter);
                }
                ($this->rec === 0) ?: $this->rec--;
                return $dstArr;
            } else {
                throw new SerializerException('Serializer Error. Src is not an array.');
            }
        }

        foreach ($src as $k => $val) {

            if (!$key = $this->keyExists($class, $k)) {
                continue;
            }

            $rp = new \ReflectionProperty($dst, $key);
            $docParse = $this->parseDoc($rp->getDocComment());
            $objFlag = false;
            if (is_array($val) && $docParse->isValid()) {
                $docParseDetail = $this->parseDoc($rp->getDocComment(), new \ReflectionClass($dst), true);
                if ($docParseDetail->isValid()) {

                    if ($docParseDetail->isArray()) {
                        $temp = [];
                        foreach ($val as $idx => $recVal) {
                            $temp[$idx] = $this->unserialize($recVal, $docParseDetail->getClassName(), null, $useSetter);
                        }
                        $val = $temp;
                    } else {

                        $val = $this->unserialize($val, $docParseDetail->getClassName(), null, $useSetter);
                    }
                    $objFlag = true;
                }
            }

            $setter = false;
            if ($useSetter) {
                $setter = 'set' . ucfirst($key);
                $setter = method_exists($dst, $setter) ? $setter : false;
            }

            if ($setter) {
                $dst->$setter($val);
                continue;
            }

            if (null === $val) {
                $rp->setAccessible(true);
                $rp->setValue($dst, null);
                continue;
            }

            if (!$objFlag && $docParse->isScalar()) {
                $val = $this->generateValue($docParse->getClassName(), $val);
            }

            $rp->setAccessible(true);
            $rp->setValue($dst, $val);

        }
        ($this->rec === 0) ?: $this->rec--;
        return $dst;
    }

    /**
     * @param string $class
     * @param string $key
     * @return bool | string
     */
    private function keyExists($class, $key)
    {
        $camelKey = self::camelize($key);
        if (property_exists($class, $camelKey)) {
            return $camelKey;
        }
        if (property_exists($class, $key)) {
            return $key;
        }
        return false;
    }

    /**
     * @param mixed $src
     * @param bool $withNull
     * @param bool $snakeCase
     * @param bool $useGetter
     * @return array
     */
    public function normalize($src, $withNull = false, $snakeCase = true, $useGetter = false)
    {
        if(null === $src){
            return null;
        }

        if (is_array($src)) {
            $res = [];
            foreach ($src as $k => $v) {
                $res[$k] = $this->normalize($v, $withNull, $snakeCase, $useGetter);
            }
            return $res;
        }

        if (!is_object($src)) {
            $this->rec--;
            return is_scalar($src) ? $src : [];
        }

        if ($this->rec++ > self::MAX_REC) {
            return [];
        }

        $ret = [];
        $reflection = new \ReflectionClass($src);
        foreach ($reflection->getProperties() as $property) {
            $key = $property->getName();
            $getter = false;
            if ($useGetter) {
                $getter = 'get' . ucfirst($key);
                $getter = method_exists($src, $getter) ? $getter : (method_exists($src, 'is' . ucfirst($key)) ? 'is' . ucfirst($key) : false);
            }
            if ($getter) {
                $value = $src->$getter();
            } else {
                $property->setAccessible(true);
                $value = $property->getValue($src);
            }

            if (is_array($value)) {
                $temp = [];
                foreach ($value as $k => $v) {
                    $temp[$k] = $this->normalize($v, $withNull, $snakeCase, $useGetter);
                }
                $value = $temp;
            } else if (is_object($value)) {
                if (!$getter && $value instanceof \DateTime) {
                    $value = $value->format('Y-m-d H:i:s');
                } else {
                    $parseDoc = $this->parseDoc($property->getDocComment(), $reflection, true);
                    if ($parseDoc->isValid()) {
                        $value = $this->normalize($value, $withNull, $snakeCase, $useGetter);
                    }
                }

            }
            if ($withNull) {
                $ret[$snakeCase ? self::uncamelize($key) : $key] = $value;
                continue;
            } elseif (null !== $value) {
                $ret[$snakeCase ? self::uncamelize($key) : $key] = $value;
            }
        }
        ($this->rec === 0) ?: $this->rec--;
        return $ret;
    }

    /**
     *
     * @param DocParserResponse $res
     * @param \ReflectionClass $rc
     * @return DocParserResponse
     */
    public function checkClassName($res, $rc)
    {
        $res->setValid(false);
        if ($class = $res->getClassName()) {

            if (strpos($class, '\\') !== 0) {
                $ns = $rc->getNamespaceName();
                $fullName = $ns . '\\' . $class;
                if (class_exists($fullName)) {
                    $res->setValid(true);
                    $res->setClassName($fullName);
                } else {
                    $file = $rc->getFileName();
                    $useName = $this->tokenParser->findUseStatement($file, $class);
                    if (class_exists($useName)) {
                        $res->setValid(true);
                        $res->setClassName($useName);
                    } elseif (class_exists($class)) {
                        $res->setValid(true);
                    }
                }
            } else if (class_exists($class)) {
                $res->setValid(true);
            }
        }
        return $res;
    }

    /**
     *
     * @param string $str
     * @param \ReflectionClass $rc
     * @param bool $deep
     * @return DocParserResponse
     */
    public function parseDoc($str, $rc = null, $deep = false)
    {
        $res = new DocParserResponse();
        $matches = [];
        if (preg_match('/@var\s+([\w\\\[\]]+)/', $str, $matches)) {
            $type = $matches[1];
            if ($type) {
                if (strpos($type, '[]')) {
                    $type = str_replace('[]', '', $type);
                    $res->setArray(true);
                }
                $res->setClassName($type);
                if ($this->isScalarClass($type)) {
                    $res->setScalar(true);
                } else {
                    $res->setValid(true);
                }
            }
        }
        if ($deep && $res->isValid()) {
            return $this->checkClassName($res, $rc);
        }
        return $res;
    }

    /**
     * @param $string
     * @return string
     */
    public static function camelize($string)
    {
        $strings = explode('_', $string);
        $first = true;
        foreach ($strings as &$v) {
            if ($first) {
                $first = false;
                continue;
            }
            $v = ucfirst($v);
        }
        return implode('', $strings);
    }

    /**
     * @param $string
     * @return string
     */
    public static function uncamelize($string)
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $string));
    }

    /**
     * @param $type
     * @param $val
     * @return mixed
     * @throws \Itav\Component\Serializer\SerializerException
     */
    private function generateValue($type, $val)
    {
        if ('int' === $type || 'integer' === $type) {
            return (int)$val;
        }

        if ('bool' === $type || 'boolean' === $type) {
            return (bool)$val;
        }

        if ('DateTime' === ltrim($type, '\\')) {
            try {
                return new \DateTime($val);
            } catch (\Exception $e) {
                try{
                    $intVal = filter_var($val, FILTER_VALIDATE_INT);
                    return $intVal ? (new \DateTime())->setTimestamp($intVal) : new \DateTime($val);
                }catch (\Exception $e2){
                    throw new SerializerException('Could not create DateTime object. '. $e2->getMessage());
                }
            }
        }
        return $val;
    }

    /**
     * @param string $className
     * @return bool
     */
    private function isScalarClass($className)
    {
        $cl = ltrim(strtolower($className), '\\');
        if (in_array($cl, [
            'int',
            'integer',
            'string',
            'bool',
            'boolean',
            'datetime',
            'array',
            'null'
        ])) {
            return true;
        }
        return false;
    }

}
