<?php

namespace Droid\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Droid\Generator;
use RuntimeException;

class GeneratePluginCommand extends Command
{
    public function configure()
    {
        $this->setName('generate:plugin')
            ->setDescription('Generate a plugin project')
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'Name of the plugin. For example: droid-hello'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force initialization, even if project appears to have been initialized before'
            )
        ;
    }
    
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $basePath = getcwd() . '/' . $name;
        if (!$name) {
            $basePath = getcwd();
            $name = basename($basePath);
        }
        $name = str_replace('droid-', '', $name);
        $output->writeLn("Generating: <info>" . $name . "</info> in <comment>" . $basePath . '</comment>');
        if (!$input->getOption('force')) {
            if (file_exists($basePath . '/.git')) {
                throw new RuntimeException("Project path already initialized");
            }
        }
        $generator = new Generator();
        $data = [];
        $data['name'] = $name;
        $data['classname'] = ucfirst($name);
        $generator->generate(__DIR__ . '/../../generator/plugin', $basePath, $data);
        
        //$output->writeLn($input->getArgument('message'));
        //return 0;
    }
}
