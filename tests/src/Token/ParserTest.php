<?php

namespace Itav\Component\Serializer\Test;

use One\TestE;
use One\Two\Three\Four\TestD;
use Itav\Component\Serializer\DocBlock\Parser;
use Itav\Component\Serializer\DocBlock\Cache;
use Itav\Component\Serializer\DocBlock\InMemoryStorage;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    /**
     * @var Parser
     */
    private $finder;

    public function setUp()
    {
        $this->finder = new Parser(new Cache(new InMemoryStorage()));
        parent::setUp();
    }

    public function testFindUseStatement()
    {
        $rp = new \ReflectionProperty(TestD::class, 'itemA');
        $actual = $this->finder->parseDoc($rp)->className();
        $expected = 'One\Two\Three\TestA';
        $this->assertEquals($expected, $actual, "Normal use without alias when two class with the same name");

        $rp = new \ReflectionProperty(TestD::class, 'itemC');
        $actual = $this->finder->parseDoc($rp)->className();
        $expected = '\One\Two\TestA';
        $this->assertEquals($expected, $actual, "Normal use without alias when two class with the same name");

        $rp = new \ReflectionProperty(TestE::class, 'itemD');
        $actual = $this->finder->parseDoc($rp)->className();
        $expected = 'One\Two\Three\Four\TestD';
        $this->assertEquals($expected, $actual, "Normal use without alias when two class with the same name");
    }
}
