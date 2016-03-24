<?php

namespace Droid\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Droid\Droid;
use Droid\Loader\YamlConfigLoader;

class RunCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('run')
            ->setDescription(
                'Run a target'
            )
            ->addArgument(
                'target',
                InputArgument::OPTIONAL,
                'Target to run'
            )
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $target = $input->getArgument('target');
        if (!$target) {
            $target = 'build';
        }
        $output->writeln("Running target `$target`");
        $filename = $this->getDroidFilename();
        
        $droid = new Droid($filename);
        $droid->addTask(new \Droid\Task\EchoTask());
        
        $loader = new YamlConfigLoader();
        $loader->load($droid, $filename);
        
        $res = $droid->runTarget($target);
        $output->writeln("Done: " . $res);
    }
    
    public function getDroidFilename()
    {
        $filename = __DIR__ . '/../../example/droid.yml';
        return $filename;
    }
}
