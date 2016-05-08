<?php

namespace Droid\Model;

use RuntimeException;

class Inventory
{
    private $hosts = [];
    private $hostGroups = [];
    
    use VariableTrait;
    
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

    public function getHostGroup($name)
    {
        if (!$this->hasHostGroup($name)) {
            throw new RuntimeException("No such host group: " . $name);
        }
        return $this->hostGroups[$name];
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
        $name = str_replace(' ', ',', $name);
        $names = explode(",", $name);
        $hosts = [];
        foreach ($names as $name) {
            if ($name) {
                $found = false;
                if ($this->hasHostGroup($name)) {
                    foreach ($this->getHostGroup($name)->getHosts() as $host) {
                        $hosts[$host->getName()] = $host;
                    }
                } elseif ($this->hasHost($name)) {
                    $host = $this->getHost($name);
                    $hosts[$host->getName()] = $host;
                } else {
                    throw new RuntimeException("Unknown host (group): " . $name);
                }
            }
        }
        return $hosts;
    }
}
