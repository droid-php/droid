<?php

namespace Droid\Transform;

use InvalidArgumentException;

use Droid\Model\Inventory\Inventory;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class InventoryTransformer implements TransformerInterface
{
    protected $accessor;
    protected $inventory;

    public function __construct(
        Inventory $inventory,
        PropertyAccessorInterface $accessor
    ) {
        $this->inventory = $inventory;
        $this->accessor = $accessor;
    }

    public function transform($value, $context = array())
    {
        if (! is_string($value)) {
            throw new InvalidArgumentException(
                'Cannot perform transformation because the supplied value is not a string.'
            );
        }

        if (empty($value)) {
            return $value;
        }

        if (substr($value, 0, 1) !== '%' || substr($value, -1) !== '%') {
            return $value;
        }

        $parsed = $this->parse($value);
        if ($parsed === false) {
            throw new TransformerException(
                sprintf(
                    'Cannot perform transformation because the supplied value "%s" is not an expression of the form "%%object.property%%".',
                    $value
                )
            );
        }

        list($identifier, $property) = $parsed;

        $struct = $this->resolve($identifier);
        if ($struct === false) {
            throw new TransformerException(
                sprintf(
                    'Cannot perform transformation because the supplied value "%s" ("%s") does not identify anything in the Inventory.',
                    $value,
                    $identifier
                )
            );
        }

        if (! $this->accessor->isReadable($struct, $property)) {
            throw new TransformerException(
                sprintf(
                    'Cannot perform transformation because the supplied value "%s" does not resolve to a readable element in the Inventory.',
                    $value
                )
            );
        }

        return $this->accessor->getValue($struct, $property);
    }

    protected function parse($expression)
    {
        $pos = strrpos($expression, '.');
        if ($pos === false) {
            return false;
        }
        return array(
            substr($expression, 1, $pos-1),
            substr($expression, $pos+1, -1)
        );
    }

    protected function resolve($expression)
    {
        if ($this->inventory->hasHost($expression)) {
            return $this->inventory->getHost($expression);
        }
        if ($this->inventory->hasHostGroup($expression)) {
            return $this->inventory->getHostGroup($expression);
        }
        if ($this->inventory->hasVariable($expression)) {
            return $this->inventory->getVariable($expression);
        }
        return false;
    }
}
