<?php

namespace LaraWelP\SageThemeBlocks\Actions;

use WP_REST_Request;
use WP_REST_Server;

class SyncBlocksLocally
{
    public function rest_after_insert_wp_template($post, WP_REST_Request $request)
    {
        $id = $request->get_param('id');

        $results = get_block_templates([
            'slug__in' => [$id]
        ]);

        global $wpdb;

        if (!$post) {
            if (count($results) === 1) {
                // delete from posts where post_type = wp_template and post_name = $results[0]->slug
                $wpdb->delete(
                    $wpdb->posts,
                    [
                        'post_type' => 'wp_template',
                        'post_name' => $results[0]->slug,
                    ]
                );
            }
        } else {
            if ($post->post_name === 'index') {
                $wpdb->delete(
                    $wpdb->posts,
                    [
                        'ID' => $post->ID,
                    ]
                );
            }
        }

        $content = $request->get_param('content');
        if (count($results) === 1 && isset($results[0]->path)) {
            if (file_exists($results[0]->path)) {
                file_put_contents($results[0]->path, $content);
            }
        } else if (str($id)->endsWith('//index')) {
            file_put_contents(app()->basePath('templates/index.html'), $content);
        } else if ($post) {
            file_put_contents(app()->basePath('resources/views/' . $post->post_name . '.html'), $content);
            $wpdb->delete(
                $wpdb->posts,
                [
                    'post_type' => 'wp_template',
                    'ID' => $post->ID,
                ]
            );
        }

        return $post;
    }

    public function rest_after_insert_wp_template_part($post, WP_REST_Request $request)
    {
        //area,content,slug,title
        $slug = $request->get_param('slug');

        global $wpdb;

        if (!$post) {
            // delete from posts where post_type = wp_template and post_name = $results[0]->slug
            $wpdb->delete(
                $wpdb->posts,
                [
                    'post_type' => 'wp_template_part',
                    'post_name' => $slug,
                ]
            );
        } else {
            $wpdb->delete(
                $wpdb->posts,
                [
                    'ID' => $post->ID,
                ]
            );
        }

        $content = $request->get_param('content');

        $filename = strlen($slug ?? '') > 0 ? $slug : str($request->get_param('id'))
            ->replace(wp_basename(app()->basePath()) . '//', '')
            ->toString();

        file_put_contents(
            app()->basePath('parts/' . $filename . '.html'),
            $content
        );

        return $post;
    }

    public function rest_pre_echo_response($result, WP_REST_Server $instance, WP_REST_Request $request)
    {
        $watching = [
            '/wp/v2/template-parts',
            '/wp/v2/templates',
        ];

        if ($request->get_method() !== 'POST' || !in_array($request->get_route(), $watching)) {
            return $result;
        }

        $theme_slug = wp_basename(app()->basePath());
        if ($request->get_route() === '/wp/v2/templates') {
            $result['id'] = str($result['id'])
                ->replace($theme_slug . '//', '')
                ->prepend($theme_slug . '//resources-views-')->toString();

            $result['slug'] = 'resources-views-' . $result['slug'];

            return $result;
        } else {
            $result['id'] = str($result['id'])
                ->replace($theme_slug . '//', '')
                ->prepend($theme_slug . '//')->toString();

            return $result;
        }
    }
}