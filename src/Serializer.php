<?php

namespace Itav\Component\Serializer;

class AnnotateException extends \Exception {
    
}

class DocParserResponse {

    private $valid = false;
    private $className = '';
    private $array = false;

    public function isValid() {
        return (bool) $this->valid;
    }

    public function getClassName() {
        return $this->className;
    }

    public function isArray() {
        return (bool) $this->isArray;
    }

    public function setValid($valid) {
        $this->valid = $valid;
        return $this;
    }

    public function setClassName($className) {
        $this->className = $className;
        return $this;
    }

    public function setArray($isArray) {
        $this->isArray = $isArray;
        return $this;
    }

}

class Serializer {

    const MAX_REC = 50;

    private $rec = 0;
    private $tokenParser;

    public function __construct() {
        $this->tokenParser = new TokenParser();
    }

    public function unserialize($src, $class, $dst = null) {
        if ($this->rec++ > self::MAX_REC) {
            return null;
        }
        $classAray = false;
        if (strpos($class, '[]')) {
            $class = str_replace('[]', '', $class);
            $classAray = true;
        }

        if (!$src) {
            throw new AnnotateException('Wrong Src.');
        }

        if (!$class && !class_exists($class)) {

            throw new AnnotateException('Class not exist');
        }

        $rc = new \ReflectionClass($class);
        $classFile = $rc->getFileName();

        if ($dst) {
            if (!($dst instanceof $class)) {
                throw new AnnotateException('Dst does not match to class');
            }
        } else {

            $dst = new $class;
        }

        if (!is_array($src)) {
            if (is_object($src)) {
                //TODO array map or walk recursive
                $src = (array) $src;
            } elseif (($tmp = json_decode($src, true)) && json_last_error() == JSON_ERROR_NONE) {
                $src = $tmp;
            } else {
                throw new AnnotateException('Wrong Src');
            }
        }

        if ($classAray) {
            if ($this->isNumArray($src)) {
                $dstArr = [];
                for ($i = 0; $i < count($src); $i++) {
                    $dstArr[] = $this->unserialize($src[$i], $class);
                }
                ($this->rec === 0) ? : $this->rec--;
                return $dstArr;
            } else {
                throw new AnnotateException('Wrong Src. Array expected.');
            }
        }

        foreach ($src as $k => $val) {
            $key = self::camelize($k);
            if (is_array($val)) {
                if ($this->isNumArray($val)) {
                    if (property_exists($class, $key)) {
                        $rp = new \ReflectionProperty($dst, $key);
                        if (preg_match('/@var\s+([\w\\\[\]]+)/', $rp->getDocComment(), $matches)) {
                            //TODO parse use statements and try to find class with prefix
                            $type = $matches[1];
                            if ($type && strpos($type, '[]')) {
                                $type = str_replace('[]', '', $type);
                                $classExists = false;
                                if (strpos($type, '\\') !== 0) {
                                    $rc = new \ReflectionClass($class);
                                    $ns = $rc->getNamespaceName();
                                    if (class_exists($ns . '\\' . $type)) {
                                        $classExists = true;
                                        $type = $ns . '\\' . $type;
                                    } else {
                                        $file = $rc->getFileName();
                                        $tempType = $this->tokenParser->findUseStatement($file, $type);
                                        if (class_exists($tempType)) {
                                            $classExists = true;
                                            $type = $tempType;
                                        } elseif (class_exists($type)) {
                                            $classExists = true;
                                        }
                                    }
                                } else if (class_exists($type)) {
                                    $classExists = true;
                                }
                                if ($classExists) {
                                    $temp = [];
                                    for ($j = 0; $j < count($val); $j++) {
                                        $temp[] = $this->unserialize($val[$j], $type);
                                    }
                                    $val = $temp;
                                }
                            }
                        }
                    }
                } else {

                    if (property_exists($class, $key)) {
                        $rp = new \ReflectionProperty($dst, $key);
                        if (preg_match('/@var\s+([\w\\\[\]]+)/', $rp->getDocComment(), $matches)) {
                            //TODO parse use statements and try to find class with prefix
                            $type = $matches[1];
                            $classExists = false;
                            if ($type) {

                                if (strpos($type, '\\') !== 0) {
                                    $rc = new \ReflectionClass($class);
                                    $ns = $rc->getNamespaceName();
                                    if (class_exists($ns . '\\' . $type)) {
                                        $classExists = true;
                                        $type = $ns . '\\' . $type;
                                    } else {
                                        $file = $rc->getFileName();
                                        $tempType = $this->tokenParser->findUseStatement($file, $type);
                                        if (class_exists($tempType)) {
                                            $classExists = true;
                                            $type = $tempType;
                                        } elseif (class_exists($type)) {
                                            $classExists = true;
                                        }
                                    }
                                } else if (class_exists($type)) {
                                    $classExists = true;
                                }
                            }
                            if ($classExists) {
                                $val = $this->unserialize($val, $type);
                            }
                        }
                    }
                }
            }


            if (property_exists($class, $key)) {
                $rp = new \ReflectionProperty($dst, $key);
                if (!is_null($val)) {
                    if (preg_match('/@var\s+([\w\\\[\]]+)/', $rp->getDocComment(), $matches)) {
                        $type = $matches[1];
                        if ('DateTime' == $type || '\DateTime' == $type) {
                            $val = new \DateTime($val);
                        }
                    }
                }
                $rp->setAccessible(true);
                $rp->setValue($dst, $val);
            }
        }
        ($this->rec === 0) ? : $this->rec--;
        return $dst;
    }

