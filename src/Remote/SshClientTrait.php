<?php

namespace Droid\Remote;

use SSHClient\ClientBuilder\ClientBuilder;

use Droid\Model\Host;

/**
 * Provide means to build pre-configured SSH and Secure Copy clients.
 */
trait SshClientTrait
{
    protected $sshClientBuilder;
    protected $sshGateway;
    protected $sshOptions = array();

    /**
     * @return \SSHClient\Client\Client
     */
    public function getSshClient()
    {
        return $this->getSshBuilder()->buildClient();
    }

    /**
     * @return \SSHClient\Client\Client
     */
    public function getScpClient()
    {
        return $this->getSshBuilder()->buildSecureCopyClient();
    }

    /**
     * @return \SSHClient\ClientBuilder\ClientBuilder
     */
    public function getSshBuilder()
    {
        if (! $this->sshClientBuilder) {
            $this->sshClientBuilder = new ClientBuilder(new SshConfig($this));
        }
        return $this->sshClientBuilder;
    }

    /**
     * Get SSH Options as an array of option name to option value, suitable as
     * OpenSSH "-o" options.
     *
     * @return array
     */
    public function getSshOptions()
    {
        return $this->sshOptions;
    }

    /**
     * Set OpenSSH "-o" options.
     *
     * @param array $options
     *
     * @throws \UnexpectedValueException
     */
    public function setSshOptions($options)
    {
        if (!is_array($options)) {
            throw new \UnexpectedValueException('Expected an array of options.');
        }
        $this->sshOptions = $options;
    }

    /**
     * Get an SSH gateway Host.
     *
     * @return \Droid\Model\Host
     */
    public function getSshGateway()
    {
        return $this->sshGateway;
    }

    /**
     * Set an SSH gateway Host.
     *
     * @param \Droid\Model\Host $gatewayHost
     */
    public function setSshGateway(Host $gatewayHost)
    {
        $this->sshGateway = $gatewayHost;
    }
}