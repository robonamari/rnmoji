<?php

function rnmoji_settings_page(): void
{
    // Handle emoji upload
    if (isset($_POST["upload_emoji"]) && !empty($_FILES["emoji_file"]["name"])) {
        handle_emoji_upload();
    }

    // Handle backup creation
    if (isset($_POST["backup_emoji"])) {
        create_backup();
    }

    // Handle backup upload
    if (isset($_POST["upload_backup"]) && !empty($_FILES["backup_file"]["name"])) {
        upload_backup();
    }

    // Handle emoji renaming
    if (isset($_POST["rename_emoji"]) && !empty($_POST["emoji_name"]) && isset($_POST["old_emoji"])) {
        rename_emoji();
    }

    $emoji_files = scandir(RNMOJI_UPLOAD_DIR);
    ?>
    <div class="wrap">
        <h1>Plugin Settings</h1>

        <!-- Emoji upload form -->
        <form method="post" enctype="multipart/form-data">
            <h2>Upload New Emoji</h2>
            <input type="file" name="emoji_file" required />
            <input type="submit" name="upload_emoji" value="Upload Emoji" class="button button-primary" />
        </form>

        <hr />

        <!-- Backup form -->
        <form method="post" enctype="multipart/form-data">
            <h2>Backup</h2>
            <input type="submit" name="backup_emoji" value="Create Backup" class="button button-primary" />
            <input type="file" name="backup_file" />
            <input type="submit" name="upload_backup" value="Upload Backup" class="button button-primary" />
        </form>

        <hr />

        <h2>Uploaded Emojis</h2>
        <table class="form-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Emoji Image</th>
                    <th>Emoji Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $index = 1;
            foreach ($emoji_files as $file) {
                if ($file !== "." && $file !== "..") {
                    $emoji_name = pathinfo($file, PATHINFO_FILENAME);
                    $emoji_url = RNMOJI_UPLOAD_URL . $file;
                    ?>
                    <tr>
                        <td><?= $index++ ?></td>
                        <td><img src="<?= $emoji_url ?>" alt="<?= $emoji_name ?>" style="width: 25px; height: 25px;" /></td>
                        <td>
                            <form method="post" style="display: flex; gap: 5px;">
                                <input type="hidden" name="old_emoji" value="<?= $file ?>" />
                                <input type="text" name="emoji_name" value="<?= $emoji_name ?>" required />
                                <input type="submit" name="rename_emoji" value="Rename" class="button button-secondary" />
                            </form>
                        </td>
                        <td>
                            <a href="<?= admin_url("admin-post.php?action=delete_emoji&file=" . urlencode($file)) ?>" class="button button-secondary">Delete</a>
                        </td>
                    </tr>
                    <?php
                }
            } ?>
            </tbody>
        </table>
    </div>
    <?php
}

function handle_emoji_upload(): void
{
    if (!file_exists(RNMOJI_UPLOAD_DIR)) {
        mkdir(RNMOJI_UPLOAD_DIR, 0777, true);
    }

    $file = $_FILES["emoji_file"];
    $target_path = RNMOJI_UPLOAD_DIR . basename($file["name"]);
    $emoji_name = pathinfo($file["name"], PATHINFO_FILENAME);

    if (file_exists($target_path)) {
        echo '<div class="error"><p>File name is already taken. Emoji not uploaded.</p></div>';
    } else {
        $uploaded_image = imagecreatefromstring(file_get_contents($file["tmp_name"]));

        if ($uploaded_image !== false) {
            $resized_image = imagescale($uploaded_image, 64, 64);
            $webp_path = RNMOJI_UPLOAD_DIR . $emoji_name . ".webp";

            if (imagewebp($resized_image, $webp_path)) {
                imagedestroy($resized_image);
                imagedestroy($uploaded_image);
                echo '<div class="updated"><p>Emoji uploaded and resized successfully.</p></div>';
            } else {
                echo '<div class="error"><p>Error converting image to WebP.</p></div>';
            }
        } else {
            echo '<div class="error"><p>Error loading image.</p></div>';
        }
    }
}

function create_backup(): void
{
    $zip = new ZipArchive();
    $backup_file = plugin_dir_path(__FILE__) . "emoji-backup.zip";

    if (file_exists($backup_file)) {
        unlink($backup_file);
    }

    if ($zip->open($backup_file, ZipArchive::CREATE) === true) {
        $files = scandir(RNMOJI_UPLOAD_DIR);

        foreach ($files as $file) {
            if ($file !== "." && $file !== "..") {
                $file_path = RNMOJI_UPLOAD_DIR . $file;

                if (is_file($file_path)) {
                    $zip->addFile($file_path, $file);
                }
            }
        }

        $zip->close();
        echo '<div class="updated"><p>Backup created successfully. <a href="' . plugin_dir_url(__FILE__) . "emoji-backup.zip" . '">Download Backup</a></p></div>';
    } else {
        echo '<div class="error"><p>Error creating backup.</p></div>';
    }
}

function upload_backup(): void
{
    if (!file_exists(RNMOJI_UPLOAD_DIR)) {
        mkdir(RNMOJI_UPLOAD_DIR, 0777, true);
    }

    $uploaded_backup = $_FILES["backup_file"];
    $zip = new ZipArchive();

    if ($zip->open($uploaded_backup["tmp_name"])) {
        $zip->extractTo(RNMOJI_UPLOAD_DIR);
        $zip->close();
        echo '<div class="updated"><p>Backup uploaded successfully.</p></div>';
    } else {
        echo '<div class="error"><p>Error uploading backup file.</p></div>';
    }
}

function rename_emoji(): void
{
    $old_emoji = $_POST["old_emoji"];
    $new_name = sanitize_text_field($_POST["emoji_name"]);
    $old_path = RNMOJI_UPLOAD_DIR . $old_emoji;
    $new_path = RNMOJI_UPLOAD_DIR . $new_name . "." . pathinfo($old_emoji, PATHINFO_EXTENSION);

    if (rename($old_path, $new_path)) {
        echo '<div class="updated"><p>Emoji renamed successfully.</p></div>';
    } else {
        echo '<div class="error"><p>Error renaming emoji.</p></div>';
    }
}

function rnmoji_delete_emoji(): void
{
    if (!current_user_can("manage_options") || !isset($_GET["file"])) {
        echo '<div class="error"><p>Unauthorized access.</p></div>';
        return;
    }

    $file_path = RNMOJI_UPLOAD_DIR . basename($_GET["file"]);

    if (file_exists($file_path)) {
        unlink($file_path);

        $redirect_url = add_query_arg("message", "emoji_deleted", admin_url("plugins.php?page=rnmoji-settings"));
        wp_redirect($redirect_url);
        exit();
    } else {
        echo '<div class="error"><p>File not found.</p></div>';
    }
}

add_action("admin_post_delete_emoji", "rnmoji_delete_emoji");

add_filter("comment_text", function ($comment_text) {
    $emoji_dir = RNMOJI_UPLOAD_URL;
    $files = scandir(RNMOJI_UPLOAD_DIR);
    $emojis = [];

    foreach ($files as $file) {
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        if ($extension) {
            $emoji_name = ":" . pathinfo($file, PATHINFO_FILENAME) . ":";
            $emojis[$emoji_name] = '<img src="' . $emoji_dir . "/" . $file . '" alt="' . $emoji_name . '" style="width: 25px; height: 25px;" />';
        }
    }
    return str_replace(array_keys($emojis), array_values($emojis), $comment_text);
});

?>
