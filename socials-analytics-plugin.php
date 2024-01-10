<?php
/*
Plugin Name: Socials Analytics Plugin
Description: Plugin to process Social platforms JSON data.
Version: 2.1
Author: IMaeots
*/

// Include your model.php file
require_once(plugin_dir_path(__FILE__) . 'JSONProcessorTikTok.php');
require_once(plugin_dir_path(__FILE__) . 'JSONProcessorInstagram.php');
require_once(plugin_dir_path(__FILE__) . 'JSONProcessorX.php');

/*
 * Helper functions that help :)
 */
function display_error_msg($message): void
{
    ?>
    <script>
        var errorMessage = "<?php echo addslashes(esc_js($message)); ?>";
        alert(errorMessage);
    </script>
    <?php
}

function findFiles($dir, $type = 'json'): array
{
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    $requiredFiles = [];
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === $type) {
            $requiredFiles[] = $file->getPathname();
        }
    }
    return $requiredFiles;
}

// Remove the extracted directory and its contents
function rrmdir($dir): void
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir.'/'.$object)) {
                    rrmdir($dir.'/'.$object);
                } else {
                    unlink($dir.'/'.$object);
                }
            }
        }
        rmdir($dir);
    }
}


add_action('init', 'handle_upload_actions');
function handle_upload_actions() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            $action = $_POST['action'];
            if ($action === 'handle_instagram_upload') {
                handle_instagram_upload();
            } elseif ($action === 'handle_tiktok_upload') {
                handle_tiktok_upload();
            } elseif ($action === 'handle_x_upload') {
                handle_x_upload();
            }
        }
    }
}


// Lower level action handles for file uploads.
function handle_instagram_upload(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['zip_file'])) {
        $file = $_FILES['zip_file'];

        // File size check
        $maxFileSize = 512 * 1024 * 1024; // 0,5 Gigabyte
        if ($file['size'] > $maxFileSize) {
            $msg = "File size exceeds the allowed limit. Maximum file size is 0,5GB.";
            // Display error message
            display_error_msg($msg);
        }
        // Check if a file was uploaded
        if ($file['error'] === UPLOAD_ERR_OK) {
            $zip = new ZipArchive;

            // Path to the uploaded zip file
            $zipFilePath = $_FILES['zip_file']['tmp_name'];

            // Directory where you want to extract the zip contents
            $extractPath = 'instagram-extracted/';

            // Create the extraction directory if it doesn't exist
            if (!file_exists($extractPath)) {
                mkdir($extractPath, 0777, true);
            }

            // Open the zip file
            if ($zip->open($zipFilePath) === TRUE) {
                // Extract the contents of the zip file
                $zip->extractTo($extractPath);
                $zip->close();

                // Find JSON files within directories
                $jsonFiles = findFiles($extractPath);

                // Give JSON files to JSONProcessor and get processed data back
                $jsonProcessor = new JSONProcessorInstagram($jsonFiles);
                $dictData = $jsonProcessor->getInstagramDataAsDict();
                $slideshowTexts = $jsonProcessor->getSlideshowTexts();

                // Delete user input zip content
                rrmdir($extractPath);

                // Start a session, input data, redirect.
                session_start();
                $_SESSION['data'] = $dictData;
                $_SESSION['data_for_slideshow_json'] = $slideshowTexts;

                wp_redirect(site_url('/instagram-wrapped'));
                exit();


            } else {
                echo 'Failed to open the zip file';
            }
        } else {
            echo 'Error uploading zip';
        }


    } elseif (isset($_POST['start_demo'])) {
        // Make fake data, input into session, and redirect.
        $jsonProcessor = new JSONProcessorInstagram([""]);
        $dictData = $jsonProcessor->getInstagramDataAsDict();
        $slideshowTexts = $jsonProcessor->getSlideshowTexts();

        session_start();
        $_SESSION['data'] = $dictData;
        $_SESSION['data_for_slideshow_json'] = $slideshowTexts;

        wp_redirect(site_url('/instagram-wrapped'));
        exit();
    }
}


function handle_tiktok_upload(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['jsonFile'])) {
        $file = $_FILES['jsonFile'];

        // File size check
        $maxFileSize = 1024 * 1024 * 1024; // 1 gigabyte
        if ($file['size'] > $maxFileSize) {
            $msg = "File size exceeds the allowed limit. Maximum file size is 1GB.";
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
                            $jsonProcessor = new JSONProcessorTikTok($jsonData);
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
                $jsonProcessor = new JSONProcessorTikTok($jsonContents);

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
        $jsonProcessor = new JSONProcessorTikTok("");
        $dictData = $jsonProcessor->getTikTokDataAsDict();
        session_start();
        $_SESSION['data'] = $jsonProcessor->getTikTokDataAsDict();
        $_SESSION['data_for_slideshow_json'] = $jsonProcessor->getTikTokSlideshowTexts($dictData);

        wp_redirect(site_url('/tiktok-wrapped'));
        exit();
    }
}


function handle_facebook_upload(): void
{

}


function handle_x_upload(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['zip_file'])) {
        $file = $_FILES['zip_file'];

        // File size check
        $maxFileSize = 1024 * 1024 * 1024; // 1 Gigabyte
        if ($file['size'] > $maxFileSize) {
            $msg = "File size exceeds the allowed limit. Maximum file size is 1GB.";
            // Display error message
            display_error_msg($msg);
        }

        // Check if a file was uploaded
        if ($file['error'] === UPLOAD_ERR_OK) {
            $zip = new ZipArchive;

            // Path to the uploaded zip file
            $zipFilePath = $_FILES['zip_file']['tmp_name'];

            // Directory where you want to extract the zip contents
            $extractPath = 'x-extracted/';

            // Create the extraction directory if it doesn't exist
            if (!file_exists($extractPath)) {
                mkdir($extractPath, 0777, true);
            }

            // Open the zip file
            if ($zip->open($zipFilePath) === TRUE) {
                // Extract the contents of the zip file
                $zip->extractTo($extractPath);
                $zip->close();

                // Find JSON files within directories
                $jsFiles = findFiles($extractPath, 'js');
                // Give JSON files to JSONProcessor and get processed data back
                $jsonProcessor = new JSONProcessorX($jsFiles);
                $dictData = $jsonProcessor->getXDataAsDict();
                $slideshowTexts = $jsonProcessor->getSlideshowTexts();

                // Delete user input zip content
                rrmdir($extractPath);

                // Start a session, input data, redirect.
                session_start();
                $_SESSION['data'] = $dictData;
                $_SESSION['data_for_slideshow_json'] = $slideshowTexts;

                wp_redirect(site_url('/x-wrapped'));
                exit();


            } else {
                echo 'Failed to open the zip file';
            }
        } else {
            echo 'Error uploading zip';
        }


    } elseif (isset($_POST['start_demo'])) {
        // Make fake data, input into session, and redirect.
        $jsonProcessor = new JSONProcessorX([""]);
        $dictData = $jsonProcessor->getXDataAsDict();
        $slideshowTexts = $jsonProcessor->getSlideshowTexts();
        
        session_start();
        $_SESSION['data'] = $dictData;
        $_SESSION['data_for_slideshow_json'] = $slideshowTexts;

        wp_redirect(site_url('/x-wrapped'));
        exit();
    }
}
