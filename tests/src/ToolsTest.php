<?php

namespace Itav\Component\Serializer\Test;

use Itav\Component\Serializer\Tools;
use PHPUnit\Framework\TestCase;

class ToolsTest extends TestCase
{
    public function testCamelize()
    {
        $actual = Tools::camelize('');
        $expected = '';
        $this->assertEquals($expected, $actual);

        $actual = Tools::camelize('snake_snake');
        $expected = 'snakeSnake';
        $this->assertEquals($expected, $actual);

        $actual = Tools::camelize('UPPER_UPPER');
        $expected = 'UPPERUPPER';
        $this->assertEquals($expected, $actual);

        $actual = Tools::camelize('Pascal_snake');
        $expected = 'PascalSnake';
        $this->assertEquals($expected, $actual);

        $actual = Tools::camelize('entity_UUID');
        $expected = 'entityUUID';
        $this->assertEquals($expected, $actual);
    }

    public function testUncamelize()
    {
        $actual = Tools::uncamelize('');
        $expected = '';
        $this->assertEquals($expected, $actual);

        $actual = Tools::uncamelize('camelCamel');
        $expected = 'camel_camel';
        $this->assertEquals($expected, $actual);

        $actual = Tools::uncamelize('UPPERUPPER');
        $expected = 'upperupper';
        $this->assertEquals($expected, $actual);

        $actual = Tools::uncamelize('PascalSnake');
        $expected = 'pascal_snake';
        $this->assertEquals($expected, $actual);

        $actual = Tools::uncamelize('entityUUID');
        $expected = 'entity_uuid';
        $this->assertEquals($expected, $actual);
    }

    public function testGetterGenerator()
    {
        $actual = Tools::genGetter(Getters::class, 'one');
        $expected = 'one';
        $this->assertEquals($expected, $actual);

        $actual = Tools::genGetter(Getters::class, 'two');
        $expected = 'isTwo';
        $this->assertEquals($expected, $actual);

        $actual = Tools::genGetter(Getters::class, 'three');
        $expected = 'getThree';
        $this->assertEquals($expected, $actual);

        $actual = Tools::genGetter(Getters::class, 'four');
        $expected = 'getFour';
        $this->assertEquals($expected, $actual);

        $actual = Tools::genGetter(Getters::class, 'five');
        $this->assertNull($actual);
    }

    public function testSetterGenerator()
    {
        $actual = Tools::genSetter(Setters::class, 'one');
        $this->assertNull($actual);

        $actual = Tools::genSetter(Setters::class, 'two');
        $expected = 'setTwo';
        $this->assertEquals($expected, $actual);

        $actual = Tools::genSetter(Setters::class, 'three');
        $this->assertNull($actual);

        $actual = Tools::genSetter(Setters::class, 'four');
        $expected = 'setFour';
        $this->assertEquals($expected, $actual);
    }
}
