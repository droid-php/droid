<?php

namespace Droid\Core;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use RuntimeException;

class ComposerInstallCommand extends Command
{
    public function configure()
    {
        $this->setName('composer:install')
            ->setDescription('Composer install')
            ->addArgument(
                'message',
                InputArgument::OPTIONAL,
                'Message to output'
            )
            ->addOption(
                'color',
                'c',
                InputOption::VALUE_REQUIRED,
                'Set color of the output',
                null
            )
        ;
    }
    
    public function execute(InputInterface $input, OutputInterface $output)
    {
        //$output->writeLn("Running composer install");
        exec("composer install");
        return true;
    }
}
