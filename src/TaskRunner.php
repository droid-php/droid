<?php

namespace Droid;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Application as App;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Droid\Model\Host;
use RuntimeException;

class TaskRunner
{
    private $output;
    private $app;
    private $connections = [];
    
    public function __construct(App $app, OutputInterface $output)
    {
        $this->app = $app;
        $this->output = $output;
    }
    
    public function runTarget($project, $targetName)
    {
        $target = $project->getTargetByName($targetName);
        if (!$target) {
            throw new RuntimeException("Target not found: " . $targetName);
        }

        foreach ($target->getTasks() as $task) {

            $command = $this->app->find($task->getCommandName());
            /* NOTE: The command instance gets reused between tasks.
             * This gives the unexpected behaviour that after the first run, the app-definitions are merged
             * This throws in the required 'command' argument, and other defaults like -v, -ansi, etc
             */
             
            if (!$command) {
                throw new RuntimeException("Unsupported command: " . $task->getCommandName());
            }
            
            foreach ($task->getItems() as $item) {
                $variables = array_merge($project->getVariables(), $target->getVariables());
                
                $variables['item'] = (string)$item;
                $commandInput = $this->prepareCommandInput($command, $task->getArguments(), $variables);

                $this->output->writeln(
                    "<comment> * Executing: " . $command->getName() ."</comment> " .
                    $this->commandInputToText($commandInput) .
                    "</comment>"
                );

                if ($target->getHosts()) {
                    if (!$this->app->hasInventory()) {
                        throw new RuntimeException(
                            "Can't run remote commands without inventory, please use --droid-inventory"
                        );
                    }
                    $inventory = $this->app->getInventory();
                    $hosts = $inventory->getHostsByName($target->getHosts());

                    $res = $this->runRemoteCommand($command, $commandInput, $hosts);
                } else {
                    $res = $this->runLocalCommand($command, $commandInput);
                }
                if ($res) {
                    throw new RuntimeException("Task failed: " . $task->getCommandName());
                }
            }
        }
        return 0;
    }
    
    public function commandInputToText(ArrayInput $commandInput)
    {
        $out = '';
        //print_r($commandInput->getArguments());
        foreach ($commandInput->getArguments() as $key => $value) {
            if ($key!='command') {
                $out .= '' . $key . '=<info>' . $value . '</info> ';
            }
        }
        foreach ($commandInput->getOptions() as $key => $value) {
            if ($value) {
                $out .= '--' . $key;
                $out .= '=<info>' . $value . '</info> ';
            }
        }
        return $out;
    }
    
    public function runLocalCommand(Command $command, ArrayInput $commandInput)
    {
        //$commandInput->setArgument('command', $command->getName());
        $res = $command->run($commandInput, $this->output);
        return $res;
    }
    
    public function runRemoteCommand(Command $command, ArrayInput $commandInput, $hosts)
    {
        foreach ($hosts as $host) {
            $ssh = $this->getSshConnection($host);
            
            $cmd = 'php /tmp/droid.phar ' . $command->getName()  . ' "LOL" --ansi';
            $out = $ssh->exec($cmd);

            echo $out;
            $err = $ssh->getStdError();
            echo $err;
            $exitCode = $ssh->getExitStatus();
            if ($exitCode!=0) {
                throw new RuntimeException("Remote task returned non-zero exitcode: " . $exitCode);
            }
        }
        return 0;
    }
    
    public function prepareCommandInput(Command $command, $arguments, $variables)
    {
        $variableString = '';
        foreach ($variables as $name => $value) {
            $variableString .= ' ' . $name . '=`<info>' . $value . '</info>`';
        }
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $this->output->writeln(
                "<comment> * TASK " . $command->getName() ."</comment> " . trim($variableString) . "</comment>"
            );
        }

