<?php

namespace Droid\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        
        $output->writeln("HOSTS");
        foreach ($inventory->getHosts() as $host) {
            $output->writeln('   <comment>'. $host->getName() . "</comment>");
            $output->writeln("      Public: " . $host->getPublicIp() . ':' . $host->getPublicPort());
            if ($host->getPrivateIp()) {
                $output->writeln("      Private: " . $host->getPrivateIp() . ':' . $host->getPrivatePort());
            }
            
            $output->writeln("      Auth: " . $host->getAuth());
            foreach ($host->getVariables() as $name => $value) {
                $output->writeln("      - $name=`$value`");
            }
        }

        $output->writeln("HOST GROUPS");
        foreach ($inventory->getHostGroups() as $group) {
            $output->write("   <info>" . $group->getName() . "</info>: ");
            $hostnames ='';
            foreach ($group->getHosts() as $host) {
                $hostnames .= '<comment>' . trim($host->getName()) . '</comment>, ';
            }
            $output->writeln(trim($hostnames, ' ,'));

            foreach ($group->getVariables() as $name => $value) {
                $output->writeln("    - $name=`$value`");
            }
        }
        $output->writeln("Done");
    }
}
