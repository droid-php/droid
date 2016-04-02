<?php

namespace Droid\Model;

use InvalidArgumentException;

class Step
{
    private $commandName;
    private $parameters;
    
    public function __construct($commandName, $parameters = [])
    {
        $this->commandName = $commandName;
        if ($parameters) {
            $this->parameters = $parameters;
        } else {
            $this->parameters=[];
        }
    }
    
    public function getCommandName()
    {
        return $this->commandName;
    }
    
    public function getParameters()
    {
        return $this->parameters;
    }
    
    public function hasParameter($name)
    {
        if (isset($this->parameters[$name])) {
            return true;
        }
        return false;
    }
    
    public function getParameter($name)
    {
        if (!$this->hasParameter($name)) {
            throw new InvalidArgumentException("No such parameter: " . $name);
        }
        return $this->parameters[$name];
    }
}
