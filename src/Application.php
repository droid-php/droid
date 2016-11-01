<?php

namespace Droid;

use RuntimeException;

use Droid\Model\Inventory\Inventory;
use Droid\Model\Inventory\Remote\Check\PhpVersionCheck;
use Droid\Model\Inventory\Remote\Check\WorkingDirectoryCheck;
use Droid\Model\Inventory\Remote\Enabler;
use Droid\Model\Inventory\Remote\SynchroniserComposer;
use Droid\Model\Inventory\Remote\SynchroniserPhar;
use Droid\Model\Project\Environment;
use Droid\Model\Project\Project;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

use Droid\Command\TargetRunCommand;
use Droid\Loader\YamlLoader;
use Droid\Logger\LoggerFactory;
use Droid\Transform\DataStreamTransformer;
use Droid\Transform\FileTransformer;
use Droid\Transform\InventoryTransformer;
use Droid\Transform\Render\LightnCandyRenderer;
use Droid\Transform\SubstitutionTransformer;
use Droid\Transform\Transformer;

class Application extends ConsoleApplication
{
    const DROID_BIN_NAME = 'droid.phar';

    protected $project;
    protected $inventory;
    protected $autoLoader;
    protected $droidConfig;
    protected $basePath;
    protected $loaderErrors;
    protected $transformer;

    public function __construct($autoLoader, $basePath = '')
    {
        $this->autoLoader = $autoLoader;
        $this->basePath = $basePath;
        parent::__construct();

        $this->setName('Droid');
        $this->setVersion('1.0.0');

        $this->inventory = new Inventory();
        $environment = new Environment;
        $this->inventory->setEnvironment($environment);
        $this->transformer = $this->buildTransformer();

        $loader = new YamlLoader($this->basePath, $this->transformer);

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

        // Load droid.yml
        $loader->load($this->project, $this->inventory, $environment);

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

        $this->configureOutput($output);

        return parent::run($input, $output);
    }

    private function configureOutput(OutputInterface $output)
    {
        $formatter = $output->getFormatter();
        if (!$formatter) {
            return;
        }
        $formatter->setStyle(
            'host',
            new OutputFormatterStyle('black', 'blue', array('reverse'))
        );
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
            $runner = new TaskRunner(
                $this,
                $this->transformer,
                new LoggerFactory,
                new ExpressionLanguage
            );
            $enabler = $this->configureHostEnabler();
            if (! $enabler) {
                # TODO output a warning about not being able to run remote cmds
            } else {
                $runner->setEnabler($enabler);
            }
            foreach ($this->getProject()->getTargets() as $target) {
                $command = new TargetRunCommand;
                $command->setName($target->getName());
                $command->setDescription("Run target: " . $target->getName());
                $command->setTarget($target->getName());
                $command->setTaskRunner($runner);
                $this->add($command);
            }
        }
    }

    protected function configureHostEnabler()
    {
        $localComposerFiles = null;
        $localDroidBinary = null;
        try {
            $localComposerFiles = $this->locateLocalComposerFiles();
        } catch (RuntimeException $eComposer) {
            try {
                $localDroidBinary = $this->locateLocalDroidBinary();
            } catch (RuntimeException $ePhar) {
                # No Op
            }
        }
        if (! ($localComposerFiles || $localDroidBinary)) {
            return;
        }

        $enabler = $localComposerFiles
            ? new Enabler(new SynchroniserComposer($localComposerFiles))
            : new Enabler(new SynchroniserPhar($localDroidBinary))
        ;

        $phpCheck = new PhpVersionCheck;
        $phpCheck->configure(array('min_php_version' => 50509));

        $dirCheck = new WorkingDirectoryCheck;
        $dirCheck->configure(array('working_dir_path' => '/usr/local/droid'));

        return $enabler
            ->addHostCheck($phpCheck)
            ->addHostCheck($dirCheck)
        ;
    }

    protected function locateLocalDroidBinary()
    {
        $candidatePath = getcwd() . DIRECTORY_SEPARATOR
            . $this->getDroidBinaryFilename()
        ;

        if (!file_exists($candidatePath)) {
            throw new RuntimeException(sprintf(
                'Unable to find the droid binary. Tried: "%s"',
                $candidatePath
            ));
        }
        $fh = fopen($candidatePath, 'rb');
        if ($fh === false) {
            throw new \RuntimeException(sprintf(
                'Unable to open the droid binary file. Please check read permissions for %s.',
                $candidatePath
            ));
        }
        fclose($fh);

        return $candidatePath;
    }

    protected function locateLocalComposerFiles()
    {
        $candidatePath = $this->basePath;
        $composerJson = $candidatePath . DIRECTORY_SEPARATOR . 'composer.json';

        if (!file_exists($composerJson)) {
            throw new RuntimeException(sprintf(
                'Unable to find composer.json. Tried: "%s"',
                $composerJson
            ));
        }
        $fh = fopen($composerJson, 'rb');
        if ($fh === false) {
            throw new \RuntimeException(sprintf(
                'Unable to open composer.json. Please check read permissions for %s.',
                $composerJson
            ));
        }
        fclose($fh);

        return $candidatePath;
    }

    protected function buildTransformer()
    {
        return new Transformer(
            new DataStreamTransformer,
            new FileTransformer,
            new InventoryTransformer(
                $this->inventory,
                PropertyAccess::createPropertyAccessor()
            ),
            new SubstitutionTransformer(
                new LightnCandyRenderer
            )
        );
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
