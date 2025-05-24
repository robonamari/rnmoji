<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/backup-emoji.php';
require_once __DIR__ . '/../includes/rename-emoji.php';
require_once __DIR__ . '/../includes/upload-backup.php';
require_once __DIR__ . '/../includes/upload-emoji.php';

/**
 * Display the settings page for the rnmoji plugin.
 *
 * Handles emoji upload, backup, rename, and displays the emoji list.
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
        if (
            isset(
                $_POST['rename_emoji'],
                $_POST['emoji_name'],
                $_POST['old_emoji']
            )
        ) {
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
        <h1><?= esc_html__('Plugin Settings', 'rnmoji') ?></h1>

        <form method="post" enctype="multipart/form-data" novalidate>
            <h2><?= esc_html__('Upload New Emoji', 'rnmoji') ?></h2>
            <p>
                <?= esc_html__('You can upload up to 2000 custom emojis.', 'rnmoji') ?><br>
                <strong><?= esc_html__('Upload Requirements:', 'rnmoji') ?></strong><br>
                - <?= esc_html__('File Type:', 'rnmoji') ?>
                <span style="direction:ltr; unicode-bidi:embed;">.jpg, .jpeg, .png, .gif, .webp</span><br>
                - <?= esc_html__('Max file size: 256 KB', 'rnmoji') ?><br>
                - <?= esc_html__('Recommended dimensions: 62 x 62 pixels', 'rnmoji') ?><br>
                - <?= esc_html__('Naming: Emoji names must be at least 2 characters long and can only contain alphanumeric characters and underscores', 'rnmoji') ?>
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
                value="<?= esc_attr__('Upload Emoji', 'rnmoji') ?>"
                class="button button-primary"
            />
        </form>

        <hr>

        <form method="post" novalidate>
            <h2><?= esc_html__('Backup', 'rnmoji') ?></h2>
            <input
                type="submit"
                name="backup_emoji"
                value="<?= esc_attr__('Create Backup', 'rnmoji') ?>"
                class="button button-primary"
            />
        </form>

        <form method="post" enctype="multipart/form-data" novalidate>
            <h2><?= esc_html__('Upload Backup', 'rnmoji') ?></h2>
            <input
                type="file"
                name="backup_file"
                accept=".zip"
                required
            />
            <input
                type="submit"
                name="upload_backup"
                value="<?= esc_attr__('Upload Backup', 'rnmoji') ?>"
                class="button button-primary"
            />
        </form>

        <hr>

        <h2 style="display:flex; justify-content:space-between; align-items:center;">
            <span>
                <?= sprintf(
                    esc_html__('Emojis - %s slots available', 'rnmoji'),
                    number_format_i18n($available_slots)
                ) ?>
            </span>
            <span>
                <input
                    type="search"
                    id="emoji_search"
                    placeholder="<?= esc_attr__('Search Emojis', 'rnmoji') ?>"
                    aria-label="<?= esc_attr__('Search Emojis', 'rnmoji') ?>"
                />
            </span>
        </h2>

        <table
            class="wp-list-table widefat fixed striped"
            role="grid"
            aria-describedby="emoji-table-description"
        >
            <caption id="emoji-table-description" class="screen-reader-text">
                <?= esc_html__('List of uploaded emojis with actions', 'rnmoji') ?>
            </caption>
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col"><?= esc_html__('Emoji', 'rnmoji') ?></th>
                    <th scope="col"><?= esc_html__('Emoji Name', 'rnmoji') ?></th>
                    <th scope="col"><?= esc_html__('Actions', 'rnmoji') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $index = 1;
                foreach ($emoji_files as $file):
                    $name = pathinfo($file, PATHINFO_FILENAME);
                    $url = RNMOJI_UPLOAD_URL . $file;
                ?>
                    <tr>
                        <td><?= $index++ ?></td>
                        <td>
                            <img
                                src="<?= esc_url($url) ?>"
                                alt="<?= esc_attr($name) ?>"
                                width="25"
                                height="25"
                                loading="lazy"
                            />
                        </td>
                        <td>
                            <form method="post" style="display:flex; gap:0.5rem;" novalidate>
                                <input type="hidden" name="old_emoji" value="<?= esc_attr($file) ?>">
                                <input
                                    type="text"
                                    name="emoji_name"
                                    value="<?= esc_attr($name) ?>"
                                    required
                                    pattern="[A-Za-z0-9_]{2,}"
                                    title="<?= esc_attr__('At least 2 chars, alphanumeric and underscores only', 'rnmoji') ?>"
                                    aria-label="<?= esc_attr__('Rename emoji name', 'rnmoji') ?>"
                                />
                                <input
                                    type="submit"
                                    name="rename_emoji"
                                    value="<?= esc_attr__('Rename', 'rnmoji') ?>"
                                    class="button button-secondary"
                                />
                            </form>
                        </td>
                        <td>
                            <a
                                href="<?= esc_url(admin_url('admin-post.php?action=delete_emoji&file=' . urlencode($file))) ?>"
                                class="button button-secondary"
                                onclick="return confirm('<?= esc_js(__('Are you sure you want to delete this emoji?', 'rnmoji')) ?>');"
                            >
                                <?= esc_html__('Delete', 'rnmoji') ?>
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
