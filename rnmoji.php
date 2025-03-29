<?php
/*
Plugin Name: rnmoji
Plugin URI: https://github.com/robonamari/rnmoji
Description: Add custom emojis to WordPress comment sections with rnmoji, allowing users to express themselves with fun and unique emoji reactions.
Version: 1.0.0
Requires PHP: 7.4
Author: robonamari
Author URI: https://robonamari.com
License: MIT
Text Domain: rnmoji
Domain Path: /languages
*/

// Exit if accessed directly
if (!defined("ABSPATH")) {
    exit();
}

define("RNMOJI_UPLOAD_DIR", plugin_dir_path(__FILE__) . "uploads/");
define("RNMOJI_UPLOAD_URL", plugin_dir_url(__FILE__) . "uploads/");

require_once plugin_dir_path(__FILE__) . "rnmoji-functions.php";

function rnmoji_load_textdomain()
{
    load_plugin_textdomain(
        "rnmoji",
        false,
        dirname(plugin_basename(__FILE__)) . "/languages/"
    );
}
add_action("plugins_loaded", "rnmoji_load_textdomain");

function rnmoji_plugin_action_links(array $links, string $file): array
{
    if ($file === plugin_basename(__FILE__)) {
        $settings_link =
            '<a href="' .
            admin_url("plugins.php?page=rnmoji-settings") .
            '">' .
            __("Settings", "rnmoji") .
            "</a>";
        array_unshift($links, $settings_link);
    }
    return $links;
}
add_filter("plugin_action_links", "rnmoji_plugin_action_links", 10, 2);

function rnmoji_add_plugin_settings_page(): void
{
    add_submenu_page(
        null,
        __("Plugin Settings rnmoji", "rnmoji"),
        __("rnmoji", "rnmoji"),
        "manage_options",
        "rnmoji-settings",
        "rnmoji_settings_page"
    );
}
add_action("admin_menu", "rnmoji_add_plugin_settings_page");
