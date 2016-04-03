<?php

namespace Droid;

use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArgvInput;
use Droid\Model\Project;
use Droid\Loader\YamlProjectLoader;
use RuntimeException;

class Application extends ConsoleApplication
{
    protected $project;
    protected $autoLoader;
    protected $droidConfig;
    
    public function __construct($autoLoader)
    {
        $this->autoLoader = $autoLoader;
        parent::__construct();
        
        $this->getDefinition()->addOptions(
            [
                new InputOption(
                    '--droid-config',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'The droid config file to use',
                    null
                ),
            ]
        );

        $this->setName('Droid');
        $this->setVersion('1.0.0');
        
        $input = new ArgvInput();

        // bind the application's input definition to it
        $input->bind($this->getDefinition());

        $this->droidConfig = $input->getOption('droid-config');

        $filename = $this->getDroidFilename();
        $this->project = new Project($filename);
        $loader = new YamlProjectLoader();
        $loader->load($this->project, $filename);

        $this->registerCustomCommands();

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
    protected function registerCustomCommands()
    {
        // Keep the core default commands to have the HelpCommand
        // which is used when using the --help option
        $defaultCommands = parent::getDefaultCommands();

        // Register commands defined in project's droid.yml
        foreach ($this->getProject()->getRegisteredCommands() as $registeredCommand) {
            $className = $registeredCommand->getClassName();
            $command = new $className();
            if ($registeredCommand->hasProperty('name')) {
                $command->setName($registeredCommand->getProperty('name'));
            }
            $this->add($command);
        }
        
        // Automatically register commands by scanning namespaces for a 'DroidPlugin' class.
        //print_r($this->autoLoader);
        $prefixes = $this->autoLoader->getPrefixesPsr4();
        
        foreach ($prefixes as $namespace => $paths) {
            $className = $namespace . 'DroidPlugin';
            if (class_exists($className)) {
                $plugin = new $className($this);
                $commands = $plugin->getCommands();
                foreach ($commands as $command) {
                    $this->add($command);
                }
            }
        }
    }
    
    public function getDroidFilename()
    {
        if ($this->droidConfig) {
            $filename = $this->droidConfig;
            switch ($filename[0]) {
                case '/':
                    // absolute filename
                    break;
                case '~':
                    // relative to home
                    $home = getenv("HOME");
                    $filename = $home . '/' . $filename;
                    break;
                default:
                    // relative from pwd/cwd
                    $filename = getcwd() . '/' . $filename;
                    break;
            }
        } else {
            // no parameters, assume 'droid.yml' in current working directory
            $filename = getcwd() . '/droid.yml';
        }
        if (!file_exists($filename)) {
            throw new RuntimeException("Droid configuration not found in " . $filename);
        }
        return $filename;
    }
    
    public function setAutoLoader($autoLoader)
    {
        $this->autoLoader = $autoLoader;
        return $this;
    }
    
    public function getAutoLoader()
    {
        return $this->autoLoader;
    }
}
