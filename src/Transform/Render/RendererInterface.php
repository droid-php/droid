<?php

namespace Droid\Transform\Render;

interface RendererInterface
{
    public function render($value, $context = array());
}
