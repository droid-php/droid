<?php

namespace Droid\Core;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use RuntimeException;

class BowerInstallCommand extends Command
{
    public function configure()
    {
        $this->setName('bower:install')
            ->setDescription('Bower install')
        ;
    }
    
    public function execute(InputInterface $input, OutputInterface $output)
    {
        //$output->writeLn("Running bower install");
        exec("bower install");
        return true;
    }
}
