<?php

namespace Droid\Model;

trait TaskTrait
{
    private $tasks = [];
    
    public function addTask(Task $task)
    {
        $this->tasks[] = $task;
    }
    
    public function getTasks()
    {
        return $this->tasks;
    }
}
