<?php
return [
    'hot_url' => env('BUD_HOT_URL', 'http://localhost:3000/bud/hot'),
    'sync_blocks' => env('WP_SAGE_SYNC_BLOCKS', wp_get_environment_type() === 'local'),
    'wrap_template' => env('WP_SAGE_WRAP_TEMPLATE', false),
];