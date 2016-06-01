<?php

namespace Droid\Remote;

use Droid\Application;

/**
 * Ensure that a remote host has the same version of droid as the local host.
 *
 * This implementation copies local composer files to a remote host and remotely
 * executes composer on them. It installs composer to the host's working
 * directory if necessary. It uses SSH and Secure Copy clients provided by the
 * Host model.
 */
class SynchroniserComposer implements SynchroniserInterface
{
    protected $composerPath;

    /**
     * @param string $composerPath Path to the local composer files
     */
    public function __construct($composerPath = null)
    {
        $this->composerPath = $composerPath;
    }

    public function sync(AbleInterface $host)
    {
        if (! $this->composerPath) {
            throw new SynchronisationException(
                $host->getName(),
                'Path to local composer files is missing.'
            );
        }
        $this->uploadComposerFiles($host);
        $this->executeComposerInstall($host);

        $host->setDroidCommandPrefix('php vendor/bin/droid');
    }

    private function uploadComposerFiles($host, $timeout = null)
    {
        foreach (array('composer.json', 'composer.lock') as $filename) {
            $scp = $host->getScpClient();
            $scp->copy(
                sprintf('%s/%s', $this->composerPath, $filename),
                $scp->getRemotePath($host->getWorkingDirectory() . '/'),
                null,
                $timeout
            );
            if ($scp->getExitCode()) {
                throw new SynchronisationException($host->getName(), sprintf(
                    'Unable to upload composer files: "%s".',
                    $scp->getErrorOutput()
                ));
            }
        }
    }

    /**
     * Execute composer install from the host's working directory. Install
     * composer to the working directory if it isn't already installed there.
     */
    private function executeComposerInstall($host)
    {
        $ssh = $host->getSshClient();
        $ssh->exec(array(
            sprintf('cd %s;', $host->getWorkingDirectory()),
            'stat composer.phar'
        ));
        if ($ssh->getExitCode()) {
            $this->installComposer($host);
        }
        $ssh = $host->getSshClient();
        $ssh->exec(array(
            sprintf('cd %s;', $host->getWorkingDirectory()),
            'php composer.phar install --no-dev'
        ));
        if ($ssh->getExitCode()) {
            throw new SynchronisationException($host->getName(), sprintf(
                'Unable to execute composer install: "%s".',
                $ssh->getErrorOutput()
            ));
        }
    }

    /**
     * This method:-
     * - Downloads and does basic validation of the SHA384 of composer-setup.php
     * - Downloads and verifies composer-setup.php
     * - Executes and then removes composer-setup.php
     */
    private function installComposer($host)
    {
        $get_installer = array(
            '$d=file_get_contents("https://composer.github.io/installer.sig");',
            'if($d===false||strlen(trim($d))!==96){echo"Sigfail";exit(2);}',
            'if(copy("https://getcomposer.org/installer","composer-setup.php")!==true){echo"Dlfail";exit(2);}',
            'if(hash_file("SHA384","composer-setup.php")!==trim($d)){unlink("composer-setup.php");echo"SetupCorrupt";exit(2);}'
        );
        $ssh = $host->getSshClient();
        $ssh->exec(array(
            sprintf('cd %s;', $host->getWorkingDirectory()),
            sprintf('php -r \'%s\';', implode('', $get_installer)),
            'php composer-setup.php;',
            'rm composer-setup.php'
        ));
        if ($ssh->getExitCode()) {
            throw new SynchronisationException($host->getName(), sprintf(
                'Unable to install composer: "%s".',
                $ssh->getErrorOutput()
            ));
        }
    }
}
