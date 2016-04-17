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

        foreach ($inventory->getVariables() as $name => $value) {
            $output->writeln("- $name=`$value`");
        }
        
        foreach ($inventory->getHosts() as $host) {
            $output->writeln("<info>Host " . $host->getName() . "</info>");
            $output->writeln("   Auth: " . $host->getAuth());
            foreach ($host->getVariables() as $name => $value) {
                $output->writeln("    - $name=`$value`");
            }
        }

        foreach ($inventory->getHostGroups() as $group) {
            $output->write("<info>Group " . $group->getName() . "</info>: ");
            $hostnames ='';
            foreach ($group->getHosts() as $host) {
                $hostnames .= '<comment>' . $host->getName() . '</comment>, ';
            }
            $output->writeln(trim($hostnames, ' ,'));

            foreach ($group->getVariables() as $name => $value) {
                $output->writeln("    - $name=`$value`");
            }
        }
        $output->writeln("Done");
    }
}
