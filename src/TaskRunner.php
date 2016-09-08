<?php

namespace Droid;

use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

use Droid\Model\Inventory\Remote\EnablementException;
use Droid\Model\Inventory\Remote\EnablerInterface;
use Droid\Model\Project\Task;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\Process\Process;

use Droid\Transform\Transformer;

class TaskRunner
{
    private $output;
    private $app;
    private $connections = [];
    private $expr;
    private $transformer;

    public function __construct(
        Application $app,
        Transformer $transformer,
        ExpressionLanguage $expr = null
    ) {
        $this->app = $app;
        $this->transformer = $transformer;
        if (! $expr) {
            $this->expr = new ExpressionLanguage;
        } else {
            $this->expr = $expr;
        }
    }

    public function setEnabler(EnablerInterface $enabler)
    {
        $this->enabler = $enabler;
        return $this;
    }

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
        return $this;
    }

    public function runTaskRemotely(Task $task, $variables, $hostsExpression)
    {
        $command = $this->app->find($task->getCommandName());

        $items = $this->getTaskItems($task, $variables);
        if (empty($items)) {
            return;
        }

        $taskHosts = $this->getTaskHosts($task, $variables, $hostsExpression);
        $summaryOfHosts = $this->getSummaryOfHosts($hostsExpression, $task->getHostFilter());

        foreach ($items as $item) {
            $variables['item'] = $item;
            $this->output->writeln(
                sprintf(
                    '<comment>%s `%s`</comment>: <info>%s</info> on <comment>%s</comment>',
                    ucfirst($task->getType()),
                    $task->getName(),
                    $command->getName(),
                    $summaryOfHosts
                )
            );
            $commandInput = array();
            foreach ($taskHosts as $host) {
                $perHostVars = array_merge($variables, array('host' => $host));
                // Allow host-level variables to override project, module and target variables 
                $perHostVars = array_merge($perHostVars, $host->getVariables());
                $commandInput[$host->name] = $this
                    ->prepareCommandInput($command, $task->getArguments(), $perHostVars)
                ;
                $printableArgs = $this->commandInputToText($commandInput[$host->name]);
                if ($printableArgs === '') {
                    $printableArgs = 'with zero arguments';
                }
                $this->output->writeln(
                    sprintf('<comment>Host %s</comment>: %s', $host->name, $printableArgs)
                );
            }
            $this->runRemoteCommand($task, $command, $commandInput, $taskHosts);
        }
    }

    public function runTaskLocally(Task $task, $variables)
    {
        $command = $this->app->find($task->getCommandName());

        $items = $this->getTaskItems($task, $variables);

        foreach ($items as $item) {
            $variables['item'] = $item;
            $commandInput = $this->prepareCommandInput($command, $task->getArguments(), $variables);
            $this->output->writeln(
                sprintf(
                    '<comment>%s `%s`</comment>: <info>%s</info> %s <comment>locally</comment>',
                    ucfirst($task->getType()),
                    $task->getName(),
                    $command->getName(),
                    $this->commandInputToText($commandInput)
                )
            );
            if ($this->runLocalCommand($task, $command, $commandInput)) {
                throw new RuntimeException("Task failed: " . $task->getCommandName());
            }
        }
    }

    public function runTaskList($list, $variables, $targetHosts)
    {
        // Build up '$triggers' array for all changed tasks
        $triggers = [];

        $tasks = $list->getTasksByType('task');

        foreach ($tasks as $task) {
            $hostsExpression = $task->getHosts() ?: $targetHosts;
            if ($hostsExpression && ! $this->app->hasInventory()) {
                throw new RuntimeException(
                    'Cannot run remote commands without inventory, please use --droid-inventory'
                );
            } elseif ($hostsExpression && ! $this->enabler) {
                throw new RuntimeException(
                    'Cannot run remote commands because the local composer.json or droid.phar cannot be found'
                );
            } elseif ($hostsExpression) {
                $this->runTaskRemotely($task, $variables, $hostsExpression);
            } else {
                $this->runTaskLocally($task, $variables);
            }
            if (! $task->getChanged()) {
                continue;
            }
            foreach ($task->getTriggers() as $name) {
                if (array_key_exists($name, $triggers)) {
                    continue;
                }
                $listedTrigger = $list->getTaskByName($name);
                if (!$listedTrigger) {
                    throw new RuntimeException("Unknown trigger: " . $name);
                }
                $triggers[$name] = $listedTrigger;
            }
        }

        // Call all triggered handlers
        foreach ($triggers as $trigger) {
            $hostsExpression = $trigger->getHosts() ?: $targetHosts;
            if ($hostsExpression) {
                $this->runTaskRemotely($trigger, $variables, $hostsExpression);
            } else {
                $this->runTaskLocally($trigger, $variables);
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
            $variables = array_merge($module->variables, $project->variables, $target->variables);
            $variables['mod_path'] = $module->getBasePath();
            $this->runTaskList($module, $variables, $target->getHosts());
        }

        $variables = array_merge($project->variables, $target->variables);
        $this->runTaskList($target, $variables, $target->getHosts());
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
            if (! $value) {
                continue;
            }
            $out .= '--' . $key;
            if (is_string($value)) {
                $out .= '=<info>' . $value . '</info>';
            }
            $out .= ' ';
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
        $process = new Process(
            sprintf(
                '%s%s %s %s --ansi',
                $task->getElevatePrivileges() ? 'sudo ' : '',
                $_SERVER['argv'][0],
                $command->getName(),
                (string) $commandInput
            )
        );
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

    /**
     * Run a command on the supplied hosts.
     *
     * @param Task $task
     * @param Command $command
     * @param array $commandInput An array of ArrayInput instances indexed by
     *                            host name
     * @param array $hosts The list of hosts on which to run the command
     *
     * @throws InvalidArgumentException when $commandInput is not an array or
     *                                  is missing an ArrayInput for a host
     * @throws RuntimeException
     *
     * @return number always zero
     */
    public function runRemoteCommand(Task $task, Command $command, $commandInput, $hosts)
    {
        if (!is_array($commandInput)) {
            throw new InvalidArgumentException(
                'Expected $commandInput as an array of ArrayInput instances'
            );
        }

        $running = array();

        foreach ($hosts as $host) {
            if (!isset($commandInput[$host->getName()])
                || ! $commandInput[$host->getName()] instanceof ArrayInput
            ) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Expected an ArrayInput for host "%s".',
                        $host->getName()
                    )
                );
            }

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
                sprintf('cd %s;', $host->getWorkingDirectory()),
                sprintf(
                    '%s%s',
                    $task->getElevatePrivileges() ? 'sudo ' : '',
                    $host->getDroidCommandPrefix()
                ),
                $command->getName(),
                (string) $commandInput[$host->getName()],
                '--ansi'
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
                $outputter
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
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
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
            $this->output->writeln(
                "<comment> * TASK " . $command->getName() ."</comment> " . trim($variableString) . "</comment>"
            );
        }

        // Variable substitution in arguments
        foreach ($arguments as $name => $value) {
            if (! is_string($value) || empty($value)) {
                continue;
            }
            $value = $this->transformer->transformVariable($value, $variables);
            if (! is_string($value) || empty($value)) {
                $arguments[$name] = $value;
                continue;
            }
            if ($value[0] == '@') {
                $fileContent = $this->transformer->transformFile(substr($value, 1));
                $value = $this->transformer->transformDataStream($fileContent);
            } elseif ($value[0] == '!') {
                $templateContent = $this->transformer->transformFile(substr($value, 1));
                $content = $this->transformer->transformVariable($templateContent, $variables);
                $value = $this->transformer->transformDataStream($content);
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
            } elseif ($argument->isRequired()) {
                throw new RuntimeException(
                    sprintf(
                        'Missing required argument "%s" for command "%s".',
                        $name,
                        $command->getName()
                    )
                );
            }
        }

        foreach ($definition->getOptions() as $option) {
            $name = $option->getName();
            if (! array_key_exists($name, $arguments)) {
                continue;
            }
            $inputs['--' . $name] = $arguments[$name];
        }

        $commandInput = new ArrayInput($inputs, $definition);
        return $commandInput;
    }

    private function getTaskItems(Task $task, $variables)
    {
        $items = $task->getItems();
        if (is_string($items)) {
            // it's a variable name, resolve it into an array
            if (!isset($variables[$items])) {
                throw new RuntimeException("Items is refering to non-existant variable: " . $items);
            }
            $items = $variables[$items];
        }
        if (!is_array($items)) {
            throw new RuntimeException("Items is not an array: " . gettype($items));
        }
        if (! $task->getItemFilter()) {
            return $items;
        }
        $selectedItems = array();
        foreach ($items as $candidate) {
            try {
                if ($this->expr->evaluate($task->getItemFilter(), array('item' => $candidate))) {
                    $selectedItems[] = $candidate;
                }
            } catch (SyntaxError $e) {
                throw new UnexpectedValueException(
                    sprintf(
                        'Unable to parse Task with_items_filter expression "%s"',
                        $task->getItemFilter()
                    ),
                null,
                $e
                );
            }
        }
        return $selectedItems;
    }

    private function getSummaryOfHosts($hostsExpression, $hostFilter = null)
    {
        if ($hostFilter) {
            return sprintf('%s where "%s"', $hostsExpression, $hostFilter);
        }
        return $hostsExpression;
    }

    private function getTaskHosts($task, $variables, $hostsExpression)
    {
        if (! $this->app->hasInventory()) {
            throw new RuntimeException(
                'Cannot run remote commands without inventory, please use --droid-inventory'
            );
        }

        $candidateHosts = $this->app->getInventory()->getHostsByName($hostsExpression);

        if (empty($candidateHosts)) {
            return array();
        } elseif (! $task->getHostFilter()) {
            return $candidateHosts;
        }

        $taskHosts = array();

        foreach ($candidateHosts as $host) {
            try {
                if ($this->expr->evaluate($task->getHostFilter(), array('host' => $host))) {
                    $taskHosts[] = $host;
                }
            } catch (SyntaxError $e) {
                throw new UnexpectedValueException(
                    sprintf(
                        'Unable to parse Task host_filter expression "%s"',
                        $task->getHostFilter()
                    ),
                    null,
                    $e
                );
            }
        }

        return $taskHosts;
    }
}
