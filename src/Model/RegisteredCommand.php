<?php

namespace Droid\Model;

use InvalidArgumentException;

class RegisteredCommand
{
    private $className;
    private $properties;
    
    public function __construct($className, $properties = [])
    {
        $this->className = $className;
        $this->properties = $properties;
    }

    public function getClassName()
    {
        return $this->className;
    }
    
    public function getProperties()
    {
        return $this->properties;
    }
    
    public function hasProperty($name)
    {
        return isset($this->properties[$name]);
    }
    public function getProperty($name)
    {
        if (!$this->hasProperty($name)) {
            throw new InvalidArgumentException("No such command property: " . $name);
        }
        return $this->properties[$name];
    }
}
