<?php

namespace Droid\Test\Transform;

use Droid\Transform\Render\RendererInterface;
use Droid\Transform\SubstitutionTransformer;
use Droid\Transform\Render\RendererException;

class SubstitutionTransformerTest extends \PHPUnit_Framework_TestCase
{
    protected $renderer;
    protected $transformer;

    protected function setUp()
    {
        $this->renderer = $this
            ->getMockBuilder(RendererInterface::class)
            ->getMockForAbstractClass()
        ;
        $this->transformer = new SubstitutionTransformer($this->renderer);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage value is not a string
     */
    public function testTransformFailsIfValueIsNotAString()
    {
        $this->transformer->transform(0);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage context is not an array
     */
    public function testTransformFailsIfContextIsNotAnArray()
    {
        $this->transformer->transform('non-empty', 0);
    }

    public function testIdentityTransformIfValueIsEmpty()
    {
        $this->assertSame('', $this->transformer->transform(''));
    }

    public function testIdentityTransformIfContextIsEmpty()
    {
        $this->assertSame(
            'non-empty',
            $this->transformer->transform('non-empty', array())
        );
    }

    public function testTransformInvokesRenderer()
    {
        $value = 'some-value';
        $context = array('some' => 'context');

        $this
            ->renderer
            ->expects($this->once())
            ->method('render')
            ->with($value, $context)
        ;

        $this->transformer->transform($value, $context);
    }

    /**
     * @expectedException \Droid\Transform\TransformerException
     * @expectedExceptionMessage value could not be transformed
     */
    public function testTransformCatchesRendererExceptionAndThrowsTransformerException()
    {
        $value = 'some-value';
        $context = array('some' => 'context');

        $this
            ->renderer
            ->method('render')
            ->willThrowException(new RendererException)
        ;

        $this->transformer->transform($value, $context);
    }
}
