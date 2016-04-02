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
            $output->writeln(" - " . $target->getName());
            $output->writeln("     Steps:");
            foreach ($target->getSteps() as $step) {
                $output->writeln("     - " . $step->getCommandName());
                $parameters = $step->getParameters();
                if ($parameters) {
                    foreach ($parameters as $key => $value) {
                        $output->writeln("        * $key = `$value`");
                    }
                }
            }
        }
        $output->writeln("Done: ");
    }
}
