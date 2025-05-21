<?php
declare(strict_types=1);

/**
 * Display the settings page for the rnmoji plugin.
 *
 * @return void
 */
function rnmoji_settings_page(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['upload_emoji'], $_FILES['emoji_file']['name'])) {
            upload_emoji();
        }
        if (isset($_POST['backup_emoji'])) {
            backup_emoji();
        }
        if (isset($_POST['upload_backup'], $_FILES['backup_file']['name'])) {
            upload_backup();
        }
        if (isset($_POST['rename_emoji'], $_POST['emoji_name'], $_POST['old_emoji'])) {
            rename_emoji();
        }
    }

    $max_slots = 2000;
    $emoji_files = is_dir(RNMOJI_UPLOAD_DIR)
        ? array_diff(scandir(RNMOJI_UPLOAD_DIR), ['.', '..'])
        : [];

    $current_count = count($emoji_files);
    $available_slots = $max_slots - $current_count;
    ?>
    <div class="wrap">
        <h1><?= esc_html__('Plugin Settings', 'rnmoji'); ?></h1>

        <form method="post" enctype="multipart/form-data" novalidate>
            <h2><?= esc_html__('Upload New Emoji', 'rnmoji'); ?></h2>
            <p>
                <?= esc_html__('You can upload up to 2000 custom emojis.', 'rnmoji'); ?><br>
                <strong><?= esc_html__('Upload Requirements:', 'rnmoji'); ?></strong><br>
                - <?= esc_html__('File Type:', 'rnmoji'); ?> .jpg, .jpeg, .png, .gif, .webp<br>
                - <?= esc_html__('Max file size: 256 KB', 'rnmoji'); ?><br>
                - <?= esc_html__('Recommended dimensions: 62 x 62 pixels', 'rnmoji'); ?><br>
                - <?= esc_html__('Naming: Emoji names must be at least 2 characters long and can only contain alphanumeric characters and underscores', 'rnmoji'); ?>
            </p>
            <input
                type="file"
                name="emoji_file"
                accept=".jpg,.jpeg,.png,.gif,.webp"
                required
                aria-describedby="upload-emoji-requirements"
            />
            <input
                type="submit"
                name="upload_emoji"
                value="<?= esc_attr__('Upload Emoji', 'rnmoji'); ?>"
                class="button button-primary"
            />
        </form>

        <hr>

        <form method="post" novalidate>
            <h2><?= esc_html__('Backup', 'rnmoji'); ?></h2>
            <input
                type="submit"
                name="backup_emoji"
                value="<?= esc_attr__('Create Backup', 'rnmoji'); ?>"
                class="button button-primary"
            />
        </form>

        <form method="post" enctype="multipart/form-data" novalidate>
            <h2><?= esc_html__('Upload Backup', 'rnmoji'); ?></h2>
            <input
                type="file"
                name="backup_file"
                accept=".zip"
                required
            />
            <input
                type="submit"
                name="upload_backup"
                value="<?= esc_attr__('Upload Backup', 'rnmoji'); ?>"
                class="button button-primary"
            />
        </form>

        <hr>

        <h2
            style="display:flex; justify-content:space-between; align-items:center;"
        >
            <span>
                <?= sprintf(
                    esc_html__('Emojis - %s slots available', 'rnmoji'),
                    number_format_i18n($available_slots)
                ); ?>
            </span>
            <span>
                <input
                    type="search"
                    id="emoji_search"
                    placeholder="<?= esc_attr__('Search Emojis', 'rnmoji'); ?>"
                    aria-label="<?= esc_attr__('Search Emojis', 'rnmoji'); ?>"
                />
            </span>
        </h2>

        <table class="wp-list-table widefat fixed striped" role="grid" aria-describedby="emoji-table-description">
            <caption id="emoji-table-description" class="screen-reader-text">
                <?= esc_html__('List of uploaded emojis with actions', 'rnmoji'); ?>
            </caption>
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col"><?= esc_html__('Emoji', 'rnmoji'); ?></th>
                    <th scope="col"><?= esc_html__('Emoji Name', 'rnmoji'); ?></th>
                    <th scope="col"><?= esc_html__('Actions', 'rnmoji'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $index = 1;
                foreach ($emoji_files as $file) :
                    $name = pathinfo($file, PATHINFO_FILENAME);
                    $url = RNMOJI_UPLOAD_URL . $file;
                    ?>
                    <tr>
                        <td><?= $index++; ?></td>
                        <td><img src="<?= esc_url($url); ?>" alt="<?= esc_attr($name); ?>" width="25" height="25" loading="lazy"></td>
                        <td>
                            <form method="post" style="display:flex; gap:0.5rem;" novalidate>
                                <input type="hidden" name="old_emoji" value="<?= esc_attr($file); ?>">
                                <input
                                    type="text"
                                    name="emoji_name"
                                    value="<?= esc_attr($name); ?>"
                                    required
                                    pattern="[A-Za-z0-9_]{2,}"
                                    title="<?= esc_attr__('At least 2 chars, alphanumeric and underscores only', 'rnmoji'); ?>"
                                    aria-label="<?= esc_attr__('Rename emoji name', 'rnmoji'); ?>"
                                />
                                <input
                                    type="submit"
                                    name="rename_emoji"
                                    value="<?= esc_attr__('Rename', 'rnmoji'); ?>"
                                    class="button button-secondary"
                                />
                            </form>
                        </td>
                        <td>
                            <a
                                href="<?= esc_url(admin_url('admin-post.php?action=delete_emoji&file=' . urlencode($file))); ?>"
                                class="button button-secondary"
                                onclick="return confirm('<?= esc_js(__('Are you sure you want to delete this emoji?', 'rnmoji')); ?>');"
                            >
                                <?= esc_html__('Delete', 'rnmoji'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('emoji_search');
            const rows = document.querySelectorAll('table.wp-list-table tbody tr');

            searchInput.addEventListener('input', () => {
                const filter = searchInput.value.toLowerCase();
                rows.forEach(row => {
                    const input = row.querySelector('input[name="emoji_name"]');
                    if (!input) return;
                    const name = input.value.toLowerCase();
                    row.style.display = name.includes(filter) ? '' : 'none';
                });
            });
        });
        </script>
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
function upload_emoji(): void
{
    if (!file_exists(RNMOJI_UPLOAD_DIR) && !mkdir(RNMOJI_UPLOAD_DIR, 0777, true) && !is_dir(RNMOJI_UPLOAD_DIR)) {
        echo '<div class="error"><p>' . esc_html__('Unable to create upload directory.', 'rnmoji') . '</p></div>';
        return;
    }

    $emoji_files = array_diff(scandir(RNMOJI_UPLOAD_DIR), ['.', '..']);
    if (count($emoji_files) >= 2000) {
        echo '<div class="error"><p>' . esc_html__('You have reached the maximum limit of 2000 emojis. Upload not allowed.', 'rnmoji') . '</p></div>';
        return;
    }

    $file = $_FILES['emoji_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo '<div class="error"><p>' . esc_html__('Error uploading file.', 'rnmoji') . '</p></div>';
        return;
    }

    if ($file['size'] > 256 * 1024) {
        echo '<div class="error"><p>' . esc_html__('File size must be 256 KB or less.', 'rnmoji') . '</p></div>';
        return;
    }

    $emoji_name = pathinfo($file['name'], PATHINFO_FILENAME);

    if (!preg_match('/^[A-Za-z0-9_]{2,}$/', $emoji_name)) {
        echo '<div class="error"><p>' . esc_html__('Emoji name must be at least 2 characters and only contain letters, numbers, and underscores.', 'rnmoji') . '</p></div>';
        return;
    }

    $target_path = RNMOJI_UPLOAD_DIR . $emoji_name . '.webp';

    if (file_exists($target_path)) {
        echo '<div class="error"><p>' . esc_html__('File name is already taken. Emoji not uploaded.', 'rnmoji') . '</p></div>';
        return;
    }

    $uploaded_image = @imagecreatefromstring(file_get_contents($file['tmp_name']));
    if ($uploaded_image === false) {
        echo '<div class="error"><p>' . esc_html__('Error loading image.', 'rnmoji') . '</p></div>';
        return;
    }

    $resized_image = imagescale($uploaded_image, 64, 64);
    if ($resized_image === false) {
        echo '<div class="error"><p>' . esc_html__('Error resizing image.', 'rnmoji') . '</p></div>';
        imagedestroy($uploaded_image);
        return;
    }

    if (!imagewebp($resized_image, $target_path)) {
        echo '<div class="error"><p>' . esc_html__('Error converting image to WebP.', 'rnmoji') . '</p></div>';
    } else {
        echo '<div class="updated"><p>' . esc_html__('Emoji uploaded and resized successfully.', 'rnmoji') . '</p></div>';
    }

    imagedestroy($resized_image);
    imagedestroy($uploaded_image);
}

