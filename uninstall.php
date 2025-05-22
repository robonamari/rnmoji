<?php
/**
 * Uninstall script for the rnmoji plugin.
 *
 * This script removes all plugin-related files and directories upon uninstallation.
 *
 * @package rnmoji
 */

declare(strict_types=1);

// Exit if accessed directly.
defined('WP_UNINSTALL_PLUGIN') || exit;

/**
 * Handles the uninstallation process of the rnmoji plugin.
 *
 * Deletes all files and directories associated with the plugin.
 *
 * @return void
 */
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
            $action = $fileinfo->isDir() ? 'rmdir' : 'unlink';
            $action($fileinfo->getRealPath());
        }

        rmdir($plugin_dir);
    }
}

rnmoji_uninstall();
