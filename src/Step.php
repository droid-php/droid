<?php

namespace Droid;

class Step
{
    private $taskName;
    private $parameters;
    
    public function __construct($taskName, $parameters = [])
    {
        $this->taskName = $taskName;
        $this->parameters = $parameters;
    }
    
    public function getTaskName()
    {
        return $this->taskName;
    }
    
    public function getParameters()
    {
        return $this->parameters;
    }
}