/**
 * Create a ZIP backup of uploaded emojis.
 *
 * @return void
 */
function backup_emoji(): void
{
    $backup_file = plugin_dir_path(__FILE__) . 'emoji-backup.zip';

    if (file_exists($backup_file) && !unlink($backup_file)) {
        echo '<div class="error"><p>' . esc_html__('Unable to delete existing backup file.', 'rnmoji') . '</p></div>';
        return;
    }

    $zip = new ZipArchive();
    if ($zip->open($backup_file, ZipArchive::CREATE) !== true) {
        echo '<div class="error"><p>' . esc_html__('Failed to create backup ZIP file.', 'rnmoji') . '</p></div>';
        return;
    }

    $files = array_diff(scandir(RNMOJI_UPLOAD_DIR), ['.', '..']);

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
            '<div class="updated"><p>Backup created successfully. <a href="%s" download>%s</a></p></div>',
            esc_url($backup_url),
            esc_html__('Download Backup', 'rnmoji')
        );
    } else {
        echo '<div class="error"><p>' . esc_html__('Backup creation failed.', 'rnmoji') . '</p></div>';
    }
}

/**
 * Upload and extract backup ZIP.
 *
 * @return void
 */
function upload_backup(): void
{
    if (!isset($_FILES['backup_file'])) {
        echo '<div class="error"><p>' . esc_html__('No backup file uploaded.', 'rnmoji') . '</p></div>';
        return;
    }

    $file = $_FILES['backup_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo '<div class="error"><p>' . esc_html__('Error uploading backup file.', 'rnmoji') . '</p></div>';
        return;
    }

    if (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'zip') {
        echo '<div class="error"><p>' . esc_html__('Uploaded file is not a ZIP archive.', 'rnmoji') . '</p></div>';
        return;
    }

    $tmp_path = $file['tmp_name'];
    $zip = new ZipArchive();

    if ($zip->open($tmp_path) !== true) {
        echo '<div class="error"><p>' . esc_html__('Failed to open ZIP archive.', 'rnmoji') . '</p></div>';
        return;
    }

    $extract_path = RNMOJI_UPLOAD_DIR;
    if (!is_dir($extract_path) && !mkdir($extract_path, 0777, true) && !is_dir($extract_path)) {
        echo '<div class="error"><p>' . esc_html__('Failed to create emoji directory.', 'rnmoji') . '</p></div>';
        $zip->close();
        return;
    }

    if (!$zip->extractTo($extract_path)) {
        echo '<div class="error"><p>' . esc_html__('Failed to extract backup files.', 'rnmoji') . '</p></div>';
        $zip->close();
        return;
    }

    $zip->close();

    echo '<div class="updated"><p>' . esc_html__('Backup uploaded and extracted successfully.', 'rnmoji') . '</p></div>';
}

