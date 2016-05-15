<?php

namespace Droid;

use RuntimeException;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Application as App;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use Droid\Model\Task;
use Droid\Remote\EnablementException;
use Droid\Remote\EnablerInterface;
use LightnCandy\LightnCandy;

class TaskRunner
{
    private $output;
    private $app;
    private $connections = [];

    public function __construct(
        App $app,
        OutputInterface $output,
        EnablerInterface $enabler
    ) {
        $this->app = $app;
        $this->output = $output;
        $this->enabler = $enabler;
    }
    
    public function runTask(Task $task, $variables, $hosts)
    {
        $command = $this->app->find($task->getCommandName());
        /* NOTE: The command instance gets reused between tasks.
         * This gives the unexpected behaviour that after the first run, the app-definitions are merged
         * This throws in the required 'command' argument, and other defaults like -v, -ansi, etc
         *
         * The issue is now resolved as tasks are now executed in external process (runLocalCommand)
         * but will return if the command is called with ->run
         */

        if (!$command) {
            throw new RuntimeException("Unsupported command: " . $task->getCommandName());
        }
        $items = $task->getItems();
        if (is_string($items)) {
            // it's a variable name, resolve it into an array
            $var = $items;
            if (!isset($variables[$var])) {
                throw new RuntimeException("Items is refering to non-existant variable: " . $var);
            }
            $items = $variables[$var];
        }
        if (!is_array($items)) {
            throw new RuntimeException("Items is not an array: " . gettype($items));
        }
        foreach ($items as $item) {
            $variables['item'] = $item;
            $commandInput = $this->prepareCommandInput($command, $task->getArguments(), $variables);

            $out = "<comment>" . ucfirst($task->getType()) . " `" . $task->getName() . "`</comment>: <info>" . $command->getName() ."</info> ";
            $out .= $this->commandInputToText($commandInput);
            if ($hosts) {
                $out .= "on <comment>" . $hosts . "</comment>";
            } else {
                $out .= "on <comment>local</comment>";
            }
            
            $this->output->writeln($out);

            if ($hosts) {
                if (!$this->app->hasInventory()) {
                    throw new RuntimeException(
                        "Can't run remote commands without inventory, please use --droid-inventory"
                    );
                }
                $inventory = $this->app->getInventory();
                $hostArray = $inventory->getHostsByName($hosts);

                $res = $this->runRemoteCommand($task, $command, $commandInput, $hostArray);
            } else {
                $res = $this->runLocalCommand($task, $command, $commandInput);
            }
            if ($res) {
                throw new RuntimeException("Task failed: " . $task->getCommandName());
            }
        }
    }

