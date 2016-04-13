<?php

namespace Droid;

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
        $commands[] = new \Droid\Command\GeneratePluginCommand();
        return $commands;
    }
}
