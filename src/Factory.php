<?php
declare(strict_types=1);

namespace Itav\Component\Serializer;

use Itav\Component\Serializer\DocBlock\Parser;
use Itav\Component\Serializer\DocBlock\Cache;
use Itav\Component\Serializer\DocBlock\CacheStorageInterface;
use Itav\Component\Serializer\DocBlock\InMemoryStorage;

class Factory
{
    public static function create(
        ?CacheStorageInterface $storage = null,
        string $datetimeFormat = Serializer::DATE_FORMAT,
        int $errorMode = Serializer::ERROR_MODE_SILENT
    ): Serializer {
        if (null === $storage) {
            $storage = new InMemoryStorage();
        }
        $tokenParser = new Parser(new Cache($storage));
        return new Serializer($tokenParser, $datetimeFormat, $errorMode);
    }
}
