<?php

namespace Droid\Test\TaskRunner;

use Droid\Model\Inventory\Host;

use Droid\Transform\Render\LightnCandyRenderer;

class LightnCandyRendererTest extends \PHPUnit_Framework_TestCase
{
    private $renderer;

    public function setUp()
    {
        $this->renderer = new LightnCandyRenderer;
    }

    public function testWillResolveTopLevelVariables()
    {
        $value = '{{{ some-var }}}';
        $context = array('some-var' => 'something');
        $expectedRendering = 'something';

        $this->assertSame(
            $expectedRendering,
            $this->renderer->render($value, $context)
        );
    }

    public function testWillResolveNestedVariables()
    {
        $value = '{{{ top.some-var }}}';
        $context = array('top' => array('some-var' => 'something'));
        $expectedRendering = 'something';

        $this->assertSame(
            $expectedRendering,
            $this->renderer->render($value, $context)
        );
    }

    public function testWillResolveHostProperty()
    {
        $host = new Host('host.example.com');

        $value = '{{{ host.name }}}';
        $context = array('host' => $host);
        $expectedRendering = 'host.example.com';

        $this->assertSame(
            $expectedRendering,
            $this->renderer->render($value, $context)
        );
    }

    public function testWillResolveHostVariable()
    {
        $host = new Host('host.example.com');
        $host->setVariable('role', 'master');

        $value = '{{{ host.variables.role }}}';
        $context = array('host' => $host);
        $expectedRendering = 'master';

        $this->assertSame(
            $expectedRendering,
            $this->renderer->render($value, $context)
        );
    }
}
