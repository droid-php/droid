<?php

namespace Droid\Model;

class Target
{
    private $name;
    private $steps = [];
    
    public function __construct($name)
    {
        $this->name = $name;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function addStep(Step $step)
    {
        $this->steps[] = $step;
    }
    
    public function getSteps()
    {
        return $this->steps;
    }
}
