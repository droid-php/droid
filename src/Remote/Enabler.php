<?php

namespace Droid\Remote;

/**
 * Enable remote execution of droid commands.
 *
 * This implementation asserts that a high enough version of PHP is installed
 * on a host before calling on SynchroniserInterface to arrange for droid to be
 * made available on the host.
 */
class Enabler implements EnablerInterface
{
    private $minPhpVersion = 50509;
    protected $synchroniser;

    public function __construct(SynchroniserInterface $synchroniser)
    {
        $this->synchroniser = $synchroniser;
    }

    public function enable(AbleInterface $host)
    {
        $host->unable();

        $this->assertPhpVersion($host->getSshClient(), $host->getName());

        try {
            $this->synchroniser->sync($host);
        } catch (SynchronisationException $e) {
            throw new EnablementException(
                $host->getName(), 'Failure during binary synchronisation.', null, $e
            );
        }

        $host->able();
    }

    private function assertPhpVersion($ssh, $hostname)
    {
        $ssh->exec(array('php', '-r', '"echo PHP_VERSION_ID;"'));
        if ($ssh->getExitCode()) {
            throw new EnablementException(
                $hostname, 'Unable to check remote PHP version. Is PHP installed?'
            );
        }
        $version = trim($ssh->getOutput());
        if ($version < $this->minPhpVersion) {
            throw new EnablementException($hostname, sprintf(
                'The remotely installed version of PHP is too low. Got %s; Expected PHP >= %d.',
                $version,
                $this->minPhpVersion
            ));
        }
    }
}