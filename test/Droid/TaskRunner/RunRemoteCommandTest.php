<?php

namespace Droid\Test\TaskRunner;

use Droid\Model\Inventory\Remote\AbleInterface;
use Droid\Model\Inventory\Remote\EnablementException;
use Droid\Model\Inventory\Remote\Enabler;
use Droid\Model\Inventory\Remote\SynchroniserInterface;
use Droid\Model\Project\Task;
use Psr\Log\LoggerInterface;
use SSHClient\Client\ClientInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

use Droid\Application;
use Droid\Logger\LoggerFactory;
use Droid\TaskRunner;
use Droid\Test\AutoloaderAwareTestCase;
use Droid\Transform\Transformer;

class RunRemoteCommandTest extends AutoloaderAwareTestCase
{
    private $app;
    private $synchroniser;
    private $enabler;
    private $expr;
    private $logger;
    private $loggerFac;
    private $output;
    private $input;
    private $task;
    private $command;
    private $host;
    private $sshClient;
    private $taskRunner;
    private $transformer;

    public function setUp()
    {
        $this->synchroniser = $this
            ->getMockBuilder(SynchroniserInterface::class)
            ->setConstructorArgs(array('/tmp/droid-remote-command-test'))
            ->getMock()
        ;
        $this->enabler = $this
            ->getMockBuilder(Enabler::class)
            ->setConstructorArgs(array($this->synchroniser))
            ->getMock()
        ;
        $this->task = $this
            ->getMockBuilder(Task::class)
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
        $this->input = $this
            ->getMockBuilder(ArrayInput::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->command = $this
            ->getMockBuilder(Command::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->host = $this
            ->getMockBuilder(AbleInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass()
        ;
        $this->sshClient = $this
            ->getMockBuilder(ClientInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->transformer = $this
            ->getMockBuilder(Transformer::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->app = new Application($this->autoloader);
        $this->taskRunner = new TaskRunner(
            $this->app,
            $this->transformer,
            $this->loggerFac,
            $this->expr
        );
        $this
            ->taskRunner
            ->setEnabler($this->enabler)
            ->setOutput($this->output)
        ;

        $this
            ->host
            ->method('getName')
            ->willReturn('host.example.com')
        ;
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Expected an ArrayInput for host "host.example.com".
     */
    public function testRunRemoteCommandFailsWhenPerHostCommandInputIsMissing()
    {
        $this
            ->host
            ->expects($this->never())
            ->method('enabled')
        ;

        $this->taskRunner->runRemoteCommand(
            $this->task,
            $this->command,
            array(),
            array($this->host)
        );
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Expected an ArrayInput for host "host.example.com".
     */
    public function testRunRemoteCommandFailsWhenPerHostCommandInputIsTheWrongTrousers()
    {
        $this
            ->host
            ->expects($this->never())
            ->method('enabled')
        ;

        $this->taskRunner->runRemoteCommand(
            $this->task,
            $this->command,
            array($this->host->getName() => 'Hang in there Grommit, everything\'s under control'),
            array($this->host)
        );
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Unable to run remote command
     */
    public function testRunRemoteCommandCannotUploadDroid()
    {
        $this
            ->host
            ->expects($this->once())
            ->method('enabled')
            ->willReturn(false)
        ;
        $this
            ->enabler
            ->expects($this->once())
            ->method('enable')
            ->willThrowException(new EnablementException)
        ;
        $this
            ->host
            ->expects($this->never())
            ->method('getSshClient')
        ;

        $this->taskRunner->runRemoteCommand(
            $this->task,
            $this->command,
            array($this->host->getName() => $this->input),
            array($this->host)
        );
    }

    public function testRunRemoteCommandNeedsNotUploadDroid()
    {
        $this
            ->host
            ->expects($this->once())
            ->method('enabled')
            ->willReturn(true)
        ;
        $this
            ->enabler
            ->expects($this->never())
            ->method('enable')
        ;
        $this
            ->host
            ->expects($this->once())
            ->method('getSshClient')
            ->willReturn($this->sshClient)
        ;

        $this->taskRunner->runRemoteCommand(
            $this->task,
            $this->command,
            array($this->host->getName() => $this->input),
            array($this->host)
        );
    }

    public function testRunRemoteCommandNonZeroExitCode()
    {
        $this
            ->host
            ->expects($this->once())
            ->method('enabled')
            ->willReturn(false)
        ;
        $this
            ->enabler
            ->expects($this->once())
            ->method('enable')
            ->with($this->host)
        ;
        $this
            ->host
            ->expects($this->once())
            ->method('getSshClient')
            ->willReturn($this->sshClient)
        ;
        $this
            ->sshClient
            ->expects($this->once())
            ->method('startExec')
            ->with($this->isType('array'))
        ;
        $this
            ->sshClient
            ->expects($this->atLeastOnce())
            ->method('getExitCode')
            ->willReturn(1)
        ;

        $this->taskRunner->runRemoteCommand(
            $this->task,
            $this->command,
            array($this->host->getName() => $this->input),
            array($this->host)
        );
    }

    public function testRunRemoteCommandGoodResult()
    {
        $this
            ->host
            ->expects($this->once())
            ->method('enabled')
            ->willReturn(false)
        ;
        $this
            ->enabler
            ->expects($this->once())
            ->method('enable')
            ->with($this->host)
        ;
        $this
            ->host
            ->expects($this->once())
            ->method('getWorkingDirectory')
            ->willReturn('/tmp')
        ;
        $this
            ->host
            ->expects($this->once())
            ->method('getDroidCommandPrefix')
            ->willReturn('droid')
        ;
        $this
            ->command
            ->expects($this->once())
            ->method('getName')
            ->willReturn('do:something')
        ;
        $this
            ->input
            ->expects($this->once())
            ->method('__toString')
            ->willReturn('--now')
        ;
        $this
            ->host
            ->expects($this->once())
            ->method('getSshClient')
            ->willReturn($this->sshClient)
        ;
        $this
            ->sshClient
            ->expects($this->once())
            ->method('startExec')
            ->with(
                array(
                    'cd /tmp;',
                    'droid',
                    'do:something',
                    '--now',
                    '--ansi',
                )
            )
        ;
        $this
            ->sshClient
            ->expects($this->once())
            ->method('getExitCode')
            ->willReturn(0)
        ;

        $this->taskRunner->runRemoteCommand(
            $this->task,
            $this->command,
            array($this->host->getName() => $this->input),
            array($this->host)
        );
    }

    public function testRunRemoteCommandElevated()
    {
        $this
            ->host
            ->expects($this->once())
            ->method('enabled')
            ->willReturn(false)
        ;
        $this
            ->enabler
            ->expects($this->once())
            ->method('enable')
            ->with($this->host)
        ;
        $this
            ->host
            ->expects($this->once())
            ->method('getWorkingDirectory')
            ->willReturn('/tmp')
        ;
        $this
            ->task
            ->expects($this->once())
            ->method('getElevatePrivileges')
            ->willReturn(true)
        ;
        $this
            ->host
            ->expects($this->once())
            ->method('getDroidCommandPrefix')
            ->willReturn('droid')
        ;
        $this
            ->command
            ->expects($this->once())
            ->method('getName')
            ->willReturn('do:something')
        ;
        $this
            ->input
            ->expects($this->once())
            ->method('__toString')
            ->willReturn('--now')
        ;
        $this
            ->host
            ->expects($this->once())
            ->method('getSshClient')
            ->willReturn($this->sshClient)
        ;
        $this
            ->sshClient
            ->expects($this->once())
            ->method('startExec')
            ->with(
                array(
                    'cd /tmp;',
                    'sudo droid',
                    'do:something',
                    '--now',
                    '--ansi',
                )
            )
        ;
        $this
            ->sshClient
            ->expects($this->once())
            ->method('getExitCode')
            ->willReturn(0)
        ;

        $this->taskRunner->runRemoteCommand(
            $this->task,
            $this->command,
            array($this->host->getName() => $this->input),
            array($this->host)
        );
    }
}
