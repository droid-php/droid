<?php

namespace Droid\Loader;

use Symfony\Component\Yaml\Parser as YamlParser;
use Droid\Model\Inventory;
use Droid\Model\Host;
use Droid\Model\HostGroup;
use RuntimeException;

class YamlInventoryLoader
{
    public function load(Inventory $inventory, $filename)
    {
        if (!file_exists($filename)) {
            throw new RuntimeException("File not found: $filename");
        }
        
        $parser = new YamlParser();
        $data = $parser->parse(file_get_contents($filename));
        
        if (isset($data['hosts'])) {
            foreach ($data['hosts'] as $hostName => $hostNode) {
                $host = new Host($hostName);
                if (isset($hostNode['hostname'])) {
                    $host->setHostName($hostNode['hostname']);
                }
                $inventory->addHost($host);
            }
        }
        
        if (isset($data['groups'])) {
            foreach ($data['groups'] as $groupName => $groupNode) {
                $group = new HostGroup($groupName);
                foreach ($groupNode as $hostName) {
                    if (!$inventory->hasHost($hostName)) {
                        throw new RuntimeException("Group $groupName refers to undefined host: $hostName");
                    }
                    $host = $inventory->getHost($hostName);
                    $group->addHost($host);
                }
                $inventory->addHostGroup($group);
            }
        }
        //print_r($inventory);
    }
}
