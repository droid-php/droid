<?php

namespace Droid\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ModuleInstallCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('module:install')
            ->setDescription(
                'Install third-party droid modules in droid-vendor'
            )
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln("Installing droid modules:");
        $project = $this->getApplication()->getProject();
        $vendorPath = 'droid-vendor';

        if (!file_exists($vendorPath)) {
            mkdir($vendorPath);
        }

        foreach ($project->getModules() as $module) {
            $source = $module->getSource();
            $sourceType = 'local';
            if (substr($source, 0, 4)=='git@') {
                $sourceType = 'git';
            }
            $destPath = $vendorPath . '/' . $module->getName();
            $output->writeln("- <info>" . $module->getName() . "</info> from $sourceType <comment>" . $source . "</comment>");

            switch ($sourceType) {
                case 'git':
                    $part = explode(" ", $module->getSource());
                    $url = $part[0];
                    $branch = 'master';
                    if (isset($part[1])) {
                        $branch = $part[1];
                    }

                    if (!file_exists($destPath)) {
                        $output->writeLn("Cloning from $url into $destPath");
                        $cmd = 'git clone ' . $url . ' ' . $destPath;
                        exec($cmd);
                    } else {
                        $output->writeLn("  Fetching updates");
                        $cmd = "cd $destPath && git fetch";
                        exec($cmd);
                    }
                    $output->writeLn("  Switching to branch <comment>$branch</comment>");
                    $cmd = "cd $destPath && git checkout $branch -q";
                    exec($cmd);
                    break;
                case 'local':
                    $output->writeLn('  Skipping local module: ' . $module->getName());
                    break;
            }
        }

        $output->writeln("Done");
    }
}
