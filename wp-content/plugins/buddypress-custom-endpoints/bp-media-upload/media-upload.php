<?php

// function handle_media_upload_background($post_id, $media_files) {
//     $s3 = get_s3_client();
//     $uploaded_media = handle_media_upload($post_id, $media_files, 'user_post');

//     if (is_wp_error($uploaded_media)) {
//         // Update post with error status if upload fails
//         wp_update_post([
//             'ID' => $post_id,
//             'post_status' => 'error',
//             'post_content' => 'Media upload failed: ' . $uploaded_media->get_error_message(),
//         ]);
//         return;
//     }

//     // Update post status to 'publish' when upload is successful
//     wp_update_post(['ID' => $post_id, 'post_status' => 'publish']);

//     // Notify followers after publishing
//     $user_id = get_post_field('post_author', $post_id);
//     $followers = get_followers_list($user_id);
//     foreach ($followers as $follower) {
//         send_new_post_notification($follower['id'], $post_id, $user_id);
//     }
// }
// add_action('handle_media_upload_background', 'handle_media_upload_background', 10, 2);

/**
 * Handle media uploads for a post.
 *
 * @param int $post_id The ID of the post to attach the media to.
 * @param array $media_files The array of files to upload.
 * @param string $context The context for meta keys (e.g., 'user_post', 'vehicle_post').
 * @return WP_Error|array An array of attachment IDs on success, or a WP_Error object on failure.
 */
function handle_media_upload($post_id, $media_files, $context)
{
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $allowed_mb = 500; // Max file size in MB
    $max_file_size = $allowed_mb * 1024 * 1024; // Convert to bytes
    $allowed_mime_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/quicktime', 'image/avif');
    $uploaded_media = [];

    $s3 = get_s3_client(); // Securely get the S3 client

    foreach ($media_files['name'] as $index => $name) {
        if ($media_files['name'][$index]) {
            $_FILES['upload_media'] = [
                'name' => $media_files['name'][$index],
                'type' => $media_files['type'][$index],
                'tmp_name' => $media_files['tmp_name'][$index],
                'error' => $media_files['error'][$index],
                'size' => $media_files['size'][$index],
            ];
            // Validate the file first (size and type)
            $validation_result = validate_media_file($_FILES['upload_media'], $max_file_size, $allowed_mime_types);
            if (is_wp_error($validation_result)) {
                return $validation_result; // Return error if validation fails
            }
            $upload_result = process_file_upload($_FILES['upload_media'], $post_id, $context, $s3);
            if (is_wp_error($upload_result)) {
                return $upload_result;
            }
            // // If the upload is a video, initiate MediaConvert job
            // if ($upload_result['type'] === 'video') {
            //     $thumbnail_result = generate_video_thumbnail_and_upload($upload_result, $post_id, $context, $s3);
            //     if (is_wp_error($thumbnail_result)) {
            //         return $thumbnail_result;
            //     }
            //     $upload_result['thumbnail_url'] = $thumbnail_result['url'];
            // }


            $uploaded_media[] = $upload_result;
        }
    }

    return $uploaded_media;
}

function validate_media_file($file, $max_file_size, $allowed_mime_types)
{
    // Check file size
    if ($file['size'] > $max_file_size) {
        return new WP_Error('file_size_exceeded', "File size exceeds the maximum limit of " . ($max_file_size / 1024 / 1024) . "MB.", ['status' => 400]);
    }

    // Check PHP upload limits
    $php_max_size = min(ini_get('upload_max_filesize'), ini_get('post_max_size'));
    if ($file['size'] > wp_convert_hr_to_bytes($php_max_size)) {
        return new WP_Error('php_limit_exceeded', "File size exceeds PHP upload limits of $php_max_size.", ['status' => 400]);
    }

    // Validate mime type
    $file_type = wp_check_filetype($file['name']);
    if (!in_array($file_type['type'], $allowed_mime_types)) {
        return new WP_Error('invalid_file_type', "Invalid file type.", ['status' => 400]);
    }

    return true; // Validation passed
}

