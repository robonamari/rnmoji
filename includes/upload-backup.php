<?php
declare(strict_types=1);

/**
 * Upload and extract a ZIP backup.
 *
 * @return void
 */
function upload_backup(): void {
    $file = $_FILES['backup_file'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        echo '<div class="error"><p>' .
            esc_html__('Error uploading backup file.', 'rnmoji') .
            '</p></div>';
        return;
    }

    if (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'zip') {
        echo '<div class="error"><p>' .
            esc_html__('Uploaded file is not a ZIP archive.', 'rnmoji') .
            '</p></div>';
        return;
    }

    $zip = new ZipArchive();
    if ($zip->open($file['tmp_name']) !== true) {
        echo '<div class="error"><p>' .
            esc_html__('Failed to open ZIP archive.', 'rnmoji') .
            '</p></div>';
        return;
    }

    if (!$zip->extractTo(RNMOJI_UPLOAD_DIR)) {
        echo '<div class="error"><p>' .
            esc_html__('Failed to extract backup files.', 'rnmoji') .
            '</p></div>';
        $zip->close();
        return;
    }

    $zip->close();

    echo '<div class="updated"><p>' .
        esc_html__('Backup uploaded and extracted successfully.', 'rnmoji') .
        '</p></div>';
}
