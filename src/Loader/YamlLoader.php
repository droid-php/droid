<?php

namespace Droid\Loader;

use Exception;
use RuntimeException;

use Droid\Model\Feature\Firewall\Rule;
use Droid\Model\Inventory\Host;
use Droid\Model\Inventory\HostGroup;
use Droid\Model\Inventory\Inventory;
use Droid\Model\Project\Environment;
use Droid\Model\Project\Module;
use Droid\Model\Project\Project;
use Droid\Model\Project\Target;
use Droid\Model\Project\Task;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser as YamlParser;

use Droid\Utils;
use Droid\Transform\Transformer;

class YamlLoader
{
    public $errors = array();

    protected $appBasedir;
    protected $modulePaths = array();
    protected $ignoreModules = false;
    protected $transformer;

    public function __construct(
        $appBasePath,
        Transformer $transformer
    ) {
        $this->appBasedir = $appBasePath . DIRECTORY_SEPARATOR;
        $this->modulePaths[] = $this->appBasedir . 'modules';
        $this->modulePaths[] = $this->appBasedir . 'droid-vendor';
        $this->transformer = $transformer;
    }

    public function load(
        Project $project,
        Inventory $inventory,
        Environment $environment
    ) {
        $data = $this->loadYaml($project->getConfigFilePath());
        $this->loadEnvironment($environment, $data);
        $this->loadInventory($inventory, $data);
        $this->loadProject($project, $data);
    }

    public function loadYaml($filename)
    {

        if (!file_exists($filename)) {
            $this->errors[] = sprintf(
                'Failed to load yaml file "%s": File not found.',
                $filename
            );
            return array();
        }

        $parser = new YamlParser();
        $data = null;
        try {
            $data = $parser->parse(file_get_contents($filename));
            if (! is_array($data)) {
                $this->errors[] = sprintf(
                    'Failed to get any yaml content from file "%s".',
                    $filename
                );
                return array();
            }
        } catch (ParseException $e) {
            $this->errors[] = sprintf(
                'Failed to parse yaml content from file "%s": %s',
                $filename,
                $e->getMessage()
            );
            return array();
        }
        if (isset($data['include'])) {
            foreach ($data['include'] as $line) {
                $filenames = glob($line);
                if (count($filenames)==0) {
                    $this->errors[] = sprintf(
                        'Failed to include any files after processing Include directive "%s".',
                        $line
                    );
                    continue;
                }
                foreach ($filenames as $filename) {
                    if (! is_file($filename)) {
                        continue;
                    }
                    if (! is_readable($filename)) {
                        $this->errors[] = sprintf(
                            'Failed to include "%s". File is not readable.',
                            $filename
                        );
                        continue;
                    }
                    $includeData = $this->loadYaml($filename);
                    $data = array_merge_recursive($data, $includeData);
                }
            }
        }
        return $data;

    }

    private function loadEnvironment(Environment $environment, $data)
    {
        if (! array_key_exists('environment', $data)
            || ! is_array($data['environment'])
        ) {
            return;
        }

        foreach ($data['environment'] as $k => $v) {
            if (property_exists(Environment::class, $k)) {
                $environment->$k = $v;
            }
        }
    }

    private function loadProject(Project $project, $data)
    {
        if (array_key_exists('name', $data) && is_string($data['name'])) {
            $project->name = $data['name'];
        }
        if (array_key_exists('description', $data) && is_string($data['description'])) {
            $project->description = $data['description'];
        }

        $this->loadVariables($data, $project);

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

    public function setIgnoreModules($flag)
    {
        $this->ignoreModules = $flag;
    }

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
        if (! $this->ignoreModules) {
            $this->errors[] = sprintf(
                'The module "%s" could not be found. Try running droid module:install.',
                $module->getName()
            );
        }
    }

    public function loadModule(Module $module)
    {
        $path = $this->getModulePath($module);
        if (!$path) {
            return null;
        }
        $filename = $path . '/droid.yml';
        $data = $this->loadYaml($filename);
        $module->setBasePath($path);
        if (array_key_exists('description', $data) && is_string($data['description'])) {
            $module->setDescription($data['description']);
        }
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
        if (! isset($data['firewall_rules'])) {
            return;
        }
        foreach ($data['firewall_rules'] as $ruleData) {
            if (!isset($ruleData['address']) || !isset($ruleData['port'])) {
                throw new \RuntimeException(
                    sprintf(
                        'A Firewall rule for "%s" cannot be loaded because it does not define both address and port.',
                        $obj->getName()
                    )
                );
            }
            $rule = new Rule();
            $rule
                ->setAddress($ruleData['address'])
                ->setPort($ruleData['port'])
            ;
            if (isset($ruleData['protocol'])) {
                $rule->setProtocol($ruleData['protocol']);
            }
            if (isset($ruleData['direction'])) {
                $rule->setDirection($ruleData['direction']);
            }
            if (isset($ruleData['comment'])) {
                $rule->setComment($ruleData['comment']);
            }
            if (isset($ruleData['action'])) {
                $rule->setAction($ruleData['action']);
            }
            $obj->addRule($rule);
        }
    }

