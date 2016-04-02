<?php

namespace Droid\Model;

//use Droid\Task\TaskInterface;
use RuntimeException;

class Project
{
    //private $tasks = [];
    private $targets = [];
    private $registeredCommands = [];
    
    public function __construct($filename)
    {
        if (!file_exists($filename)) {
            throw new RuntimeException("Droid project file not found: " . $filename);
        }
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
}
