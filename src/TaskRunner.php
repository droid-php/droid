<?php

namespace Droid;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Application as App;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
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
            if ($task->hasLoopParameters()) {
                throw new RuntimeException("Loops need to be re-implemented");
                /*
                foreach ($task->getLoopParameters() as $i => $loopParameters) {
                    $parameters = array_merge($task->getParameters(), $project->getParameters());
                    $parameters = array_merge($parameters, $loopParameters);
                    $res = $this->runCommand($task->getCommandName(), $parameters, $target);
                }
                */
            } else {
                $parameters = array_merge($task->getParameters(), $project->getParameters());

                $command = $this->app->find($task->getCommandName());
                /* NOTE: The command instance gets reused between tasks.
                 * This gives the unexpected behaviour that after the first run, the app-definitions are merged
                 * This throws in the required 'command' argument, and other defaults like -v, -ansi, etc
                 */
                 
                if (!$command) {
                    throw new RuntimeException("Unsupported command: " . $task->getCommandName());
                }
                $commandInput = $this->prepareCommandInput($command, $parameters);

                $this->output->writeln(
                    "<comment> * Executing: " . $command->getName() ."</comment> " . $this->commandInputToText($commandInput) . "</comment>"
                );

                if ($target->getHosts()) {
                    $res = $this->runRemoteCommand($command, $commandInput, $target->getHosts());
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
        $username = 'root';
        if (!$this->app->hasInventory()) {
            throw new RuntimeException("Can't run remote commands without inventory, please use --droid-inventory");
        }
        $inventory = $this->app->getInventory();
        $host = $inventory->getHost($hosts);
        
        $this->output->writeln(
            "<comment> * Connecting: " . $host->getHostname() ."</comment> "
        );
        $ssh = new \phpseclib\Net\SSH2($host->getHostname());
        $agent = new \phpseclib\System\SSH\Agent();
        $res = $ssh->login($username, $agent);
        if (!$res) {
            throw new RuntimeException("Login failed");
        }
        $cmd = '/tmp/droid.phar ' . $command->getName()  . ' "LOL"';
        $out = $ssh->exec($cmd);
        $ssh->enableQuietMode();

        echo $out;
        $err = $ssh->getStdError();
        echo $err;

        $res = true;
    }
    
    public function prepareCommandInput(Command $command, $params)
    {
        $paramText = '';
        foreach ($params as $key => $value) {
            $paramText .= '' . $key . '=<info>' . $value . '</info> ';
        }
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $this->output->writeln(
                "<comment> * TASK " . $command->getName() ."</comment> " . trim($paramText) . "</comment>"
            );
        }

        // Variable substitution in params
        foreach ($params as $key => $value) {
            foreach ($params as $key2 => $value2) {
                $value = str_replace('{{' . $key2 . '}}', $value2, $value);
                $params[$key] = $value;
            }
        }
        
        $arguments = [];
        $params['command'] = $command->getName();
        
        $definition = $command->getDefinition();
        
        foreach ($definition->getArguments() as $argument) {
            $name = $argument->getName();
            if (isset($params[$name])) {
                $arguments[$name]=$params[$name];
            } else {
                if ($argument->isRequired()) {
                    throw new RuntimeException("Missing required argument: " . $name);
                }
            }
        }

        foreach ($definition->getOptions() as $option) {
            $name = $option->getName();
            if (isset($params[$name])) {
                $arguments['--' . $name] = $params[$name];
            }
        }
        
        $commandInput = new ArrayInput($arguments, $definition);
        return $commandInput;
    }
}
