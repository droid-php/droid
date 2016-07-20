<?php

namespace Droid\Test\TaskRunner;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\OutputInterface;

use Droid\Application;
use Droid\Model\Inventory\Host;
use Droid\Model\Inventory\Remote\EnablerInterface;
use Droid\TaskRunner;

use Droid\Test\AutoloaderAwareTestCase;

class PrepareCommandInputTest extends AutoloaderAwareTestCase
{
    private $app;
    private $command;
    private $enabler;
    private $output;
    private $taskRunner;

    public function setUp()
    {
        $this->app = new Application($this->autoloader);
        $this->command = $this->getMockCommand();
        $this->enabler = $this
            ->getMockBuilder(EnablerInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->output = $this
            ->getMockBuilder(OutputInterface::class)
            ->getMock()
        ;
        $this->taskRunner = new TaskRunner($this->app, $this->output, $this->enabler);
    }

    private function getMockCommand()
    {
        $mock = $this
            ->getMockBuilder(Command::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getDefinition'))
            ->getMock()
        ;
        $mock
            ->method('getDefinition')
            ->willReturn(
                new InputDefinition(
                    array(new InputArgument('what', InputArgument::REQUIRED))
                )
            )
        ;
        return $mock;
    }

    public function testWillResolveTopLevelVariables()
    {
        $expectedArguments = array('what' => 'something');

        $this->assertSame(
            $expectedArguments,
            $this
                ->taskRunner
                ->prepareCommandInput(
                    $this->command,
                    array('what' => '{{{ some-var }}}'),
                    array('some-var' => 'something')
                )
                ->getArguments()
        );
    }

    public function testWillResolveNestedVariables()
    {
        $expectedArguments = array('what' => 'something');

        $this->assertSame(
            $expectedArguments,
            $this
                ->taskRunner
                ->prepareCommandInput(
                    $this->command,
                    array('what' => '{{{ top.some-var }}}'),
                    array('top' => array('some-var' => 'something'))
                )
                ->getArguments()
        );
    }

    public function testWillResolveHostProperty()
    {
        $host = new Host('host.example.com');

        $expectedArguments = array('what' => 'host.example.com');

        $this->assertSame(
            $expectedArguments,
            $this
                ->taskRunner
                ->prepareCommandInput(
                    $this->command,
                    array('what' => '{{{ host.name }}}'),
                    array('host' => $host)
                )
                ->getArguments()
        );
    }

    public function testWillResolveHostVariable()
    {
        $host = new Host('host.example.com');
        $host->setVariable('role', 'master');

        $expectedArguments = array('what' => 'master');

        $this->assertSame(
            $expectedArguments,
            $this
                ->taskRunner
                ->prepareCommandInput(
                    $this->command,
                    array('what' => '{{{ host.variables.role }}}'),
                    array('host' => $host)
                )
                ->getArguments()
        );
    }
}
