<?php

namespace Droid\Model;

use Droid\Model\Inventory;
use RuntimeException;

class Firewall
{
    protected $inventory;
    public function __construct(Inventory $inventory)
    {
        $this->inventory = $inventory;
    }
    
    public function getRulesByHostname($name)
    {
        $host = $this->inventory->getHost($name);
        $groups = $this->inventory->getHostGroups();
        $rules = [];
        foreach ($groups as $group) {
            if (in_array($host, $group->getHosts())) {
                foreach ($group->getRules() as $rule) {
                    $rules[] = $rule;
                }
            }
        }
    
        foreach ($host->getRules() as $rule) {
            $rules[] = $rule;
        }
        return $rules;
    }
    
    public function constructAddresses($address)
    {
        $address = str_replace(' ', '', $address);
        $addresses = explode(',', $address);
        $res = [];
        foreach ($addresses as $address) {
            $add = $this->constructAddress($address);
            if (!$add || (count($add)==0)) {
                throw new RuntimeException("Can't parse: " . $address);
            }
            $res = array_merge($res, $add);
        }
        return $res;
    }
    
    public function constructAddress($address)
    {
        switch ($address) {
            case 'all':
                return ['0.0.0.0/32'];
        }
        $part = explode('.', $address);
        if (count($part)==4) {
            // simple ip address or subnet
            return [$address];
        }
        
        $part = explode(':', $address);
        if ($this->inventory->hasHost($part[0])) {
            $host = $this->inventory->getHost($part[0]);
            if (isset($part[1])) {
                switch ($part[1]) {
                    case 'public':
                        return [$host->getPublicIp()];
                    case 'private':
                        return [$host->getPrivateIp()];
                    default:
                        throw new RuntimeException("Expected public or private: " . $part[1]);
                }
            } else {
                return [$host->getPublicIp()];
            }
        }
        
        if ($this->inventory->hasHostGroup($part[0])) {
            $group = $this->inventory->getHostGroup($part[0]);
            $res = [];
            foreach ($group->getHosts() as $host) {
                if (isset($part[1])) {
                    switch ($part[1]) {
                        case 'public':
                            $res[] = $host->getPublicIp();
                            break;
                        case 'private':
                            $res[] = $host->getPrivateIp();
                            break;
                        default:
                            throw new RuntimeException("Expected public or private: " . $part[1]);
                    }
                } else {
                    $res[] = $host->getPublicIp();
                }
            }
            return $res;
        }

    }
}
