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
        if ($this->options === false) {
            return array();
        }
        if (empty($this->options)) {
            $opts = array();
            if ($this->host->getKeyFile()) {
                $opts['IdentityFile'] = $this->host->getKeyFile();
                $opts['IdentitiesOnly'] = 'yes';
            }
            if ($this->host->getPort() != 22) {
                $opts['Port'] = $this->host->getPort();
            }
            $this->options = $opts;
        }
        return $this->options;
    }
}