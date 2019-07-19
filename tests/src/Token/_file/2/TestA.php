<?php

namespace One\Two;

use One\TestA as TestA_One;

class TestA
{
    /**
     * @var TestA_One
     */
    private $itemOne;
    /**
     * @var \One\Two\Three\TestA
     */
    private $itemTwo;
}
