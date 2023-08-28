<?php

namespace LaraWelP\SageThemeBlocks\Actions;

use LaraWelP\SageThemeBlocks\Filters;
use function Roots\bundle;

class EnqueueBlockAssets
{
    public function __invoke()
    {
        if(!is_admin()) {
            return;
        }
        $backtrace = debug_backtrace();
        // if it does not have the _wp_get_iframed_editor_assets function in backtrace, bail
        if (!array_filter($backtrace, function ($item) {
            return $item['function'] === '_wp_get_iframed_editor_assets';
        })) {
            $themeFolder = basename(app()->basePath());
            echo '<script src="' . request()->schemeAndHttpHost() . '/wp-content/themes/' . $themeFolder . '/vendor/larawelp/sage-theme-blocks/js/editor_fixes.js"></script>';
            return;
        }
        if (function_exists('Roots\bundle')) {
            bundle('app')->enqueue();
        }
        add_action('wp_print_footer_scripts', function () {
            if (function_exists('Roots\bundle')) {
                bundle('editor')->enqueue();
            }
            do_action(Filters::BLOCK_IFRAME_FOOTER_SCRIPTS);
        });
    }
}
