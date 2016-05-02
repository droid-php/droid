<?php

namespace Droid\Test;

use Droid\Test\AutoloaderAwareTestCase;

use Droid\Application;
use Droid\TaskRunner;
use Droid\Remote\AbleInterface;
use Droid\Remote\EnablerInterface;
use Droid\Remote\EnablementException;
use Droid\Remote\SynchroniserInterface;

use SSHClient\Client\ClientInterface;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class RunRemoteCommandTest extends AutoloaderAwareTestCase
{
    private $synchroniser;
    private $enabler;
    private $output;
    private $input;
    private $command;
    private $host;
    private $sshClient;

    public function setUp()
    {
        $this->synchroniser = $this
            ->getMockBuilder(SynchroniserInterface::class)
            ->setConstructorArgs(array('/tmp/droid-remote-command-test'))
            ->getMock()
        ;
        $this->enabler = $this
            ->getMockBuilder(EnablerInterface::class)
            ->setConstructorArgs(array($this->synchroniser))
            ->getMock()
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

        $app = new Application($this->autoloader);
        $runner = new TaskRunner($app, $this->output, $this->enabler);
        $runner->runRemoteCommand($this->command, $this->input, array($this->host));
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

        $app = new Application($this->autoloader);
        $runner = new TaskRunner($app, $this->output, $this->enabler);
        $runner->runRemoteCommand($this->command, $this->input, array($this->host));
    }

    /**
     * @expectedException RuntimeException
     */
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
            ->method('exec')
            ->with($this->isType('array'))
        ;
        $this
            ->sshClient
            ->expects($this->once())
            ->method('getExitCode')
            ->willReturn(1)
        ;

        $app = new Application($this->autoloader);
        $runner = new TaskRunner($app, $this->output, $this->enabler);
        $runner->runRemoteCommand($this->command, $this->input, array($this->host));
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
            ->method('getSshClient')
            ->willReturn($this->sshClient)
        ;
        $this
            ->sshClient
            ->expects($this->once())
            ->method('exec')
            ->with($this->isType('array'))
        ;
        $this
            ->sshClient
            ->expects($this->once())
            ->method('getExitCode')
            ->willReturn(0)
        ;

        $app = new Application($this->autoloader);
        $runner = new TaskRunner($app, $this->output, $this->enabler);
        $runner->runRemoteCommand($this->command, $this->input, array($this->host));
    }
}