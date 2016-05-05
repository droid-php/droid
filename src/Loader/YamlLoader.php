<?php

namespace Droid\Loader;

use Symfony\Component\Yaml\Parser as YamlParser;
use Droid\Model\Project;
use Droid\Model\Target;
use Droid\Model\Inventory;
use Droid\Model\Host;
use Droid\Model\HostGroup;
use Droid\Model\RegisteredCommand;
use Droid\Model\Task;
use Droid\Utils;
use RuntimeException;

class YamlLoader
{
    public function load(Project $project, Inventory $inventory, $filename)
    {
        if (!file_exists($filename)) {
            throw new RuntimeException("File not found: $filename");
        }
        
        $parser = new YamlParser();
        $data = $parser->parse(file_get_contents($filename));
        $this->loadProject($project, $data);
        $this->loadInventory($inventory, $data);
    }
    
    private function loadProject(Project $project, $data)
    {
        $this->loadVariables($data, $project);

        if (isset($data['register'])) {
            foreach ($data['register'] as $registerNode) {
                foreach ($registerNode as $className => $parameters) {
                    $command = new RegisteredCommand($className, $parameters);
                    $project->addRegisteredCommand($command);
                }
            }
        }

        
        if (isset($data['targets'])) {
            foreach ($data['targets'] as $targetName => $targetNode) {
                $target = new Target($targetName);
                $this->loadVariables($targetNode, $target);
                
                $project->addTarget($target);
                if (isset($targetNode['hosts'])) {
                    $target->setHosts($targetNode['hosts']);
                }
                
                if (isset($targetNode['tasks'])) {
                    foreach ($targetNode['tasks'] as $taskNode) {
                        $task = new Task();
                        foreach ($taskNode as $key => $value) {
                            switch ($key) {
                                case 'name':
                                    $task->setName($taskNode[$key]);
                                    break;
                                case 'command':
                                    $task->setCommandName($taskNode[$key]);
                                    break;
                                case 'with_items':
                                    $task->setItems($taskNode[$key]);
                                    break;
                                case 'arguments':
                                    foreach ($taskNode['arguments'] as $var => $val) {
                                        $task->setArgument($var, $val);
                                    }
                                    break;
                                default:
                                    // Assume commandname
                                    $task->setCommandName($key);
                                    if (is_array($value)) {
                                        foreach ($value as $var => $val) {
                                            $task->setArgument($var, $val);
                                        }
                                    }
                                    if (is_string($value)) {
                                        preg_match_all(
                                            "/(\w+)[\s]*=[\s]*((?:[^\"'\s]+)|'(?:[^']*)'|\"(?:[^\"]*)\")/",
                                            $value,
                                            $matches
                                        );
                                        for ($i=0; $i<count($matches[1]); $i++) {
                                            $val = trim($matches[2][$i], " \"");
                                            $task->setArgument($matches[1][$i], $val);
                                        }
                                    }
                            }
                        }
                        $target->addTask($task);
                    }
                }
            }
        }
    }
    
    private function loadInventory(Inventory $inventory, $data)
    {
        if (isset($data['hosts'])) {
            $this->loadHosts($inventory, $data['hosts']);
        }
        if (isset($data['groups'])) {
            $this->loadHostGroups($inventory, $data['groups']);
        }
    }

    private function loadHosts(Inventory $inventory, $hosts)
    {
        $want_gateway = array();
        foreach ($hosts as $hostName => $hostData) {
            $host = new Host($hostName);
            $inventory->addHost($host);
            if (!$hostData) {
                continue;
            }
            foreach ($hostData as $key => $value) {
                switch ($key) {
                    case 'variables':
                        $this->loadVariables($hostData, $host);
                        break;
                    case 'address':
                        $host->setAddress($value);
                        break;
                    case 'username':
                        $host->setUsername($value);
                        break;
                    case 'password':
                        $host->setPassword($value);
                        break;
                    case 'auth':
                        $host->setAuth($value);
                        break;
                    case 'keyfile':
                        $host->setKeyFile(Utils::absoluteFilename($value));
                        break;
                    case 'keypass':
                        $host->setKeyPass($value);
                        break;
                    case 'ssh_options':
                        $host->setSshOptions($value);
                        break;
                    case 'ssh_gateway':
                        if (! $inventory->hasHost($value)) {
                            $want_gateway[$hostName] = $value;
                            break;
                        }
                        $host->setSshGateway($inventory->getHost($value));
                        break;
                    default:
                        throw new RuntimeException("Unknown host property: " . $key);
                }
            }
        }
        foreach ($want_gateway as $want => $gateway) {
            if (! $inventory->hasHost($gateway)) {
                throw new RuntimeException(sprintf(
                    'Host "%s" requires an unknown host "%s" as its ssh gateway.',
                    $want,
                    $gateway
                ));
            }
            $inventory
                ->getHost($want)
                ->setSshGateway($inventory->getHost($gateway))
            ;
        }
    }

    private function loadHostGroups(Inventory $inventory, $groups)
    {
        foreach ($groups as $groupName => $groupNode) {
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
    
    public function loadVariables($data, $obj)
    {
        if (isset($data['variables'])) {
            foreach ($data['variables'] as $name => $value) {
                $obj->setVariable($name, $value);
            }
        }
    }
}
