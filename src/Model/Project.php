<?php

namespace Droid\Model;

//use Droid\Task\TaskInterface;
use RuntimeException;

class Project
{
    //private $tasks = [];
    private $targets = [];
    private $registeredCommands = [];
    private $parameters = [];
    private $basePath;
    
    public function __construct($filename)
    {
        if (!file_exists($filename)) {
            throw new RuntimeException("Droid project file not found: " . $filename);
        }
        $this->basePath = dirname($filename);
    }
    
    public function getBasePath()
    {
        return $this->basePath;
    }
    
    public function addRegisteredCommand(RegisteredCommand $registeredCommand)
    {
        $this->registeredCommands[] = $registeredCommand;
    }
    
    public function getRegisteredCommands()
    {
        return $this->registeredCommands;
    }
    
    public function addTarget(Target $target)
    {
        $this->targets[] = $target;
    }
    
    public function getTargets()
    {
        return $this->targets;
    }
    
    public function getTargetByName($targetName)
    {
        foreach ($this->targets as $target) {
            if ($target->getName() == $targetName) {
                return $target;
            }
        }
        return null;
    }
    
    public function setParameter($name, $value)
    {
        $this->parameters[$name] = $value;
        return $this;
    }
    
    public function hasParameter($name)
    {
        return isset($this->parameters[$name]);
    }
    
    public function getParameter($name)
    {
        if (!$this->hasParameter($name)) {
            throw new RuntimeException("No such project parameter: " . $name);
        }
        return $this->parameters[$name];
    }
    
    public function getParameters()
    {
        return $this->parameters;
    }
}
