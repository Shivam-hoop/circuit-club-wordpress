<?php
// Register REST API routes
add_action('rest_api_init', function () {
    $namespace = 'buddypress/v1';

    register_rest_route(
        $namespace,
        '/vehicle/(?P<vehicle_id>\d+)/post',
        array(
            'methods' => 'POST',
            'callback' => 'add_vehicle_post',
            'permission_callback' => 'is_user_logged_in',
        )
    );
    register_rest_route($namespace, '/vehicle/(?P<vehicle_id>\d+)/post/(?P<post_id>\d+)', array(
        'methods' => 'POST',
        'callback' => 'edit_vehicle_post',
        'permission_callback' => 'is_user_logged_in',
    )
    );

    register_rest_route(
        $namespace,
        '/vehicle/(?P<vehicle_id>\d+)/posts',
        array(
            'methods' => 'GET',
            'callback' => 'get_vehicle_posts',
            // 'permission_callback' => 'is_user_logged_in',
            'permission_callback' => '__return_true'

        )
    );

    register_rest_route(
        $namespace,
        '/vehicle/post/(?P<post_id>\d+)',
        array(
            'methods' => 'DELETE',
            'callback' => 'delete_vehicle_post_callback',
            'permission_callback' => 'is_user_logged_in',
        )
    );
    register_rest_route(
        $namespace,
        '/vehicle/post/(?P<post_id>\d+)',
        array(
            'methods' => 'GET',
            'callback' => 'get_vehicle_single_post',
            'permission_callback' => '__return_true',
        )
    );
});

