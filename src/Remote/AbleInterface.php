<?php

namespace Droid\Remote;

/**
 * Implementers of AbleInterface provide means to be marked as being able to
 * remotely execute droid command and provide access to pre-configured SSH and
 * Secure Copy clients.
 */
interface AbleInterface
{
    /**
     * Mark the object as being able to remotely execute droid commands.
     */
    public function able();

    /**
     * Mark the object as being unable to remotely execute droid commands.
     */
    public function unable();

    /**
     * Find out whether or not the object has been marked as being able to
     * remotely execute droid commands.
     *
     * @return boolean
     */
    public function enabled();

    /**
     * Get an SSH client, configured for connecting to a particular host.
     *
     * @return \SSHClient\Client\ClientInterface
     */
    public function getSshClient();

    /**
     * Get an Secure Copy client, configured for connecting to a particular host.
     *
     * @return \SSHClient\Client\ClientInterface
     */
    public function getScpClient();

    /**
     * Get the name of the object.
     *
     * @return string
     */
    public function getName();
}