<?php

namespace Droid\Model\Exception;

/**
 * RuntimeException which identifies a host in the message.
 */
class HostException extends \RuntimeException
{
    public function __construct(
        $host = null, $message = null, $code = null, $previous = null
    ) {
        $message = sprintf(
            '[%s] %s',
            $host ? (string) $host : 'Unknown host',
            $message ?: 'No Message'
        );
        return parent::__construct($message, $code, $previous);
    }
}