function generate_video_thumbnail_and_upload($upload_result, $post_id, $context, $s3)
{
    $video_path = $_FILES['upload_media']['tmp_name'];
    $thumbnail_path = create_video_thumbnail($video_path, sys_get_temp_dir());
    if (is_wp_error($thumbnail_path)) {
        return $thumbnail_path;
    }

    $thumbnail_upload_result = upload_to_s3(
        ['name' => basename($thumbnail_path), 'tmp_name' => $thumbnail_path],
        $post_id,
        $context . '_thumbnail',  // Use a unique context key for thumbnail
        $s3,
        'image'
    );

    if (!is_wp_error($thumbnail_upload_result)) {
        add_post_meta($post_id, "{$context}_thumbnail_url", $thumbnail_upload_result['url'], true);
    }

    // Clean up temporary thumbnail file
    unlink($thumbnail_path);

    return $thumbnail_upload_result;
}
function process_file_upload($file, $post_id, $context, $s3)
{
    error_log("file type : - " . json_encode($file));
    $file_type = wp_check_filetype($file['name']);
    $is_image = strpos($file_type['type'], 'image') !== false;
    $is_video = strpos($file_type['type'], 'video') !== false;
    if ($is_image) {
        $optimized_path = optimize_image($file['tmp_name']);
        if (!$optimized_path || !file_exists($optimized_path)) {
            return new WP_Error('optimization_failed', "Image optimization failed or file path is invalid.", ['status' => 500]);
        }
        $file['tmp_name'] = $optimized_path;
        return upload_to_s3($file, $post_id, $context, $s3, 'image');
    }

    $is_video = strpos($file_type['type'], 'video') !== false;
    if ($is_video) {
        // $compressed_path = compress_video_background($post_id, $file);
        $compressed_path = compress_video($file['tmp_name']); // Assumes `compress_video` is defined as in your code

        // $compressed_path = enqueue_video_for_compression($post_id, $file);
        if (!$compressed_path || !file_exists($compressed_path)) {
            return new WP_Error('compression_failed', "Video compression failed or file path is invalid.", ['status' => 500]);
        }
        $file['tmp_name'] = $compressed_path;
        return upload_to_s3($file, $post_id, $context, $s3, 'video');
    }

    return new WP_Error('unsupported_media', "Unsupported media type.", ['status' => 400]);
}

function upload_to_s3($file, $post_id, $context, $s3, $type)
{
    $bucket = BUCKET_NAME;
    // Get file extension
    $file_info = pathinfo($file['name']);
    $extension = isset($file_info['extension']) ? '.' . $file_info['extension'] : '';

    // Generate a unique file name with a timestamp and random string
    $unique_name = $file_info['filename'] . '-' . time() . '-' . wp_generate_password(8, false) . $extension;
    if ($context !== 'chat-attachment') {
        $key = 'uploads/' . $unique_name;
    } else {
        $key = "$context/" . $unique_name;
    }
    // $key = 'uploads/' . $unique_name;
    $cloudfront_domain = 'https://d2goql3w04c7pg.cloudfront.net';
    $region = AWS_REGION;

    try {
        $result = $s3->putObject([
            'Bucket' => $bucket,
            'Key' => $key,
            'SourceFile' => $file['tmp_name'],
        ]);

        // Replace S3 URL with CloudFront URL
        $s3_url = $result['ObjectURL'];
        error_log($s3_url);
        $cloudfront_url = str_replace("https://{$bucket}.s3.{$region}.amazonaws.com", $cloudfront_domain, $s3_url);

        if ($context !== 'chat-attachment') {
            add_post_meta($post_id, "{$context}_media_type", $type);
            add_post_meta($post_id, "{$context}_media", $cloudfront_url);
        }


        return [
            'type' => $type,
            'url' => $cloudfront_url,
        ];
    } catch (Aws\S3\Exception\S3Exception $e) {
        return new WP_Error('s3_upload_failed', $e->getMessage(), ['status' => 500]);
    }
}

/**
 * Create a video thumbnail using AWS MediaConvert.
 *
 * @param string $video_url The URL of the uploaded video in S3.
 * @param object $s3 The S3 client.
 * @return string|WP_Error The URL of the generated thumbnail or an error.
 */

function create_video_thumbnail($file_path, $output_path)
{
    // Set the time position (e.g., at 1 second) for the thumbnail
    $thumbnail_path = $output_path . '/' . pathinfo($file_path, PATHINFO_FILENAME) . '-thumbnail.webp';
    $command = sprintf("ffmpeg -y -i %s -ss 00:00:01 -vframes 1 %s", escapeshellarg($file_path), escapeshellarg($thumbnail_path));

    $output = [];
    $return_var = 0;
    exec($command . ' 2>&1', $output, $return_var);

    if ($return_var !== 0) {
        return new WP_Error('thumbnail_generation_failed', 'Thumbnail generation failed. Check server logs for more details.', ['status' => 500]);
    }

    return $thumbnail_path;
}


