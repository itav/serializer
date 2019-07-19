<?php

namespace Test\StrangeData;

class StrangeIntegers
{
    /**
     * @var int
     */
    private $zero;
    /**
     * @var int
     */
    private $float;
    /**
     * @var int
     */
    private $minus;
    /**
     * @var int
     */
    private $withLetter;

    public function __construct($zero = null, $float = null, $minus = null, $withLetter = null)
    {
        $this->zero = $zero;
        $this->float = $float;
        $this->minus = $minus;
        $this->withLetter = $withLetter;
    }

    public function getZero()
    {
        return $this->zero;
    }

    public function getFloat()
    {
        return $this->float;
    }

    public function getMinus()
    {
        return $this->minus;
    }

    public function getWithLetter()
    {
        return $this->withLetter;
    }
}
