<?php

namespace Droid\Command;

use Droid\Model\Inventory\Inventory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PingCommand extends Command
{
    protected $inventory;

    /**
     *
     * @param Inventory $inventory
     */
    public function setInventory(Inventory $inventory)
    {
        $this->inventory = $inventory;
        return $this;
    }

    /**
     * @return \Droid\Model\Inventory\Inventory
     */
    public function getInventory()
    {
        return $this->inventory;
    }

    protected function configure()
    {
        $this
            ->setName('ping')
            ->setDescription(
                'Connect to SSH on the specified host, or all hosts in the Inventory.'
            )
            ->addArgument(
                'hostname',
                InputArgument::OPTIONAL,
                'The name of a host to ping'
            )
            ->setHelp(implode("\n", array(
                'Connects to and immediately disconnect from the SSH service on a specified Inventory host or all Inventory hosts.',
                'An SSH connection is attempted only to Inventory hosts having a "keyfile" configured in their entry in the Inventory.'
            )))
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if (! $this->getInventory() || ! $this->getInventory()->getHosts()) {
            throw new RuntimeException('To perform this command I require an Inventory of Hosts.');
        }
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $hosts = array();
        if ($input->getArgument('hostname')) {
            $hosts[] = $this->getInventory()->getHost($input->getArgument('hostname'));
        } else {
            $hosts = $this->getInventory()->getHosts();
        }

        $output->writeln(
            sprintf(
                'I attempt to Ping %d host%s.',
                sizeof($hosts),
                sizeof($hosts) == 1 ? '' : 's'
            )
        );

        foreach ($hosts as $host) {
            $ssh = $host->getSshClient();
            $output->writeln(
                sprintf('<host>%s</> Ping.', $host->getName())
            );
            $ssh->exec(array('/bin/true'));
            if ($ssh->getExitCode()) {
                $output->writeln(
                    sprintf(
                        '<host>%s</> Ping fail (code %d):-',
                        $host->getName(),
                        $ssh->getExitCode()
                    )
                );
                $output->write($ssh->getErrorOutput(), true);
            } else {
                $output->writeln(
                    sprintf('<host>%s</>  <info>Pong</>.', $host->getName())
                );
                $stdout = $ssh->getOutput();
                if (strlen($stdout)) {
                    $output->write($stdout, true);
                }
                $stderr = $ssh->getErrorOutput();
                if (strlen($stderr)) {
                    $output->write($stderr, true);
                }
            }
        }

        $output->writeln('Finished Pinging hosts.');
    }
}
