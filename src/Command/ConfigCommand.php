<?php

namespace Droid\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Droid\Project;
use Droid\Loader\YamlProjectLoader;

class ConfigCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('config')
            ->setDescription(
                'Show project configuration'
            )
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln("Droid configuration:");
        $project = $this->getApplication()->getProject();

        $output->writeln("Targets: ");
        
        foreach ($project->getTargets() as $target) {
            $output->writeln("<info>Target: " . $target->getName() . "</info>");
            if ($target->getHosts()) {
                $output->writeln("     Hosts: " . $target->getHosts());
            }
            $output->writeln("     Variables: " . $target->getVariablesAsString());
            foreach ($target->getTasks() as $task) {
                $output->writeln("    <comment> Task: <info>" . $task->getName() . "</info> " . $task->getCommandName() . "</comment>");
                $arguments = $task->getArguments();
                if ($arguments) {
                    foreach ($arguments as $name => $value) {
                        if (!is_array($value)) {
                            $output->writeln("        * $name = `$value`");
                        } else {
                            $output->writeln("        * $name = [array]");
                        }
                    }
                }
            }
        }
        $output->writeln("Done: ");
    }
}
