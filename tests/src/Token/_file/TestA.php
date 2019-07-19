<?php

namespace One\Two\Three;

use One\TestA as TestA_One;
use One\Two\TestA as TestA_Two;

class TestA
{
    /**
     * @var TestA_One
     */
    private $itemOne;
    /**
     * @var TestA_Two
     */
    private $itemTwo;
}
