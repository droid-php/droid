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
        foreach ($data['register'] as $registerNode) {
            foreach ($registerNode as $className => $parameters) {
                $command = new RegisteredCommand($className, $parameters);
                $project->addRegisteredCommand($command);
            }
        }

        foreach ($data['parameters'] as $key => $value) {
            $project->setParameter($key, $value);
        }
        
        foreach ($data['targets'] as $targetName => $targetNode) {
            $target = new Target($targetName);
            $project->addTarget($target);
            if (isset($targetNode['tasks'])) {
                foreach ($targetNode['tasks'] as $taskNodes) {
                    foreach ($taskNodes as $commandName => $parameters) {
                        $taskParameters = [];
                        $loopParameters = null;
                        if ($parameters) {
                            foreach ($parameters as $key => $value) {
                                switch ($key) {
                                    case '$loop':
                                        $loopParameters = $value;
                                        break;
                                    default:
                                        $taskParameters[$key] = $value;
                                        break;
                                }
                            }
                        }
                        $task = new Task($commandName, $taskParameters);
                        if ($loopParameters) {
                            $task->setLoopParameters($loopParameters);
                        }
                        $target->addTask($task);
                    }
                }
            }
        }
    }
}
