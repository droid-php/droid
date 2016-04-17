<?php

namespace Droid\Loader;

use Symfony\Component\Yaml\Parser as YamlParser;
use Droid\Model\Inventory;
use Droid\Model\Host;
use Droid\Model\HostGroup;
use RuntimeException;
use Droid\Utils;

class YamlInventoryLoader
{
    public function load(Inventory $inventory, $filename)
    {
        if (!file_exists($filename)) {
            throw new RuntimeException("File not found: $filename");
        }
        
        $parser = new YamlParser();
        $data = $parser->parse(file_get_contents($filename));
        $this->loadVariables($data, $inventory);
        
        if (isset($data['hosts'])) {
            foreach ($data['hosts'] as $hostName => $data) {
                $host = new Host($hostName);
                if ($data) {
                    foreach ($data as $key => $value) {
                        switch ($key) {
                            case 'variables':
                                $this->loadVariables($data, $host);
                                break;
                            case 'address':
                                $host->setAddress($data[$key]);
                                break;
                            case 'username':
                                $host->setUsername($data[$key]);
                                break;
                            case 'password':
                                $host->setPassword($data[$key]);
                                break;
                            case 'auth':
                                $host->setAuth($data[$key]);
                                break;
                            case 'keyfile':
                                $filename = Utils::absoluteFilename($data[$key]);
                                $host->setKeyFile($filename);
                                break;
                            case 'keypass':
                                $host->setKeyPass($data[$key]);
                                break;
                            default:
                                throw new RuntimeException("Unknown host property: " . $key);
                        }
                    }
                }
                $inventory->addHost($host);
            }
        }
        
        if (isset($data['groups'])) {
            foreach ($data['groups'] as $groupName => $groupNode) {
                $group = new HostGroup($groupName);
                foreach ($groupNode['hosts'] as $hostName) {
                    if (!$inventory->hasHost($hostName)) {
                        throw new RuntimeException("Group $groupName refers to undefined host: $hostName");
                    }
                    $host = $inventory->getHost($hostName);
                    $group->addHost($host);
                }
                $this->loadVariables($groupNode, $group);
                $inventory->addHostGroup($group);
            }
        }
        //print_r($inventory);
    }
    
    public function loadVariables($data, $obj)
    {
        if (isset($data['variables'])) {
            foreach ($data['variables'] as $name => $value) {
                $obj->setVariable($name, $value);
            }
        }
    }
}
