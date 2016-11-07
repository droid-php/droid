<?php

namespace Droid\Command;

use RuntimeException;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Droid\Generator;
use Droid\Logger\LoggerFactory;

class GeneratePluginCommand extends Command
{
    private $loggerFac;
    private $pluginGen;

    public function __construct(
        Generator $pluginGenerator,
        LoggerFactory $loggerFactory,
        $name = null
    ) {
        $this->pluginGen = $pluginGenerator;
        $this->loggerFac = $loggerFactory;
        parent::__construct($name);
    }

    public function configure()
    {
        $help = <<<'EOT'
This command creates a skeleton project for a Droid Command plugin.

A Command Plugin is a collection of one or more Console Commands.  The name
given to the plugin is a namespace for the Commands. For example, the mysql
plugin includes the commands:-

    mysql:load
    mysql:dump

When the name argument is given, the plugin project is created in a directory of
the same name.  The directory must exist.

When the name argument is omitted, the plugin project is created in the current
working directory and the name of the plugin becomes the directory name.
EOT;

        $this->setName('generate:plugin')
            ->setDescription('Generate a project for the development of a Command Plugin.')
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'Name of the Plugin.'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force generation when the plugin appears to already exist (.git/ exists).'
            )
            ->setHelp($help)
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
        if (!$input->getOption('force') && file_exists($basePath . '/.git')) {
            throw new RuntimeException('Plugin appears to already exist (.git/ exists).');
        }

        $name = str_replace('droid-', '', $name);
        $output->writeLn("Generating: <info>" . $name . "</info> in <comment>" . $basePath . '</comment>');

        $data = [];
        $data['name'] = $name;
        $data['classname'] = ucfirst($name);

        $this->pluginGen->setLogger($this->loggerFac->makeLogger($output));
        $this->pluginGen->generate(__DIR__ . '/../../generator/plugin', $basePath, $data);
    }
}
