<?php

namespace Droid\Transform;

use InvalidArgumentException;

use Droid\Transform\Render\RendererException;
use Droid\Transform\Render\RendererInterface;

class SubstitutionTransformer implements TransformerInterface
{
    protected $renderer;

    public function __construct(RendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * Add templates, as a map of name to template, to the template renderer.
     *
     * @param array $templates
     */
    public function addTemplates($templates)
    {
        $this->renderer->addTemplates($templates);
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

        if (! is_array($context)) {
            throw new InvalidArgumentException(
                'Cannot perform transformation because the supplied context is not an array.'
            );
        }

        if (empty($context)) {
            return $value;
        }

        $transformed = null;

        try {
            $transformed = $this->renderer->render($value, $context);
        } catch (RendererException $e) {
            throw new TransformerException(
                'The supplied value could not be transformed by variable substitution.',
                null,
                $e
            );
        }

        return $transformed;
    }
}
