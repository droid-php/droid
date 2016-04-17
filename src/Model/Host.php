<?php

namespace Droid\Model;

use RuntimeException;

class Host
{
    private $name;
    private $hostname;
    private $port;
    private $username;
    
    use VariableTrait;
    
    public function __construct($name)
    {
        $this->name = $name;
        $this->hostname = $name;
        $this->port = 22;
        $this->username = 'root';
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
    
    public function getUsername()
    {
        return $this->username;
    }
    
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }
    
}
