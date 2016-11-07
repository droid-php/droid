<?php

namespace Droid;

use Droid\Generator;
use Droid\Logger\LoggerFactory;

class DroidPlugin
{
    public function __construct($droid)
    {
        $this->droid = $droid;
    }

    public function getCommands()
    {
        $commands = [];
        $commands[] = new \Droid\Command\TargetRunCommand();
        $commands[] = new \Droid\Command\ConfigCommand();
        $commands[] = new \Droid\Command\DebugEchoCommand();
        $commands[] = new \Droid\Command\GeneratePluginCommand(
            new Generator,
            new LoggerFactory
        );
        $commands[] = new \Droid\Command\InventoryCommand();
        $commands[] = new \Droid\Command\ModuleInstallCommand();
        $commands[] = new \Droid\Command\PingCommand;
        return $commands;
    }
}
