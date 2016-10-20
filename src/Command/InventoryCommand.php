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

        $output->writeln("HOSTS");
        foreach ($inventory->getHosts() as $host) {
            $output->writeln('   <comment>'. $host->getName() . "</comment>");
            if ($host->droid_ip) {
                $output->writeln(
                    sprintf(
                        '      Droid socket: %s:%d',
                        $host->droid_ip,
                        $host->getConnectionPort()
                    )
                );
            }
            if ($host->public_ip) {
                $output->writeln(
                    sprintf(
                        '      Public: %s:%d',
                        $host->public_ip,
                        $host->getConnectionPort()
                    )
                );
            }
            if ($host->private_ip) {
                $output->writeln(
                    sprintf(
                        '      Private: %s:%d',
                        $host->private_ip,
                        $host->getConnectionPort()
                    )
                );
            }

            foreach ($host->variables as $name => $value) {
                if (is_array($value)) {
                    $value = '{...}';
                }
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
                if (is_array($value)) {
                    $value = '{...}';
                }
                $output->writeln("    - $name=`$value`");
            }
        }
        $output->writeln("Done");
    }
}
