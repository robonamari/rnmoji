<?php
declare(strict_types=1);

/**
 * Handle deleting an uploaded emoji file.
 *
 * @return void
 */
function rnmoji_delete_emoji(): void {
    if (!current_user_can('manage_options') || !isset($_GET['file'])) {
        echo '<div class="error"><p>' .
            esc_html__('Unauthorized access.', 'rnmoji') .
            '</p></div>';
        return;
    }

    $file_path = RNMOJI_UPLOAD_DIR . basename($_GET['file']);
    if (file_exists($file_path)) {
        unlink($file_path);
        wp_redirect(add_query_arg(
            'message',
            'emoji_deleted',
            admin_url('plugins.php?page=rnmoji-settings')
        ));
        exit();
    }

    echo '<div class="error"><p>' .
        esc_html__('File not found.', 'rnmoji') .
        '</p></div>';
}