/**
 * Rename an emoji file.
 *
 * @return void
 */
function rename_emoji(): void
{
    $old = $_POST['old_emoji'] ?? '';
    $new = $_POST['emoji_name'] ?? '';

    if (!preg_match('/^[A-Za-z0-9_]{2,}$/', $new)) {
        echo '<div class="error"><p>' . esc_html__('Emoji name must be at least 2 characters and contain only letters, numbers, and underscores.', 'rnmoji') . '</p></div>';
        return;
    }

    $old_path = RNMOJI_UPLOAD_DIR . $old;
    $new_path = RNMOJI_UPLOAD_DIR . $new . '.webp';

    if (!file_exists($old_path)) {
        echo '<div class="error"><p>' . esc_html__('Original emoji file does not exist.', 'rnmoji') . '</p></div>';
        return;
    }

    if (file_exists($new_path)) {
        echo '<div class="error"><p>' . esc_html__('New emoji name already exists.', 'rnmoji') . '</p></div>';
        return;
    }

    if (rename($old_path, $new_path)) {
        echo '<div class="updated"><p>' . esc_html__('Emoji renamed successfully.', 'rnmoji') . '</p></div>';
    } else {
        echo '<div class="error"><p>' . esc_html__('Failed to rename emoji.', 'rnmoji') . '</p></div>';
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
