<?php

namespace Droid\Transform;

interface TransformerInterface
{
    public function transform($value, $context = array());
}
