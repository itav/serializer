<?php
declare(strict_types=1);

namespace Itav\Component\Serializer\DocBlock;

class Collection
{
    private $items = [];

    public function add(string $value, string $index): Collection
    {
        $this->items[$index] = $value;
        return $this;
    }

    public function getItem(string $index): ?string
    {
        if (array_key_exists($index, $this->items)) {
            return $this->items[$index];
        }

        return null;
    }
    
    public function clear(): int
    {
        $cnt = count($this->items);
        $this->items = [];
        return $cnt;
    }
}
