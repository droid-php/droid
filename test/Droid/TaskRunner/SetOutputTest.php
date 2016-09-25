<?php

namespace Droid\Test\TaskRunner;

use Droid\Model\Inventory\Remote\Enabler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Droid\Application;
use Droid\Logger\LoggerFactory;
use Droid\TaskRunner;
use Droid\Test\AutoloaderAwareTestCase;
use Droid\Transform\Transformer;

class SetOutputTest extends AutoloaderAwareTestCase
{
    private $app;
    private $enabler;
    private $logger;
    private $loggerFac;
    private $output;
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
        $this->logger = $this
            ->getMockBuilder(LoggerInterface::class)
            ->getMock()
        ;
        $this->loggerFac = $this
            ->getMockBuilder(LoggerFactory::class)
            ->getMock()
        ;
        $this->output = $this
            ->getMockBuilder(OutputInterface::class)
            ->getMock()
        ;
        $this->transformer = $this
            ->getMockBuilder(Transformer::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->taskRunner = new TaskRunner(
            $this->app,
            $this->transformer,
            $this->loggerFac
        );
        $this
            ->taskRunner
            ->setEnabler($this->enabler)
        ;
    }

    public function testSetOutputWillInjectLoggerIntoEnabler()
    {
        $this
            ->loggerFac
            ->expects($this->once())
            ->method('makeLogger')
            ->with($this->equalTo($this->output))
            ->willReturn($this->logger)
        ;
        $this
            ->enabler
            ->expects($this->once())
            ->method('setLogger')
            ->with($this->logger)
        ;

        $this->taskRunner->setOutput($this->output);
    }
}
