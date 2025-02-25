<?php
add_action('rest_api_init', function () {
    register_rest_route('buddypress/v1', '/share-post', array(
        'methods' => 'POST',
        'callback' => 'repost',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ));
});

function repost(WP_REST_Request $request) {
    $post_id = $request->get_param('post_id');
    $user_id = $request->get_param('user_id');

    // Fetch the original post details
    $original_post = get_post($post_id);

    if (!$original_post) {
        return new WP_Error('no_post', 'Invalid post ID', array('status' => 404));
    }

    // Check if the post being reposted is already a repost
    $original_shared_post_id = get_post_meta($post_id, 'shared_post_id', true);
    if ($original_shared_post_id) {
        $post_id = $original_shared_post_id; // Point to the original post
    }

    // Update share count on the original post
    $share_count = (int) get_post_meta($post_id, 'share_count', true);
    $share_count++;
    update_post_meta($post_id, 'share_count', $share_count);

    // Create a new post with the original content and set it as a repost
    $new_post = array(
        'post_title'    => $original_post->post_title,
        'post_content'  => $original_post->post_content,
        'post_status'   => 'publish',
        'post_author'   => $user_id,
        // 'post_type'     => $original_post->post_type,
        'post_type'     => 'user_post',
    );

    $new_post_id = wp_insert_post($new_post);

    // Save repost metadata
    if (!is_wp_error($new_post_id)) {
        update_post_meta($new_post_id, 'shared_post_id', $post_id); // Link back to the original post
        update_post_meta($new_post_id, 'shared_by', $user_id);
        update_post_meta($new_post_id, 'shared_at', current_time('mysql'));

        // Prepare the response data
        $response_data = array(
            'new_post_id'    => $new_post_id,
            'shared_by'      => get_the_author_meta('display_name', $user_id),
            'shared_at'      => current_time('mysql'),
            'message'        => 'Post reposted successfully!',
        );

        return rest_ensure_response($response_data);
    } else {
        return new WP_Error('repost_failed', 'Failed to repost the post', array('status' => 500));
    }
}