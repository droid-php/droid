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
                    foreach ($targetNode['tasks'] as $taskNodes) {
                        foreach ($taskNodes as $commandName => $variables) {
                            $taskVariables = [];
                            $loopVariables = null;
                            if ($variables) {
                                foreach ($variables as $name => $value) {
                                    switch ($name) {
                                        case '$loop':
                                            $loopVariables = $value;
                                            break;
                                        default:
                                            $taskVariables[$name] = $value;
                                            break;
                                    }
                                }
                            }
                            $task = new Task($commandName, $taskVariables);
                            if ($loopVariables) {
                                $task->setLoopVariables($loopVariables);
                            }
                            $target->addTask($task);
                        }
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
