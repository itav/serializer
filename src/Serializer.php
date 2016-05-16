<?php

namespace Itav\Component\Serializer;

class AnnotateException extends \Exception {
    
}

class Serializer {
    
    const MAX_REC = 50;
    private $rec = 0;
    private $tokenParser;
    
    public function __construct() {
        $this->tokenParser = new TokenParser();
    }

    public function unserialize($src, $class, $dst = null) {

        if (!$src) {
            throw new AnnotateException('Wrong Src');
        }

        if (!$class && !class_exists($class)) {
            
            throw new AnnotateException('Class not exist');
        }

        if ($dst) {
            if (!($dst instanceof $class)) {
                throw new AnnotateException('Dst does not match to class');
            }
        } else {

            $dst = new $class;
        }
        
        if(!is_array($src)){
            if(is_object($src)){
                //TODO array map or walk recursive
                $src = (array) $src;
            }elseif( ($tmp = json_decode($src, true)) && json_last_error() == JSON_ERROR_NONE){
                $src = $tmp;
            }else{
                throw new AnnotateException('Wrong Src');
            }
        }

        foreach ($src as $k => $val) {
            $key = self::camelize($k);
            if(is_array($val)){
                if (property_exists($class, $key)) {
                    $rp = new \ReflectionProperty($dst, $key);
                    if (preg_match('/@var\s+([^\s]+)/', $rp->getDocComment(), $matches)) {
                        //TODO parse use statements and try to find class with prefix
                        $type = $matches[1];
                        if ($type && class_exists($type)) {
                            $this->rec++;
                            if($this->rec <= self::MAX_REC){
                                $val = $this->unserialize($val, $type);
                            }else{
                                //TODO $val = $callback
                                continue;
                            }
                        }                        
                    }
                }                
            }
            if (property_exists($class, $key)) {
                $rp = new \ReflectionProperty($dst, $key);
                if ($rp->isPrivate() || $rp->isProtected()) {
                    // Run if the property is private
                    $setter = 'set' . ucfirst($key);
                    if (method_exists($dst, $setter)) {
                        if (!is_null($val)) {
                            if (preg_match('/@var\s+([^\s]+)/', $rp->getDocComment(), $matches)) {
                                $type = $matches[1];
                                if ('DateTime' == $type || '\DateTime' == $type) {
                                    $val = new \DateTime($val);
                                }
                            }
                        }
                        $dst->$setter($val);
                    }
                } else {
                    // Run if the property is Public
                    if (!is_null($val)) {
                        if (preg_match('/@var\s+([^\s]+)/', $rp->getDocComment(), $matches)) {
                            $type = $matches[1];
                            if ('DateTime' == $type || '\DateTime' == $type) {
                                $val = new \DateTime($val);
                            }
                        }
                    }
                    $dst->$key = $val;
                }
            }
        }
        return $dst;
    }

    public static function normalize($src, $with_null = false) {
        if (!is_object($src)) {
            return [];
        }
        $ret = [];
        $reflection = new ReflectionClass($src);

        foreach ($reflection->getProperties() as $property) {
            $key = $property->getName();
            if ($key == 'tableName') {
                continue;
            }
            if ($property->isPublic()) {

                $value = $property->getValue($src);
                if ($value instanceof \DateTime) {
                    $value = $value->format("Y-m-d H:i:s");
                }
                if ($with_null) {
                    $ret[self::uncamelize($key)] = $value;
                    continue;
                } elseif (!is_null($value)) {

                    $ret[self::uncamelize($key)] = $value;
                }
            } else {
                $getterName = 'get' . ucfirst($key);
                $checkerName = 'is' . ucfirst($key);
                if (method_exists($src, $getterName)) {
                    $value = call_user_func(array($src, $getterName));
                    if ($value instanceof \DateTime) {
                        $value = $value->format("Y-m-d H:i:s");
                    }
                    if ($with_null) {
                        $ret[self::uncamelize($key)] = $value;
                        continue;
                    } elseif (!is_null($value)) {

                        $ret[self::uncamelize($key)] = $value;
                    }
                } elseif (method_exists($src, $checkerName)) {

                    $value = call_user_func(array($src, $checkerName));
                    if ($value instanceof \DateTime) {
                        $value = $value->format("Y-m-d H:i:s");
                    }
                    if ($with_null) {
                        $ret[self::uncamelize($key)] = $value;
                        continue;
                    } elseif (!is_null($value)) {

                        $ret[self::uncamelize($key)] = $value;
                    }
                }
            }
        }
        return $ret;
    }

    public static function camelize($string) {
        $strings = explode("_", $string);
        $first = true;
        foreach ($strings as &$v) {
            if ($first) {
                $first = false;
                continue;
            }
            $v = ucfirst($v);
        }
        return implode("", $strings);
    }

    public static function uncamelize($string) {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $string));
    }

}

