<?php

namespace One;

use One\Two\Three\TestA;

class TestE
{
    /**
     * @var TestA
     */
    private $itemA;
    /**
     * @var \One\Two\TestA
     */
    private $itemB;
    /**
     * @var \One\Two\Three\TestA
     */
    private $itemC;
    /**
     * @var TestD
     */
    private $itemD;
}
use One\Two\Three\Four\TestD;