<?php

namespace Droid\Loader;

use Symfony\Component\Yaml\Parser as YamlParser;
use Droid\Model\Project;
use Droid\Model\Target;
use Droid\Model\Inventory;
use Droid\Model\Host;
use Droid\Model\HostGroup;
use Droid\Model\RegisteredCommand;
use Droid\Model\Task;
use Droid\Model\Firewall;
use Droid\Model\Rule;
use Droid\Model\Module;
use Droid\Utils;
use RuntimeException;

class YamlLoader
{
    public function load(Project $project, Inventory $inventory, $filename)
    {
        $this->modulePaths[] = 'modules';
        $this->modulePaths[] = 'droid-vendor';
        $data = $this->loadYaml($filename);
        $this->loadProject($project, $data);
        $this->loadInventory($inventory, $data);
    }
    
    public function loadYaml($filename)
    {

        if (!file_exists($filename)) {
            throw new RuntimeException("File not found: $filename");
        }
        
        $parser = new YamlParser();
        $data = $parser->parse(file_get_contents($filename));
        if (isset($data['include'])) {
            foreach ($data['include'] as $line) {
                $filenames = glob($line);
                if (count($filenames)==0) {
                    throw new RuntimeException("Include(s) not found: " . $line);
                }
                foreach ($filenames as $filename) {
                    if (!file_exists($filename)) {
                        throw new RuntimeException("Include filename does not exist: " . $filename);
                    }
                    $includeData = $this->loadYaml($filename);
                    $data = array_merge_recursive($data, $includeData);
                }
            }
        }
        return $data;

    }
    
    private function loadProject(Project $project, $data)
    {
        $this->loadVariables($data, $project);

        if (isset($data['register'])) {
            foreach ($data['register'] as $registerNode) {
                foreach ($registerNode as $className => $parameters) {
                    $command = new RegisteredCommand($className, $parameters);
                    $project->addRegisteredCommand($command);
                }
            }
        }
        
        if (isset($data['modules'])) {
            foreach ($data['modules'] as $name => $source) {
                $module = new Module($name, $source);
                $this->loadModule($module);
                $project->addModule($module);
            }
        }

        
        if (isset($data['targets'])) {
            foreach ($data['targets'] as $targetName => $targetNode) {
                $target = new Target($targetName);
                $this->loadVariables($targetNode, $target);
                
                $project->addTarget($target);
                if (isset($targetNode['hosts'])) {
                    $target->setHosts($targetNode['hosts']);
                }
                
                if (isset($targetNode['modules'])) {
                    foreach ($targetNode['modules'] as $moduleName) {
                        $module = $project->getModule($moduleName);
                        $target->addModule($module);
                    }
                }
                $this->loadTasks($targetNode, $target, 'tasks');
                $this->loadTasks($targetNode, $target, 'triggers');
            }
        }
    }
    
    protected $ignoreModules = false;
    
    public function setIgnoreModules($flag)
    {
        $this->ignoreModules = $flag;
    }
    
    protected $modulePaths = [];
    public function getModulePaths()
    {
        return $this->modulePaths;
    }
    
    public function getModulePath(Module $module)
    {
        foreach ($this->getModulePaths() as $path) {
            $modulePath = $path . '/' . $module->getName();
            if (file_exists($modulePath . '/droid.yml')) {
                return $modulePath;
            }
        }
        if ($this->ignoreModules) {
            return;
        }
        throw new RuntimeException("Module path not found for: " . $module->getName());
    }
    
    public function loadModule(Module $module)
    {
        $path = $this->getModulePath($module);
        if (!$path) {
            return null;
        }
        $filename = $path . '/droid.yml';
        $data = $this->loadYaml($filename);
        $module->setDescription($data['project']['description']);
        $this->loadVariables($data, $module);
        $this->loadTasks($data, $module, 'tasks');
        $this->loadTasks($data, $module, 'triggers');
    }
    
    private function loadInventory(Inventory $inventory, $data)
    {
        if (isset($data['hosts'])) {
            $this->loadHosts($inventory, $data['hosts']);
        }
        if (isset($data['groups'])) {
            $this->loadHostGroups($inventory, $data['groups']);
        }
    }
    
    private function loadRules($obj, $data)
    {
        if (isset($data['inbound'])) {
            foreach ($data['inbound'] as $ruleData) {
                $rule = new Rule();
                $rule->setAddress($ruleData['address']);
                $rule->setPort($ruleData['port']);
                
                if (isset($ruleData['action'])) {
                    $rule->setAction($ruleData['action']);
                }
                if (isset($ruleData['direction'])) {
                    $rule->setAction($ruleData['direction']);
                }
                $rule->setDirection('inbound');
                $obj->addRule($rule);
            }
        }
    }

