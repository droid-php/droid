<?php

namespace Droid\Remote;

use Droid\Model\Exception\HostException;

/**
 * RuntimeException thrown in the course of enabling an object to remotely
 * execute droid commands.
 */
class EnablementException extends HostException
{
    public function __construct(
        $host = null,
        $message = null,
        $code = null,
        $previous = null
    ) {
        $message = sprintf(
            'Unable to run remote commands: %s',
            $message ?: 'No Message'
        );
        return parent::__construct($host, $message, $code, $previous);
    }
}
