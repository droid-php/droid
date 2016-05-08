<?php

namespace Droid\Model;

class Rule
{
    
    protected $address;
    protected $port;
    protected $protocol = 'tcp';
    protected $direction = 'inbound';
    protected $action = 'allow';
    
    public function getAddress()
    {
        return $this->address;
    }
    
    public function setAddress($address)
    {
        $this->address = $address;
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

    public function getDirection()
    {
        return $this->direction;
    }
    
    public function setDirection($direction)
    {
        $this->direction = $direction;
        return $this;
    }

    public function getProtocol()
    {
        return $this->protocol;
    }
    
    public function setProtocol($protocol)
    {
        $this->protocol = $protocol;
        return $this;
    }
    

    
    public function getAction()
    {
        return $this->action;
    }
    
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }
}
