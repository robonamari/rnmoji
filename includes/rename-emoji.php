<?php
declare(strict_types=1);

/**
 * Rename an uploaded emoji.
 *
 * @return void
 */
function rename_emoji(): void {
    $old = $_POST['old_emoji'] ?? '';
    $new = $_POST['emoji_name'] ?? '';

    if (!preg_match('/^[A-Za-z0-9_]{2,}$/', $new)) {
        echo '<div class="error"><p>' .
            esc_html__('Emoji name must be at least 2 characters and contain only letters, numbers, and underscores.', 'rnmoji') .
            '</p></div>';
        return;
    }

    $old_path = RNMOJI_UPLOAD_DIR . $old;
    $new_path = RNMOJI_UPLOAD_DIR . $new . '.webp';

    if (!file_exists($old_path)) {
        echo '<div class="error"><p>' .
            esc_html__('Original emoji file does not exist.', 'rnmoji') .
            '</p></div>';
        return;
    }

    if (file_exists($new_path)) {
        echo '<div class="error"><p>' .
            esc_html__('New emoji name already exists.', 'rnmoji') .
            '</p></div>';
        return;
    }

    if (rename($old_path, $new_path)) {
        echo '<div class="updated"><p>' .
            esc_html__('Emoji renamed successfully.', 'rnmoji') .
            '</p></div>';
    } else {
        echo '<div class="error"><p>' .
            esc_html__('Failed to rename emoji.', 'rnmoji') .
            '</p></div>';
    }
}
