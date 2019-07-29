<?php
declare(strict_types=1);

namespace Itav\Component\Serializer\DocBlock;

use Itav\Component\Serializer\Tools;
use ReflectionProperty;

class Parser
{
    private static $paths = [
        'A' => [T_USE => 'B'],
        'B' => [T_WHITESPACE => 'C'],
        'C' => [T_STRING => 'E', T_NS_SEPARATOR => 'D'],
        'D' => [T_STRING => 'E'],
        'E' => [';' => 'K', ',' => 'B', T_WHITESPACE => 'F', T_NS_SEPARATOR => 'D'],
        'F' => [';' => 'K', ',' => 'B', T_AS => 'G'],
        'G' => [T_WHITESPACE => 'H'],
        'H' => [T_STRING => 'I'],
        'I' => [';' => 'K', ',' => 'B', T_WHITESPACE => 'J'],
        'J' => [';' => 'K', ',' => 'B'],
        'K' => [T_WHITESPACE => 'A'],
    ];

    private $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function parseDoc(ReflectionProperty $rp): ?DocBlockInfo
    {
        $content = $rp->getDocComment() ?: '';
        if ('' === $content || false === strpos($content, '@var')) {
            return null;
        }
        $matches = [];
        if (!preg_match('/@var\s+([\w\\\[\]]+)/', $content, $matches)) {
            return null;
        }

        $type = $matches[1] ?? '';
        if ('' === $type) {
            return null;
        }

        if (Tools::isBuiltInClass($type)) {
            return new DocBlockInfo($type, true, false);
        }

        $isArray = Tools::isCollection($type) ?: false;
        $isAbsolute = (0 === strpos($type, '\\'));

        switch (true) {
            case $isAbsolute && class_exists($type):
                return new DocBlockInfo($type, false, $isArray);
            case class_exists($name = "{$rp->getDeclaringClass()->getNamespaceName()}\\$type"):
            case class_exists($name = $this->findUseStatement($rp->getDeclaringClass()->getFileName(), $type) ?? ''):
                return new DocBlockInfo($name, false, $isArray);
            case class_exists($type):
                return new DocBlockInfo($type, false, $isArray);
        }
        return null;
    }

    private function findUseStatement(string $filename, string $className): ?string
    {
        if ($item = $this->cache->find($filename, $className)) {
            return $item;
        }

        $tokens = token_get_all(file_get_contents($filename));

        $node = 'A';
        $alias = '';
        $fullClassName = '';
        while ($token = next($tokens)) {
            $code = $token[0] ?? $token;
            switch ($node) {
                case 'B':
                    if ($className === $alias) {
                        $this->cache->save($filename, $className, $fullClassName);
                        return $fullClassName;
                    }
                    $fullClassName = '';
                    break;
                case 'C':
                    $alias = $code === T_STRING ? $token[1] : $alias;
                    $fullClassName .= $token[1];
                    break;
                case 'D':
                    $alias = $code === T_STRING ? $token[1] : $alias;
                    $fullClassName .= $token[1];
                    break;
                case 'E':
                    $fullClassName .= $code === T_NS_SEPARATOR ? $token[1] : '';
                    break;
                case 'H':
                    $alias = $code === T_STRING ? $token[1] : '';
                    break;
                case 'K':
                    if ($className === $alias) {
                        $this->cache->save($filename, $className, $fullClassName);
                        return $fullClassName;
                    }
                    $fullClassName = '';
                    break;
            }
            $node = array_key_exists($code, self::$paths[$node]) ? self::$paths[$node][$code] : 'A';
        }

        if ('K' === $node && $className === $alias) {
            $this->cache->save($filename, $className, $fullClassName);
            return $fullClassName;
        }
        return null;
    }
}
