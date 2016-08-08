<?php

namespace Droid\Transform;

use InvalidArgumentException;

class FileTransformer implements TransformerInterface
{
    public function transform($value, $context = array())
    {
        if (! is_string($value)) {
            throw new InvalidArgumentException(
                'Cannot perform transformation because the supplied value is not a string path to a file.'
            );
        }
        if (empty($value)) {
            throw new InvalidArgumentException(
                'Cannot perform transformation because the supplied value is not a non-empty string path to a file.'
            );
        }
        if (! file_exists($value)) {
            throw new InvalidArgumentException(
                'Cannot perform transformation because the supplied value is not a path to an existing file.'
            );
        }
        if (! is_file($value)) {
            throw new InvalidArgumentException(
                'Cannot perform transformation because the supplied value is not a path to a file.'
            );
        }
        if (! is_readable($value)) {
            throw new InvalidArgumentException(
                'Cannot perform transformation because the supplied value is not a path to a readable file.'
            );
        }

        $content = file_get_contents($value);

        if ($content === false) {
            throw new InvalidArgumentException(
                'Cannot perform transformation for some unknown reason (value is a readable, existing file).'
            );
        }

        return $content;
    }
}
