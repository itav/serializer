<?php

namespace Itav\Component\Serializer;

class TokenParser {

    const MAX_DEPTH = 100;

    /**
     *
     * @var integer
     */
    private $pointer = 0;

    /**
     *
     * @var integer
     */
    private $tokenCount = 0;

    /**
     *
     * @var array
     */
    private $useStatements = [];

    /**
     *
     * @var array
     */
    private $tokens = [];

    public function findUseStatement($filename, $className) {

        if (!$filename || !$className) {
            return null;
        }

        if (isset($this->useStatements[$filename][$className]) && $this->useStatements[$filename][$className]) {
            return $this->useStatements[$filename][$className];
        }

        $this->tokens = token_get_all(file_get_contents($filename));
        $this->tokenCount = count($this->tokens);

        while ($j = $this->next()) {
            $class = '';
            $alias = '';
            $explicitAlias = false;
            $max = (($j + self::MAX_DEPTH) > $this->tokenCount) ? $this->tokenCount : $j + self::MAX_DEPTH;
            for ($j; $j < $max; $j++) {
                $token = $this->tokens[$j];
                $isNameToken = $token[0] === T_STRING || $token[0] === T_NS_SEPARATOR;
                if (!$explicitAlias && $isNameToken) {
                    $class .= $token[1];
                    $alias = $token[1];
                } else if ($explicitAlias && $isNameToken) {
                    $alias .= $token[1];
                } else if ($token[0] === T_AS) {
                    $explicitAlias = true;
                    $alias = '';
                } else if ($token === ',') {
                    if ($alias === $className) {
                        $this->useStatements[$filename][$alias] = $class;
                        return $class;
                    }
                    $class = '';
                    $alias = '';
                    $explicitAlias = false;
                } else if ($token === ';') {
                    if ($alias === $className) {
                        $this->useStatements[$filename][$alias] = $class;
                        return $class;
                    }
                    break;
                }
            }
            $this->pointer = $j + 1;
        }
    }

    private function next($key = T_USE) {
        for ($i = $this->pointer; $i < $this->tokenCount; $i++) {
            if ($this->tokens[$i][0] === $key) {

                $this->pointer = $i + 1;
                return $i;
            }
        }
        return false;
    }

}
