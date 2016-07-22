<?php

namespace Droid;

use RuntimeException;

use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Droid\Model\Inventory\Inventory;
use Droid\Model\Project\Project;

use Droid\Command\TargetRunCommand;
use Droid\Loader\YamlLoader;

class Application extends ConsoleApplication
{
    const DROID_BIN_NAME = 'droid.phar';

    protected $project;
    protected $inventory;
    protected $autoLoader;
    protected $droidConfig;
    protected $basePath;
    protected $loaderErrors;

    public function __construct($autoLoader, $basePath = '')
    {
        $this->autoLoader = $autoLoader;
        $this->basePath = $basePath;
        parent::__construct();

        $this->setName('Droid');
        $this->setVersion('1.0.0');
        $loader = new YamlLoader();

        // extract --droid-config argument, before interpreting other arguments
        foreach ($_SERVER['argv'] as $i => $argument) {
            if (substr($argument, 0, 15)=='--droid-config=') {
                $this->droidConfig = substr($argument, 15);
                unset($_SERVER['argv'][$i]);
            }
            if (substr($argument, 0, 14)=='module:install') {
                $loader->setIgnoreModules(true);
            }
        }

        $filename = $this->getDroidFilename();

        if (! file_exists($filename)) {
            $this->registerCustomCommands();
            return;
        }

        $this->project = new Project($filename);
        $this->inventory = new Inventory();

        // Load droid.yml
        $loader->load($this->project, $this->inventory, $this->basePath);

        $this->loaderErrors = $loader->errors;
        if ($this->loaderErrors) {
            return;
        }

        $this->registerCustomCommands();
    }

    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        if ($input === null) {
            $input = new ArgvInput;
        }

        if ($output === null) {
            $output = new ConsoleOutput;
        }

        if ($this->loaderErrors) {
            $messages = $this->formatErrorMessages($this->loaderErrors);
            array_push($messages, '<comment>Stop.</comment>');
            $output->write($messages, true);
            exit(1);
        }

        return parent::run($input, $output);
    }

    protected function formatErrorMessages($messages)
    {
        return array_map(
            function ($x) {
                return sprintf('<error>Error</error> %s', $x);
            },
            $messages
        );
    }

    public function getProject()
    {
        if (!$this->hasProject()) {
            throw new RuntimeException("No project configured");
        }
        return $this->project;
    }

    public function hasProject()
    {
        return isset($this->project);
    }

    public function getInventory()
    {
        if (!$this->hasInventory()) {
            throw new RuntimeException("No inventory configured");
        }
        return $this->inventory;
    }

    public function hasInventory()
    {
        return isset($this->inventory);
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

        if ($this->hasProject()) {
            // Register commands defined in project's droid.yml
            foreach ($this->getProject()->getRegisteredCommands() as $registeredCommand) {
                $className = $registeredCommand->getClassName();
                $command = new $className();
                if ($registeredCommand->hasProperty('name')) {
                    $command->setName($registeredCommand->getProperty('name'));
                }
                $this->add($command);
            }
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

        foreach ($this->all() as $command) {
            if (method_exists($command, 'setInventory')) {
                $command->setInventory($this->inventory);
            }
        }

        if ($this->hasProject()) {
            foreach ($this->getProject()->getTargets() as $target) {
                $command = new TargetRunCommand;
                $command->setName($target->getName());
                $command->setDescription("Run target: " . $target->getName());
                $command->setTarget($target->getName());
                $this->add($command);

                //print_r($target);
            }
        }
        //exit();
    }

    private function getDroidFilename()
    {
        if ($this->droidConfig) {
            $filename = Utils::absoluteFilename($this->droidConfig);
        } else {
            // no parameters, assume 'droid.yml' in current working directory
            $filename = getcwd() . '/droid.yml';
        }
        return $filename;
    }

    public function getDroidBinaryFilename()
    {
        return self::DROID_BIN_NAME;
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

    public function getBasePath()
    {
        return $this->basePath;
    }
}
