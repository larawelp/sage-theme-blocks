<?php

namespace LaraWelP\SageThemeBlocks\Actions;

class FooterScripts
{
    public static function printFooterScripts()
    {
        $hoturl = config('sage-theme-blocks.hot_url');
        echo <<<html
<script>
window.configureBudClient = x => {
    x.path = '{$hoturl}';
}
</script>
html;
    }
}