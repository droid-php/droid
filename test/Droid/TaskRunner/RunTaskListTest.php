<?php

namespace Droid\Test\TaskRunner;

use RuntimeException;

use Droid\Model\Inventory\Host;
use Droid\Model\Inventory\Inventory;
use Droid\Model\Inventory\Remote\EnablerInterface;
use Droid\Model\Project\Target;
use Droid\Model\Project\Task;
use Symfony\Component\Console\Output\OutputInterface;

use Droid\Application;
use Droid\TaskRunner;
use Droid\Test\AutoloaderAwareTestCase;
use Droid\Transform\Transformer;

class RunTaskListTest extends AutoloaderAwareTestCase
{
    private $app;
    private $enabler;
    private $host;
    private $inventory;
    private $output;
    private $target;
    private $task;
    private $taskRunner;
    private $transformer;

    public function setUp()
    {
        $this->app = $this
            ->getMockBuilder(Application::class)
            ->setConstructorArgs(array($this->autoloader))
            ->setMethods(array('hasInventory'))
            ->getMock()
        ;
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
        $this->target = $this
            ->getMockBuilder(Target::class)
            ->disableOriginalConstructor()
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
        $this->taskRunner = $this
            ->getMockBuilder(TaskRunner::class)
            ->setConstructorArgs(
                array($this->app, $this->transformer)
            )
            ->setMethods(array('runTaskRemotely', 'runTaskLocally'))
            ->getMock()
            ->setOutput($this->output)
            ->setEnabler($this->enabler)
        ;
    }

    public function testRunTaskListWithoutTasksDoesNothing()
    {
        $this
            ->target
            ->method('getTasksByType')
            ->with('task')
            ->willReturn(array())
        ;

        $this
            ->taskRunner
            ->expects($this->never())
            ->method('runTaskLocally')
        ;
        $this
            ->taskRunner
            ->expects($this->never())
            ->method('runTaskRemotely')
        ;

        $this->taskRunner->runTaskList($this->target, array(), '');
    }

    public function testRunTaskListWithoutHostsRunsTaskLocally()
    {
        $this
            ->target
            ->method('getTasksByType')
            ->with('task')
            ->willReturn(array($this->task))
        ;
        $this
            ->task
            ->method('getChanged')
            ->willReturn(false)
        ;

        $this
            ->taskRunner
            ->expects($this->never())
            ->method('runTaskRemotely')
        ;
        $this
            ->taskRunner
            ->expects($this->once())
            ->method('runTaskLocally')
            ->with($this->task, array())
        ;

        $this->taskRunner->runTaskList($this->target, array(), '');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Cannot run remote commands without inventory
     */
    public function testRunTaskListWithHostsWithoutInventoryFails()
    {
        $this
            ->app
            ->method('hasInventory')
            ->willReturn(false)
        ;
        $this
            ->target
            ->method('getTasksByType')
            ->with('task')
            ->willReturn(array($this->task))
        ;

        $this->taskRunner->runTaskList($this->target, array(), 'foo');
    }

    public function testRunTaskListWithTargetHostsRunsTaskRemotelyWithTargetHosts()
    {
        $this
            ->app
            ->method('hasInventory')
            ->willReturn(true)
        ;
        $this
            ->target
            ->method('getTasksByType')
            ->with('task')
            ->willReturn(array($this->task))
        ;
        $this
            ->task
            ->method('getChanged')
            ->willReturn(false)
        ;

        $this
            ->taskRunner
            ->expects($this->never())
            ->method('runTaskLocally')
        ;
        $this
            ->taskRunner
            ->expects($this->once())
            ->method('runTaskRemotely')
            ->with($this->task, array(), 'some-hosts')
        ;

        $this->taskRunner->runTaskList($this->target, array(), 'some-hosts');
    }

    public function testRunTaskListWithTaskHostsRunsTaskRemotelyWithTaskHosts()
    {
        $this
            ->app
            ->method('hasInventory')
            ->willReturn(true)
        ;
        $this
            ->target
            ->method('getTasksByType')
            ->with('task')
            ->willReturn(array($this->task))
        ;
        $this
            ->task
            ->method('getChanged')
            ->willReturn(false)
        ;
        $this
            ->task
            ->method('getHosts')
            ->willReturn('some-task-hosts')
        ;

        $this
            ->taskRunner
            ->expects($this->never())
            ->method('runTaskLocally')
        ;
        $this
            ->taskRunner
            ->expects($this->once())
            ->method('runTaskRemotely')
            ->with($this->task, array(), 'some-task-hosts')
        ;

        $this->taskRunner->runTaskList($this->target, array(), 'some-hosts');
    }
}
