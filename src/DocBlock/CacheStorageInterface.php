<?php
declare(strict_types=1);

namespace Itav\Component\Serializer\DocBlock;

interface CacheStorageInterface
{
    public function save(string $fileName, string $className, string $fullClassName): bool;
    public function find(string $fileName, string $className): ?string;
    public function clear(): int;
}
