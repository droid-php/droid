<?php

namespace Droid\Transform\Render;

use Twig_Environment;
use Twig_Error;

class TwigRenderer implements RendererInterface
{
    protected $twig;

    public function __construct(Twig_Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Add templates, as a map of name to template, to the template loader.
     *
     * @param array $templates
     */
    public function addTemplates($templates)
    {
        foreach ($templates as $name => $template) {
            $this->twig->getLoader()->setTemplate($name, $template);
        }
    }

    public function render($value, $context = array())
    {
        $result = null;

        try {
            $result = $this->twig->render($value, $context);
        } catch (Twig_Error $e) {
            throw new RendererException(
                sprintf('Failed to render the template named "%s"', $value),
                null,
                $e
            );
        }

        return $result;
    }
}
