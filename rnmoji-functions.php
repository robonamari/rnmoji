<?php
declare(strict_types=1);

/**
 * Display the settings page for the rnmoji plugin.
 *
 * @return void
 */
function rnmoji_settings_page(): void
{
    if (isset($_POST['upload_emoji'], $_FILES['emoji_file']['name'])) {
        handle_emoji_upload();
    }
    if (isset($_POST['backup_emoji'])) {
        create_backup();
    }
    if (isset($_POST['upload_backup'], $_FILES['backup_file']['name'])) {
        upload_backup();
    }
    if (isset($_POST['rename_emoji'], $_POST['emoji_name'], $_POST['old_emoji'])) {
        rename_emoji();
    }

    $max_slots = 2000;

    if (is_dir(RNMOJI_UPLOAD_DIR)) {
        $emoji_files = array_diff(scandir(RNMOJI_UPLOAD_DIR), ['.', '..']);
    } else {
        $emoji_files = [];
    }

    $current_count = count($emoji_files);
    $available_slots = $max_slots - $current_count;
    ?>
    <div class="wrap">
        <h1><?php _e('Plugin Settings', 'rnmoji'); ?></h1>

        <form method="post" enctype="multipart/form-data">
            <h2><?php _e('Upload New Emoji', 'rnmoji'); ?></h2>
            <p>
                <?= __('You can upload up to 2000 custom emojis.', 'rnmoji'); ?><br />
                <strong><?php _e('Upload Requirements:', 'rnmoji'); ?></strong><br />
                - <?php _e('File Type:', 'rnmoji'); ?> .jpg, .jpeg, .png, .gif, .webp<br />
                - <?php _e('Max file size: 256 KB', 'rnmoji'); ?><br />
                - <?php _e('Recommended dimensions: 62 x 62 pixels', 'rnmoji'); ?><br />
                - <?php _e('Naming: Emoji names must be at least 2 characters long and can only contain alphanumeric characters and underscores', 'rnmoji'); ?>
            </p>
            <input type="file" name="emoji_file" accept=".jpg,.jpeg,.png,.gif,.webp" required />
            <input type="submit" name="upload_emoji" value="<?= esc_attr(__('Upload Emoji', 'rnmoji')); ?>" class="button button-primary" />
        </form>

        <hr />

        <form method="post" enctype="multipart/form-data">
            <h2><?php _e('Backup', 'rnmoji'); ?></h2>
            <input type="submit" name="backup_emoji" value="<?= esc_attr(__('Create Backup', 'rnmoji')); ?>" class="button button-primary" />
            <input type="file" name="backup_file" accept=".zip" required />
            <input type="submit" name="upload_backup" value="<?= esc_attr(__('Upload Backup', 'rnmoji')); ?>" class="button button-primary" />
        </form>

        <hr />

        <h2>
            <?php
            printf(
                __('Uploaded Emojis - (%s) slots available', 'rnmoji'),
                number_format_i18n($available_slots)
            );
            ?>
        </h2>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?php _e('Emoji Image', 'rnmoji'); ?></th>
                    <th><?php _e('Emoji Name', 'rnmoji'); ?></th>
                    <th><?php _e('Actions', 'rnmoji'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 1;
                foreach ($emoji_files as $file) :
                    $name = pathinfo($file, PATHINFO_FILENAME);
                    $url = RNMOJI_UPLOAD_URL . $file;
                ?>
                    <tr>
                        <td><?= $i++; ?></td>
                        <td><img src="<?= esc_url($url); ?>" alt="<?= esc_attr($name); ?>" width="25" height="25" /></td>
                        <td>
                            <form method="post" style="display:flex;gap:5px" novalidate>
                                <input type="hidden" name="old_emoji" value="<?= esc_attr($file); ?>" />
                                <input type="text" name="emoji_name" value="<?= esc_attr($name); ?>" required pattern="[A-Za-z0-9_]{2,}" title="At least 2 chars, alphanumeric and underscores only" />
                                <input type="submit" name="rename_emoji" value="<?= esc_attr(__('Rename', 'rnmoji')); ?>" class="button button-secondary" />
                            </form>
                        </td>
                        <td>
                            <a href="<?= esc_url(admin_url('admin-post.php?action=delete_emoji&file=' . urlencode($file))); ?>" class="button button-secondary">
                                <?= __('Delete', 'rnmoji'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/**
 * Handle the emoji upload process.
 *
 * Resizes uploaded emoji image to 64x64 and converts to WebP.
 *
 * @return void
 */
function handle_emoji_upload(): void
{
    if (!file_exists(RNMOJI_UPLOAD_DIR)) {
        mkdir(RNMOJI_UPLOAD_DIR, 0777, true);
    }

    $emoji_files = array_diff(scandir(RNMOJI_UPLOAD_DIR), ['.', '..']);
    if (count($emoji_files) >= 2000) {
        echo '<div class="error"><p>' . __('You have reached the maximum limit of 2000 emojis. Upload not allowed.', 'rnmoji') . '</p></div>';
        return;
    }

    $file = $_FILES['emoji_file'];

    if ($file['size'] > 256 * 1024) {
        echo '<div class="error"><p>' . __('File size must be 256 KB or less.', 'rnmoji') . '</p></div>';
        return;
    }

    $emoji_name = pathinfo($file['name'], PATHINFO_FILENAME);

    if (!preg_match('/^[A-Za-z0-9_]{2,}$/', $emoji_name)) {
        echo '<div class="error"><p>' . __('Emoji name must be at least 2 characters and only contain letters, numbers, and underscores.', 'rnmoji') . '</p></div>';
        return;
    }

    $target_path = RNMOJI_UPLOAD_DIR . $emoji_name . '.webp';

    if (file_exists($target_path)) {
        echo '<div class="error"><p>' . __('File name is already taken. Emoji not uploaded.', 'rnmoji') . '</p></div>';
        return;
    }

    $uploaded_image = @imagecreatefromstring(file_get_contents($file['tmp_name']));
    if ($uploaded_image === false) {
        echo '<div class="error"><p>' . __('Error loading image.', 'rnmoji') . '</p></div>';
        return;
    }

    $resized_image = imagescale($uploaded_image, 64, 64);
    if ($resized_image === false) {
        echo '<div class="error"><p>' . __('Error resizing image.', 'rnmoji') . '</p></div>';
        imagedestroy($uploaded_image);
        return;
    }

    if (!imagewebp($resized_image, $target_path)) {
        echo '<div class="error"><p>' . __('Error converting image to WebP.', 'rnmoji') . '</p></div>';
    } else {
        echo '<div class="updated"><p>' . __('Emoji uploaded and resized successfully.', 'rnmoji') . '</p></div>';
    }

    imagedestroy($resized_image);
    imagedestroy($uploaded_image);
}


/**
 * Create a ZIP backup of uploaded emojis.
 *
 * @return void
 */
function create_backup(): void
{
    $zip = new ZipArchive();
    $backup_file = plugin_dir_path(__FILE__) . 'emoji-backup.zip';

    if (file_exists($backup_file)) {
        unlink($backup_file);
    }

    if ($zip->open($backup_file, ZipArchive::CREATE) !== true) {
        echo '<div class="error"><p>' . __('Error creating backup.', 'rnmoji') . '</p></div>';
        return;
    }

    foreach (scandir(RNMOJI_UPLOAD_DIR) as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $file_path = RNMOJI_UPLOAD_DIR . $file;
        if (is_file($file_path)) {
            $zip->addFile($file_path, $file);
        }
    }

    $zip->close();

    echo '<div class="updated"><p>' . __('Backup created successfully.', 'rnmoji') .
        ' <a href="' . esc_url(plugin_dir_url(__FILE__) . 'emoji-backup.zip') . '">' .
        __('Download Backup', 'rnmoji') . '</a></p></div>';
}

/**
 * Upload and extract a ZIP backup of emojis.
 *
 * @return void
 */
function upload_backup(): void
{
    if (!file_exists(RNMOJI_UPLOAD_DIR)) {
        mkdir(RNMOJI_UPLOAD_DIR, 0777, true);
    }

    $uploaded_backup = $_FILES['backup_file'];
    $zip = new ZipArchive();

    if ($zip->open($uploaded_backup['tmp_name']) !== true) {
        echo '<div class="error"><p>' . __('Error uploading backup file.', 'rnmoji') . '</p></div>';
        return;
    }

    $zip->extractTo(RNMOJI_UPLOAD_DIR);
    $zip->close();

    echo '<div class="updated"><p>' . __('Backup uploaded successfully.', 'rnmoji') . '</p></div>';
}

/**
 * Rename an emoji file.
 *
 * @return void
 */
function rename_emoji(): void
{
    $old_emoji = $_POST['old_emoji'] ?? '';
    $new_name = sanitize_text_field($_POST['emoji_name'] ?? '');

    if ($old_emoji === '' || $new_name === '') {
        echo '<div class="error"><p>' . __('Invalid emoji name or old filename.', 'rnmoji') . '</p></div>';
        return;
    }

    $old_path = RNMOJI_UPLOAD_DIR . $old_emoji;
    $extension = pathinfo($old_emoji, PATHINFO_EXTENSION);
    $new_path = RNMOJI_UPLOAD_DIR . $new_name . '.' . $extension;

    if (!file_exists($old_path)) {
        echo '<div class="error"><p>' . __('Original emoji file not found.', 'rnmoji') . '</p></div>';
        return;
    }

    if (file_exists($new_path)) {
        echo '<div class="error"><p>' . __('New emoji name already exists.', 'rnmoji') . '</p></div>';
        return;
    }

    if (rename($old_path, $new_path)) {
        echo '<div class="updated"><p>' . __('Emoji renamed successfully.', 'rnmoji') . '</p></div>';
    } else {
        echo '<div class="error"><p>' . __("Error renaming emoji.", "rnmoji") . "</p></div>";
    }
}

/**
 * Delete an emoji file.
 *
 * @return void
 */
function rnmoji_delete_emoji(): void
{
    if (!current_user_can("manage_options") || !isset($_GET["file"])) {
        echo '<div class="error"><p>' . __("Unauthorized access.", "rnmoji") . "</p></div>";
        return;
    }

    $file_path = RNMOJI_UPLOAD_DIR . basename($_GET["file"]);

    if (file_exists($file_path)) {
        unlink($file_path);
        $redirect_url = add_query_arg("message", "emoji_deleted", admin_url("plugins.php?page=rnmoji-settings"));
        wp_redirect($redirect_url);
        exit();
    }

    echo '<div class="error"><p>' . __("File not found.", "rnmoji") . "</p></div>";
}

add_action("admin_post_delete_emoji", "rnmoji_delete_emoji");

/**
 * Replace emoji shortcodes with images in comments.
 *
 * @param string $comment_text Comment content.
 * @return string Comment content with emoji images.
 */
add_filter("comment_text", function (string $comment_text): string {
    $emoji_dir = rtrim(RNMOJI_UPLOAD_URL, '/') . '/';
    $files = scandir(RNMOJI_UPLOAD_DIR);
    foreach ($files as $file) {
        if (in_array($file, ['.', '..'], true)) {
            continue;
        }
        $name = pathinfo($file, PATHINFO_FILENAME);
        $url = esc_url($emoji_dir . $file);
        $comment_text = str_replace(":$name:", "<img src=\"$url\" alt=\":$name:\" title=\":$name:\" width=\"20\" height=\"20\" />", $comment_text);
    }
    return $comment_text;
});
