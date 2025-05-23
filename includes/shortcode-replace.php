<?php
declare(strict_types=1);
/**
 * Replace emoji shortcodes in comments with image tags.
 *
 * @param string $comment_text
 * @return string
 */
add_filter('comment_text', function (string $comment_text): string {
    $emoji_dir = rtrim(RNMOJI_UPLOAD_URL, '/') . '/';
    foreach (scandir(RNMOJI_UPLOAD_DIR) as $file) {
        if (in_array($file, ['.', '..'], true)) {
            continue;
        }

        $name = pathinfo($file, PATHINFO_FILENAME);
        $url = esc_url($emoji_dir . $file);
        $comment_text = str_replace(
            ":$name:",
            "<img src=\"$url\" alt=\":$name:\" title=\":$name:\" width=\"20\" height=\"20\" />",
            $comment_text
        );
    }
    return $comment_text;
});
