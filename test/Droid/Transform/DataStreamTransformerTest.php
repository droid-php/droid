<?php

namespace Droid\Test\Transform;

use Droid\Transform\DataStreamTransformer;

class DataStreamTransformerTest extends \PHPUnit_Framework_TestCase
{
    protected $transformer;

    protected function setUp()
    {
        $this->transformer = new DataStreamTransformer;
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage value is not a string
     */
    public function testTransformFailsIfValueIsNotAString()
    {
        $this->transformer->transform(0);
    }

    public function testTransformEmptyStringIntoDataStreamString()
    {
        $input = '';
        $expected = 'data:,';
        $this->assertSame(
            $expected,
            $this->transformer->transform($input)
        );
    }

    public function testTransformStringIntoDataStreamString()
    {
        $input = 'Question: Do you know utf-8? Answer: ✓';
        $expected = 'data:application/octet-stream;base64,'
            . base64_encode($input)
        ;
        $this->assertSame(
            $expected,
            $this->transformer->transform($input)
        );
    }
}
