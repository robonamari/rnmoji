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
