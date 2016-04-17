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
            if ($task->hasLoopVariables()) {
                throw new RuntimeException("Loops need to be re-implemented");
                /*
                foreach ($task->getLoopParameters() as $i => $loopParameters) {
                    $parameters = array_merge($task->getParameters(), $project->getParameters());
                    $parameters = array_merge($parameters, $loopParameters);
                    $res = $this->runCommand($task->getCommandName(), $parameters, $target);
                }
                */
            } else {
                $variables = array_merge($project->getVariables(), $target->getVariables(), $task->getVariables());

                $command = $this->app->find($task->getCommandName());
                /* NOTE: The command instance gets reused between tasks.
                 * This gives the unexpected behaviour that after the first run, the app-definitions are merged
                 * This throws in the required 'command' argument, and other defaults like -v, -ansi, etc
                 */
                 
                if (!$command) {
                    throw new RuntimeException("Unsupported command: " . $task->getCommandName());
                }
                $commandInput = $this->prepareCommandInput($command, $variables);

                $this->output->writeln(
                    "<comment> * Executing: " . $command->getName() ."</comment> " . $this->commandInputToText($commandInput) . "</comment>"
                );

                if ($target->getHosts()) {
                    if (!$this->app->hasInventory()) {
                        throw new RuntimeException("Can't run remote commands without inventory, please use --droid-inventory");
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
        return true;
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
            $username = 'root';
            $this->output->writeln(
                "<comment> * Connecting: " . $host->getHostname() ."</comment> "
            );
            $ssh = $this->getSshConnection($host);
            
            $cmd = '/tmp/droid.phar ' . $command->getName()  . ' "LOL"';
            $out = $ssh->exec($cmd);

            echo $out;
            $err = $ssh->getStdError();
            echo $err;
        }

        $res = true;
    }
    
    public function prepareCommandInput(Command $command, $variables)
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

        // Variable substitution in variables
        foreach ($variables as $name => $value) {
            foreach ($variables as $name2 => $value2) {
                $value = str_replace('{{' . $name2 . '}}', $value2, $value);
                $variables[$name] = $value;
            }
        }
        
        $arguments = [];
        $variables['command'] = $command->getName();
        
        $definition = $command->getDefinition();
        
        foreach ($definition->getArguments() as $argument) {
            $name = $argument->getName();
            if (isset($variables[$name])) {
                $arguments[$name]=$variables[$name];
            } else {
                if ($argument->isRequired()) {
                    throw new RuntimeException("Missing required argument: " . $name);
                }
            }
        }

        foreach ($definition->getOptions() as $option) {
            $name = $option->getName();
            if (isset($variables[$name])) {
                $arguments['--' . $name] = $variables[$name];
            }
        }
        
        $commandInput = new ArrayInput($arguments, $definition);
        return $commandInput;
    }
    
    
    protected function getSshConnection(Host $host)
    {
        $hostname = $host->getHostName();

        $username = $host->getUsername();
        $port = $host->getPort();

        $this->output->writeLn(" - Connecting: <info>$username@$hostname:$port</info>");
        
        $ssh = new \phpseclib\Net\SSH2($hostname);

        $res = null;
        
        /*
        if ($input->getOption('keyfile')) {
            // Load a private key
            $keyName = $input->getOption('keyfile');
            $key = new \phpseclib\Crypt\RSA();
            $passphrase = $input->getOption('passphrase');
            if ($passphrase) {
                $key->setPassword($passphrase);
            }

            if (!$key->loadKey(file_get_contents($keyName))) {
                throw new RuntimeException("Loading key failed: " . $keyName);
            }
            $res = $ssh->login($username, $key);
        }
        
        if ($input->getOption('agent')) {
        */
            $agent = new \phpseclib\System\SSH\Agent();
            $res = $ssh->login($username, $agent);
        /*
        }
        
        if ($input->getOption('password')) {
            $res = $ssh->login($username, $input->getOption('password'));
        }
        */
        
        if (!$res) {
            throw new RuntimeException("Login failed: " . $hostname . ' as ' . $username);
        }
        $ssh->enableQuietMode();
        
        $timeout = 3;
        
        $ssh->setTimeout($timeout);

        return $ssh;
    }
}
