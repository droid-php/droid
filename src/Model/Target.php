<?php

namespace Droid\Model;

class Target
{
    private $name;
    private $hosts;
    private $tasks = [];
    
    use VariableTrait;
    
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
    
    public function getHosts()
    {
        return $this->hosts;
    }
    
    public function setHosts($hosts)
    {
        $this->hosts = $hosts;
        return $this;
    }
    
}