    public function normalize($src, $with_null = false) {
        if (!is_object($src)) {
            return [];
        }
        
        if ($this->rec++ > self::MAX_REC) {
            return [];
        }
        
        $ret = [];
        $reflection = new \ReflectionClass($src);

        foreach ($reflection->getProperties() as $property) {
            $key = $property->getName();
            $property->setAccessible(true);
            $value = $property->getValue($src);
            if (is_array($value)) {
                if ($this->isNumArray($value)) {
                    $temp = [];
                    for ($i = 0; $i < count($value); $i++) {
                        $temp[] = $this->normalize($value[$i], $with_null);
                    }
                    $value = $temp;
                }
            } else if (is_object($value)) {
                if ($value instanceof \DateTime) {
                    $value = $value->format("Y-m-d H:i:s");
                } else {                
                    $response = $this->parseDoc($property->getDocComment(), $reflection->getName());
                    if($response->isValid()){
                        $value = $this->normalize($value, $with_null);
                    }
                }
               
            }
            if ($with_null) {
                $ret[self::uncamelize($key)] = $value;
                continue;
            } elseif (!is_null($value)) {

                $ret[self::uncamelize($key)] = $value;
            }
        }
        ($this->rec === 0) ? : $this->rec--;
        return $ret;
    }

    /**
     * 
     * @param string $class
     * @param string $classOrigin
     * @return DocParserResponse
     */
    public function checkClassName($class, $classOrigin) {
        
        $res = new DocParserResponse();
        if ($class) {

            if (strpos($class, '\\') !== 0) {
                $rc = new \ReflectionClass($classOrigin);
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
     * @param string $classOrigin
     * @return DocParserResponse
     */
    public function parseDoc($str, $classOrigin) {
        $res = new DocParserResponse();
        if (preg_match('/@var\s+([\w\\\[\]]+)/', $str, $matches)) {
            //TODO parse use statements and try to find class with prefix
            $type = $matches[1];
            if ($type && strpos($type, '[]')) {
                $type = str_replace('[]', '', $type);
                $res->setArray(true);
            }
            $res->setValid(true);
            $res->setClassName($type);
        }
        if ($res->isValid()) {
            return $this->checkClassName($res->getClassName(), $classOrigin);
        }
        return $res;
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

    public function isNumArray(&$array) {
        if (!is_array($array)) {
            return false;
        }
        $keys = array_keys($array);
        return (array_keys($keys) === $keys);
    }

}

