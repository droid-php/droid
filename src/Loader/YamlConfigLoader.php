<?php

namespace Droid\Loader;

use Symfony\Component\Yaml\Parser as YamlParser;
use Droid\Droid;
use Droid\Target;
use Droid\Step;
use RuntimeException;

class YamlConfigLoader
{
    public function load(Droid $droid, $filename)
    {
        if (!file_exists($filename)) {
            throw new RuntimeException("File not found: $filename");
        }
        $parser = new YamlParser();
        $data = $parser->parse(file_get_contents($filename));
        foreach ($data as $targetName => $targetNode) {
            //echo "TARGET: $targetName\n";
            //print_r($targetNode);
            $target = new Target($targetName);
            $droid->addTarget($target);
            if (isset($targetNode['steps'])) {
                foreach ($targetNode['steps'] as $taskName => $stepNode) {
                    //echo "STEP: $taskName\n";
                    $step = new Step($taskName, $stepNode);
                    $target->addStep($step);
                }
            }
        }
        //print_r($data);
    }
}
