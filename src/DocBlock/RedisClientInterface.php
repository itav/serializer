<?php
declare(strict_types=1);

namespace Itav\Component\Serializer\DocBlock;

interface RedisClientInterface
{
    public function connect($host, $port = 6379, $timeout = 0.0, $reserved = null, $retry_interval = 0);
    public function auth($pass);
    public function set($key, $value);
    public function get($key);
    public function delete($key);
}
