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
