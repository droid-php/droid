<?php

namespace Droid;

use RuntimeException;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Application as App;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Process\Process;
use Droid\Model\Task;
use Droid\Remote\EnablementException;
use Droid\Remote\EnablerInterface;

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
         */

        if (!$command) {
            throw new RuntimeException("Unsupported command: " . $task->getCommandName());
        }

        foreach ($task->getItems() as $item) {
            $variables['item'] = (string)$item;
            $commandInput = $this->prepareCommandInput($command, $task->getArguments(), $variables);

            $out = "<comment> * Executing: " . $command->getName() ."</comment> ";
            $out .= $this->commandInputToText($commandInput);
            if ($hosts) {
                $out .= "on <comment>" . $hosts . "</comment>";
            } else {
                $out .= "on <comment>local machine</comment>";
            }
            
            $this->output->writeln($out);

            if ($hosts) {
                if (!$this->app->hasInventory()) {
                    throw new RuntimeException(
                        "Can't run remote commands without inventory, please use --droid-inventory"
                    );
                }
                $inventory = $this->app->getInventory();
                $hosts = $inventory->getHostsByName($hosts);

                $res = $this->runRemoteCommand($command, $commandInput, $hosts);
            } else {
                $res = $this->runLocalCommand($command, $commandInput);
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
            foreach ($module->getTasks() as $task) {
                $variables = array_merge($module->getVariables(), $project->getVariables(), $target->getVariables());
                $this->runTask($task, $variables, $target->getHosts());
            }
        }

        foreach ($target->getTasks() as $task) {
            $variables = array_merge($project->getVariables(), $target->getVariables());
            $this->runTask($task, $variables, $target->getHosts());
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
        $running = array();

        foreach ($hosts as $host) {

            if (! $host->enabled()) {
                # we will wait for a host to be enabled before doing real work
                try {
                    $this->enabler->enable($host);
                } catch (EnablementException $e) {
                    throw new RuntimeException('Unable to run remote command', null, $e);
                }
            }

            $ssh = $host->getSshClient();

            $outputter = function($type, $buf) use ($host) {
                $fmt = '<fg=black;bg=blue;options=bold> ' . $host->getName() . ' </> %s';
                if ($type === Process::ERR) {
                    $fmt = '<error>' . $host->getName() . '</error> %s';
                }
                foreach (explode("\n", $buf) as $line) {
                    $this->writeln(sprintf($fmt, $line));
                }
            };

            $ssh->startExec(
                array(
                    'php', '/tmp/droid.phar', $command->getName(),
                    (string) $commandInput, '--ansi'
                ),
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
                '<comment>[%s] exited with code "%d"</comment>',
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
                if ($value) {
                    if ($value[0]=='@') {
                        $datafile = substr($value, 1);
                        if (!file_exists($datafile)) {
                            throw new RuntimeException("Can't load data-file: " . $datafile);
                        }
                        $data = file_get_contents($datafile);
                        $data = 'data:application/octet-stream;charset=utf-8;base64,' . base64_encode($data);
                        $value = $data;
                    }
                }
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
}
