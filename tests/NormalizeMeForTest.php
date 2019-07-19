<?php

namespace Itav\Component\Serializer\Test;

class NormalizeMeForTest
{

    private $string = 'str';
    private $int = 1;
    private $float = 1.1;
    private $bool = false;
    private $null = null;
    private $array = [1,2];
    /**
     * @var NestedClassOne[]
     */
    private $objArray = [];
    /**
     * @var \DateTime
     */
    private $date;

    public function __construct()
    {
        $this->date = new \DateTime('2017-12-27 12:10:10');
        $this->objArray[] = new NestedClassOne;
    }
}

class NestedClassOne
{
    private $string = 'str';
    private $int = 1;
    private $float = 1.1;
    private $bool = false;
    private $null = null;
    private $array = [1,2];
}
