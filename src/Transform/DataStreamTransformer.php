<?php

namespace Droid\Transform;

use InvalidArgumentException;

class DataStreamTransformer implements TransformerInterface
{
    public function transform($value, $context = array())
    {
        if (! is_string($value)) {
            throw new InvalidArgumentException(
                'Cannot perform transformation because the supplied value is not a string.'
            );
        }

        if (empty($value)) {
            return 'data:,';
        }

        $encoded = base64_encode($value);

        if ($encoded === false) {
            throw new InvalidArgumentException(
                'Cannot perform transformation because the supplied value could not be encoded with the standard base64 encoder.'
            );
        }

        return sprintf(
            'data:application/octet-stream;base64,%s',
            $encoded
        );
    }
}
