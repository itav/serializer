<?php

namespace Itav\Component\Serializer\Test;

class DenormalizeTestSetter
{
    private $int;
    private $string;
    private $float;

    public function __construct($int = null, $string = null, $float = null)
    {
        $this->string = $string;
        $this->int = $int;
        $this->float = $float;
    }

    public function setInt($val)
    {
        $this->int = 2 * $val;
    }

    private function setString($val)
    {
        $this->string = strtoupper($val);
    }
}