    public function runTarget($project, $targetName)
    {
        $target = $project->getTargetByName($targetName);
        if (!$target) {
            throw new RuntimeException("Target not found: " . $targetName);
        }

        foreach ($target->getModules() as $module) {
            $tasks = $module->getTasksByType('task');
            foreach ($tasks as $task) {
                $variables = array_merge($module->getVariables(), $project->getVariables(), $target->getVariables());
                $hosts = $target->getHosts();
                if ($task->getHosts()!='') {
                    $hosts = $task->getHosts();
                }
                $this->runTask($task, $variables, $hosts);
            }
        }

        $tasks = $target->getTasksByType('task');
        foreach ($tasks as $task) {
            $variables = array_merge($project->getVariables(), $target->getVariables());
            $hosts = $target->getHosts();
            if ($task->getHosts()) {
                $hosts = $task->getHosts();
            }
            $this->runTask($task, $variables, $hosts);
        }

        // Build up '$triggers' array for all changed tasks
        $triggers = [];
        foreach ($tasks as $task) {
            if ($task->getChanged()) {
                foreach ($task->getTriggers() as $name) {
                    $triggers[$name] = $name;
                }
            }
        }

        // Call all triggered handlers
        foreach ($triggers as $name) {
            $task = $target->getTaskByName($name);
            if (!$task) {
                throw new RuntimeException("Unknown trigger: " . $name);
            }
            $hosts = $target->getHosts();
            if ($task->getHosts()) {
                $hosts = $task->getHosts();
            }
            $this->runTask($task, $variables, $hosts);
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
    
    public function taskOutput(Task $task, $type, $buf, $hostname)
    {
        $fmt = '<fg=black;bg=blue;options=reverse>' . $hostname . '</> %s';
        if ($type === Process::ERR) {
            $fmt = '<error>' . $hostname . '</error> %s';
        }
        
        foreach (explode("\n", $buf) as $line) {
            if ($line!='') {
                if (substr($line, 0, 14)=='[DROID-RESULT]') {
                    $json = substr($line, 15);
                    $data = json_decode($json, true);
                    
                    if (isset($data['changed']) && ($data['changed']=='true')) {
                        $task->setChanged(true);
                    }
                    
                    $this->output->writeln(sprintf($fmt, '<fg=cyan>' . $json . '</>'));
                } else {
                    $this->output->writeln(sprintf($fmt, $line));
                }
            }
        }
    }

    public function runLocalCommand(Task $task, Command $command, ArrayInput $commandInput)
    {
        //$commandInput->setArgument('command', $command->getName());
        //$res = $command->run($commandInput, $this->output);
        
        $argv = $_SERVER['argv'];
        $filename = $argv[0];
        $process = new Process($filename . ' ' . $command->getName() . ' ' . (string)$commandInput . ' --ansi');
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
            $this->output->writeLn("Full command-line: " . $process->getCommandLine());
        }
        
        $process->start();
        $output = $this->output;
        $runner = $this;
        $process->wait(function ($type, $buf) use ($runner, $task, $output) {
            $runner->taskOutput($task, $type, $buf, 'localhost');
        });
        
        return $process->getExitCode();
    }

    public function runRemoteCommand(Task $task, Command $command, ArrayInput $commandInput, $hosts)
    {
        $running = array();

        foreach ($hosts as $host) {
            if (!$host->enabled()) {
                # we will wait for a host to be enabled before doing real work
                try {
                    $this->enabler->enable($host);
                } catch (EnablementException $e) {
                    throw new RuntimeException('Unable to run remote command', null, $e);
                }
            }

            $ssh = $host->getSshClient();

            $runner = $this;
            $outputter = function ($type, $buf) use ($runner, $task, $host) {
                $runner->taskOutput($task, $type, $buf, $host->getName());
            };

            
            $cmd = array(
                'php', '/tmp/droid.phar', $command->getName(),
                (string)$commandInput, '--ansi'
            );
            
            if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
                $this->output->writeLn(
                    "Full command-line on " .
                    $host->getName() . ": " .
                    json_encode($cmd, JSON_UNESCAPED_SLASHES)
                );
            }

            $ssh->startExec(
                $cmd,
                $outputter->bindTo($this->output)
            );
            $running[] = array($host, $ssh);
        }

        $failures = array();
        $tts = sizeof($running);
        while (sizeof($running)) {
            if ($tts-- == 0) {
                $tts = sizeof($running) -1;
                usleep(200000);
            }
            list($host, $ssh) = array_shift($running);
            if ($ssh->isRunning()) {
                array_push($running, array($host, $ssh));
            }
            if ($ssh->getExitCode()) {
                #throw new RuntimeException("Remote task returned non-zero exitcode: " . $exitCode);
                $failures[] = array($host, $ssh);
            }
        }

        foreach ($failures as list($host, $ssh)) {
            $this->output->writeln(sprintf(
                '<error>[%s] exited with code "%d"</error>',
                $host->getName(),
                $ssh->getExitCode()
            ));
        }
        return 0;
    }

    public function prepareCommandInput(Command $command, $arguments, $variables)
    {
        $variableString = '';
        foreach ($variables as $name => $value) {
            switch (gettype($value)) {
                case 'string':
                    $valueText = $value;
                    break;
                case 'array':
                    $valueText = json_encode($value);
                    break;
                default:
                    $valueText = '{' . gettype($value) . '}';
            }
            $variableString .= ' ' . $name . '=`<info>' . $valueText . '</info>`';
        }
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $this->output->writeln(
                "<comment> * TASK " . $command->getName() ."</comment> " . trim($variableString) . "</comment>"
            );
        }

        // Variable substitution in arguments
        foreach ($arguments as $name => $value) {
            $php = LightnCandy::compile($value);
            $renderer = LightnCandy::prepare($php);
            $value = $renderer($variables);
            
            if ($value) {
                if (($value[0]=='@') || ($value[0]=='!')) {
                    $datafile = substr($value, 1);
                    if (!file_exists($datafile)) {
                        throw new RuntimeException("Can't load data-file: " . $datafile);
                    }
                    $data = file_get_contents($datafile);
                    if ($value[0]=='!') {
                        $php = LightnCandy::compile($data);
                        $renderer = LightnCandy::prepare($php);
                        $data = $renderer($variables);
                    }
                    $data = 'data:application/octet-stream;charset=utf-8;base64,' . base64_encode($data);
                    $value = $data;
                }
            }
            $arguments[$name] = $value;
        }

        $inputs = [];
        $arguments['command'] = $command->getName();

        $definition = $command->getDefinition();

        foreach ($definition->getArguments() as $argument) {
            $name = $argument->getName();
            if (isset($arguments[$name])) {
                $inputs[$name] = $arguments[$name];
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
}
