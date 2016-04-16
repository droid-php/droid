<?php

namespace Droid\Model;

use RuntimeException;

class Host
{
    private $name;
    private $hostname;
    private $port;
    
    public function __construct($name)
    {
        $this->name = $name;
        $this->hostname = $name;
        $this->port = 22;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getHostname()
    {
        return $this->hostname;
    }
    
    public function setHostname($hostname)
    {
        $this->hostname = $hostname;
        return $this;
    }
    
    public function getPort()
    {
        return $this->port;
    }
    
    public function setPort($port)
    {
        $this->port = $port;
        return $this;
    }
}
