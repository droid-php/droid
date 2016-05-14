<?php

namespace Droid\Model;

class Target
{
    private $name;
    private $hosts;
    
    use VariableTrait;
    use TaskTrait;
    use ModuleTrait;
    
    public function __construct($name)
    {
        $this->name = $name;
    }
    
    public function getName()
    {
        return $this->name;
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
