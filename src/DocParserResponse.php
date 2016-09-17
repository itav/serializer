<?php

namespace Itav\Component\Serializer;

class DocParserResponse
{

    /**
     * @var bool
     */
    private $valid = false;
    /**
     * @var string
     */
    private $className = '';
    /**
     * @var bool
     */
    private $array = false;

    /**
     * @return bool
     */
    public function isValid()
    {
        return (bool)$this->valid;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return bool
     */
    public function isArray()
    {
        return (bool)$this->array;
    }

    /**
     * @param $valid
     * @return $this
     */
    public function setValid($valid)
    {
        $this->valid = $valid;
        return $this;
    }

    /**
     * @param $className
     * @return $this
     */
    public function setClassName($className)
    {
        $this->className = $className;
        return $this;
    }

    /**
     * @param $array
     * @return $this
     */
    public function setArray($array)
    {
        $this->array = $array;
        return $this;
    }
}