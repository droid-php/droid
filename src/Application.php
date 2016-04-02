<?php

namespace Droid;

use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\InputInterface;
use Droid\Model\Project;
use Droid\Loader\YamlProjectLoader;
use RuntimeException;

class Application extends ConsoleApplication
{
    protected $project;
    
    public function __construct()
    {
        $filename = $this->getDroidFilename();
        $this->project = new Project($filename);
        $loader = new YamlProjectLoader();
        $loader->load($this->project, $filename);

        parent::__construct();

        $this->setName('Droid');
        $this->setVersion('1.0.0');

    }
    
    public function getProject()
    {
        if (!$this->project) {
            throw new RuntimeException("No project configured");
        }
        return $this->project;
    }
    

    /**
     * Gets the default commands that should always be available.
     *
     * @return array An array of default Command instances
     */
    protected function getDefaultCommands()
    {
        // Keep the core default commands to have the HelpCommand
        // which is used when using the --help option
        $defaultCommands = parent::getDefaultCommands();

        foreach ($this->getProject()->getRegisteredCommands() as $registeredCommand) {
            $className = $registeredCommand->getClassName();
            $defaultCommands[] = new $className();
        }
        $defaultCommands[] = new \Droid\Command\RunCommand();
        $defaultCommands[] = new \Droid\Command\ConfigCommand();
        $defaultCommands[] = new \Droid\Core\BowerInstallCommand();
        $defaultCommands[] = new \Droid\Core\ComposerInstallCommand();
        $defaultCommands[] = new \Droid\Core\EchoCommand();
        
        return $defaultCommands;
    }
    
    public function getDroidFilename()
    {
        $filename = __DIR__ . '/../example/droid.yml';
        return $filename;
    }
}
