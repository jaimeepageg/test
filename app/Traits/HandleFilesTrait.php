<?php

namespace Ceremonies\Traits;

trait HandleFilesTrait
{

    /**
     * Borrowed from https://www.php.net/manual/en/reserved.variables.files.php#109958
     * to make the $_FILES easier to work with.
     *
     * @return array
     */
    private function getRequestFiles(): array
    {

        $result = array(
            'featured_image' => $_FILES['featured_image'],
        );

        // Logo isn't always present
        if (isset($_FILES['logo'])){
            $result['logo'] = $_FILES['logo'];
        }

        foreach($_FILES['gallery'] as $key1 => $value1) {
            foreach($value1 as $key2 => $value2) {
                $result['gallery'][$key2][$key1] = $value2;
            }
        }

        return $result;

    }

    /**
     * Borrwed from https://gist.github.com/hissy/7352933 to
     * handle uploading files to the WP media library.
     *
     * @param $file
     * @return int
     */
    private function addFileToWordPress($file) : int
    {

        $filename = basename($file['name']);
        $upload_file = wp_upload_bits($filename, null, file_get_contents($file['tmp_name']));

        if (!$upload_file['error']) {

            $wp_filetype = wp_check_filetype($filename, null);
            $attachment = array(
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
                'post_content' => '',
                'post_status' => 'inherit'
            );

            $attachment_id = wp_insert_attachment( $attachment, $upload_file['file']);

            if (is_wp_error($attachment_id)) {
                throw new \Exception($attachment_id->get_error_message());
            } else {
                require_once(ABSPATH . "wp-admin" . '/includes/image.php');
                $attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] );
                wp_update_attachment_metadata( $attachment_id,  $attachment_data );
            }

            return $attachment_id;

        } else {
            throw new \Exception('Failed to upload file.');
        }



    }

    private function addToFilesystem($fileName, $tmpPath) {
        $fullPath = CER_UPLOADS_ROOT . 'choices/' . $fileName;
        $completed = move_uploaded_file($tmpPath, $fullPath);
        return $completed ? $fullPath : false;
    }

    private function deleteFile($fileName)
    {
        $fullPath = CER_UPLOADS_ROOT . $fileName;
        return unlink($fullPath);
    }

    /**
     * Checks if a directory exists, if it doesn't then
     * creates it.
     *
     * @param $directory
     * @return void
     */
    private function directoryExists($directory) {
        $fullPath = CER_UPLOADS_ROOT . $directory . '/';
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0777, true);
        }
    }

}