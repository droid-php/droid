<?php

namespace Droid\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use RuntimeException;
use Droid\TaskRunner;

class TargetRunCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('target:run')
            ->setDescription(
                'Run a target'
            )
            ->addArgument(
                'target',
                InputArgument::OPTIONAL,
                'Target to run'
            )
        ;
    }
    
    private $target;
    
    public function setTarget($target)
    {
        $this->target = $target;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $target = $input->getArgument('target');
        if (!$target) {
            $target = 'default';
            if ($this->target) {
                $target = $this->target;
            }
        }
        $output->writeln("<info>Droid: Running target `$target`</info>");
        $project = $this->getApplication()->getProject();
        
        $runner = new TaskRunner($this->getApplication(), $output);
        $res = $runner->runTarget($project, $target);
        
        $output->writeln("Result: " . $res);
        $output->writeln('--------------------------------------------');
    }
}
