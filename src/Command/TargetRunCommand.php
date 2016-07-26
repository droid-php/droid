<?php

namespace Droid\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Droid\TaskRunner;

class TargetRunCommand extends Command
{
    protected $target;
    protected $taskRunner;

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

    public function setTarget($target)
    {
        $this->target = $target;
    }

    public function setTaskRunner(TaskRunner $taskRunner)
    {
        $this->taskRunner = $taskRunner;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $target = $input->getArgument('target');
        if (!$target) {
            $target = $this->target ?: 'default';
        }

        $output->writeln("<info>Droid: Running target `$target`</info>");

        $res = $this
            ->taskRunner
            ->setOutput($output)
            ->runTarget(
                $this->getApplication()->getProject(),
                $target
            )
        ;

        $output->writeln("Result: " . $res);
        $output->writeln('--------------------------------------------');
    }
}
