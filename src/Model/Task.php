<?php

namespace Droid\Model;

use InvalidArgumentException;

class Task
{
    private $commandName;
    private $loopVariables;
    
    use VariableTrait;
    
    public function __construct($commandName, $variables = [])
    {
        $this->commandName = $commandName;
        if ($variables) {
            $this->variables = $variables;
        } else {
            $this->variables=[];
        }
    }
    
    public function setLoopVariables($loopVariables)
    {
        $this->loopVariables = $loopVariables;
    }
    
    public function hasLoopVariables()
    {
        return isset($this->loopVariables);
    }
    
    public function getLoopVariables()
    {
        return $this->loopVariables;
    }
    
    public function getCommandName()
    {
        return $this->commandName;
    }
}
