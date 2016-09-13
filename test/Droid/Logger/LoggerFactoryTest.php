<?php

namespace Droid\Test\Logger;

use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Output\OutputInterface;

use Droid\Logger\LoggerFactory;
use Droid\Logger\ConsoleLogger;

class LoggerFactoryTest extends PHPUnit_Framework_TestCase
{
    protected $output;

    protected function setUp()
    {
        $this->output = $this
            ->getMockBuilder(OutputInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    /**
     * @covers \Droid\Logger\LoggerFactory::makeLogger
     */
    public function testMakeLoggerWillReturnInstanceofConsoleLogger()
    {
        $fac = new LoggerFactory;

        $this->assertInstanceof(
            ConsoleLogger::class,
            $fac->makeLogger($this->output),
            'LoggerFactory produces instances of ConsoleLogger.'
        );
    }
}
