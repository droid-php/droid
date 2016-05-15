<?php

namespace Droid\Model;

trait TaskTrait
{
    private $tasks = [];
    
    public function addTask(Task $task)
    {
        $this->tasks[] = $task;
    }
    
    public function getAllTasks()
    {
        return $this->tasks;
    }
    
    public function getTasksByType($type)
    {
        $res = [];
        foreach ($this->tasks as $task) {
            if ($task->getType() == $type) {
                $res[] = $task;
            }
        }
        return $res;
    }
    
    public function getTaskByName($name)
    {
        foreach ($this->tasks as $task) {
            if ($task->getName() == $name) {
                return $task;
            }
        }
        return null;
    }
}
