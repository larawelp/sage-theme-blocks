<?php

namespace LaraWelP\SageThemeBlocks\Actions;

use Symfony\Component\Finder\Finder;
use WP_Block_Template;
use WP_Theme_JSON_Resolver;

class OverwriteBlocksLogic
{
    public function get_block_templates($query_result, $query, $template_type)
    {
        if ($template_type !== 'wp_template') {
            return $query_result;
        }

        $template_custom = WP_Theme_JSON_Resolver::get_theme_data()->get_custom_templates();
        $template_base = get_default_block_template_types();

        $toObject = function ($slug, $template_type, $origin) use ($template_base, $template_custom) {
            $template_slug = $slug;
            $template_file = app()->basePath($slug . '.html');
            $theme_slug = basename(app()->basePath());

            $slug = $template_slug == 'index' ? $template_slug : str($template_slug)
                ->replace('/', '-')
                ->slug()->toString();

            if ($slug === 'index') {
                $template_file = app()->basePath('templates/index.html');
            }

            $new_template_item = array(
                'slug' => $slug,
                'path' => $template_file,
                'theme' => $theme_slug,
                'type' => $template_type,
            );

            if (isset($template_custom[$slug])) {
                $new_template_item['title'] = $template_custom[$slug]['title'];
                $new_template_item['description'] = $template_custom[$slug]['description'] ?? '';
            }

            $checkDefaultTemplate = str($slug)->replace('resources-views-', '')->toString();
            if (isset($template_base[$checkDefaultTemplate])) {
                $new_template_item['title'] = $template_base[$checkDefaultTemplate]['title'];
                $new_template_item['description'] = $template_base[$checkDefaultTemplate]['description'];
            }

            $obj = _build_block_template_result_from_file($new_template_item, $template_type);

            if (wp_get_environment_type() !== 'local') {
                global $wpdb;
                // find the post_id, if exists, by slug from posts where post_type = wp_template and post_name = $slug
                $post_id = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT ID FROM $wpdb->posts WHERE post_type = %s AND post_name = %s",
                        'wp_template',
                        $slug
                    ));

                if ($post_id) {
                    $obj->wp_id = (int)$post_id;
                    $obj->source = 'custom';
                    $obj->is_custom = true;
                    $obj->content = get_post_field('post_content', $post_id);
                }
            }

            $obj->path = $origin;

            return $obj;
        };


        $removeDuplicateSlugs = function ($carry, $item) {
            $carry[$item->slug] = $item;
            return $carry;
        };


        // use Finder to find all *.html files in the resources/views directory
        $finder = new Finder();
        $files = $finder->files()
            ->in(app()->basePath('resources/views'))
            ->name('*.html');
        $fromTheme = [];
        foreach ($files->getIterator() as $file) {
            $slug = str($file->getPathname())
                ->replace('.html', '')
                ->replace(app()->basePath() . '/', '')
                ->toString();
            $fromTheme[] = $toObject($slug, $template_type, $file->getPathname());
        }

        // always add templates/index.html
        if (file_exists(base_path('templates/index.html'))) {
            $fromTheme[] = $toObject('index', $template_type, app()->basePath('templates/index.html'));
        }

        $collection = collect($fromTheme);
        foreach ($query_result as $item) {
            $local = $collection->firstWhere('slug', $item->slug);
            if ($local) {
                $item->path = $local->path;
            }
        }

        if ($template_type === 'wp_template' && empty($query_result) && isset($query['slug__in'])) {
            $ours = [];
            foreach ($query['slug__in'] as $slug) {
                $found = $collection->firstWhere('id', $slug);
                if (!$found) {
                    $found = $collection->firstWhere('slug', $slug);
                }
                if ($found) {
                    $ours[] = $found;
                }
            }
            return array_values(array_reduce($ours, $removeDuplicateSlugs, []));
        } else if ($template_type === 'wp_template') {

            $combined = array_merge(
                $query_result,
                $fromTheme
            );

            if (isset($query['slug__in'])) {
                $ours = [];
                foreach ($query['slug__in'] as $slug) {
                    $found = $collection->firstWhere('id', $slug);
                    if (!$found) {
                        $found = $collection->firstWhere('slug', $slug);
                    }
                    if ($found) {
                        $ours[] = $found;
                    }
                }
                $combined = $ours;
            }

            return array_values(array_reduce($combined, $removeDuplicateSlugs, []));
        }

        return array_values(array_reduce($query_result, $removeDuplicateSlugs, []));
    }

    public function find($id, $type)
    {
        if ($type instanceof WP_Block_Template) {
            $type = $type->id;
        }

        if (str($type)->endsWith('templates//index')) {
            $type = 'index';
        }

        $results = get_block_templates([
            'slug__in' => [$type]
        ]);

        if (count($results) === 1) {
            return $results[0];
        }
    }
}