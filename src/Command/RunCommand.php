<?php

namespace Droid\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use RuntimeException;

class RunCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('run')
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

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $target = $input->getArgument('target');
        if (!$target) {
            $target = 'default';
        }
        $output->writeln("<info>Droid: Running target `$target`</info>");
        $project = $this->getApplication()->getProject();
        
        $res = $this->runTarget($project, $target, $input, $output);
        $output->writeln("Done: " . $res);
    }
    
    public function runTarget($project, $targetName, $input, $output)
    {
        $target = $project->getTargetByName($targetName);
        if (!$target) {
            throw new RuntimeException("Target not found: " . $targetName);
        }
        
        foreach ($target->getSteps() as $step) {
            $params = '';
            foreach ($step->getParameters() as $key => $value) {
                $params .= $key . '=' . $value . ' ';
            }
            $output->writeln("<comment> * Step: " . $step->getCommandName() ." " . trim($params) . "</comment>");
            //$command = $this->getCommandByName($step->getCommandName());
            
            $command = $this->getApplication()->find($step->getCommandName());
            if (!$command) {
                throw new RuntimeException("Unsupported command: " . $step->getCommandName());
            }
            //$command->verifyParameters($step->getParameters());

            $arguments = [];
            $arguments['command'] = $step->getCommandName();
            
            $definition = $command->getDefinition();
            //print_r($definition);
            foreach ($definition->getArguments() as $argument) {
                $name = $argument->getName();
                if ($name!='command') {
                    if ($step->hasParameter($name)) {
                        $arguments[$name]=$step->getParameter($name);
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
                if ($step->hasParameter($name)) {
                    $arguments['--' . $name]=$step->getParameter($name);
                }
            }
            
            $commandInput = new ArrayInput($arguments);
            //print_r($commandInput);
            $res = $command->run($commandInput, $output);
            if ($res) {
                throw new RuntimeException("Step failed: " . $command->getName());
            }
        }
        return true;
    }
}
