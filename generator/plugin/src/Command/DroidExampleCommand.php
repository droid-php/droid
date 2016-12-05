<?php

namespace Droid\Plugin\{{classname}}\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class {{classname}}ExampleCommand extends Command
{
    public function configure()
    {
        $this
            ->setName('{{name}}:example')
            ->setDescription('This is an example command')
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
        $output->writeLn("{{classname}} Example: " . $input->getArgument('message') . " @ " . $input->getOption('color'));
    }
}
