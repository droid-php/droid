<?php

namespace Droid\Plugin\{{classname}};

use Droid\Plugin\{{classname}}\Command\{{classname}}ExampleCommand;

class DroidPlugin
{
    public function __construct($droid)
    {
        $this->droid = $droid;
    }

    public function getCommands()
    {
        $commands = [];
        $commands[] = new {{classname}}ExampleCommand();
        return $commands;
    }
}
