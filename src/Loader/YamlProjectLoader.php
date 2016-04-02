<?php

namespace Droid\Loader;

use Symfony\Component\Yaml\Parser as YamlParser;
use Droid\Model\Project;
use Droid\Model\Target;
use Droid\Model\RegisteredCommand;
use Droid\Model\Step;
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
                $command = new RegisteredCommand($className);
                $project->addRegisteredCommand($command);
            }
        }
        
        foreach ($data['targets'] as $targetName => $targetNode) {
            $target = new Target($targetName);
            $project->addTarget($target);
            if (isset($targetNode['steps'])) {
                foreach ($targetNode['steps'] as $stepNodes) {
                    foreach ($stepNodes as $commandName => $parameters) {
                        $step = new Step($commandName, $parameters);
                        $target->addStep($step);
                    }
                }
            }
        }
    }
}
