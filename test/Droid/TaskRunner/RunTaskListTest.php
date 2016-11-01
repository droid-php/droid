<?php

namespace Droid\Test\TaskRunner;

use RuntimeException;

use Droid\Model\Inventory\Host;
use Droid\Model\Inventory\Inventory;
use Droid\Model\Inventory\Remote\Enabler;
use Droid\Model\Project\Target;
use Droid\Model\Project\Task;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

use Droid\Application;
use Droid\Logger\LoggerFactory;
use Droid\TaskRunner;
use Droid\Test\AutoloaderAwareTestCase;
use Droid\Transform\Transformer;

class RunTaskListTest extends AutoloaderAwareTestCase
{
    private $app;
    private $enabler;
    private $expr;
    private $host;
    private $inventory;
    private $logger;
    private $loggerFac;
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
            ->getMockBuilder(Enabler::class)
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
        $this->logger = $this
            ->getMockBuilder(LoggerInterface::class)
            ->getMock()
        ;
        $this->loggerFac = $this
            ->getMockBuilder(LoggerFactory::class)
            ->getMock()
        ;
        $this->expr = $this
            ->getMockBuilder(ExpressionLanguage::class)
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
                array($this->app, $this->transformer, $this->loggerFac, $this->expr, $this->transformer)
            )
            ->setMethods(array('runTaskRemotely', 'runTaskLocally'))
            ->getMock()
            ->setEnabler($this->enabler)
            ->setOutput($this->output)
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
