<?php

namespace Droid\Transform\Render;

use LightnCandy\LightnCandy;

class LightnCandyRenderer implements RendererInterface
{
    public function render($value, $context = array())
    {
        $compiled = $this->compile($value);
        if ($compiled === false) {
            throw new RendererException('Failure to compile!');
        }

        $renderer = $this->prepare($compiled);
        if ($renderer === false) {
            throw new RendererException('Failure to prepare compiled template content.');
        }

        return $renderer($context);
    }

    protected function compile($templateContent)
    {
        return LightnCandy::compile(
            $templateContent,
            array('flags' => self::compilerFlags())
        );
    }

    protected function prepare($compiledTemplateContent)
    {
        return LightnCandy::prepare(
            $compiledTemplateContent
        );
    }

    private static function compilerFlags()
    {
        return
            LightnCandy::FLAG_INSTANCE
            | LightnCandy::FLAG_BESTPERFORMANCE
        ;
    }
}
