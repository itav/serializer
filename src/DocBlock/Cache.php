<?php
declare(strict_types=1);

namespace Itav\Component\Serializer\DocBlock;

class Cache
{
    private $storage;

    public function __construct(CacheStorageInterface $storage)
    {
        $this->storage = $storage;
    }
    
    public function save(string $fileName, string $className, string $fullClassName): bool
    {
        return $this->storage->save($fileName, $className, $fullClassName);
    }

    public function find(string $fileName, string $className): ?string
    {
        return $this->storage->find($fileName, $className);
    }
}
