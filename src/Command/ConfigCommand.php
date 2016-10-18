<?php

namespace Droid\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        $project = $this->getApplication()->getProject();
        if (! $project) {
            return;
        }

        $header = 'Droid configuration:';
        $output->writeln($header);

        if ($project->getModules()) {
            $output->writeln("Modules: ");
        }
        foreach ($project->getModules() as $module) {
            $output->writeln("<info>Module: " . $module->getName() . "</info> " . $module->getSource());
            $output->writeLn("     Description: " . $module->getDescription());
            $output->writeln("     Variables: " . $module->getVariablesAsString());
        }

        foreach ($project->getTargets() as $target) {
            $output->writeln("<info>Target: " . $target->getName() . "</info>");
            if ($target->getHosts()) {
                $output->writeln("     Hosts: " . $target->getHosts());
            }

            $output->writeln("     Variables: " . $target->getVariablesAsString());
            foreach ($target->getModules() as $module) {
                $output->writeln("    <comment> Module: <info>" . $module->getName() . "</info></comment>");
            }
            foreach ($target->getAllTasks() as $task) {
                $output->writeln("    <comment> " . ucfirst($task->getType()) . ": <info>" . $task->getName() . "</info> " . $task->getCommandName() . "</comment>");
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
        $output->writeln("Done.");
    }
}