/**
 * Add a vehicle post.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
// function add_vehicle_post(WP_REST_Request $request)
// {
//     $user_id = get_current_user_id();
//     $vehicle_id = $request->get_param('vehicle_id');
//     $params = $request->get_params();

//     switch_to_user_language($user_id);

//     // Validate input data
//     if (empty($params)) {
//         return new WP_Error('missing_json_data', 'JSON data is required', array('status' => 400));
//     }

//     $description = isset($params['description']) ? sanitize_textarea_field($params['description']) : '';

//     // Check if the vehicle exists and belongs to the current user
//     $vehicle_post = get_post($vehicle_id);
//     if (!$vehicle_post || $vehicle_post->post_type !== 'vehicle' || $vehicle_post->post_author != $user_id) {
//         return new WP_Error('invalid_vehicle', 'Invalid vehicle ID or permission denied', array('status' => 403));
//     }

//     // Check if either description or media is provided
//     if (empty($description) && empty($_FILES['media']['name'])) {
//         return new WP_Error('missing_content', 'Either a description or media must be provided', array('status' => 400));
//     }

//     // Prepare vehicle post data for insertion
//     $post_data = array(
//         'post_title' => 'Vehicle Post for ' . $vehicle_id,
//         'post_content' => $description,
//         'post_status' => 'publish',
//         'post_type' => 'vehicle_post',
//         'post_author' => $user_id,
//         'meta_input' => array(
//             'vehicle_id' => $vehicle_id,
//         ),
//     );

//     // Insert the vehicle post
//     $post_id = wp_insert_post($post_data);

//     if (is_wp_error($post_id)) {
//         return new WP_Error('post_creation_failed', 'Failed to create post', array('status' => 500));
//     }

//     // Handle media upload
//     $uploaded_media = handle_media_upload($post_id, $_FILES['media'], 'vehicle_post');
//     if (is_wp_error($uploaded_media)) {
//         return $uploaded_media;
//     }
//         // Send notifications to all followers
//     $followers = get_followers_list($user_id);
//     foreach ($followers as $follower) {
//         send_new_vehicle_post_notification($follower['id'], $post_id, $user_id);
//     }

//     // Success response
//     return new WP_REST_Response(['success' => true, 'post_id' => $post_id, 'media' => $uploaded_media], 200);
// }
function add_vehicle_post(WP_REST_Request $request)
{
    $user_id = get_current_user_id();
    $vehicle_id = $request->get_param('vehicle_id');
    $params = $request->get_params();

    switch_to_user_language($user_id);

    // Validate input data
    if (empty($params)) {
        return new WP_Error('missing_json_data', 'JSON data is required', array('status' => 400));
    }

    $description = isset($params['description']) ? sanitize_textarea_field($params['description']) : '';

    // Check if the vehicle exists and belongs to the current user
    $vehicle_post = get_post($vehicle_id);
    if (!$vehicle_post || $vehicle_post->post_type !== 'vehicle' || $vehicle_post->post_author != $user_id) {
        return new WP_Error('invalid_vehicle', 'Invalid vehicle ID or permission denied', array('status' => 403));
    }

    // Check if either description or media is provided
    if (empty($description) && empty($_FILES['media']['name'])) {
        return new WP_Error('missing_content', 'Either a description or media must be provided', array('status' => 400));
    }

    // Handle media upload first
    if (!empty($_FILES['media']['name'])) {
        $uploaded_media = handle_media_upload(null, $_FILES['media'], 'vehicle_post'); // Post ID is null because the post hasn't been created yet

        if (is_wp_error($uploaded_media)) {
            return $uploaded_media; // Return error if media upload failed
        }
    }

    // Prepare vehicle post data for insertion after media upload is successful
    $post_data = array(
        'post_title' => 'Vehicle Post for ' . $vehicle_id,
        'post_content' => $description,
        'post_status' => 'publish',
        'post_type' => 'vehicle_post',
        'post_author' => $user_id,
        'meta_input' => array(
            'vehicle_id' => $vehicle_id,
        ),
    );

    // Insert the vehicle post
    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) {
        return new WP_Error('post_creation_failed', 'Failed to create post', array('status' => 500));
    }

    // If media was uploaded, associate it with the created post
    if (!empty($_FILES['media']['name'])) {
        // associate_media_with_post($post_id, $uploaded_media);
        $uploaded_media = handle_media_upload($post_id, $_FILES['media'], 'vehicle_post');

    }

    // Send notifications to all followers
    $followers = get_followers_list($user_id);
    foreach ($followers as $follower) {
        send_new_vehicle_post_notification($follower['id'], $post_id, $user_id);
    }

    // Success response
    return new WP_REST_Response(['success' => true, 'post_id' => $post_id, 'media' => $uploaded_media], 200);
}



function edit_vehicle_post(WP_REST_Request $request)
{
    $user_id = get_current_user_id();
    switch_to_user_language($user_id);
    $vehicle_id = $request->get_param('vehicle_id');
    $post_id = $request->get_param('post_id');
    $params = $request->get_params();

    // Validate if the post exists and belongs to the current user
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'vehicle_post' || $post->post_author != $user_id) {
        return new WP_Error('invalid_post', 'Invalid post ID or permission denied', array('status' => 403));
    }

    // Validate if the vehicle exists and belongs to the current user
    $vehicle_post = get_post($vehicle_id);
    if (!$vehicle_post || $vehicle_post->post_type !== 'vehicle' || $vehicle_post->post_author != $user_id) {
        return new WP_Error('invalid_vehicle', 'Invalid vehicle ID or permission denied', array('status' => 403));
    }

    if (empty($params)) {
        return new WP_Error('missing_json_data', 'JSON data is required', array('status' => 400));
    }

    $description = isset($params['description']) ? sanitize_textarea_field($params['description']) : '';

    // Check if either description or media is provided
    if (empty($description) && empty($_FILES['media']['name'])) {
        return new WP_Error('missing_content', 'Either a description or media must be provided', array('status' => 400));
    }

    // Update the vehicle post content
    $post_data = array(
        'ID'           => $post_id,
        'post_content' => $description,
    );

    $updated_post_id = wp_update_post($post_data);

    if (is_wp_error($updated_post_id)) {
        return new WP_Error('post_update_failed', 'Failed to update post', array('status' => 500));
    }

    // Handle media upload
    $uploaded_media = handle_media_upload($post_id, $_FILES['media'], 'vehicle_post');
    if (is_wp_error($uploaded_media)) {
        return $uploaded_media;
    }

    return new WP_REST_Response(['success' => true, 'post_id' => $updated_post_id, 'media' => $uploaded_media], 200);
}

/**
 * Get vehicle posts.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function get_vehicle_posts(WP_REST_Request $request)
{
    $vehicle_id = $request->get_param('vehicle_id');
    $user_id = get_current_user_id();
    switch_to_user_language($user_id);
    error_log(get_user_language_preference($user_id));
    // Get pagination parameters
    $page = $request->get_param('page') ? (int) $request->get_param('page') : 1;
    $per_page = $request->get_param('per_page') ? (int) $request->get_param('per_page') : 10;

    $args = array(
        'post_type' => 'vehicle_post',
        'meta_query' => array(
            array(
                'key' => 'vehicle_id',
                'value' => $vehicle_id,
                'compare' => '=',
            ),
        ),
        'posts_per_page' => $per_page,
        'paged' => $page,
    );

   
    $query = new WP_Query($args);
    $posts = $query->posts;

    $response_posts = array_map(function ($post) use ($user_id) {
        return prepare_post_response($post);
    }, $posts);

    return new WP_REST_Response(array('success' => true, 'posts' => $response_posts, 'total_posts' => $query->found_posts), 200);
}



/**
 * Delete a vehicle post.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */

