<?php

namespace Droid\Test\Remote;

use Droid\Remote\AbleInterface;
use Droid\Remote\SynchronisationException;
use Droid\Remote\SynchroniserPhar;

use SSHClient\Client\ClientInterface;

class SynchroniserPharTest extends \PHPUnit_Framework_TestCase
{
    protected $host;
    protected $sshClient;
    protected $scpClient;

    public function setUp()
    {
        $this->host = $this
            ->getMockBuilder(AbleInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass()
        ;
        $this->sshClient = $this
            ->getMockBuilder(ClientInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->scpClient = $this
            ->getMockBuilder(ClientInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    /**
     * @expectedException \Droid\Remote\SynchronisationException
     * @expectedExceptionMessage Local droid is missing
     */
    public function testSyncFailsWhenLocalDroidMissing()
    {
        $synchroniser = new SynchroniserPhar;
        $synchroniser->sync($this->host);
    }

    /**
     * @expectedException \Droid\Remote\SynchronisationException
     * @expectedExceptionMessage Unable to read the droid binary file /tmp/not-a-file.
     */
    public function testSyncFailsWhenLocalDroidUnreadable()
    {
        $synchroniser = new SynchroniserPhar('/tmp/not-a-file');
        $synchroniser->sync($this->host);
    }

    public function testSyncOccursWhenLocalDroidDiffers()
    {
        $localDroidPath = '/tmp/droid-synchroniser-test-file';

        $fh = fopen($localDroidPath, 'w');
        fwrite($fh, 'droid-synchroniser-test-' . md5('' . rand()));
        fclose($fh);

        $digest = sha1_file($localDroidPath);

        $this
            ->host
            ->method('getWorkingDirectory')
            ->willReturn('/tmp')
        ;
        $this
            ->host
            ->expects($this->once())
            ->method('getSshClient')
            ->willReturn($this->sshClient)
        ;
        $this
            ->sshClient
            ->expects($this->once())
            ->method('exec')
            ->with(array(sprintf(
                'echo "%s /tmp/droid.phar" > /tmp/droid.phar.sha1 && sha1sum --status -c /tmp/droid.phar.sha1',
                $digest
            )))
            ->willReturnSelf()
        ;
        $this
            ->sshClient
            ->expects($this->once())
            ->method('getExitCode')
            ->willReturn(1) # differs
        ;

        $this
            ->host
            ->expects($this->once())
            ->method('getScpClient')
            ->willReturn($this->scpClient)
        ;
        $this
            ->scpClient
            ->expects($this->once())
            ->method('getRemotePath')
            ->with('/tmp/')
            ->willReturn('user@host:/tmp/')
        ;
        $this
            ->scpClient
            ->expects($this->once())
            ->method('copy')
            ->with($localDroidPath, 'user@host:/tmp/')
            ->willReturnSelf()
        ;
        $this
            ->scpClient
            ->expects($this->once())
            ->method('getExitCode')
            ->willReturn(0) # copy succeeded
        ;
        $this
            ->host
            ->expects($this->once())
            ->method('setDroidCommandPrefix')
            ->with('php droid.phar')
        ;

        $synchroniser = new SynchroniserPhar($localDroidPath);
        $synchroniser->sync($this->host);

        return $localDroidPath;
    }

    /**
     * @group wip
     * @depends testSyncOccursWhenLocalDroidDiffers
     * @expectedException \Droid\Remote\SynchronisationException
     * @expectedExceptionMessage Unable to upload droid
     */
    public function testSyncOccursAndFails($localDroidPath)
    {
        $this
            ->host
            ->method('getSshClient')
            ->willReturn($this->sshClient)
        ;
        $this
            ->sshClient
            ->method('exec')
            ->willReturnSelf()
        ;
        $this
            ->sshClient
            ->method('getExitCode')
            ->willReturn(1) # differs
        ;

        $this
            ->host
            ->method('getScpClient')
            ->willReturn($this->scpClient)
        ;
        $this
            ->host
            ->method('getScpPath')
            ->willReturnArgument(0)
        ;
        $this
            ->scpClient
            ->method('getRemotePath')
            ->willReturn('host:/tmp/')
        ;
        $this
            ->scpClient
            ->method('copy')
            ->willReturnSelf()
        ;
        $this
            ->scpClient
            ->method('getExitCode')
            ->willReturn(1) # copy failed
        ;
        $this
            ->host
            ->expects($this->once())
            ->method('getName')
            ->willReturn('test_host')
        ;
        $this
            ->scpClient
            ->expects($this->once())
            ->method('getErrorOutput')
            ->willReturn('Copy failed - this is only a test!')
        ;

        $synchroniser = new SynchroniserPhar($localDroidPath);
        $synchroniser->sync($this->host);
    }

    /**
     * @depends testSyncOccursWhenLocalDroidDiffers
     */
    public function testSyncSkippedWhenLocalDroidMatches($localDroidPath)
    {
        $this
            ->host
            ->method('getSshClient')
            ->willReturn($this->sshClient)
        ;
        $this
            ->sshClient
            ->method('exec')
            ->willReturnSelf()
        ;
        $this
            ->sshClient
            ->method('getExitCode')
            ->willReturn(0) # digest matched
        ;

        $this
            ->host
            ->expects($this->never())
            ->method('getScpClient')
        ;
        $this
            ->host
            ->expects($this->once())
            ->method('setDroidCommandPrefix')
            ->with('php droid.phar')
        ;

        $synchroniser = new SynchroniserPhar($localDroidPath);
        $synchroniser->sync($this->host);
    }
}