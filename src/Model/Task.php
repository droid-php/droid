<?php

namespace Droid\Model;

use InvalidArgumentException;
use RuntimeException;

class Task
{
    private $name;
    private $commandName;
    private $arguments = [];
    private $items = [];
    
    public function setArgument($name, $value)
    {
        $this->arguments[$name] = $value;
    }
    
    public function getArgument($name)
    {
        if (!$this->hasArgument($name)) {
            throw new RuntimeException('No such argument');
        }
        return $this->arguments[$name];
    }
    
    public function hasArgument($name)
    {
        return isset($this->arguments[$name]);
    }
    
    public function getArguments()
    {
        return $this->arguments;
    }
    
    public function getCommandName()
    {
        return $this->commandName;
    }
    
    public function setCommandName($commandName)
    {
        if (!strpos($commandName, ':')) {
            throw new RuntimeException("Invalid command-name: " . $commandName);
        }
        
        $this->commandName = $commandName;
        return $this;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    
    public function setItems($items)
    {
        $this->items = $items;
    }
    
    public function getItems()
    {
        if (count($this->items)==0) {
            return [
                'default'
            ];
        }
        return $this->items;
    }
}
