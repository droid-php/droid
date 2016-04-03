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
        
        foreach ($data['targets'] as $targetName => $targetNode) {
            $target = new Target($targetName);
            $project->addTarget($target);
            if (isset($targetNode['tasks'])) {
                foreach ($targetNode['tasks'] as $taskNodes) {
                    foreach ($taskNodes as $commandName => $parameters) {
                        $task = new Task($commandName, $parameters);
                        $target->addTask($task);
                    }
                }
            }
        }
    }
}
