<?php

namespace Droid\Plugin\{{classname}};

class DroidPlugin
{
    public function __construct($droid)
    {
        $this->droid = $droid;
    }
    
    public function getCommands()
    {
        $commands = [];
        $commands[] = new \Droid\Plugin\{{classname}}\Command\{{classname}}ExampleCommand();
        return $commands;
    }
}
