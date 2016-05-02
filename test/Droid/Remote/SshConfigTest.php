<?php

namespace Droid\Test\Remote;

use SSHClient\Client\ClientInterface;
use SSHClient\ClientBuilder\ClientBuilder;
use Symfony\Component\Process\Process;

use Droid\Model\Host;
use Droid\Remote\SshConfig;

class SshConfigTest extends \PHPUnit_Framework_TestCase
{
    protected $host;
    protected $sshClient;
    protected $sshClientBuilder;

    public function setUp()
    {
        $this->host = $this
            ->getMockBuilder(Host::class)
            ->setConstructorArgs(array('test_host'))
            ->getMock()
        ;
    }

    public function testMinimalConfig()
    {
        $this
            ->host
            ->expects($this->at(0))
            ->method('getName')
        ;
        $this
            ->host
            ->expects($this->at(1))
            ->method('getUsername')
        ;

        new SshConfig($this->host);
    }

    public function testKeyfileInConfig()
    {
        $this
            ->host
            ->method('getKeyFile')
            ->willReturn('/path/to/some/file')
        ;

        $config = new SshConfig($this->host);
        $builder = new ClientBuilder($config);
        $builder->buildSSHPrefix();

        $this->assertArraySubset(
            array(
                'IdentityFile' => '/path/to/some/file',
                'IdentitiesOnly' => 'yes',
            ),
            $config->getOptions()
        );
    }

    public function testStandardPortConfig()
    {
        $this
            ->host
            ->method('getPort')
            ->willReturn(22)
        ;

        $config = new SshConfig($this->host);
        $builder = new ClientBuilder($config);
        $builder->buildSSHPrefix();

        $this->assertArrayNotHasKey('Port', $config->getOptions());
    }

    public function testNonStandardPortConfig()
    {
        $this
            ->host
            ->method('getPort')
            ->willReturn(10022)
        ;

        $config = new SshConfig($this->host);
        $builder = new ClientBuilder($config);
        $builder->buildSSHPrefix();

        $this->assertArraySubset(
            array(
                'Port' => 10022,
            ),
            $config->getOptions()
        );
    }
}