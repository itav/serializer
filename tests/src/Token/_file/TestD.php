<?php

namespace One\Two\Three\Four;

use \One\TestE;
use \One\Two\TestA as TestA_Two;
use One\Two\Three\TestA;

class TestD
{
    use TraitDome;
    /**
     * @var TestA
     */
    private $itemA;
    /**
     * @var TestE
     */
    private $itemB;
    /**
     * @var TestA_Two
     */
    private $itemC;
    /**
     * @var \One\TestA
     */
    private $itemD;

    public function smt()
    {
        $ar = [1,2];
        array_walk($ar, function ($item) use ($ar) {});
        throw new Exception('');
    }
}

trait TraitDome
{

}