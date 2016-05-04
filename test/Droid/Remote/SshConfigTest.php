<?php

namespace Droid\Test\Remote;

use SSHClient\ClientBuilder\ClientBuilder;

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

    public function testSshOptions()
    {
        $this
            ->host
            ->expects($this->atLeastOnce())
            ->method('getSshOptions')
            ->willReturn(array('ProxyCommand' => 'ssh gwuser@gw ncat %h %p'))
        ;

        $config = new SshConfig($this->host);
        $builder = new ClientBuilder($config);
        $builder->buildSSHPrefix();

        $this->assertArraySubset(
            array(
                'ProxyCommand' => 'ssh gwuser@gw ncat %h %p',
            ),
            $config->getOptions()
        );
    }

    public function testSshOptionsOverrideKeyfile()
    {
        $this
            ->host
            ->method('getKeyFile')
            ->willReturn('/path/to/some/file')
        ;
        $this
            ->host
            ->method('getSshOptions')
            ->willReturn(array(
                'IdentityFile' => '/some/other/file',
                'IdentitiesOnly' => 'no'
            ))
        ;

        $config = new SshConfig($this->host);
        $builder = new ClientBuilder($config);
        $builder->buildSSHPrefix();

        $this->assertArraySubset(
            array(
                'IdentityFile' => '/some/other/file',
                'IdentitiesOnly' => 'no',
            ),
            $config->getOptions()
        );
    }

    public function testSshGatewayHost()
    {
        $gw = $this
            ->getMockBuilder(Host::class)
            ->setConstructorArgs(array('gateway_host'))
            ->getMock()
        ;
        $gw
            ->method('getSshOptions')
            ->willReturn(array(
                'IdentityFile' => '/gateway_host.key'
            ))
        ;
        $gw
            ->method('getName')
            ->willReturn('gateway_host')
        ;
        $gw
            ->method('getUsername')
            ->willReturn('gwuser')
        ;
        $gw
            ->method('getSshBuilder')
            ->willReturn(new ClientBuilder(new SshConfig($gw)))
        ;
        $this
            ->host
            ->method('getSshGateway')
            ->willReturn($gw)
        ;
        $this
            ->host
            ->method('getSshOptions')
            ->willReturn(array(
                'IdentityFile' => '/test_host.key'
            ))
        ;

        $config = new SshConfig($this->host);
        $builder = new ClientBuilder($config);
        $builder->buildSSHPrefix();

        $this->assertArraySubset(
            array(
                'IdentityFile' => '/test_host.key',
                'ProxyCommand' => 'ssh -o IdentityFile=/gateway_host.key gwuser@gateway_host nc %h %p',
            ),
            $config->getOptions()
        );
    }
}