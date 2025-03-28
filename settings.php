<?php
function rnmoji_plugin_action_links(array $links, string $file): array
{
    if ($file === plugin_basename(__FILE__)) {
        $settings_link =
            '<a href="' .
            admin_url("plugins.php?page=rnmoji-settings") .
            '">تنظیمات</a>';
        array_unshift($links, $settings_link);
    }
    return $links;
}
add_filter("plugin_action_links", "rnmoji_plugin_action_links", 10, 2);
