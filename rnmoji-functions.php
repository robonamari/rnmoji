<?php

function rnmoji_settings_page(): void
{
    // فرم آپلود ایموجی
    if (
        isset($_POST["upload_emoji"]) &&
        !empty($_FILES["emoji_file"]["name"])
    ) {
        if (!file_exists(RNMOJI_UPLOAD_DIR)) {
            mkdir(RNMOJI_UPLOAD_DIR, 0777, true);
        }

        $file = $_FILES["emoji_file"];
        $target_path = RNMOJI_UPLOAD_DIR . basename($file["name"]);
        $emoji_name = pathinfo($file["name"], PATHINFO_FILENAME);

        if (file_exists($target_path)) {
            echo '<div class="error"><p>نام فایل تکراری است. ایموجی آپلود نشد.</p></div>';
        } else {
            $uploaded_image = imagecreatefromstring(
                file_get_contents($file["tmp_name"])
            );

            if ($uploaded_image !== false) {
                $resized_image = imagescale($uploaded_image, 64, 64);
                $webp_path = RNMOJI_UPLOAD_DIR . $emoji_name . ".webp";

                if (imagewebp($resized_image, $webp_path)) {
                    imagedestroy($resized_image);
                    imagedestroy($uploaded_image);
                    echo '<div class="updated"><p>ایموجی با موفقیت آپلود و تغییر اندازه داده شد.</p></div>';
                } else {
                    echo '<div class="error"><p>خطا در تبدیل تصویر به WebP.</p></div>';
                }
            } else {
                echo '<div class="error"><p>خطا در بارگذاری تصویر.</p></div>';
            }
        }
    }

    // فرم بکاپ گیری ایموجی‌ها
    if (isset($_POST["backup_emoji"])) {
        $zip = new ZipArchive();
        $backup_file = RNMOJI_UPLOAD_DIR . "emoji-backup.zip";
        if ($zip->open($backup_file, ZipArchive::CREATE) === true) {
            $files = scandir(RNMOJI_UPLOAD_DIR);
            foreach ($files as $file) {
                if ($file !== "." && $file !== "..") {
                    $zip->addFile(RNMOJI_UPLOAD_DIR . $file, $file);
                }
            }
            $zip->close();
            echo '<div class="updated"><p>بکاپ با موفقیت تهیه شد. <a href="' .
                content_url("/uploads/rnmoji/emoji-backup.zip") .
                '">دانلود بکاپ</a></p></div>';
        } else {
            echo '<div class="error"><p>خطا در ایجاد بکاپ.</p></div>';
        }
    }

    // فرم آپلود بکاپ
    if (
        isset($_POST["upload_backup"]) &&
        !empty($_FILES["backup_file"]["name"])
    ) {
        if (!file_exists(RNMOJI_UPLOAD_DIR)) {
            mkdir(RNMOJI_UPLOAD_DIR, 0777, true);
        }
        $uploaded_backup = $_FILES["backup_file"];
        $zip = new ZipArchive();
        if ($zip->open($uploaded_backup["tmp_name"])) {
            $zip->extractTo(RNMOJI_UPLOAD_DIR);
            $zip->close();
            echo '<div class="updated"><p>بکاپ با موفقیت بارگذاری شد.</p></div>';
        } else {
            echo '<div class="error"><p>خطا در آپلود فایل بکاپ.</p></div>';
        }
    }

    // فرم تغییر نام ایموجی
    if (
        isset($_POST["rename_emoji"]) &&
        !empty($_POST["emoji_name"]) &&
        isset($_POST["old_emoji"])
    ) {
        $old_emoji = $_POST["old_emoji"];
        $new_name = sanitize_text_field($_POST["emoji_name"]);
        $old_path = RNMOJI_UPLOAD_DIR . $old_emoji;
        $new_path =
            RNMOJI_UPLOAD_DIR .
            $new_name .
            "." .
            pathinfo($old_emoji, PATHINFO_EXTENSION);

        if (rename($old_path, $new_path)) {
            echo '<div class="updated"><p>ایموجی با موفقیت تغییر نام یافت.</p></div>';
        } else {
            echo '<div class="error"><p>خطا در تغییر نام ایموجی.</p></div>';
        }
    }

    $emoji_files = scandir(RNMOJI_UPLOAD_DIR);
    ?>
    <div class="wrap">
        <h1>تنظیمات افزونه rnmoji</h1>

        <!-- فرم آپلود ایموجی -->
        <form method="post" enctype="multipart/form-data">
            <h2>آپلود ایموجی جدید</h2>
            <input type="file" name="emoji_file" required />
            <input type="submit" name="upload_emoji" value="آپلود ایموجی" class="button button-primary" />
        </form>

        <hr />

        <!-- فرم بکاپ گیری ایموجی‌ها -->
        <form method="post">
            <h2>بکاپ گیری ایموجی‌ها</h2>
            <input type="submit" name="backup_emoji" value="تهیه بکاپ" class="button button-secondary" />
        </form>

        <hr />

        <!-- فرم آپلود بکاپ -->
        <form method="post" enctype="multipart/form-data">
            <h2>آپلود بکاپ</h2>
            <input type="file" name="backup_file" />
            <input type="submit" name="upload_backup" value="آپلود بکاپ" class="button button-secondary" />
        </form>

        <hr />

        <h2>ایموجی‌های آپلود شده</h2>
        <table class="form-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>تصویر ایموجی</th>
                    <th>نام ایموجی</th>
                    <th>عملیات</th>
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
                                <input type="submit" name="rename_emoji" value="تغییر نام" class="button button-secondary" />
                            </form>
                        </td>
                        <td>
                            <a href="<?= admin_url(
                                "admin-post.php?action=delete_emoji&file=" .
                                    urlencode($file)
                            ) ?>" class="button button-secondary">حذف</a>
                        </td>
                    </tr>
                    <?php
                }
            }?>
            </tbody>
        </table>
    </div>
    <?php
}

add_filter("comment_text", function ($comment_text) {
    $emoji_dir = get_site_url() . "/wp-content/uploads/rnmoji";
    $files = scandir(RNMOJI_UPLOAD_DIR);
    $emojis = [];

    foreach ($files as $file) {
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        if ($extension) {
            $emoji_name = ":" . pathinfo($file, PATHINFO_FILENAME) . ":";
            $emojis[$emoji_name] =
                '<img src="' .
                $emoji_dir .
                "/" .
                $file .
                '" alt="' .
                $emoji_name .
                '" style="width: 25px; height: 25px;" />';
        }
    }
    return str_replace(
        array_keys($emojis),
        array_values($emojis),
        $comment_text
    );
});

function delete_emoji()
{
    if (!current_user_can("manage_options") || !isset($_GET["file"])) {
        echo '<div class="error"><p>دسترسی غیرمجاز.</p></div>';
        return;
    }

    $file_path = RNMOJI_UPLOAD_DIR . basename($_GET["file"]);

    if (file_exists($file_path)) {
        unlink($file_path);

        $redirect_url = add_query_arg(
            "message",
            "emoji_deleted",
            admin_url("plugins.php?page=rnmoji-settings")
        );
        wp_redirect($redirect_url);
        exit();
    } else {
        echo '<div class="error"><p>فایل یافت نشد.</p></div>';
    }
}
add_action("admin_post_delete_emoji", "delete_emoji");
?>
