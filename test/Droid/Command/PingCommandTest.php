<?php

namespace Droid\Test\Command;

use PHPUnit_Framework_TestCase;
use RuntimeException;

use Droid\Model\Inventory\Host;
use Droid\Model\Inventory\Inventory;
use SSHClient\Client\ClientInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use Droid\Command\PingCommand;

class PingCommandTest extends PHPUnit_Framework_TestCase
{
    protected $app;
    protected $command;
    protected $host;
    protected $inventory;
    protected $ssh;
    protected $tester;

    protected function setUp()
    {
        $this->inventory = $this
            ->getMockBuilder(Inventory::class)
            ->getMock()
        ;
        $this->host = $this
            ->getMockBuilder(Host::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->ssh = $this
            ->getMockBuilder(ClientInterface::class)
            ->getMock()
        ;

        $this->command = new PingCommand;
        #$this->command->setInventory($this->inventory);

        $this->app = new Application;
        $this->app->add($this->command);

        $this->tester = new CommandTester($this->command);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage To perform this command I require an Inventory of Hosts
     * @covers \Droid\Command\PingCommand::configure
     * @covers \Droid\Command\PingCommand::initialize
     */
    public function testCommandWithoutInventoryWillThrowException()
    {
        $this->tester->execute(array(
            'command' => $this->command->getName(),
            'hostname' => 'some-host-name',
        ));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage To perform this command I require an Inventory of Hosts
     * @covers \Droid\Command\PingCommand::configure
     * @covers \Droid\Command\PingCommand::initialize
     */
    public function testCommandWithoutHostsWillThrowException()
    {
        $this->command->setInventory($this->inventory);
        $this
            ->inventory
            ->method('getHosts')
            ->willReturn(array())
        ;
        $this->tester->execute(array(
            'command' => $this->command->getName(),
            'hostname' => 'some-host-name',
        ));
    }

    /**
     * @expectedException RuntimeException
     * @covers \Droid\Command\PingCommand::configure
     * @covers \Droid\Command\PingCommand::execute
     */
    public function testCommandWithNameOfUnknownHostWillThrowException()
    {
        $this->command->setInventory($this->inventory);
        $this
            ->inventory
            ->method('getHosts')
            ->willReturn(array($this->host))
        ;
        $this
            ->inventory
            ->expects($this->once())
            ->method('getHost')
            ->with($this->equalTo('some-host-name'))
            ->willThrowException(new RuntimeException)
        ;
        $this->tester->execute(array(
            'command' => $this->command->getName(),
            'hostname' => 'some-host-name',
        ));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage I cannot ping the host named "some-host-name": there is no "keyfile" (SSH IdentityFile) configured with which to authenticate the "some-user-name" user
     * @covers \Droid\Command\PingCommand::configure
     * @covers \Droid\Command\PingCommand::execute
     */
    public function testCommandWithNameOfImproperlyConfiguredHostWillThrowException()
    {
        $this->command->setInventory($this->inventory);
        $this
            ->inventory
            ->method('getHosts')
            ->willReturn(array($this->host))
        ;
        $this
            ->inventory
            ->method('getHost')
            ->willReturn($this->host)
        ;
        $this
            ->host
            ->expects($this->once())
            ->method('getKeyFile')
            ->willReturn(null)
        ;
        $this
            ->host
            ->expects($this->once())
            ->method('getName')
            ->willReturn('some-host-name')
        ;
        $this
            ->host
            ->expects($this->once())
            ->method('getUsername')
            ->willReturn('some-user-name')
        ;
        $this->tester->execute(array(
            'command' => $this->command->getName(),
            'hostname' => 'some-host-name',
        ));
    }

    /**
     * @covers \Droid\Command\PingCommand::execute
     */
    public function testCommandWithFailedPingWillOutputErrorMessage()
    {
        $this->command->setInventory($this->inventory);
        $this
            ->inventory
            ->method('getHosts')
            ->willReturn(array($this->host))
        ;
        $this
            ->inventory
            ->method('getHost')
            ->willReturn($this->host)
        ;
        $this
            ->host
            ->expects($this->once())
            ->method('getKeyFile')
            ->willReturn('file')
        ;
        $this
            ->host
            ->expects($this->once())
            ->method('getSshClient')
            ->willReturn($this->ssh)
        ;
        $this
            ->host
            ->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('host1')
        ;
        $this
            ->ssh
            ->expects($this->once())
            ->method('exec')
            ->with($this->equalTo(array('/bin/true')))
        ;
        $this
            ->ssh
            ->expects($this->exactly(2))
            ->method('getExitCode')
            ->willReturn(127)
        ;
        $this
            ->ssh
            ->expects($this->once())
            ->method('getErrorOutput')
            ->willReturn('some stderr output')
        ;
        $this->tester->execute(array(
            'command' => $this->command->getName(),
        ));

        $this->assertRegExp(
            '/I attempt to Ping 1 host/',
            $this->tester->getDisplay()
        );

        $this->assertRegExp(
            '/host1 Ping fail \(code 127\)/',
            $this->tester->getDisplay()
        );

        $this->assertRegExp(
            '/some stderr output/',
            $this->tester->getDisplay()
        );
    }

    /**
     * @covers \Droid\Command\PingCommand::execute
     */
    public function testCommandWithSuccessfullPingWillOutputSuucessMessage()
    {
        $this->command->setInventory($this->inventory);
        $this
            ->inventory
            ->method('getHosts')
            ->willReturn(array($this->host))
        ;
        $this
            ->inventory
            ->method('getHost')
            ->willReturn($this->host)
        ;
        $this
            ->host
            ->expects($this->once())
            ->method('getKeyFile')
            ->willReturn('file')
        ;
        $this
            ->host
            ->expects($this->once())
            ->method('getSshClient')
            ->willReturn($this->ssh)
        ;
        $this
            ->host
            ->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('host1')
        ;
        $this
            ->ssh
            ->expects($this->once())
            ->method('exec')
            ->with($this->equalTo(array('/bin/true')))
        ;
        $this
            ->ssh
            ->expects($this->once())
            ->method('getExitCode')
            ->willReturn(0)
        ;
        $this
            ->ssh
            ->expects($this->once())
            ->method('getOutput')
            ->willReturn('some stdout output')
        ;
        $this
            ->ssh
            ->expects($this->once())
            ->method('getErrorOutput')
            ->willReturn('some stderr output')
        ;
        $this->tester->execute(array(
            'command' => $this->command->getName(),
        ));

        $this->assertRegExp(
            '/I attempt to Ping 1 host/',
            $this->tester->getDisplay()
        );

        $this->assertRegExp(
            '/host1\s*Pong/',
            $this->tester->getDisplay()
        );

        $this->assertRegExp(
            '/some stdout output/',
            $this->tester->getDisplay()
        );

        $this->assertRegExp(
            '/some stderr output/',
            $this->tester->getDisplay()
        );
    }

    /**
     * @covers \Droid\Command\PingCommand::configure
     * @covers \Droid\Command\PingCommand::execute
     */
    public function testCommandWithoutArgsWillPingAllSuitableHosts()
    {
        $this->command->setInventory($this->inventory);
        $this
            ->inventory
            ->method('getHosts')
            ->willReturn(array($this->host, $this->host, $this->host))
        ;
        $this
            ->host
            ->expects($this->exactly(3))
            ->method('getKeyFile')
            ->willReturnOnConsecutiveCalls('file', 'file', null)
        ;
        $this
            ->host
            ->expects($this->exactly(2))
            ->method('getSshClient')
            ->willReturn($this->ssh)
        ;
        $this
            ->host
            ->expects($this->exactly(4))
            ->method('getName')
            ->willReturnOnConsecutiveCalls('host1', 'host1', 'host2', 'host2')
        ;
        $this
            ->ssh
            ->expects($this->exactly(2))
            ->method('exec')
            ->with($this->equalTo(array('/bin/true')))
        ;
        $this->tester->execute(array(
            'command' => $this->command->getName(),
        ));

        $this->assertRegExp(
            '/I attempt to Ping 2 hosts/',
            $this->tester->getDisplay()
        );

        $this->assertRegExp(
            '/host1\s*Pong/',
            $this->tester->getDisplay()
        );

        $this->assertRegExp(
            '/host2\s*Pong/',
            $this->tester->getDisplay()
        );
    }
}