function optimize_image($file_path)
{
    // Check if Imagick is installed and enabled
    if (!class_exists('Imagick')) {
        return new WP_Error('imagick_not_found', 'Imagick is not available on the server.', ['status' => 500]);
    }

    try {
        $imagick = new Imagick($file_path);

        // Convert to WebP format
        $imagick->setImageFormat('webp');

        // Optionally set the quality for the WebP image (0 to 100)
        $imagick->setImageCompressionQuality(60); // Adjust quality as needed
        // Define the optimized file path
        $optimized_path = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $file_path);

        // Write the optimized image to the new path
        $imagick->writeImage($optimized_path);

        // Clean up Imagick resources
        $imagick->clear();
        $imagick->destroy();

        return $optimized_path; // Return the path of the optimized image
    } catch (ImagickException $e) {
        return new WP_Error('imagick_error', 'Image optimization failed: ' . $e->getMessage(), ['status' => 500]);
    }
}

function compress_video($file_path)
{
    $file_info = pathinfo($file_path);
    $compressed_path = $file_info['dirname'] . '/' . $file_info['filename'] . '-compressed.mp4';

    // Ensure the output file path is unique and writable
    if ($compressed_path === $file_path) {
        $compressed_path = $file_info['dirname'] . '/' . $file_info['filename'] . '-' . wp_generate_password(8, false) . '-compressed.mp4';
    }


    // $command = sprintf("ffmpeg -y -i %s -vcodec libx264 -crf 28 %s", escapeshellarg($file_path), escapeshellarg($compressed_path));
    // Compression command with lower quality and downscaling resolution
    $command = sprintf(
        "ffmpeg -y -i %s -vcodec libx264 -crf 30 -vf scale=1280:-2 -preset faster %s",
        escapeshellarg($file_path),
        escapeshellarg($compressed_path)
    );
    $output = [];
    $return_var = 0;
    exec($command . ' 2>&1', $output, $return_var);

    if ($return_var !== 0) {
        return new WP_Error('video_compression_failed', 'Video compression failed. Check server logs for more details.', ['status' => 500]);
    }
    return $compressed_path;
}

/**
 * Get the media associated with a post.
 *
 * @param int $post_id The ID of the post.
 * @param string $post_type The type of the post ('user_post' or 'vehicle_post').
 * @return array An array of media attachments.
 */

function get_post_media($post_id, $post_type)
{
    $media_meta_key = $post_type === 'user_post' ? 'user_post_media' : 'vehicle_post_media';
    $media_type_key = $post_type === 'user_post' ? 'user_post_media_type' : 'vehicle_post_media_type';
    $media_thumbnail_key = $post_type === 'user_post' ? 'user_post_thumbnail_url' : 'vehicle_post_thumbnail_url';

    $media_urls = get_post_meta($post_id, $media_meta_key, false); // Get all media URLs from S3
    $media_type = get_post_meta($post_id, $media_type_key, true);
    $media_thumbnail = get_post_meta($post_id, $media_thumbnail_key, true);
    $media = [];

    if (!empty($media_urls)) {
        foreach ($media_urls as $media_url) {
            if (filter_var($media_url, FILTER_VALIDATE_URL)) {
                // If it's a valid URL, assume it's an S3 URL
                $media[] = [
                    'type' => $media_type,
                    'url' => $media_url,
                    'thumbnail_url' => $media_thumbnail,
                ];
            } else {
                error_log("Invalid media URL: $media_url for post ID: $post_id"); // Log invalid media URL for debugging
            }
        }
    }

    return $media;
}
function send_socket_update($post_id, $data)
{
    $url = FRONT_APP_URL . "/update-progress"; // URL of the Socket.IO server
    // $url = "http://localhost:3000/update-progress"; // URL of the Socket.IO server

    $post_data = json_encode(array_merge(['postId' => $post_id], $data));
    error_log("post data : - " . json_encode($post_data));
    $formdata = $post_data;


    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $formdata,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    error_log(json_encode("response of the socket : - " . json_encode($response)));

    if (is_wp_error($response)) {
        error_log('Failed to send progress update to Socket.IO: ' . $response->get_error_message());
    }
}

function upload_single_media_to_s3($file, $context)
{
    // Securely get the S3 client
    $s3_client = get_s3_client();

    if (!$file) {
        return new WP_Error('no_file', 'No file provided for upload.');
    }

    $file_path = $file['tmp_name'];
    $file_info = pathinfo($file['name']);
    $extension = isset($file_info['extension']) ? '.' . $file_info['extension'] : '';
    $unique_name = $file_info['filename'] . '-' . time() . '-' . wp_generate_password(8, false) . $extension;

    try {
        // Upload to S3
        $result = $s3_client->putObject([
            'Bucket' => BUCKET_NAME,
            'Key' => "$context/" . $unique_name,
            'SourceFile' => $file_path,
        ]);

        // Get S3 URL
        $file_url = $result->get('ObjectURL');


        return $file_url; // 
    } catch (Exception $e) {
        return new WP_Error('s3_upload_error', 'Failed to upload to S3: ' . $e->getMessage());
    }
}