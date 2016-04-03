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
        $commands[] = new \Droid\Core\BowerInstallCommand();
        $commands[] = new \Droid\Core\ComposerInstallCommand();
        $commands[] = new \Droid\Core\DebugEchoCommand();
        return $commands;
    }
}
