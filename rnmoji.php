<?php
/**
 * Plugin Name:       rnmoji
 * Plugin URI:        https://github.com/robonamari/rnmoji
 * Description:       Add custom emojis to WordPress comment sections with rnmoji, allowing users to express themselves with fun and unique emoji reactions.
 * Version:           1.5.0
 * Requires PHP:      8.1
 * Requires at least: 6.8
 * Author:            robonamari
 * Author URI:        https://robonamari.com
 * License:           MIT
 * Text Domain:       rnmoji
 * Domain Path:       /languages
 */

declare(strict_types=1);

if (!defined("ABSPATH")) {
    exit();
}

define("RNMOJI_UPLOAD_DIR", plugin_dir_path(__FILE__) . "assets/emoji/");
define("RNMOJI_UPLOAD_URL", plugin_dir_url(__FILE__) . "assets/emoji/");

require_once __DIR__ . "/templates/settings-page.php";
/**
 * Create assets/emoji directory on plugin activation.
 */
function rnmoji_create_assets_folder(): void {
    $upload_dir = plugin_dir_path(__FILE__) . 'assets/emoji/';

    if (!file_exists($upload_dir)) {
        wp_mkdir_p($upload_dir);
    }
}

register_activation_hook(__FILE__, 'rnmoji_create_assets_folder');


/**
 * Add settings link to the plugin actions.
 *
 * @param array $links Existing plugin action links.
 * @return array Modified plugin action links.
 */
function rnmoji_plugin_action_links(array $links): array
{
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        esc_url(admin_url("admin.php?page=rnmoji-settings")),
        esc_html__("Settings", "rnmoji")
    );
    array_unshift($links, $settings_link);
    return $links;
}
add_filter(
    "plugin_action_links_" . plugin_basename(__FILE__),
    "rnmoji_plugin_action_links"
);

/**
 * Add submenu page for plugin settings.
 *
 * @return void
 */
function rnmoji_add_plugin_settings_page(): void
{
    add_submenu_page(
        null,
        esc_html__("Plugin Settings rnmoji", "rnmoji"),
        esc_html__("rnmoji", "rnmoji"),
        "manage_options",
        "rnmoji-settings",
        "rnmoji_settings_page"
    );
}
add_action("admin_menu", "rnmoji_add_plugin_settings_page");
