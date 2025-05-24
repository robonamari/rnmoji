<?php
declare(strict_types=1);

/**
 * Create a ZIP backup of all uploaded emojis.
 *
 * @return void
 */
function backup_emoji(): void {
    $backup_file = plugin_dir_path(__FILE__) . 'emoji-backup.zip';

    if (file_exists($backup_file) && !unlink($backup_file)) {
        echo '<div class="error"><p>' .
            esc_html__('Unable to delete existing backup file.', 'rnmoji') .
            '</p></div>';
        return;
    }

    $zip = new ZipArchive();
    if ($zip->open($backup_file, ZipArchive::CREATE) !== true) {
        echo '<div class="error"><p>' .
            esc_html__('Failed to create backup ZIP file.', 'rnmoji') .
            '</p></div>';
        return;
    }

    $files = array_diff(scandir(RNMOJI_UPLOAD_DIR), ['.', '..']);
    if (empty($files)) {
        echo '<div class="notice notice-warning"><p>' .
            esc_html__('No emoji files found to back up.', 'rnmoji') .
            '</p></div>';
        return;
    }

    foreach ($files as $file) {
        $full_path = RNMOJI_UPLOAD_DIR . $file;
        if (is_file($full_path)) {
            $zip->addFile($full_path, $file);
        }
    }

    $zip->close();

    if (file_exists($backup_file)) {
        $backup_url = plugin_dir_url(__FILE__) . 'emoji-backup.zip';
        printf(
            '<div class="updated"><p>%s <a href="%s" download>%s</a></p></div>',
            esc_html__('Backup created successfully.', 'rnmoji'),
            esc_url($backup_url),
            esc_html__('Download Backup', 'rnmoji')
        );
    } else {
        echo '<div class="error"><p>' .
            esc_html__('Backup creation failed.', 'rnmoji') .
            '</p></div>';
    }
}
