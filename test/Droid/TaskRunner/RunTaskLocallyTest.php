<?php

namespace Droid\Test\TaskRunner;

use Droid\Model\Inventory\Host;
use Droid\Model\Inventory\Inventory;
use Droid\Model\Inventory\Remote\Enabler;
use Droid\Model\Project\Task;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

use Droid\Application;
use Droid\Logger\LoggerFactory;
use Droid\TaskRunner;
use Droid\Test\AutoloaderAwareTestCase;
use Droid\Transform\Transformer;

class RunTaskLocallyTest extends AutoloaderAwareTestCase
{
    private $app;
    private $command;
    private $enabler;
    private $host;
    private $inventory;
    private $logger;
    private $loggerFac;
    private $output;
    private $task;
    private $taskRunner;
    private $transformer;

    public function setUp()
    {
        $this->app = $this->getPartialMockApplication();
        $this->command = $this->getMockCommand();
        $this->enabler = $this
            ->getMockBuilder(Enabler::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->host = $this
            ->getMockBuilder(Host::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->inventory = $this
            ->getMockBuilder(Inventory::class)
            ->getMock()
        ;
        $this->logger = $this
            ->getMockBuilder(LoggerInterface::class)
            ->getMock()
        ;
        $this->loggerFac = $this
            ->getMockBuilder(LoggerFactory::class)
            ->getMock()
        ;
        $this
            ->loggerFac
            ->method('makeLogger')
            ->willReturn($this->logger)
        ;
        $this->output = $this
            ->getMockBuilder(OutputInterface::class)
            ->getMock()
        ;
        $this->task = $this
            ->getMockBuilder(Task::class)
            ->getMock()
        ;
        $this->transformer = $this
            ->getMockBuilder(Transformer::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->taskRunner = $this->getPartialMockTaskRunner();

        $this
            ->host
            ->method('getName')
            ->willReturn('host.example.com')
        ;
    }

    private function getPartialMockApplication()
    {
        return $this
            ->getMockBuilder(Application::class)
            ->setConstructorArgs(array($this->autoloader))
            ->setMethods(
                array(
                    'hasInventory',
                    'getInventory',
                )
            )
            ->getMock()
        ;
    }

    private function getPartialMockTaskRunner()
    {
        return $this
            ->getMockBuilder(TaskRunner::class)
            ->setConstructorArgs(
                array($this->app, $this->transformer, $this->loggerFac)
            )
            ->setMethods(
                array(
                    'prepareCommandInput',
                    'runRemoteCommand',
                    'runLocalCommand',
                    'commandInputToText',
                )
            )
            ->getMock()
            ->setEnabler($this->enabler)
            ->setOutput($this->output)
        ;
    }

    private function getMockCommand()
    {
        $mock = $this
            ->getMockBuilder(Command::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'isEnabled',
                    'getDefinition',
                    'getName',
                    'getAliases',
                )
            )
            ->getMock()
        ;
        $mock
            ->method('isEnabled')
            ->willReturn(true)
        ;
        $mock
            ->method('getDefinition')
            ->willReturn('some-defn')
        ;
        $mock
            ->method('getAliases')
            ->willReturn(array())
        ;
        return $mock;
    }

    /**
     * @expectedException \Symfony\Component\Console\Exception\CommandNotFoundException
     */
    public function testRunTaskFailsWhenCommandNotFound()
    {
        $this
            ->task
            ->method('getCommandName')
            ->willReturn('some-unregisterd-command')
        ;
        $this->taskRunner->runTaskLocally($this->task, array());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Items is refering to non-existant variable
     */
    public function testRunTaskFailsWhenWithItemsCannotBeResolved()
    {
        $this
            ->command
            ->method('getName')
            ->willReturn('do:thing')
        ;
        $this->app->add($this->command);
        $this
            ->task
            ->method('getCommandName')
            ->willReturn('do:thing')
        ;

        $this
            ->task
            ->expects($this->once())
            ->method('getItems')
            ->willReturn('not-a-variable')
        ;
        $this->taskRunner->runTaskLocally($this->task, array());
    }

    public function testRunTaskDoesNothingWithoutAnyWithItems()
    {
        $this
            ->command
            ->method('getName')
            ->willReturn('do:thing')
        ;
        $this->app->add($this->command);
        $this
            ->task
            ->method('getCommandName')
            ->willReturn('do:thing')
        ;
        $this
            ->task
            ->method('getItems')
            ->willReturn(array())
        ;

        $this
            ->task
            ->expects($this->never())
            ->method('getArguments')
        ;
        $this->taskRunner->runTaskLocally($this->task, array());
    }

    public function testRunLocalTaskRunsLocalCommand()
    {
        $taskArgs = array('what' => 'something');
        $commandInput = new ArrayInput($taskArgs);

        $this
            ->command
            ->method('getName')
            ->willReturn('do:thing')
        ;
        $this->app->add($this->command);
        $this
            ->task
            ->method('getCommandName')
            ->willReturn('do:thing')
        ;
        $this
            ->task
            ->method('getItems')
            ->willReturn(array('an-inconsequential-value'))
        ;
        $this
            ->task
            ->method('getArguments')
            ->willReturn($taskArgs)
        ;

        $this
            ->taskRunner
            ->expects($this->once())
            ->method('prepareCommandInput')
            ->with(
                $this->command,
                array('what' => 'something'),
                array('item' => 'an-inconsequential-value')
            )
            ->willReturn($commandInput)
        ;
        $this
            ->taskRunner
            ->expects($this->once())
            ->method('runLocalCommand')
            ->with(
                $this->task,
                $this->command,
                $commandInput
            )
            ->willReturn(0)
        ;

        $this->taskRunner->runTaskLocally($this->task, array());
    }
}
