<?php

namespace Droid\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use RuntimeException;

class TargetRunCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('target:run')
            ->setDescription(
                'Run a target'
            )
            ->addArgument(
                'target',
                InputArgument::OPTIONAL,
                'Target to run'
            )
        ;
    }
    
    private $target;
    
    public function setTarget($target)
    {
        $this->target = $target;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $target = $input->getArgument('target');
        if (!$target) {
            $target = 'default';
            if ($this->target) {
                $target = $this->target;
            }
        }
        $output->writeln("<info>Droid: Running target `$target`</info>");
        $project = $this->getApplication()->getProject();
        
        $res = $this->runTarget($project, $target, $input, $output);
        $output->writeln("Result: " . $res);
        $output->writeln('--------------------------------------------');
    }
    
    public function runTarget($project, $targetName, $input, $output)
    {
        $target = $project->getTargetByName($targetName);
        if (!$target) {
            throw new RuntimeException("Target not found: " . $targetName);
        }

        foreach ($target->getTasks() as $task) {
            if ($task->hasLoopParameters()) {
                foreach ($task->getLoopParameters() as $i => $loopParameters) {
                    $parameters = array_merge($task->getParameters(), $project->getParameters());
                    $parameters = array_merge($parameters, $loopParameters);
                    $res = $this->runCommand($task->getCommandName(), $parameters, $output);
                }
            } else {
                $parameters = array_merge($task->getParameters(), $project->getParameters());
                $res = $this->runCommand($task->getCommandName(), $parameters, $output);
            }
        }
        return true;
    }
    
    public function runCommand($commandName, $params, $output)
    {
        $paramText = '';
        foreach ($params as $key => $value) {
            $paramText .= '' . $key . '=<info>' . $value . '</info> ';
        }
        $output->writeln("<comment> * TASK " . $commandName ."</comment> " . trim($paramText) . "</comment>");

        // Variable substitution in params
        foreach ($params as $key => $value) {
            foreach ($params as $key2 => $value2) {
                $value = str_replace('{{' . $key2 . '}}', $value2, $value);
                $params[$key] = $value;
            }
        }

        $command = $this->getApplication()->find($commandName);
        if (!$command) {
            throw new RuntimeException("Unsupported command: " . $task->getCommandName());
        }
        //$command->verifyParameters($task->getParameters());

        $arguments = [];
        $arguments['command'] = $commandName;

        $definition = $command->getDefinition();
        //print_r($definition);
        foreach ($definition->getArguments() as $argument) {
            $name = $argument->getName();
            if ($name!='command') {
                if (isset($params[$name])) {
                    $arguments[$name]=$params[$name];
                } else {
                    if ($argument->isRequired()) {
                        throw new RuntimeException("Missing required argument: " . $name);
                    }
                }
            }
            //print_r($argument);
        }

        foreach ($definition->getOptions() as $option) {
            $name = $option->getName();
            if (isset($params[$name])) {
                $arguments['--' . $name] = $params[$name];
            }
        }

        $commandInput = new ArrayInput($arguments);
        //print_r($commandInput);

        $argumentText = '';
        foreach ($arguments as $key => $value) {
            $argumentText .= '' . $key . '=<info>' . $value . '</info> ';
        }

        $output->writeln("<comment>    * Executing: " . $commandName ."</comment> " . trim($argumentText) . "</comment>");
        $res = $command->run($commandInput, $output);
        if ($res) {
            throw new RuntimeException("Task failed: " . $commandName());
        }
        return $res;
    }
}
