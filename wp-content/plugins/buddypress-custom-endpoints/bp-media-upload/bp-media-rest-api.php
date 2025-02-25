<?php
/*
Plugin Name: Buddypress media
Plugin URI:  https://yourwebsite.com/
Description: A custom plugin to getting uploading status
Version:     1.0
Author:      Shivam Jha
Author URI:  https://yourwebsite.com/
License:     GPL2
*/
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function get_video_compression_status($request) {
    $post_id = $request['post_id'];
    $status = get_post_meta($post_id, 'compression_status', true);

    return new WP_REST_Response(['status' => $status], 200);
}

add_action('rest_api_init', function () {
    register_rest_route('buddypress/v1', '/video-compression-status/(?P<post_id>\d+)', [
        'methods' => 'GET',
        'callback' => 'get_video_compression_status',
        'permission_callback' => '__return_true'
    ]);
    register_rest_route('buddypress/v1', '/upload-progress/(?P<post_id>\d+)', [
        'methods' => 'GET',
        'callback' => 'get_upload_progress',
        'permission_callback' => '__return_true', // Adjust permissions as needed
    ]);
});

/**
 * Retrieve the upload progress for a given post ID.
 *
 * @param WP_REST_Request $request The REST request.
 * @return WP_REST_Response|WP_Error The REST response or error.
 */
function get_upload_progress(WP_REST_Request $request)
{
    $post_id = intval($request['post_id']);

    if (!$post_id || !get_post($post_id)) {
        return new WP_Error('invalid_post', 'Invalid post ID', ['status' => 404]);
    }

    // Retrieve the progress meta
    $progress = get_post_meta($post_id, '_upload_progress', true);

    if ($progress === '') {
        return new WP_Error('no_progress', 'No upload progress found for this post', ['status' => 404]);
    }

    return new WP_REST_Response([
        'post_id'  => $post_id,
        'progress' => $progress,
    ], 200);
}
