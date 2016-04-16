<?php

namespace Droid\Model;

use RuntimeException;

class HostGroup
{
    private $name;
    private $hosts = [];
    
    public function __construct($name)
    {
        $this->name = $name;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function addHost(Host $host)
    {
        $this->hosts[$host->getName()] = $host;
        return $this;
    }
    
    public function getHosts()
    {
        return $this->hosts;
    }
}
