<?php

namespace Itav\Component\Serializer\Test;

use DateTime;
use Itav\Component\Serializer\Factory;
use Itav\Component\Serializer\Nested\Test\NestedForFactory;
use Itav\Component\Serializer\Serializer;
use Itav\Component\Serializer\SerializerException;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Test\Constructor\ReqConstructor;
use Test\StrangeData\StrangeIntegers;

//TODO test max recursive normalize i denormalize
class SerializerTest extends TestCase
{
    /**
     * @var Serializer
     */
    private $serializer;

    public function setUp()
    {
        $this->serializer = Factory::create();
        parent::setUp();
    }

    /**
     * @throws SerializerException
     * @throws ReflectionException
     */
    public function testNormalize()
    {
        $actual = $this->serializer->normalize(new NormalizeMeForTest);
        $expected = $this->getNormalizeData();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws SerializerException
     * @throws ReflectionException
     */
    public function testDenormalize()
    {
        $actual = $this->serializer->denormalize($this->getNormalizeData(), NormalizeMeForTest::class);
        $expected = new NormalizeMeForTest;
        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws SerializerException
     * @throws ReflectionException
     */
    public function testDenormalizeWithSetter()
    {
        $actual = $this->serializer->denormalize(['int' => 1, 'string' => 'str', 'float' => 1.22], DenormalizeTestSetter::class, null, true);
        $expected = new DenormalizeTestSetter(2, 'STR', 1.22);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws SerializerException
     * @throws ReflectionException
     */
    public function testDenormalizeStrangeIntegers()
    {
        $data['zero'] = 0;
        $data['float'] = 1.22;
        $data['minus'] = -100;
        $data['withLetter'] = 'E+10';

        /** @var StrangeIntegers $actual */
        $actual = $this->serializer->denormalize($data, StrangeIntegers::class);
        $this->assertTrue(0 === $actual->getZero());
        $this->assertNull($actual->getFloat());
        $this->assertTrue(-100 === $actual->getMinus());
        $this->assertNull($actual->getWithLetter());

        $data['zero'] = ' 0 ';
        $data['float'] = ' 1.22 ';
        $data['minus'] = ' -100';
        $data['withLetter'] = '+10';
        $actual = $this->serializer->denormalize($data, StrangeIntegers::class);
        $this->assertTrue(0 === $actual->getZero());
        $this->assertNull($actual->getFloat());
        $this->assertTrue(-100 === $actual->getMinus());
        $this->assertTrue(10 === $actual->getWithLetter());

        $data['zero'] = '';
        $data['float'] = '-100.000';
        $data['minus'] = null;
        $data['withLetter'] = [];
        $actual = $this->serializer->denormalize($data, StrangeIntegers::class);
        $this->assertNull($actual->getZero());
        $this->assertNull($actual->getFloat());
        $this->assertNull($actual->getMinus());
        $this->assertNull($actual->getWithLetter());
    }

    /**
     * @throws SerializerException
     * @throws ReflectionException
     */
    public function testDenormalizeWithRequiredConstructorParams()
    {
        /** @var ReqConstructor $actual */
        $actual = $this->serializer->denormalize(['a' => 'unit_a', 'b' => 'unit_b'], ReqConstructor::class);
        $this->assertTrue('unit_a' === $actual->getA());
        $this->assertTrue('unit_b' === $actual->getB());

        $actual = $this->serializer->denormalize([], ReqConstructor::class);
        $this->assertNull($actual->getA());
        $this->assertNull($actual->getB());
    }

    /**
     * @throws SerializerException
     * @throws ReflectionException
     */
    public function testDenormalizeDoNotModifyDefaults()
    {
        $expected = new NormalizeMeForTest;
        $actual = $this->serializer->denormalize([], NormalizeMeForTest::class);

        $this->assertEquals($expected, $actual);

        $actual = $this->serializer->denormalize([], NormalizeMeForTest::class, $expected);
        $this->assertEquals($expected, $actual);

        $actual = $this->serializer->denormalize([], NormalizeMeForTest::class, $expected, true);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws SerializerException
     * @throws ReflectionException
     */
    public function testSerialize()
    {
        $actual = $this->serializer->serialize(new NormalizeMeForTest);
        $expected = '{"string":"str","int":1,"float":1.1,"bool":false,"array":[1,2],"objArray":[{"string":"str","int":1,"float":1.1,"bool":false,"array":[1,2]}],"date":"2017-12-27 12:10:10"}';
        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws SerializerException
     * @throws ReflectionException
     */
    public function testUnserialize()
    {
        $actual = $this->serializer->unserialize(json_encode($this->getNormalizeData()), NormalizeMeForTest::class);
        $expected = new NormalizeMeForTest;
        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws SerializerException
     * @throws ReflectionException
     */
    public function testFactory()
    {
        $data = [1, null, ['a' => 'a', 'b' => 'b'], new DateTime('today')];

        $actual = $this->serializer->factory($data, CreatedByFactory::class, true);
        $expected = new CreatedByFactory(1, null, new NestedForFactory('a', 'b'), new DateTime('today'));
        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws ReflectionException
     * @throws SerializerException
     */
    public function testIfAcceptNumericArrayAsDenormalizeInput()
    {
        $actual = $this->serializer->denormalize([1 => 'some integer indexed value', 'string' => 'some string'], NormalizeMeForTest::class);
        $this->assertInstanceOf(NormalizeMeForTest::class, $actual);
    }

    public function getNormalizeData()
    {
        return [
            'string' => 'str',
            'int' => 1,
            'float' => 1.1,
            'bool' => false,
            'array' =>
                [
                    0 => 1,
                    1 => 2,
                ],
            'objArray' =>
                [
                    0 =>
                        [
                            'string' => 'str',
                            'int' => 1,
                            'float' => 1.1,
                            'bool' => false,
                            'array' =>
                                [
                                    0 => 1,
                                    1 => 2,
                                ],
                        ],
                ],
            'date' => '2017-12-27 12:10:10',
        ];
    }
}
