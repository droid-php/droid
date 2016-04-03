<?php

namespace Droid\Model;

class Target
{
    private $name;
    private $tasks = [];
    
    public function __construct($name)
    {
        $this->name = $name;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function addTask(Task $task)
    {
        $this->tasks[] = $task;
    }
    
    public function getTasks()
    {
        return $this->tasks;
    }
}
