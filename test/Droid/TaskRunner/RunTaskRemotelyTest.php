<?php

namespace Droid\Test\TaskRunner;

use Droid\Model\Inventory\Host;
use Droid\Model\Inventory\Inventory;
use Droid\Model\Inventory\Remote\EnablerInterface;
use Droid\Model\Project\Task;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

use Droid\Application;
use Droid\TaskRunner;
use Droid\Test\AutoloaderAwareTestCase;
use Droid\Transform\Transformer;

class RunTaskRemotelyTest extends AutoloaderAwareTestCase
{
    private $app;
    private $command;
    private $enabler;
    private $host;
    private $inventory;
    private $output;
    private $task;
    private $taskRunner;
    private $transformer;

    public function setUp()
    {
        $this->app = $this->getPartialMockApplication();
        $this->command = $this->getMockCommand();
        $this->enabler = $this
            ->getMockBuilder(EnablerInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->host = $this
            ->getMockBuilder(Host::class)
            ->setConstructorArgs(array('host.example.com'))
            ->getMock()
        ;
        $this->inventory = $this
            ->getMockBuilder(Inventory::class)
            ->getMock()
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
                array($this->app, $this->transformer)
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
            ->setOutput($this->output)
            ->setEnabler($this->enabler)
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
        $this->taskRunner->runTaskRemotely($this->task, array(), 'some-named-hosts');
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
        $this->taskRunner->runTaskRemotely($this->task, array(), 'some-named-hosts');
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
        $this->taskRunner->runTaskRemotely($this->task, array(), 'some-named-hosts');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot run remote commands without inventory
     */
    public function testRunRemoteTaskFailsWithoutInventory()
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
            ->willReturn(array('an-inconsequential-value'))
        ;

        $this->taskRunner->runTaskRemotely($this->task, array(), 'some-named-hosts');
    }

    public function testRunRemoteTaskRunsRemoteCommandOnAllHosts()
    {
        $taskArgs = array('what' => 'something');
        $commandInput = new ArrayInput($taskArgs);
        $hostYes = new Host('host-yes');
        $hostYes->setVariable('rehab', 'yes');
        $hostNo = new Host('host-no');
        $hostNo->setVariable('rehab', 'no-no-no');
        $hosts = array('host-yes' => $hostYes, 'host-no' => $hostNo);

        $this
            ->command
            ->method('getName')
            ->willReturn('do:thing')
        ;
        $this->app->add($this->command);
        $this
            ->app
            ->method('hasInventory')
            ->willReturn(true)
        ;
        $this
            ->app
            ->method('getInventory')
            ->willReturn($this->inventory)
        ;
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
            ->inventory
            ->method('getHostsByName')
            ->with('my-hosts')
            ->willReturn($hosts)
        ;
        $this
            ->taskRunner
            ->expects($this->exactly(2))
            ->method('prepareCommandInput')
            ->withConsecutive(
                array(
                    $this->command,
                    array('what' => 'something'),
                    array(
                        'item' => 'an-inconsequential-value',
                        'host' => $hostYes,
                        'rehab' => 'yes',
                    )
                ),
                array(
                    $this->command,
                    array('what' => 'something'),
                    array(
                        'item' => 'an-inconsequential-value',
                        'host' => $hostNo,
                        'rehab' => 'no-no-no',
                    )
                )
            )
            ->willReturnOnConsecutiveCalls($commandInput, $commandInput)
        ;
        $this
            ->taskRunner
            ->expects($this->once())
            ->method('runRemoteCommand')
            ->with(
                $this->task,
                $this->command,
                array(
                    $hostYes->name => $commandInput,
                    $hostNo->name => $commandInput,
                ),
                $hosts
            )
            ->willReturn(0)
        ;

        $this->taskRunner->runTaskRemotely($this->task, array(), 'my-hosts');
    }

    public function testRunRemoteTaskRunsRemoteCommandOnSubsetOfHosts()
    {
        $taskArgs = array('what' => 'something');
        $commandInput = new ArrayInput($taskArgs);
        $hostYes = new Host('host-yes');
        $hostYes->setVariable('rehab', 'yes');
        $hostNo = new Host('host-no');
        $hostNo->setVariable('rehab', 'no-no-no');
        $hosts = array('host-yes' => $hostYes, 'host-no' => $hostNo);

        $this
            ->command
            ->method('getName')
            ->willReturn('do:thing')
        ;
        $this->app->add($this->command);
        $this
            ->app
            ->method('hasInventory')
            ->willReturn(true)
        ;
        $this
            ->app
            ->method('getInventory')
            ->willReturn($this->inventory)
        ;
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
            ->method('getHostFilter')
            ->willReturn('host.variables["rehab"] == "yes"')
        ;
        $this
            ->task
            ->method('getArguments')
            ->willReturn($taskArgs)
        ;

        $this
            ->inventory
            ->method('getHostsByName')
            ->with('my-hosts')
            ->willReturn($hosts)
        ;
        $this
            ->taskRunner
            ->expects($this->once())
            ->method('prepareCommandInput')
            ->with(
                $this->command,
                array('what' => 'something'),
                array(
                    'item' => 'an-inconsequential-value',
                    'host' => $hostYes,
                    'rehab' => 'yes',
                )
            )
            ->willReturn($commandInput)
        ;
        $this
            ->taskRunner
            ->expects($this->once())
            ->method('runRemoteCommand')
            ->with(
                $this->task,
                $this->command,
                array($hostYes->name => $commandInput),
                array($hostYes)
            )
            ->willReturn(0)
        ;

        $this->taskRunner->runTaskRemotely($this->task, array(), 'my-hosts');
    }
}