    private function loadHosts(Inventory $inventory, $hosts)
    {
        $want_gateway = array();
        foreach ($hosts as $hostName => $hostData) {
            $host = new Host($hostName);
            $this->loadRules($host, $hostData);
            foreach ($hostData as $key => $value) {
                switch ($key) {
                    case 'variables':
                        $this->loadVariables($hostData, $host);
                        break;
                    case 'droid_ip':
                        $host->droid_ip = $value;
                        break;
                    case 'droid_port':
                        if (! is_numeric($value)) {
                            throw new RuntimeException('Expected numeric droid_port.');
                        }
                        $host->droid_port = (int) $value;
                        break;
                    case 'public_ip':
                        $host->public_ip = $value;
                        break;
                    case 'private_ip':
                        $host->private_ip = $value;
                        break;
                    case 'username':
                        $host->setUsername($value);
                        break;
                    case 'firewall_policy':
                        $host->setFirewallPolicy($value);
                        break;
                    case 'keyfile':
                        $host->setKeyFile(Utils::absoluteFilename($value));
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
                    case 'firewall_rules':
                        break;
                    default:
                        throw new RuntimeException("Unknown host property: " . $key);
                }
            }
            $inventory->addHost($host);
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

        # sanity check of hosts
        $inventoryHosts = $inventory->getHosts();
        if (empty($inventoryHosts)) {
            $this->errors[] = 'Inventory is devoid of Hosts.';
            return;
        }
        $incompleteHosts = array();
        foreach ($inventoryHosts as $host) {
            if (! $host->getConnectionIp()) {
                $incompleteHosts[] = $host->name;
            }
        }
        if ($incompleteHosts) {
            $this->errors[] = sprintf(
                'The following hosts fail to meet the minimum requirement that they exhibit an IP address (droid_ip or public_ip): %s.',
                implode(', ', $incompleteHosts)
            );
        }
    }

    private function loadHostGroups(Inventory $inventory, $groups)
    {
        foreach ($groups as $groupName => $groupNode) {
            $group = new HostGroup($groupName);
            $this->loadRules($group, $groupNode);

            if (isset($groupNode['firewall_policy'])) {
                $group->setFirewallPolicy($groupNode['firewall_policy']);
            }

            if (isset($groupNode['hosts'])) {
                foreach ($groupNode['hosts'] as $hostName) {
                    if (!$inventory->hasHost($hostName)) {
                        throw new RuntimeException("Host group `$groupName` refers to undefined host: `$hostName`");
                    }
                    $host = $inventory->getHost($hostName);
                    $group->addHost($host);
                }
            }
            $this->loadVariables($groupNode, $group);
            $inventory->addHostGroup($group);
        }
    }

    public function loadVariables($data, $obj)
    {
        if (! isset($data['variables'])) {
            return;
        }
        foreach ($data['variables'] as $name => $value) {
            if (is_array($value)) {
                array_walk_recursive(
                    $value,
                    function (&$v, $k, $txfmr) {
                        if (! is_string($v)) {
                            return;
                        }
                        $result = null;
                        try {
                            $result = $txfmr->transformInventory($v);
                        } catch (Exception $e) {
                            # No Op
                        }
                        $v = $result;
                    },
                    $this->transformer
                );
            }
            $obj->setVariable($name, $value);
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
                    case 'with_items_filter':
                        $task->setItemFilter($taskNode[$key]);
                        break;
                    case 'arguments':
                        foreach ($taskNode['arguments'] as $var => $val) {
                            $task->setArgument($var, $val);
                        }
                        break;
                    case 'hosts':
                        $task->setHosts($taskNode[$key]);
                        break;
                    case 'host_filter':
                        $task->setHostFilter($taskNode[$key]);
                        break;
                    case 'max_runtime':
                        $task->setMaxRuntime($taskNode[$key]);
                        break;
                    case 'sudo':
                        if (is_bool($taskNode[$key])) {
                            $task->setElevatePrivileges($taskNode[$key]);
                        }
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
