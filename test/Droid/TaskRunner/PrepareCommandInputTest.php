<?php

namespace Droid\Test\TaskRunner;

use Droid\Model\Inventory\Remote\Enabler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

use Droid\Application;
use Droid\Logger\LoggerFactory;
use Droid\TaskRunner;
use Droid\Transform\Transformer;

use Droid\Test\AutoloaderAwareTestCase;

class PrepareCommandInputTest extends AutoloaderAwareTestCase
{
    private $app;
    private $command;
    private $enabler;
    private $expr;
    private $logger;
    private $loggerFac;
    private $output;
    private $taskRunner;
    private $transformer;

    public function setUp()
    {
        $this->app = new Application($this->autoloader);
        $this->command = $this->getMockCommand();
        $this->enabler = $this
            ->getMockBuilder(Enabler::class)
            ->disableOriginalConstructor()
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
        $this->taskRunner = new TaskRunner(
            $this->app,
            $this->transformer,
            $this->loggerFac,
            $this->expr,
            $this->transformer
        );
        $this
            ->taskRunner
            ->setEnabler($this->enabler)
            ->setOutput($this->output)
        ;
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

    public function testWillTransformPlaceholderIntoValue()
    {
        $argumentValue = '{{{ some-var }}}';
        $variables = array('some-var' => 'something');

        $this
            ->transformer
            ->expects($this->once())
            ->method('transformVariable')
            ->with($argumentValue, $variables)
            ->willReturn('pick a value; any value')
        ;
        $this
            ->taskRunner
            ->legacyPrepareCommandInput(
                $this->command,
                array('what' => $argumentValue),
                $variables
            )
        ;
    }

    public function testWillTransformInventoryExpressionIntoInventoryProperty()
    {
        $argumentValue = '%some-host.name%';
        $variables = array('some-var' => 'something');

        $this
            ->transformer
            ->method('transformVariable')
            ->willReturnArgument(0)
        ;
        $this
            ->taskRunner
            ->legacyPrepareCommandInput(
                $this->command,
                array('what' => $argumentValue),
                $variables
            )
        ;
    }

    public function testWillTransformFilenameIntoFileContentIntoDataUri()
    {
        $variables = array('some-var' => 'something');

        $inputToMethod = '@path/to/something';

        $inputToTxfmVar = $inputToMethod;
        $outputFromTxfmVar = $inputToTxfmVar;

        $inputToTxfmFile = substr($outputFromTxfmVar, 1);
        $outputFromTxfmFile = 'is this the real life?';

        $inputToTxfmDataStream = $outputFromTxfmFile;
        $outputFromTxfmDataStream = 'is this just fantasy?';

        $this
            ->transformer
            ->expects($this->once())
            ->method('transformVariable')
            ->with($inputToTxfmVar, $variables)
            ->willReturn($outputFromTxfmVar)
        ;
        $this
            ->transformer
            ->expects($this->once())
            ->method('transformFile')
            ->with($inputToTxfmFile)
            ->willReturn($outputFromTxfmFile)
        ;
        $this
            ->transformer
            ->expects($this->once())
            ->method('transformDataStream')
            ->with($inputToTxfmDataStream)
            ->willReturn($outputFromTxfmDataStream)
        ;

        $this
            ->taskRunner
            ->legacyPrepareCommandInput(
                $this->command,
                array('what' => $inputToMethod),
                $variables
            )
        ;
    }

    public function testWillTransformFilenameIntoTemplateFileContentIntoDataUri()
    {
        $variables = array('some-var' => 'something');

        $inputToMethod = '!path/to/something';

        $inputToTxfmVar_1 = $inputToMethod;
        $outputFromTxfmVar_1 = $inputToTxfmVar_1;

        $inputToTxfmFile = substr($outputFromTxfmVar_1, 1);
        $outputFromTxfmFile = 'no escape from reality';

        $inputToTxfmVar_2 = $outputFromTxfmFile;
        $outputFromTxfmVar_2 = 'any way the wind blows';

        $inputToTxfmDataStream = $outputFromTxfmVar_2;
        $outputFromTxfmDataStream = 'nothing really matters to meeee';

        $this
            ->transformer
            ->expects($this->exactly(2))
            ->method('transformVariable')
            ->withConsecutive(
                array($inputToTxfmVar_1, $variables),
                array($inputToTxfmVar_2, $variables)
            )
            ->willReturnOnConsecutiveCalls(
                $outputFromTxfmVar_1,
                $outputFromTxfmVar_2
            )
        ;
        $this
            ->transformer
            ->expects($this->once())
            ->method('transformFile')
            ->with($inputToTxfmFile)
            ->willReturn($outputFromTxfmFile)
        ;
        $this
            ->transformer
            ->expects($this->once())
            ->method('transformDataStream')
            ->with($inputToTxfmDataStream)
            ->willReturn($outputFromTxfmDataStream)
        ;

        $this
            ->taskRunner
            ->legacyPrepareCommandInput(
                $this->command,
                array('what' => $inputToMethod),
                $variables
            )
        ;
    }
}
