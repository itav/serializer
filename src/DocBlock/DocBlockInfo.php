<?php
declare(strict_types=1);

namespace Itav\Component\Serializer\DocBlock;

class DocBlockInfo
{
    private $className;
    private $isScalar;
    private $isArray;

    public function __construct(
        string $className,
        bool $isScalar,
        bool $isArray
    ) {
        $this->className = $className;
        $this->isScalar = $isScalar;
        $this->isArray = $isArray;
    }

    public function className(): string
    {
        return $this->className;
    }

    public function isScalar(): bool
    {
        return $this->isScalar;
    }

    public function isArray(): bool
    {
        return $this->isArray;
    }
}
