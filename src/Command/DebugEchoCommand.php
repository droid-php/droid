<?php

namespace Droid\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use RuntimeException;

class DebugEchoCommand extends Command
{
    public function configure()
    {
        $this->setName('debug:echo')
            ->setDescription('Echo a message to the console')
            ->addArgument(
                'message',
                InputArgument::REQUIRED,
                'Message to output'
            )
            ->addOption(
                'color',
                'c',
                InputOption::VALUE_REQUIRED,
                'Set color of the output'
            )
        ;
    }
    
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeLn("ECHO: " . $input->getArgument('message') . " @ " . $input->getOption('color'));
        //$output->writeLn($input->getArgument('message'));
        //return 0;
    }
}
