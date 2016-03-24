<?php

namespace Droid\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Droid\Droid;
use Droid\Loader\YamlConfigLoader;

class ConfigCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('config')
            ->setDescription(
                'Show configuration'
            )
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln("Droid configuration:");
        $filename = $this->getDroidFilename();
        $output->writeln(" - Filename: " . $filename);
        
        
        $droid = new Droid($filename);
        $droid->addTask(new \Droid\Task\EchoTask());
        
        $loader = new YamlConfigLoader();
        $loader->load($droid, $filename);
        
        $output->writeln("Targets: ");
        
        foreach ($droid->getTargets() as $target) {
            $output->writeln(" - " . $target->getName());
            $output->writeln("     Steps:");
            foreach ($target->getSteps() as $step) {
                $output->writeln("     - " . $step->getTaskName());
            }
        }
        $output->writeln("Done: ");
    }
    
    public function getDroidFilename()
    {
        $filename = __DIR__ . '/../../example/droid.yml';
        return $filename;
    }
}