    private function loadHosts(Inventory $inventory, $hosts)
    {
        $want_gateway = array();
        foreach ($hosts as $hostName => $hostData) {
            $host = new Host($hostName);
            $this->loadRules($host, $hostData);
            $inventory->addHost($host);
            if (!$hostData) {
                continue;
            }
            foreach ($hostData as $key => $value) {
                switch ($key) {
                    case 'variables':
                        $this->loadVariables($hostData, $host);
                        break;
                    case 'public_ip':
                        $host->setPublicIp($value);
                        break;
                    case 'private_ip':
                        $host->setPrivateIp($value);
                        break;
                    case 'public_port':
                        $host->setPublicPort($value);
                        break;
                    case 'private_port':
                        $host->setPrivatePort($value);
                        break;
                    case 'username':
                        $host->setUsername($value);
                        break;
                    case 'password':
                        $host->setPassword($value);
                        break;
                    case 'auth':
                        $host->setAuth($value);
                        break;
                    case 'keyfile':
                        $host->setKeyFile(Utils::absoluteFilename($value));
                        break;
                    case 'keypass':
                        $host->setKeyPass($value);
                        break;
                    case 'ssh_options':
                        $host->setSshOptions($value);
                        break;
                    case 'ssh_gateway':
                        if (! $inventory->hasHost($value)) {
                            $want_gateway[$hostName] = $value;
                            break;
                        }
                        $host->setSshGateway($inventory->getHost($value));
                        break;
                    case 'inbound':
                        break;
                    default:
                        throw new RuntimeException("Unknown host property: " . $key);
                }
            }
        }
        foreach ($want_gateway as $want => $gateway) {
            if (! $inventory->hasHost($gateway)) {
                throw new RuntimeException(sprintf(
                    'Host "%s" requires an unknown host "%s" as its ssh gateway.',
                    $want,
                    $gateway
                ));
            }
            $inventory
                ->getHost($want)
                ->setSshGateway($inventory->getHost($gateway))
            ;
        }
    }

    private function loadHostGroups(Inventory $inventory, $groups)
    {
        foreach ($groups as $groupName => $groupNode) {
            $group = new HostGroup($groupName);
            $this->loadRules($group, $groupNode);

            foreach ($groupNode['hosts'] as $hostName) {
                if (!$inventory->hasHost($hostName)) {
                    throw new RuntimeException("Host group `$groupName` refers to undefined host: `$hostName`");
                }
                $host = $inventory->getHost($hostName);
                $group->addHost($host);
            }
            $this->loadVariables($groupNode, $group);
            $inventory->addHostGroup($group);
        }
    }
    
    public function loadVariables($data, $obj)
    {
        if (isset($data['variables'])) {
            foreach ($data['variables'] as $name => $value) {
                $obj->setVariable($name, $value);
            }
        }
    }
    
    public function loadTasks($data, $obj, $type = 'tasks')
    {
        if (!isset($data[$type])) {
            return;
        }
        foreach ($data[$type] as $taskNode) {
            $task = new Task();
            $task->setType(rtrim($type, 's'));
            foreach ($taskNode as $key => $value) {
                switch ($key) {
                    case 'name':
                        $task->setName($taskNode[$key]);
                        break;
                    case 'command':
                        $task->setCommandName($taskNode[$key]);
                        break;
                    case 'with_items':
                        $task->setItems($taskNode[$key]);
                        break;
                    case 'arguments':
                        foreach ($taskNode['arguments'] as $var => $val) {
                            $task->setArgument($var, $val);
                        }
                        break;
                    case 'hosts':
                        $task->setHosts($taskNode[$key]);
                        break;
                    case 'trigger':
                        // TODO: Support array of triggers
                        $task->addTrigger($taskNode[$key]);
                        break;
                    default:
                        // Assume commandname
                        $task->setCommandName($key);
                        if (is_array($value)) {
                            foreach ($value as $var => $val) {
                                $task->setArgument($var, $val);
                            }
                        }
                        if (is_string($value)) {
                            preg_match_all(
                                "/(\w+)[\s]*=[\s]*((?:[^\"'\s]+)|'(?:[^']*)'|\"(?:[^\"]*)\")/",
                                $value,
                                $matches
                            );
                            for ($i=0; $i<count($matches[1]); $i++) {
                                $val = trim($matches[2][$i], " \"");
                                $task->setArgument($matches[1][$i], $val);
                            }
                        }
                }
            }
            $obj->addTask($task);
        }
    }
}
