<?php

namespace Test\Constructor;

class ReqConstructor
{
    /**
     * @var $string
     */
    private $a;
    /**
     * @var string
     */
    private $b;

    public function __construct($a, $b = 'smt')
    {
        $this->a = $a;
        $this->b = $b;
    }

    public function getA()
    {
        return $this->a;
    }

    public function getB()
    {
        return $this->b;
    }
}
