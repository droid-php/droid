<?php

namespace Droid\Model;

use InvalidArgumentException;
use RuntimeException;

class Task
{
    private $name;
    private $type = 'task';
    private $commandName;
    private $arguments = [];
    private $items = [];
    private $triggers = [];
    
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
    
    public function getType()
    {
        return $this->type;
    }
    
    public function setType($type)
    {
        $this->type = $type;
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
    
    private $hosts;
    
    public function getHosts()
    {
        return $this->hosts;
    }
    
    public function setHosts($hosts)
    {
        $this->hosts = $hosts;
        return $this;
    }
    
    public function addTrigger($name)
    {
        $this->triggers[$name] = $name;
    }
    
    public function getTriggers()
    {
        return $this->triggers;
    }
    
    protected $changed = false;
    
    public function getChanged()
    {
        return $this->changed;
    }
    
    public function setChanged($changed)
    {
        $this->changed = $changed;
        return $this;
    }
}
