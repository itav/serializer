<?php
declare(strict_types=1);

namespace Itav\Component\Serializer\DocBlock;

class InMemoryStorage implements CacheStorageInterface
{
    private $collection;

    public function __construct()
    {
        $this->collection = new Collection();
    }

    public function save(string $fileName, string $className, string $fullClassName): bool
    {
        $this->collection->add($fullClassName, "{$fileName}_{$className}");
        return true;
    }

    public function find(string $fileName, string $className): ?string
    {
        return $this->collection->getItem("{$fileName}_{$className}");
    }

    public function clear(): int
    {
        return $this->collection->clear();
    }
}
