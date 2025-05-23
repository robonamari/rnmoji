<?php
declare(strict_types=1);

/**
 * Handle the emoji upload process: validates, resizes to 64x64, and saves as WebP.
 *
 * @return void
 */
function upload_emoji(): void {
    $emoji_files = array_diff(scandir(RNMOJI_UPLOAD_DIR), ['.', '..']);
    if (count($emoji_files) >= 2000) {
        echo '<div class="error"><p>' .
            esc_html__('You have reached the maximum limit of 2000 emojis. Upload not allowed.', 'rnmoji') .
            '</p></div>';
        return;
    }

    $file = $_FILES['emoji_file'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        echo '<div class="error"><p>' .
            esc_html__('Error uploading file.', 'rnmoji') .
            '</p></div>';
        return;
    }

    if ($file['size'] > 256 * 1024) {
        echo '<div class="error"><p>' .
            esc_html__('File size must be 256 KB or less.', 'rnmoji') .
            '</p></div>';
        return;
    }

    $emoji_name = pathinfo($file['name'], PATHINFO_FILENAME);
    if (!preg_match('/^[A-Za-z0-9_]{2,}$/', $emoji_name)) {
        echo '<div class="error"><p>' .
            esc_html__('Emoji name must be at least 2 characters and only contain letters, numbers, and underscores.', 'rnmoji') .
            '</p></div>';
        return;
    }

    $target_path = RNMOJI_UPLOAD_DIR . $emoji_name . '.webp';
    if (file_exists($target_path)) {
        echo '<div class="error"><p>' .
            esc_html__('File name is already taken. Emoji not uploaded.', 'rnmoji') .
            '</p></div>';
        return;
    }

    $uploaded_image = @imagecreatefromstring(file_get_contents($file['tmp_name']));
    if (!$uploaded_image) {
        echo '<div class="error"><p>' .
            esc_html__('Error loading image.', 'rnmoji') .
            '</p></div>';
        return;
    }

    $resized_image = imagescale($uploaded_image, 64, 64);
    if (!$resized_image) {
        imagedestroy($uploaded_image);
        echo '<div class="error"><p>' .
            esc_html__('Error resizing image.', 'rnmoji') .
            '</p></div>';
        return;
    }

    if (!imagewebp($resized_image, $target_path)) {
        echo '<div class="error"><p>' .
            esc_html__('Error converting image to WebP.', 'rnmoji') .
            '</p></div>';
    } else {
        echo '<div class="updated"><p>' .
            esc_html__('Emoji uploaded and resized successfully.', 'rnmoji') .
            '</p></div>';
    }

    imagedestroy($uploaded_image);
    imagedestroy($resized_image);
}

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
