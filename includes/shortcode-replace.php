<?php
declare(strict_types=1);

/**
 * Replace emoji shortcodes in comments with corresponding emoji images.
 *
 * Scans the emoji upload directory and replaces occurrences of :emoji_name:
 * in comment text with an <img> tag pointing to the emoji image.
 *
 * @param string $comment_text The original comment text.
 * @return string The comment text with emoji shortcodes replaced by images.
 */
add_filter('comment_text', function (string $comment_text): string {
    $emoji_dir = rtrim(RNMOJI_UPLOAD_URL, '/') . '/';

    $emoji_files = scandir(RNMOJI_UPLOAD_DIR);
    if ($emoji_files === false) {
        return $comment_text;
    }

    foreach ($emoji_files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $name = pathinfo($file, PATHINFO_FILENAME);
        $url = esc_url($emoji_dir . $file);

        $comment_text = str_replace(
            ":$name:",
            sprintf(
                '<img src="%s" alt="%s" title="%s" width="20" height="20" loading="lazy" decoding="async" />',
                $url,
                $name,
                $name
            ),
            $comment_text
        );
    }

    return $comment_text;
});
