<?php

namespace Droid\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
            ->addOption(
                'uppercase',
                'u',
                InputOption::VALUE_NONE,
                'Uppercase the output'
            )
        ;
    }
    
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $message = $input->getArgument('message');
        if ($input->getOption('uppercase')) {
            $message = strtoupper($message);
        }
        $color = $input->getOption('color');
        if (!$color) {
            $color = 'white';
        }
        $output->writeLn('<fg=' . $color . '>' . $message . '</>');
        //$output->writeLn($input->getArgument('message'));
        //return 0;
    }
}
