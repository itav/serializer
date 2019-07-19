<?php
declare(strict_types=1);

namespace Itav\Component\Serializer\Test;

use Itav\Component\Serializer\Nested\Test\NestedForFactory;

class CreatedByFactory
{
    private $int;
    private $nullInt;

    public function __construct($int, ?int $nullInt, NestedForFactory $nestedForFactory, \DateTime $dateTime)
    {
        $this->int = $int;
        $this->nullInt = $nullInt;
    }
}
