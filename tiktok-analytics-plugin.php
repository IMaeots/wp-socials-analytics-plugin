<?php
/*
Plugin Name: TikTok Analytics Plugin
Description: Plugin to process TikTok JSON data.
Version: 1.1.1
Author: IMaeots
*/

// Include your model.php file
require_once(plugin_dir_path(__FILE__) . 'JSONProcessor.php');


// Hook into WordPress action for handling file uploads
add_action('init', 'handle_tiktok_upload');

function handle_tiktok_upload(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['jsonFile'])) {
        $file = $_FILES['jsonFile'];

        // File size check
        $maxFileSize = 10 * 1024 * 1024; // 10 megabytes
        if ($file['size'] > $maxFileSize) {
            $msg = "File size exceeds the allowed limit. Maximum file size is 10MB.";
            // Display error message
            display_error_msg($msg);
        }

        // Check for errors during upload
        if ($file['error'] === UPLOAD_ERR_OK) {
            $fileExtension = pathinfo($_FILES["jsonFile"]["name"], PATHINFO_EXTENSION);
            $jsonData = null;

            // Check the file extension
            if ($fileExtension === "zip") {
                $zip = new ZipArchive;
                if ($zip->open($_FILES["jsonFile"]["tmp_name"]) === true) {
                    // Collect the number of files that are not mac metadata.
                    $numFiles = 0;
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $fileInfo = $zip->statIndex($i);
                        if (!str_starts_with($fileInfo['name'], '__MACOSX/')) {
                            continue;
                        }

                        $numFiles++;
                    }
                    // If there is only 1 file continue.
                    if ($numFiles === 1 or $zip->numFiles === 1) {
                        $filename = $zip->getNameIndex(0);
                        $newFileExtension = pathinfo($filename, PATHINFO_EXTENSION);
                        if ($newFileExtension === "json") {
                            $jsonData = $zip->getFromIndex(0);
                            $zip->close();

                            // Perform stuff with the json file
                            $jsonProcessor = new JSONProcessor($jsonData);
                            $dictData = $jsonProcessor->getTikTokDataAsDict();
                            $slideshowTexts = $jsonProcessor->getTikTokSlideshowTexts($dictData);

                            session_start();
                            $_SESSION['data'] = $dictData;
                            $_SESSION['data_for_slideshow_json'] = $slideshowTexts;

                            // Redirect to a page for displaying data
                            wp_redirect(site_url('/tiktok-wrapped'));
                            exit();
                        } else {
                            $msg = "ZIP file does not contain a JSON file.";
                            display_error_msg($msg);
                        }
                    } else {
                        $msg = "ZIP file should contain only one file.";
                        display_error_msg($msg);
                    }
                } else {
                    $msg = "Unable to open the ZIP file.";
                    display_error_msg($msg);
                }
            } elseif ($fileExtension === "json") {
                // Handle the case where it's a JSON file
                $jsonContents = file_get_contents($_FILES["jsonFile"]["tmp_name"]);
                $jsonProcessor = new JSONProcessor($jsonContents);

                // Delete the uploaded file after giving it to processor.
                unlink($_FILES['jsonFile']['tmp_name']);

                // Get wished data.
                $dictData = $jsonProcessor->getTikTokDataAsDict();
                $slideshowTexts = $jsonProcessor->getTikTokSlideshowTexts($dictData);

                // Make a session to transport data.
                session_start();
                $_SESSION['data'] = $dictData;
                $_SESSION['data_for_slideshow_json'] = $slideshowTexts;

                // Redirect to a page for displaying data
                wp_redirect(site_url('/tiktok-wrapped'));
                exit();
            } else {
                $msg = "Incorrect file extension. Must be zip or json!";
                display_error_msg($msg);
            }
        } else {
            $msg = "";
            if ($file['error'] == 1) {
                $msg = 'Uploaded file size exceeds limits.';
            }
            display_error_msg($msg);
        }
    } elseif (isset($_POST['start_demo'])) {
        $jsonProcessor = new JSONProcessor("");
        $dictData = $jsonProcessor->getTikTokDataAsDict();
        session_start();
        $_SESSION['data'] = $jsonProcessor->getTikTokDataAsDict();
        $_SESSION['data_for_slideshow_json'] = $jsonProcessor->getTikTokSlideshowTexts($dictData);

        wp_redirect(site_url('/tiktok-wrapped'));
        exit();
    }
}

// Helper function
function display_error_msg($message): void
{
    ?>
    <div class="error">
        <p><?php echo esc_html($message); ?></p>
    </div>
    <?php
}
