<?php

namespace Droid;

use Droid\Task\TaskInterface;
use RuntimeException;

class Droid
{
    private $tasks = [];
    private $targets = [];
    
    public function __construct($filename)
    {
        if (!file_exists($filename)) {
            throw new RuntimeException("Droid file not found: " . $filename);
        }
    }
    
    public function addTask(TaskInterface $task)
    {
        $this->tasks[] = $task;
    }
    
    public function getTaskByName($taskName)
    {
        foreach ($this->tasks as $task) {
            if ($task->getName() == $taskName) {
                return $task;
            }
        }
        return null;
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
    
    public function runTarget($targetName)
    {
        $target = $this->getTargetByName($targetName);
        if (!$target) {
            throw new RuntimeException("Target not found: " . $targetName);
        }
        foreach ($target->getSteps() as $step) {
            echo " * " . $step->getTaskName() ."\n";
        }
        //print_r($target);
    }
}
