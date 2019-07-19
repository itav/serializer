<?php
declare(strict_types=1);

namespace Itav\Component\Serializer\DocBlock;

class RedisCacheStorage implements CacheStorageInterface
{
    const TOKEN_CACHE_PREFIX = 'token_cache_prefix:';

    private $redis;

    public function __construct(RedisClientInterface $redis)
    {
        $this->redis = $redis;
    }

    public function save(string $fileName, string $className, string $fullClassName): bool
    {
        return $this->redis->set(self::TOKEN_CACHE_PREFIX . ":{$fileName}:{$className}", $fullClassName);
    }

    public function find(string $fileName, string $className): ?string
    {
        $item = $this->redis->get(self::TOKEN_CACHE_PREFIX . ":{$fileName}:{$className}");
        if (false === $item) {
            return null;
        }
        return $item;
    }

    public function clear(): bool
    {
        return true;
    }
}
