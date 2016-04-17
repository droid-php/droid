<?php

namespace Droid\Loader;

use Symfony\Component\Yaml\Parser as YamlParser;
use Droid\Model\Project;
use Droid\Model\Target;
use Droid\Model\RegisteredCommand;
use Droid\Model\Task;
use RuntimeException;

class YamlProjectLoader
{
    public function load(Project $project, $filename)
    {
        if (!file_exists($filename)) {
            throw new RuntimeException("File not found: $filename");
        }
        
        $parser = new YamlParser();
        $data = $parser->parse(file_get_contents($filename));
        
        if (isset($data['register'])) {
            foreach ($data['register'] as $registerNode) {
                foreach ($registerNode as $className => $parameters) {
                    $command = new RegisteredCommand($className, $parameters);
                    $project->addRegisteredCommand($command);
                }
            }
        }

        $this->loadVariables($data, $project);
        
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
    
    public function loadVariables($data, $obj)
    {
        if (isset($data['variables'])) {
            foreach ($data['variables'] as $name => $value) {
                $obj->setVariable($name, $value);
            }
        }
    }
}
