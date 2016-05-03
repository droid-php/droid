<?php

namespace Droid\Remote;

use SSHClient\ClientBuilder\ClientBuilder;

/**
 * Provide means to build pre-configured SSH and Secure Copy clients.
 */
trait SshClientTrait
{
    protected $sshClientBuilder;

    /**
     * @return \SSHClient\Client\Client
     */
    public function getSshClient()
    {
        return $this->getBuilder()->buildClient();
    }

    /**
     * @return \SSHClient\Client\Client
     */
    public function getScpClient()
    {
        return $this->getBuilder()->buildSecureCopyClient();
    }

    /**
     * @return \SSHClient\ClientBuilder\ClientBuilder
     */
    private function getBuilder()
    {
        if (! $this->sshClientBuilder) {
            $this->sshClientBuilder = new ClientBuilder(new SshConfig($this));
        }
        return $this->sshClientBuilder;
    }
}