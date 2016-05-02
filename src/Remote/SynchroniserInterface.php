<?php

namespace Droid\Remote;

/**
 * Ensure that a remote host has the same version of droid as the local host.
 */
interface SynchroniserInterface
{
    /**
     * Upload the local droid executable when its content differs from that of
     * the remote droid executable.
     *
     * @param AbleInterface $host
     *
     * @throws \Droid\Remote\SynchronisationException
     */
    public function sync(AbleInterface $host);
}