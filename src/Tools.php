<?php
declare(strict_types=1);

namespace Itav\Component\Serializer;

class Tools
{
    public static function camelize(string $string): string
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

    public static function uncamelize(string $string): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $string));
    }

    public static function checkPropExist(string $className, string $property): ?string
    {
        if (property_exists($className, $property)) {
            return $property;
        }
        $property = self::camelize($property);
        if (property_exists($className, $property)) {
            return $property;
        }
        return null;
    }

    public static function genSetter(string $className, string $prop): ?string
    {
        $setter = 'set' . ucfirst($prop);
        return method_exists($className, $setter) ? $setter : null;
    }

    public static function genGetter(string $className, string $prop): ?string
    {
        $getter = 'get' . ucfirst($prop);
        if (method_exists($className, $getter)) {
            return $getter;
        }
        $getter = 'is' . ucfirst($prop);
        if (method_exists($className, $getter)) {
            return $getter;
        }
        if (method_exists($className, $prop)) {
            return $prop;
        }
        return null;
    }

    public static function isScalarClass(string $className): bool
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

    public static function isCollection(string &$className): bool
    {
        if (strpos($className, '[]')) {
            $className = rtrim($className, '[]');
            return true;
        }
        return false;
    }

    public static function isNumericArray(array &$input): bool
    {
        return array_keys($input) === array_keys(array_values($input));
    }
}
