<?php

namespace Droid\Model;

use InvalidArgumentException;

class RegisteredCommand
{
    private $className;
    private $commandName;
    
    public function __construct($className, $commandName = null)
    {
        $this->className = $className;
        $this->commandName = $commandName;
    }

    public function getClassName()
    {
        return $this->className;
    }
    
    public function getCommandName()
    {
        return $this->commandName;
    }
}
