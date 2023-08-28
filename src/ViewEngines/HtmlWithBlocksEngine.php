<?php

namespace LaraWelP\SageThemeBlocks\ViewEngines;

use Illuminate\Contracts\View\Engine;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;

class HtmlWithBlocksEngine implements Engine
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new file engine instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    /**
     * Get the evaluated contents of the view.
     *
     * @param  string  $path
     * @param  array  $data
     * @return string
     */
    public function get($path, array $data = [])
    {
        $contents = $this->files->get($path);

        if(has_blocks($contents)) {
            $contents = do_blocks($contents);
        }

        $wrap = config('sage-theme-blocks.wrap_template', false);

        $head = view('partials.inside_head')->render();
        add_action('wp_head', function () use ($head) {
            echo $head;
        });
        $footer = view('partials.footer', ['inBlock' => true])->render();
        add_action('wp_footer', function () use ($footer) {
            echo $footer;
        });

        if($wrap) {
            global $_wp_current_template_content;

            $_wp_current_template_content = $contents;

            $path = ABSPATH . WPINC . '/template-canvas.php';

            ob_start();
            include $path;
            $contents = ob_get_clean();
        }

        return $contents;
    }

    public function getCompiler(): BladeCompiler
    {
        $class = new class(app(\Illuminate\Filesystem\Filesystem::class), base_path('storage/framework/views')) extends BladeCompiler {
                        public function isExpired($path)
                        {
                                return false;
                        }

            public function getCompiledPath($file)
            {
                            return '__';
                        }
                    };

                    return  $class;
    }
}
