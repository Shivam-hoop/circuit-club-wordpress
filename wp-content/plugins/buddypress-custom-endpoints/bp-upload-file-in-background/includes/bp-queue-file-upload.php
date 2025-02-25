<?php

add_action('rest_api_init', function () {
    register_rest_route('buddypress/v1', '/video-upload', array(
        'methods' => 'POST',
        'callback' => 'enqueue_video_upload',
        'permission_callback' => 'is_user_logged_in',
    ));
});

function enqueue_video_upload(WP_REST_Request $request) {
    $user_id = get_current_user_id();
    $file = isset($_FILES['video']) ? $_FILES['video'] : null;

    if (!$file) {
        return new WP_Error('no_file', 'No video file was uploaded', array('status' => 400));
    }

    // Perform basic file checks (size, type, etc.)
    $max_file_size = 50 * 1024 * 1024; // 50 MB
    $allowed_mime_types = array('video/mp4', 'video/mpeg', 'video/quicktime');

    if ($file['size'] > $max_file_size) {
        return new WP_Error('file_size_exceeded', 'File size exceeds the maximum limit of 50 MB', array('status' => 400));
    }

    if (!in_array($file['type'], $allowed_mime_types)) {
        return new WP_Error('invalid_file_type', 'Invalid file type. Only MP4, MPEG, and QuickTime are allowed', array('status' => 400));
    }

    // Save the file temporarily
    $upload_dir = wp_upload_dir();
    $temp_file_path = $upload_dir['path'] . '/' . $file['name'];

    if (!move_uploaded_file($file['tmp_name'], $temp_file_path)) {
        return new WP_Error('upload_failed', 'Failed to move uploaded file', array('status' => 500));
    }

    // Enqueue the background process
    as_enqueue_async_action('process_video_upload', array('file_path' => $temp_file_path, 'user_id' => $user_id));

    return new WP_REST_Response(array('success' => true, 'message' => 'Video upload has been enqueued for processing'), 200);
}
