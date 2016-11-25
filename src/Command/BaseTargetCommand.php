<?php

namespace Droid\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Droid\TaskRunner;

class BaseTargetCommand extends Command
{
    protected $target;
    protected $taskRunner;

    protected function configure()
    {
        $this
            ->setName('undefined')
            ->setDescription(
                'Undefined description'
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
        $target = $this->target;
        
        $project = $this->getApplication()->getProject();
        foreach ($input->getArguments() as $key => $value) {
            $project->setVariable($key, $value);
        }

        $output->writeln("<info>Droid: Running target `$target`</info>");

        $res = $this
            ->taskRunner
            ->setOutput($output)
            ->runTarget(
                $project,
                $target
            )
        ;

        $output->writeln("Result: " . $res);
        $output->writeln('--------------------------------------------');
    }
}
