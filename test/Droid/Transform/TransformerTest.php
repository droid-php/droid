<?php

namespace Droid\Test\Transform;

use InvalidArgumentException;

use Droid\Transform\DataStreamTransformer;
use Droid\Transform\FileTransformer;
use Droid\Transform\SubstitutionTransformer;
use Droid\Transform\Transformer;

class TransformerTest extends \PHPUnit_Framework_TestCase
{
    protected $dataStreamTransformer;
    protected $fileTransformer;
    protected $substitutionTransformer;
    protected $transformer;

    protected function setUp()
    {
        $this->dataStreamTransformer = $this
            ->getMockBuilder(DataStreamTransformer::class)
            ->getMock()
        ;
        $this->fileTransformer = $this
            ->getMockBuilder(FileTransformer::class)
            ->getMock()
        ;
        $this->substitutionTransformer = $this
            ->getMockBuilder(SubstitutionTransformer::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->transformer = new Transformer(
            $this->dataStreamTransformer,
            $this->fileTransformer,
            $this->substitutionTransformer
        );
    }

    /**
     * @expectedException \Droid\Transform\TransformerException
     */
    public function testTransformDataStreamThrowsTransformExceptionWithBadArgs()
    {
        $this
            ->dataStreamTransformer
            ->method('transform')
            ->willThrowException(new InvalidArgumentException)

        ;

        $this->transformer->transformDataStream('some-value');
    }

    public function testTransformDataStreamInvokesTransformerInterface()
    {
        $value = 'some-value';
        $fakeResult = 'a-fake-result';

        $this
            ->dataStreamTransformer
            ->expects($this->once())
            ->method('transform')
            ->with($value)
            ->willReturn($fakeResult)
        ;

        $this->assertSame(
            $fakeResult,
            $this->transformer->transformDataStream($value)
        );
    }

    /**
     * @expectedException \Droid\Transform\TransformerException
     */
    public function testTransformFileThrowsTransformExceptionWithBadArgs()
    {
        $this
            ->fileTransformer
            ->method('transform')
            ->willThrowException(new InvalidArgumentException)

        ;

        $this->transformer->transformFile('some-value');
    }

    public function testTransformFileInvokesTransformerInterface()
    {
        $value = 'some-value';
        $fakeResult = 'a-fake-result';

        $this
            ->fileTransformer
            ->expects($this->once())
            ->method('transform')
            ->with($value)
            ->willReturn($fakeResult)
        ;

        $this->assertSame(
            $fakeResult,
            $this->transformer->transformFile($value)
        );
    }

    /**
     * @expectedException \Droid\Transform\TransformerException
     */
    public function testTransformVariableThrowsTransformExceptionWithBadArgs()
    {
        $this
            ->substitutionTransformer
            ->method('transform')
            ->willThrowException(new InvalidArgumentException)

        ;

        $this->transformer->transformVariable('some-value', array());
    }

    public function testTransformVariableInvokesTransformerInterface()
    {
        $value = 'some-value';
        $context = array();
        $fakeResult = 'a-fake-result';

        $this
            ->substitutionTransformer
            ->expects($this->once())
            ->method('transform')
            ->with($value, $context)
            ->willReturn($fakeResult)
        ;

        $this->assertSame(
            $fakeResult,
            $this->transformer->transformVariable($value, $context)
        );
    }
}
