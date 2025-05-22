<?php
/**
 * Uninstall script for the rnmoji plugin.
 *
 * Deletes plugin-generated files and options during uninstallation.
 *
 * @package rnmoji
 */

declare(strict_types=1);

defined('WP_UNINSTALL_PLUGIN') || exit();

/**
 * Deletes the rnmoji emoji upload directory on uninstall.
 *
 * @return void
 */
function rnmoji_delete_emoji_folder(): void
{
    $emoji_dir = plugin_dir_path(__DIR__) . 'assets/emoji/';

    if (!is_dir($emoji_dir)) {
        return;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($emoji_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $fileinfo) {
        if ($fileinfo->isDir()) {
            rmdir($fileinfo->getRealPath());
        } else {
            unlink($fileinfo->getRealPath());
        }
    }

    rmdir($emoji_dir);
}

rnmoji_delete_emoji_folder();
