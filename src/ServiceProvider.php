<?php

namespace LaraWelP\SageThemeBlocks;

use LaraWelP\SageThemeBlocks\Actions\OverwriteBlocksLogic;
use LaraWelP\SageThemeBlocks\Actions\SyncBlocksLocally;
use LaraWelP\SageThemeBlocks\Commands\PatchBud;
use LaraWelP\SageThemeBlocks\Macros\RenderWithBlocks;
use LaraWelP\SageThemeBlocks\ViewEngines\HtmlWithBlocksEngine;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Illuminate\View\Engines\EngineResolver;

class ServiceProvider extends LaravelServiceProvider
{
    public function register()
    {
        // load commands from Commands directory
        if ($this->app->runningInConsole()) {
            $this->commands([
                PatchBud::class,
            ]);
        }

        $this->mergeConfigFrom(
            __DIR__ . '/../config/sage-theme-blocks.php',
            'sage-theme-blocks'
        );

        add_action('wp_print_footer_scripts', [
            Actions\FooterScripts::class,
            'printFooterScripts'
        ]);

        add_action('enqueue_block_assets', [
            app()->make(Actions\EnqueueBlockAssets::class),
            '__invoke'
        ]);
    }

    public function boot()
    {
        $this->overwriteLogic();


        if (config('sage-theme-blocks.sync_blocks')) {
            $this->syncBlocks();
        }

        app(RenderWithBlocks::class)();
        $this->registerViewEngine();

        $this->templateHierarchyAcorn();
        $this->templateHierarchyLaravel();

    }

    /**
     * @return void
     */
    private function overwriteLogic(): void
    {
        $overwriter = app(OverwriteBlocksLogic::class);

        add_action('get_block_templates', [
            $overwriter,
            'get_block_templates',
        ], 1, 3);

        foreach (['pre_get_block_template', 'pre_get_block_file_template'] as $action) {
            add_action($action, [
                $overwriter,
                'find',
            ], 10, 2);
        }
    }

    /**
     * @return void
     */
    private function syncBlocks(): void
    {
        $syncLogic = app(SyncBlocksLocally::class);

        if (class_exists(\WPML\FullSiteEditing\BlockTemplates::class)) {
            global $wp_filter;
            $remove = [
                'rest_after_insert_wp_template_part',
                'rest_after_insert_wp_template',
            ];

            foreach ($remove as $filterName) {
                foreach ($wp_filter[$filterName] ?? [] as $priority => $hooks) {
                    foreach ($hooks as $hook) {
                        if ($hook['function'] instanceof \Closure) {
                            // get class
                            $reflection = new \ReflectionFunction($hook['function']);
                            $class = $reflection->getClosureScopeClass();
                            if ($class && $class->name === 'WPML\LIB\WP\Hooks') {
                                // remove the hook
                                remove_action('rest_after_insert_wp_template', $hook['function'], $priority);
                            }
                        }
                    }
                }
            }
        }

        add_action('rest_after_insert_wp_template', [
            $syncLogic,
            'rest_after_insert_wp_template',
        ], 9, 2);

        add_action('rest_after_insert_wp_template_part', [
            $syncLogic,
            'rest_after_insert_wp_template_part',
        ], 9, 2);

        add_action('rest_pre_echo_response', [
            $syncLogic,
            'rest_pre_echo_response'
        ], 9, 3);
    }

    /**
     * @return void
     */
    private function registerViewEngine(): void
    {
        /**
         * @var EngineResolver $resolver
         */
        $resolver = app('view.engine.resolver');

        $resolver->register('html', function () {
            return app()->make(HtmlWithBlocksEngine::class);
        });

        app('view')->addExtension('html', 'html');
    }

    /**
     * @return void
     */
    private function templateHierarchyAcorn(): void
    {
        if (get_class(app()) !== 'Roots\Acorn\Application') {
            return;
        }

        $filters = [
            'index_template_hierarchy',
            '404_template_hierarchy',
            'archive_template_hierarchy',
            'author_template_hierarchy',
            'category_template_hierarchy',
            'tag_template_hierarchy',
            'taxonomy_template_hierarchy',
            'date_template_hierarchy',
            'home_template_hierarchy',
            'frontpage_template_hierarchy',
            'page_template_hierarchy',
            'paged_template_hierarchy',
            'search_template_hierarchy',
            'single_template_hierarchy',
            'singular_template_hierarchy',
            'attachment_template_hierarchy',
            'privacypolicy_template_hierarchy',
            'embed_template_hierarchy',
        ];

        $callback = function ($x) {
            // for every template with no .blade or no .css, add another variant with - instead of /
            $toAdd = [];
            foreach ($x as $idx => $item) {
                if (!str($item)->endsWith('.blade.php') && !str($item)->endsWith('.css')) {
                    $toAdd[] = str($item)->replace('/', '-')->toString();
                    unset($x[$idx]);
                }
            }

            // filter toAdd to include only files with html extension
            $toAdd = array_filter($toAdd, function ($item) {
                return str($item)->endsWith('.html');
            });

            $x = array_merge($x, $toAdd);

            $x[] = 'index.html';

            return $x;
        };

        foreach ($filters as $filter) {
            add_filter($filter, $callback, 11);
        }
    }
    private function templateHierarchyLaravel(): void
    {
        if (get_class(app()) !== 'Illuminate\Foundation\Application') {
            return;
        }

        add_action('pre_get_block_templates', function($null, $query, $template_type) {
            if(!did_action('template_include')) {
                if(isset($query['slug__in']) && $query['slug__in'][0] === 'index') {
                    return [];
                }
            }
        }, 10, 3);

//        listen on view make events laravel
//        app('events')->listen('creating: *', function()  {
//            ray(func_get_args()[0]);
//            ray()->backtrace();
//        });

        $filters = [
            'index_template_hierarchy',
            '404_template_hierarchy',
            'archive_template_hierarchy',
            'author_template_hierarchy',
            'category_template_hierarchy',
            'tag_template_hierarchy',
            'taxonomy_template_hierarchy',
            'date_template_hierarchy',
            'home_template_hierarchy',
            'frontpage_template_hierarchy',
            'page_template_hierarchy',
            'paged_template_hierarchy',
            'search_template_hierarchy',
            'single_template_hierarchy',
            'singular_template_hierarchy',
            'attachment_template_hierarchy',
            'privacypolicy_template_hierarchy',
            'embed_template_hierarchy',
        ];

        $callback = function ($x) {
            // for every template with no .blade or no .css, add another variant with - instead of /
            $toAdd = [];
            foreach ($x as $idx => $item) {
                if (!str($item)->endsWith('.blade.php') && !str($item)->endsWith('.css')) {
                    $toAdd[] = str($item)->replace('/', '-')->toString();
                    unset($x[$idx]);
                }
            }

            // filter toAdd to include only files with html extension
            $toAdd = array_filter($toAdd, function ($item) {
                return str($item)->endsWith('.html');
            });

            $x = array_merge($x, $toAdd);

            $x[] = 'index.html';

            return [
                'index.php',
            ];

            return $x;
        };

        foreach ($filters as $filter) {
            add_filter($filter, $callback);
        }
    }
}
