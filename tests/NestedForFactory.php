<?php
declare(strict_types=1);

namespace Itav\Component\Serializer\Nested\Test;

class NestedForFactory
{
    private $a;
    private $b;

    public function __construct(string $a, string $b)
    {
        $this->a = $a;
        $this->b = $b;
    }
}