function delete_vehicle_post_callback(WP_REST_Request $request)
{
    $user_id = get_current_user_id();
    $post_id = $request->get_param('post_id');

    // Check if the post exists and belongs to the current user
    $post = get_post($post_id);
    if (!$post || !in_array($post->post_type, array('vehicle_post', 'user_post')) || $post->post_author != $user_id) {
        return new WP_Error('invalid_post', 'Invalid post ID or permission denied', array('status' => 403));
    }

    // Check if the post is the original or a repost
    $original_shared_post_id = get_post_meta($post_id, 'shared_post_id', true);
    if (!$original_shared_post_id) {
        // It's an original post, delete all reposts
        $reposts = get_posts(array(
            'meta_key' => 'shared_post_id',
            'meta_value' => $post_id,
            'post_type' => array('vehicle_post', 'user_post'), // Check for both post types
            'numberposts' => -1
        ));

        // Delete each repost
        foreach ($reposts as $repost) {
            wp_delete_post($repost->ID, true);
        }
    } else {
        // It's a repost, decrease the share count on the original post
        $original_post_id = $original_shared_post_id;
        $share_count = (int) get_post_meta($original_post_id, 'share_count', true);
        if ($share_count > 0) {
            $share_count--;
            update_post_meta($original_post_id, 'share_count', $share_count);
        }
    }

    // Delete the current vehicle post (original or repost)
    $result = wp_delete_post($post_id, true);

    if (!$result) {
        return new WP_Error('post_deletion_failed', 'Failed to delete post', array('status' => 500));
    }

    // Success response
    return new WP_REST_Response(array('success' => true, 'post_id' => $post_id), 200);
}

/**
 * Get a specific vehicle post.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function get_vehicle_single_post(WP_REST_Request $request)
{
    $post_id = $request->get_param('post_id');
    $post = get_post($post_id);

    // Check if the post exists and is of the correct type
    if (!$post || $post->post_type !== 'vehicle_post') {
        return new WP_Error('invalid_post', 'Invalid post ID or the post does not exist', array('status' => 404));
    }

    // Prepare post data for response
    $post_data = prepare_post_response($post);

    return new WP_REST_Response(array('success' => true, 'post' => $post_data), 200);
}

function send_new_vehicle_post_notification($follower_id, $post_id, $post_author_id)
{
    $notification_data = [
        'user_id' => $follower_id,      // The follower to notify
        'item_id' => $post_id,          // Post ID
        'secondary_item_id' => $post_author_id,  // The post author
        'component_name' => 'vehicle_post',     // Component name for post notification
        'component_action' => 'new_post', // Unique action key for the new post notification
        'date_notified' => bp_core_current_time(),
        'is_new' => 1,                  // Mark as new
    ];

    // Add a notification for the follower
    bp_notifications_add_notification($notification_data);
    send_realtime_notification_to_node($follower_id);
}