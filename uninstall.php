<?php
// Exit if accessed directly.
defined("WP_UNINSTALL_PLUGIN") || exit();

function rnmoji_uninstall(): void
{
    $plugin_dir = plugin_dir_path(__FILE__);

    if (is_dir($plugin_dir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $plugin_dir,
                RecursiveDirectoryIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = $fileinfo->isDir() ? "rmdir" : "unlink";
            $todo($fileinfo->getRealPath());
        }

        rmdir($plugin_dir);
    }
}

?>
