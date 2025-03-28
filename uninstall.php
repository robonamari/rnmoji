<?php
declare(strict_types=1);

register_uninstall_hook(__FILE__, "rnmoji_uninstall");

function rnmoji_uninstall(): void
{
    $plugin_dir = plugin_dir_path(__FILE__);

    if (is_dir($plugin_dir)) {
        foreach (glob($plugin_dir . "*") as $file) {
            if (is_file($file) || is_link($file)) {
                unlink($file);
            }
        }
        rmdir($plugin_dir);
    }
}
?>
