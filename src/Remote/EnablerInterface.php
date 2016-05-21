<?php

namespace Droid\Remote;

/**
 * Enable remote execution of droid commands.
 */
interface EnablerInterface
{
    /**
     * Enable droid execution on the supplied remote host.
     *
     * @param AbleInterface $host
     */
    public function enable(AbleInterface $host);
}
