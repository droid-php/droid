<?php

namespace Droid\Remote;

/**
 * Ensure that a remote host has the same version of droid as the local host.
 */
interface SynchroniserInterface
{
    /**
     * Make available, on a remote host, the same version of a locally installed
     * Droid.
     *
     * @param AbleInterface $host
     *
     * @throws \Droid\Remote\SynchronisationException
     */
    public function sync(AbleInterface $host);
}