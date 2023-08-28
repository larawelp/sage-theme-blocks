<?php

namespace LaraWelP\SageThemeBlocks\Macros;

use Illuminate\View\View;

class RenderWithBlocks
{
    public function __invoke()
    {
        View::macro('renderWithBlocks', function () {
            $content = $this->render();
            if (str($this->getPath())->endsWith('.html')) {
                return do_blocks($content);
            }
            return $content;
        });
    }
}