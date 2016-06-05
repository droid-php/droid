<?php

namespace Droid\Model;

use Droid\Model\VariableTrait;
use RuntimeException;

class Project
{
    //private $tasks = [];
    private $targets = [];
    private $registeredCommands = [];
    private $configFilePath;

    use VariableTrait;
    use ModuleTrait;

    public function __construct($filename)
    {
        if (!file_exists($filename)) {
            throw new RuntimeException("Droid project file not found: " . $filename);
        }
        $this->configFilePath = $filename;
    }

    public function getConfigFilePath()
    {
        return $this->configFilePath;
    }

    public function getBasePath()
    {
        return dirname($this->configFilePath);
    }

    public function addRegisteredCommand(RegisteredCommand $registeredCommand)
    {
        $this->registeredCommands[] = $registeredCommand;
    }

    public function getRegisteredCommands()
    {
        return $this->registeredCommands;
    }

    public function addTarget(Target $target)
    {
        $this->targets[] = $target;
    }

    public function getTargets()
    {
        return $this->targets;
    }

    public function getTargetByName($targetName)
    {
        foreach ($this->targets as $target) {
            if ($target->getName() == $targetName) {
                return $target;
            }
        }
        return null;
    }
}
