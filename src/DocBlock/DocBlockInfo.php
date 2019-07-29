<?php
declare(strict_types=1);

namespace Itav\Component\Serializer\DocBlock;

class DocBlockInfo
{
    private $className;
    private $isBuiltIn;
    private $isArray;

    public function __construct(
        string $className,
        bool $isBuiltIn,
        bool $isArray
    ) {
        $this->className = $className;
        $this->isBuiltIn = $isBuiltIn;
        $this->isArray = $isArray;
    }

    public function className(): string
    {
        return $this->className;
    }

    public function isBuiltIn(): bool
    {
        return $this->isBuiltIn;
    }

    public function isArray(): bool
    {
        return $this->isArray;
    }
}
