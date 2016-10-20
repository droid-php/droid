<?php

namespace Droid\Test\Transform;

use stdClass;

use Droid\Model\Inventory\Inventory;
use Droid\Transform\InventoryTransformer;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class InventoryTransformerTest extends \PHPUnit_Framework_TestCase
{
    protected $accessor;
    protected $inventory;
    protected $transformer;

    protected function setUp()
    {
        $this->accessor = $this
            ->getMockBuilder(PropertyAccessorInterface::class)
            ->getMockForAbstractClass()
        ;
        $this->inventory = $this
            ->getMockBuilder(Inventory::class)
            ->getMock()
        ;
        $this->transformer = new InventoryTransformer(
            $this->inventory,
            $this->accessor
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage value is not a string
     */
    public function testTransformFailsIfValueIsNotAString()
    {
        $this->transformer->transform(0);
    }

    public function testIdentityTransformIfValueIsEmpty()
    {
        $this->assertSame('', $this->transformer->transform(''));
    }

    public function testIdentityTransformIfValueIsNotAnExpression()
    {
        $this->assertSame(
            'not-an-expression',
            $this->transformer->transform('not-an-expression')
        );
    }

    /**
     * @expectedException \Droid\Transform\TransformerException
     * @expectedExceptionMessage is not an expression of the form "%object.property%"
     */
    public function testTransformFailsIfValueIsNotAValidExpression()
    {
        $this->transformer->transform('%bad-expression%');
    }

    /**
     * @expectedException \Droid\Transform\TransformerException
     * @expectedExceptionMessage does not identify anything in the Inventory
     */
    public function testTransformFailsIfValueDoesNotResolveToInventory()
    {
        $this
            ->inventory
            ->expects($this->once())
            ->method('hasHost')
            ->willReturn(false)
        ;
        $this
            ->inventory
            ->expects($this->once())
            ->method('hasHostGroup')
            ->willReturn(false)
        ;
        $this->transformer->transform('%missing.name%');
    }

    /**
     * @expectedException \Droid\Transform\TransformerException
     * @expectedExceptionMessage does not resolve to a readable element in the Inventory
     */
    public function testTransformFailsIfValueDoesNotResolveToReadableProperty()
    {
        $thing = new stdClass;

        $this
            ->inventory
            ->expects($this->once())
            ->method('hasHost')
            ->willReturn(true)
        ;
        $this
            ->inventory
            ->expects($this->once())
            ->method('getHost')
            ->with('thing')
            ->willReturn($thing)
        ;
        $this
            ->accessor
            ->expects($this->once())
            ->method('isReadable')
            ->with($thing, 'name')
            ->willReturn(false)
        ;
        $this->transformer->transform('%thing.name%');
    }

    public function testTransformInvokesAccessorWithInventoryHost()
    {
        $thing = new stdClass;

        $this
            ->inventory
            ->expects($this->once())
            ->method('hasHost')
            ->willReturn(true)
        ;
        $this
            ->inventory
            ->expects($this->once())
            ->method('getHost')
            ->with('thing')
            ->willReturn($thing)
        ;
        $this
            ->accessor
            ->expects($this->once())
            ->method('isReadable')
            ->with($thing, 'name')
            ->willReturn(true)
        ;
        $this
            ->accessor
            ->expects($this->once())
            ->method('getValue')
            ->with($thing, 'name')
        ;
        $this->transformer->transform('%thing.name%');
    }

    public function testTransformInvokesAccessorWithInventoryHostGroup()
    {
        $thing = new stdClass;

        $this
            ->inventory
            ->expects($this->once())
            ->method('hasHostGroup')
            ->willReturn(true)
        ;
        $this
            ->inventory
            ->expects($this->once())
            ->method('getHostGroup')
            ->with('thing')
            ->willReturn($thing)
        ;
        $this
            ->accessor
            ->expects($this->once())
            ->method('isReadable')
            ->with($thing, 'name')
            ->willReturn(true)
        ;
        $this
            ->accessor
            ->expects($this->once())
            ->method('getValue')
            ->with($thing, 'name')
        ;
        $this->transformer->transform('%thing.name%');
    }
}
