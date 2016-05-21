<?php

namespace Droid\Test\Remote;

use Droid\Remote\AbleInterface;
use Droid\Remote\SynchronisationException;
use Droid\Remote\SynchroniserComposer;

use SSHClient\Client\ClientInterface;

class SynchroniserComposerTest extends \PHPUnit_Framework_TestCase
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
     * @expectedExceptionMessage Path to local composer files is missing
     */
    public function testSyncFailsWhenLocalComposerFilesMissing()
    {
        $synchroniser = new SynchroniserComposer;
        $synchroniser->sync($this->host);
    }

    /**
     * @expectedException \Droid\Remote\SynchronisationException
     * @expectedExceptionMessage Unable to upload composer files
     */
    public function testSyncFailsWhenCopyComposerJsonFails()
    {
        $this
            ->host
            ->method('getWorkingDirectory')
            ->willReturn('~/working-dir')
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
            ->with('~/working-dir/')
            ->willReturn('user@host:~/working-dir/')
        ;
        $this
            ->scpClient
            ->expects($this->once())
            ->method('copy')
            ->with('path/to/composer.json', 'user@host:~/working-dir/')
        ;
        $this
            ->scpClient
            ->expects($this->once())
            ->method('getExitCode')
            ->willReturn(2)
        ;
        $synchroniser = new SynchroniserComposer('path/to');
        $synchroniser->sync($this->host);
    }

    /**
     * @expectedException \Droid\Remote\SynchronisationException
     * @expectedExceptionMessage Unable to upload composer files
     */
    public function testSyncFailsWhenCopyComposerLockFails()
    {
        $this
            ->host
            ->method('getWorkingDirectory')
            ->willReturn('~/working-dir')
        ;
        $this
            ->host
            ->expects($this->exactly(2))
            ->method('getScpClient')
            ->willReturn($this->scpClient)
        ;
        $this
            ->scpClient
            ->expects($this->exactly(2))
            ->method('getRemotePath')
            ->with('~/working-dir/')
            ->willReturn('user@host:~/working-dir/')
        ;
        $this
            ->scpClient
            ->expects($this->exactly(2))
            ->method('copy')
            ->withConsecutive(
                array('path/to/composer.json', 'user@host:~/working-dir/'),
                array('path/to/composer.lock', 'user@host:~/working-dir/')
            )
        ;
        $this
            ->scpClient
            ->expects($this->exactly(2))
            ->method('getExitCode')
            ->willReturnOnConsecutiveCalls(0, 2)
        ;
        $synchroniser = new SynchroniserComposer('path/to');
        $synchroniser->sync($this->host);
    }

    /**
     * @expectedException \Droid\Remote\SynchronisationException
     * @expectedExceptionMessage Unable to install composer
     */
    public function testSyncFailsWhenInstallingComposerFails()
    {
        $this
            ->host
            ->method('getWorkingDirectory')
            ->willReturn('~/working-dir')
        ;
        $this
            ->host
            ->method('getScpClient')
            ->willReturn($this->scpClient)
        ;
        $this
            ->scpClient
            ->method('getRemotePath')
            ->willReturn('user@host:~/working-dir/')
        ;
        $this
            ->scpClient
            ->method('copy')
            ->withConsecutive(
                array('path/to/composer.json', 'user@host:~/working-dir/'),
                array('path/to/composer.lock', 'user@host:~/working-dir/')
            )
        ;
        $this
            ->scpClient
            ->method('getExitCode')
            ->willReturnOnConsecutiveCalls(0, 0)
        ;
        $this
            ->host
            ->expects($this->exactly(2))
            ->method('getSshClient')
            ->willReturn($this->sshClient)
        ;
        $this
            ->sshClient
            ->expects($this->exactly(2))
            ->method('exec')
            ->withConsecutive(
                array(array('cd ~/working-dir;', 'stat composer.phar')),
                array(array(
                    'cd ~/working-dir;',
                    'php -r \'$d=file_get_contents("https://composer.github.io/installer.sig");if($d===false||strlen(trim($d))!==96){echo"Sigfail";exit(2);}if(copy("https://getcomposer.org/installer","composer-setup.php")!==true){echo"Dlfail";exit(2);}if(hash_file("SHA384","composer-setup.php")!==trim($d)){unlink("composer-setup.php");echo"SetupCorrupt";exit(2);}\';',
                    'php composer-setup.php;',
                    'rm composer-setup.php'
                ))
            )
        ;
        $this
            ->sshClient
            ->expects($this->exactly(2))
            ->method('getExitCode')
            ->willReturnOnConsecutiveCalls(1, 2)
        ;

        $synchroniser = new SynchroniserComposer('path/to');
        $synchroniser->sync($this->host);
    }

    /**
     * @expectedException \Droid\Remote\SynchronisationException
     * @expectedExceptionMessage Unable to execute composer install
     */
    public function testSyncFailsWhenComposerInstallStillFailsUnexpectedly()
    {
        $this
            ->host
            ->method('getWorkingDirectory')
            ->willReturn('~/working-dir')
        ;
        $this
            ->host
            ->method('getScpClient')
            ->willReturn($this->scpClient)
        ;
        $this
            ->scpClient
            ->method('getRemotePath')
            ->willReturn('user@host:~/working-dir/')
        ;
        $this
            ->scpClient
            ->method('copy')
            ->withConsecutive(
                array('path/to/composer.json', 'user@host:~/working-dir/'),
                array('path/to/composer.lock', 'user@host:~/working-dir/')
            )
        ;
        $this
            ->scpClient
            ->method('getExitCode')
            ->willReturnOnConsecutiveCalls(0, 0)
        ;
        $this
            ->host
            ->expects($this->exactly(3))
            ->method('getSshClient')
            ->willReturn($this->sshClient)
        ;
        $this
            ->sshClient
            ->expects($this->exactly(3))
            ->method('exec')
            ->withConsecutive(
                array(array('cd ~/working-dir;', 'stat composer.phar')),
                array(array(
                    'cd ~/working-dir;',
                    'php -r \'$d=file_get_contents("https://composer.github.io/installer.sig");if($d===false||strlen(trim($d))!==96){echo"Sigfail";exit(2);}if(copy("https://getcomposer.org/installer","composer-setup.php")!==true){echo"Dlfail";exit(2);}if(hash_file("SHA384","composer-setup.php")!==trim($d)){unlink("composer-setup.php");echo"SetupCorrupt";exit(2);}\';',
                    'php composer-setup.php;',
                    'rm composer-setup.php'
                )),
                array(array('cd ~/working-dir;', 'php composer.phar install'))
            )
        ;
        $this
            ->sshClient
            ->expects($this->exactly(3))
            ->method('getExitCode')
            ->willReturnOnConsecutiveCalls(1, 0, 2)
        ;

        $synchroniser = new SynchroniserComposer('path/to');
        $synchroniser->sync($this->host);
    }

    public function testSyncSucceedsAfterInstallingComposer()
    {
        $this
            ->host
            ->method('getWorkingDirectory')
            ->willReturn('~/working-dir')
        ;
        $this
            ->host
            ->method('getScpClient')
            ->willReturn($this->scpClient)
        ;
        $this
            ->scpClient
            ->method('getRemotePath')
            ->willReturn('user@host:~/working-dir/')
        ;
        $this
            ->scpClient
            ->method('copy')
            ->withConsecutive(
                array('path/to/composer.json', 'user@host:~/working-dir/'),
                array('path/to/composer.lock', 'user@host:~/working-dir/')
            )
        ;
        $this
            ->scpClient
            ->method('getExitCode')
            ->willReturnOnConsecutiveCalls(0, 0)
        ;
        $this
            ->host
            ->expects($this->exactly(3))
            ->method('getSshClient')
            ->willReturn($this->sshClient)
        ;
        $this
            ->sshClient
            ->expects($this->exactly(3))
            ->method('exec')
            ->withConsecutive(
                array(array('cd ~/working-dir;', 'stat composer.phar')),
                array(array(
                    'cd ~/working-dir;',
                    'php -r \'$d=file_get_contents("https://composer.github.io/installer.sig");if($d===false||strlen(trim($d))!==96){echo"Sigfail";exit(2);}if(copy("https://getcomposer.org/installer","composer-setup.php")!==true){echo"Dlfail";exit(2);}if(hash_file("SHA384","composer-setup.php")!==trim($d)){unlink("composer-setup.php");echo"SetupCorrupt";exit(2);}\';',
                    'php composer-setup.php;',
                    'rm composer-setup.php'
                )),
                array(array('cd ~/working-dir;', 'php composer.phar install'))
            )
        ;
        $this
            ->sshClient
            ->expects($this->exactly(3))
            ->method('getExitCode')
            ->willReturnOnConsecutiveCalls(1, 0, 0)
        ;
        $this
            ->host
            ->expects($this->once())
            ->method('setDroidCommandPrefix')
            ->with('php vendor/bin/droid')
        ;

        $synchroniser = new SynchroniserComposer('path/to');
        $synchroniser->sync($this->host);
    }

    public function testSyncSucceeds()
    {
        $this
            ->host
            ->method('getWorkingDirectory')
            ->willReturn('~/working-dir')
        ;
        $this
            ->host
            ->method('getScpClient')
            ->willReturn($this->scpClient)
        ;
        $this
            ->scpClient
            ->method('getRemotePath')
            ->willReturn('user@host:~/working-dir/')
        ;
        $this
            ->scpClient
            ->method('copy')
            ->withConsecutive(
                array('path/to/composer.json', 'user@host:~/working-dir/'),
                array('path/to/composer.lock', 'user@host:~/working-dir/')
            )
        ;
        $this
            ->scpClient
            ->method('getExitCode')
            ->willReturnOnConsecutiveCalls(0, 0)
        ;
        $this
            ->host
            ->expects($this->exactly(2))
            ->method('getSshClient')
            ->willReturn($this->sshClient)
        ;
        $this
            ->sshClient
            ->expects($this->exactly(2))
            ->method('exec')
            ->withConsecutive(
                array(array('cd ~/working-dir;', 'stat composer.phar')),
                array(array('cd ~/working-dir;', 'php composer.phar install'))
            )
        ;
        $this
            ->sshClient
            ->expects($this->exactly(2))
            ->method('getExitCode')
            ->willReturnOnConsecutiveCalls(0, 0)
        ;
        $this
            ->host
            ->expects($this->once())
            ->method('setDroidCommandPrefix')
            ->with('php vendor/bin/droid')
        ;

        $synchroniser = new SynchroniserComposer('path/to');
        $synchroniser->sync($this->host);
    }
}