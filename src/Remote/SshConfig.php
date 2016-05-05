<?php

namespace Droid\Remote;

use SSHClient\ClientConfiguration\ClientConfiguration;

use Droid\Model\Host;

/**
 * Extends ClientConfiguration to extract SSH configuration values from a Host.
 */
class SshConfig extends ClientConfiguration
{
    protected $host;

    public function __construct(Host $host)
    {
        $this->host = $host;
        return parent::__construct($host->getName(), $host->getUsername());
    }

    public function getOptions()
    {
        if (empty($this->options)) {
            $opts = array();
            if ($this->host->getKeyFile()) {
                $opts['IdentityFile'] = $this->host->getKeyFile();
                $opts['IdentitiesOnly'] = 'yes';
            }
            if ($this->host->getPort() && $this->host->getPort() != 22) {
                $opts['Port'] = $this->host->getPort();
            }
            if ($this->host->getSshGateway()) {
                $opts['ProxyCommand'] = $this
                    ->buildProxyCommand($this->host->getSshGateway())
                ;
            }
            if (is_array($this->host->getSshOptions())) {
                $opts = array_merge($opts, $this->host->getSshOptions());
            }
            $this->options = $opts;
        }
        return $this->options;
    }

    private function buildProxyCommand(Host $gatewayHost)
    {
        $gwPrefix = $gatewayHost->getSshBuilder()->buildSSHPrefix();
        return sprintf('%s nc %%h %%p', implode(' ', $gwPrefix));
    }
}