        // Variable substitution in arguments
        foreach ($arguments as $name => $value) {
            foreach ($variables as $name2 => $value2) {
                $value = str_replace('{{' . $name2 . '}}', $value2, $value);
                $arguments[$name] = $value;
            }
        }
        
        $inputs = [];
        $arguments['command'] = $command->getName();
        
        $definition = $command->getDefinition();
        
        foreach ($definition->getArguments() as $argument) {
            $name = $argument->getName();
            if (isset($arguments[$name])) {
                $inputs[$name]=$arguments[$name];
            } else {
                if ($argument->isRequired()) {
                    throw new RuntimeException("Missing required argument: " . $name);
                }
            }
        }

        foreach ($definition->getOptions() as $option) {
            $name = $option->getName();
            if (isset($arguments[$name])) {
                $inputs['--' . $name] = $arguments[$name];
            }
        }
        
        $commandInput = new ArrayInput($inputs, $definition);
        return $commandInput;
    }
    
    
    protected function getSshConnection(Host $host)
    {
        if (isset($this->connections[$host->getName()])) {
            return $this->connections[$host->getName()];
        }
        $address = $host->getAddress();
        $username = $host->getUsername();
        $port = $host->getPort();

        $this->output->writeLn(" - Connecting: <info>$username@$address:$port</info>");
        
        $ssh = new \phpseclib\Net\SSH2($address);

        $res = null;
        
        switch ($host->getAuth()) {
            case 'key':
                // Load a private key
                $keyFile = $host->getKeyFile();
                $key = new \phpseclib\Crypt\RSA();
                $keyPass = $host->getKeyPass();
                if ($keyPass) {
                    $key->setPassword($keyPass);
                }

                if (!$key->loadKey(file_get_contents($keyFile))) {
                    throw new RuntimeException("Loading key failed: " . $keyFile);
                }
                $res = $ssh->login($username, $key);
                break;
            case 'agent':
                $agent = new \phpseclib\System\SSH\Agent();
                $res = $ssh->login($username, $agent);
                break;
            case 'password':
                $res = $ssh->login($username, $host->getPassword());
                break;
        }
        
        if (!$res) {
            throw new RuntimeException("Login failed: $username@$address:$port");
        }
        $ssh->enableQuietMode();
        
        $timeout = 3;
        
        $ssh->setTimeout($timeout);
        
        $this->output->writeLn(" - Checking remote PHP version");
        $cmd = "php -r \"echo PHP_VERSION_ID;\"";
        $version = $ssh->exec($cmd);
        if ($ssh->getExitStatus() != 0) {
            $err = $ssh->getStdError();
            echo $err;
            throw new RuntimeException("Checking remote host failed. Is PHP installed?");
        }
        if ($version<50509) {
            throw new RuntimeException("Remote host PHP version too low: $version");
        }
        
        $localDroid = getcwd() . '/droid.phar';
        
        if (!file_exists($localDroid)) {
            throw new RuntimeException("Local droid not found: " . $localDroid);
        }
        
        $remoteDroid = '/tmp/droid.phar';
        
        $sha1 = sha1(file_get_contents($localDroid));
        
        $this->output->writeLn(" - Checking remote droid.phar version");
        $cmd = 'echo "' . $sha1 . ' ' . $remoteDroid .'" > ' . $remoteDroid . '.sha1';
        $cmd .= ' && sha1sum --status -c ' . $remoteDroid . '.sha1';
        //echo $cmd . "\n";
        
        $res = $ssh->exec($cmd);
        if ($ssh->getExitStatus() != 0) {
            $this->output->writeLn(" - Uploading droid (new or updated)... $sha1");

            $scp = new \phpseclib\Net\SCP($ssh);
            if (!$scp->put(
                $remoteDroid,
                $localDroid,
                \phpseclib\Net\SCP::SOURCE_LOCAL_FILE
            )) {
                throw new Exception("Failed to send file");
            }
        }

        $this->connections[$host->getName()] = $ssh;
        return $ssh;
    }
}
