<?php

namespace Droid\Logger;

use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\OutputInterface;

class LoggerFactory
{
    public function makeLogger(OutputInterface $output)
    {
        return new ConsoleLogger(
            $output,
            array(
                LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
                LogLevel::INFO   => OutputInterface::VERBOSITY_VERBOSE,
            )
        );
    }
}
