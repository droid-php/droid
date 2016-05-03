<?php

namespace Droid;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Application as App;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Droid\Model\Host;
use Droid\Remote\EnablerInterface;
use RuntimeException;
use SSHClient\ClientConfiguration\ClientConfiguration;
use SSHClient\ClientBuilder\ClientBuilder;
use Droid\Remote\EnablementException;
use Symfony\Component\Process\Process;


class TaskRunner
{
    private $output;
    private $app;
    private $connections = [];

    public function __construct(
        App $app, OutputInterface $output, EnablerInterface $enabler
    ) {
        $this->app = $app;
        $this->output = $output;
        $this->enabler = $enabler;
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

            $outputter = function($type, $buf) {
                $fmt = '<info>%s</info>';
                if ($type === Process::ERR) {
                    $fmt = '<comment>%s</comment>';
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
