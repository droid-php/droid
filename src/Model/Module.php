<?php

namespace Droid\Model;

use InvalidArgumentException;
use RuntimeException;

class Module
{
    private $name;
    private $source;
    
    use VariableTrait;
    use TaskTrait;
    
    public function __construct($name, $source)
    {
        $this->name = $name;
        $this->source = $source;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getSource()
    {
        return $this->source;
    }
    
    protected $description;
    
    public function getDescription()
    {
        return $this->description;
    }
    
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }
    
    
}
