<?php

namespace Droid\Model;

use RuntimeException;

class Inventory
{
    private $hosts = [];
    private $hostGroups = [];
    
    public function addHost(Host $host)
    {
        $this->hosts[$host->getName()] = $host;
    }
    
    public function getHost($name)
    {
        if (!$this->hasHost($name)) {
            throw new RuntimeException("No such hostname: " . $name);
        }
        return $this->hosts[$name];
    }
    
    public function hasHost($name)
    {
        return isset($this->hosts[$name]);
    }
    
    public function getHosts()
    {
        return $this->hosts;
    }

    public function addHostGroup(HostGroup $hostGroup)
    {
        $this->hostGroups[$hostGroup->getName()] = $hostGroup;
    }
    
    public function hasHostGroup($name)
    {
        return isset($this->hostGroups[$name]);
    }
    
    public function getHostGroups()
    {
        return $this->hostGroups;
    }
    
    public function getHostsByName($name)
    {
        if ($this->hasHostGroup($name)) {
            $hosts = $this->getHostGroup($name);
            return $hosts;
        }
        $hosts = [];
        if ($this->hasHost($name)) {
            $hosts[] = $this->getHost($name);
        }
        return $hosts;
    }
}
