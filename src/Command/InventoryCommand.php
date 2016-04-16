<?php

namespace Droid\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Droid\Project;
use Droid\Loader\YamlProjectLoader;

class InventoryCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('inventory')
            ->setDescription(
                'Show inventory configuration'
            )
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln("Droid inventory:");
        $inventory = $this->getApplication()->getInventory();

        $output->writeln("Hosts: ");
        
        foreach ($inventory->getHosts() as $host) {
            $output->writeln(" - " . $host->getName());
        }

        $output->writeln("Groups: ");
        foreach ($inventory->getHostGroups() as $group) {
            $output->writeln(" - " . $group->getName());
            foreach ($group->getHosts() as $host) {
                $output->writeln("    - " . $host->getName());
            }
        }
        $output->writeln("Done");
    }
}
