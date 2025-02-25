<?php


// Register routes
add_action('rest_api_init', function () {
    register_rest_route(
        'buddypress/v1',
        '/post',
        array(
            'methods' => 'POST',
            'callback' => 'add_user_post',
            'permission_callback' => 'is_user_logged_in',
        )
    );

    register_rest_route(
        'buddypress/v1',
        '/post/(?P<post_id>\d+)',
        array(
            'methods' => array('POST', 'DELETE'),
            'callback' => 'handle_user_post',
            'permission_callback' => 'user_has_permission',
        )
    );

    register_rest_route(
        'buddypress/v1',
        '/posts',
        array(
            'methods' => 'GET',
            'callback' => 'get_all_user_posts',
            'permission_callback' => '__return_true',
        )
    );
    register_rest_route(
        'buddypress/v1',
        '/post/(?P<post_id>\d+)',
        array(
            'methods' => 'GET',
            'callback' => 'get_user_post',
            'permission_callback' => '__return_true',
        )
    );
});

function user_has_permission(WP_REST_Request $request)
{
    $post_id = $request->get_param('post_id');
    $post = get_post($post_id);
    if (empty($post)) {
        return false; // Post not found
    }
    return $post->post_author == get_current_user_id();
}

function handle_user_post(WP_REST_Request $request)
{
    $method = $request->get_method();
    if ($method === 'POST') {
        return edit_user_post($request);
    } elseif ($method === 'GET') {
        // return get_user_post($request);
    } elseif ($method === 'DELETE') {
        return delete_user_post($request);
    }
    return return_error('method_not_allowed', 'Method not allowed', 405);
}

function user_can_edit_posts(WP_REST_Request $request)
{
    $post_id = $request->get_param('post_id');
    $post = get_post($post_id);

    if (empty($post)) {
        return false; // Post not found
    }

    return $post->post_author == get_current_user_id();
}

function user_can_view_post(WP_REST_Request $request)
{
    $post_id = $request->get_param('post_id');
    $post = get_post($post_id);

    if (empty($post)) {
        return false; // Post not found
    }

    return $post->post_author == get_current_user_id();
}
// function add_user_post(WP_REST_Request $request)
// {
//     $user_id = get_current_user_id();
//     $params = $request->get_params();

//     if (empty($params)) {
//         return new WP_Error('missing_json_data', 'JSON data is required', ['status' => 400]);
//     }

//     $description = isset($params['description']) ? $params['description'] : '';
//     $media_provided = !empty($_FILES['media']['name']);

//     if (empty($description) && !$media_provided) {
//         return new WP_Error('missing_content', 'Either a description or media must be provided', ['status' => 400]);
//     }

//     // Determine post status: publish if only description, draft if media is included
//     $post_status = (!$media_provided) ? 'publish' : 'draft';

//     $post_id = wp_insert_post([
//         'post_title' => 'User Post by ' . $user_id,
//         'post_content' => sanitize_textarea_field($description),
//         'post_status' => $post_status,
//         'post_type' => 'user_post',
//         'post_author' => $user_id,
//     ]);

//     if (is_wp_error($post_id)) {
//         return new WP_Error('post_creation_failed', 'Failed to create post', ['status' => 500]);
//     }

//     if ($media_provided) {
//         // update_post_meta($post_id, '_upload_progress', 0);
//         send_socket_update($post_id, ['status' => 'uploading', 'progress' => 0]);
//         // $persistent_files = save_to_persistent_location($_FILES['media']);

//         // if (is_wp_error($persistent_files)) {
//         //     return $persistent_files;
//         // }
//         // as_enqueue_async_action('handle_media_upload_background', [$post_id, $persistent_files], 'media_upload');
//         error_log('Files_media'.json_encode($_FILES['media']));
//         as_enqueue_async_action('handle_media_upload_in_background', [$post_id, $_FILES['media']], 'media_upload');

//     }

//     return new WP_REST_Response([
//         'success' => true,
//         'post_id' => $post_id,
//         'status' => $post_status,
//         'message' => !$media_provided
//             ? 'Post has been published successfully.'
//             : 'Post is being created and will be visible shortly.',
//     ], !$media_provided ? 200 : 202);
// }



function add_user_post(WP_REST_Request $request)
{
    $user_id = get_current_user_id();
    $params = $request->get_params();

    switch_to_user_language($user_id);
    if (empty($params)) {
        return new WP_Error('missing_json_data', 'JSON data is required', array('status' => 400));
    }

    $description = isset($params['description']) ? $params['description'] : '';
    // Check if either description or media is provided
    if (empty($description) && empty($_FILES['media']['name'])) {
        return new WP_Error('missing_content', 'Either a description or media must be provided', array('status' => 400));
    }

    // Handle media upload first
    if (!empty($_FILES['media']['name'])) {
        $uploaded_media = handle_media_upload(null, $_FILES['media'], 'user_post'); // Post ID is null because the post hasn't been created yet

        if (is_wp_error($uploaded_media)) {
            return $uploaded_media; // Return error if media upload failed
        }
    }

    // Proceed to create the post only if media upload was successful (or no media provided)
    $post_data = array(
        'post_title' => 'User Post by ' . $user_id,
        'post_content' => sanitize_textarea_field($description),
        'post_status' => 'publish',
        'post_type' => 'user_post',
        'post_author' => $user_id,
    );

    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) {
        return new WP_Error('post_creation_failed', 'Failed to create post', array('status' => 500));
    }

    // If media was uploaded, associate it with the created post
    if (!empty($_FILES['media']['name'])) {
        handle_media_upload($post_id,$_FILES['media'], 'user_post');
        // associate_media_with_post($post_id, $uploaded_media);
    }

    // Send notifications to all followers
    $followers = get_followers_list($user_id);
    foreach ($followers as $follower) {
        send_new_post_notification($follower['id'], $post_id, $user_id);
    }

    return new WP_REST_Response([
        'success' => true,
        'post_id' => $post_id,
        'message' => 'Post created successfully.'
    ], 200);
}
function edit_user_post(WP_REST_Request $request)
{
    $user_id = get_current_user_id();
    switch_to_user_language($user_id);

    $post_id = $request->get_param('post_id');
    $params = $request->get_params();

    // Validate if the post exists and belongs to the current user
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'user_post' || $post->post_author != $user_id) {
        return new WP_Error('invalid_post', 'Invalid post ID or permission denied', array('status' => 403));
    }

    if (empty($params)) {
        return new WP_Error('missing_json_data', 'JSON data is required', array('status' => 400));
    }

    $description = isset($params['description']) ? sanitize_textarea_field($params['description']) : '';

    // Check if either description or media is provided
    if (empty($description) && empty($_FILES['media']['name'])) {
        return new WP_Error('missing_content', 'Either a description or media must be provided', array('status' => 400));
    }

    // Update the post content
    $post_data = array(
        'ID' => $post_id,
        'post_content' => $description,
    );

    $updated_post_id = wp_update_post($post_data);

    if (is_wp_error($updated_post_id)) {
        return new WP_Error('post_update_failed', 'Failed to update post', array('status' => 500));
    }

    // Handle media upload
    // $uploaded_media = handle_media_upload_in_background($post_id, $_FILES['media'], 'user_post');
    // if (is_wp_error($uploaded_media)) {
    //     return $uploaded_media;
    // }

    return new WP_REST_Response(['success' => true, 'post_id' => $updated_post_id], 200);
}
function get_user_post(WP_REST_Request $request)
{
    $user_id = get_current_user_id();
    switch_to_user_language($user_id);

    $post_id = $request->get_param('post_id');
    $post = get_post($post_id);

    if (!$post || $post->post_type !== 'user_post') {
        return new WP_Error('invalid_post', 'Invalid post ID', array('status' => 404));
    }

    // $image_ids = get_post_meta($post->ID, 'user_post_file', false);
    // $images = array_map('wp_get_attachment_url', $image_ids);
    $formatted_post = prepare_post_response($post);
    // Return the formatted post response
    return new WP_REST_Response(
        array(
            'success' => true,
            'post' => $formatted_post,
        ),
        200
    );

    // return new WP_REST_Response(
    //     array(
    //         'id' => $post->ID,
    //         'title' => $post->post_title,
    //         'content' => $post->post_content,
    //         'author' => $post->post_author,
    //         'date' => $post->post_date,
    //         'images' => $images,
    //     ),
    //     200
    // );
}

function get_all_user_posts(WP_REST_Request $request)
{
    $user_id = $request->get_param('user_id');
    switch_to_user_language($user_id);
    if (!$user_id) {
        return new WP_Error('missing_user_id', 'User ID is required', array('status' => 400));
    }

    // Get pagination parameters
    $page = $request->get_param('page') ? (int) $request->get_param('page') : 1;
    $per_page = $request->get_param('per_page') ? (int) $request->get_param('per_page') : 10;

    // Set up query arguments
    $args = array(
        'post_type' => 'user_post',
        'author' => $user_id,
        'posts_per_page' => $per_page,
        'paged' => $page,
    );

    // Perform the query
    $query = new WP_Query($args);
    $posts = $query->posts;

    // Process posts
    $response_posts = array_map(function ($post) use ($user_id) {
        return prepare_post_response($post);
    }, $posts);

    // Return response
    return new WP_REST_Response(
        array(
            'success' => true,
            'posts' => $response_posts,
            'total_posts' => $query->found_posts,
        ),
        200
    );
}

// function delete_user_post(WP_REST_Request $request)
// {
//     $post_id = $request->get_param('post_id');
//     $post = get_post($post_id);

//     if (empty($post)) {
//         return new WP_Error('no_post', 'Post not found', array('status' => 404));
//     }

//     if ($post->post_author != get_current_user_id()) {
//         return new WP_Error('rest_forbidden', 'You do not have permission to delete this post', array('status' => 403));
//     }

//     if (wp_delete_post($post_id)) {
//         return new WP_REST_Response(array('success' => true, 'post_id' => $post_id), 200);
//     } else {
//         return new WP_Error('delete_failed', 'Failed to delete post', array('status' => 500));
//     }
// }

function delete_user_post(WP_REST_Request $request)
{
    $post_id = $request->get_param('post_id');
    $post = get_post($post_id);

    if (empty($post)) {
        return new WP_Error('no_post', 'Post not found', array('status' => 404));
    }

    if ($post->post_author != get_current_user_id()) {
        return new WP_Error('rest_forbidden', 'You do not have permission to delete this post', array('status' => 403));
    }

    // Check if the post is the original or a repost
    $original_shared_post_id = get_post_meta($post_id, 'shared_post_id', true);
    if (!$original_shared_post_id) {
        // It's an original post, delete all reposts
        $reposts = get_posts(array(
            'meta_key' => 'shared_post_id',
            'meta_value' => $post_id,
            'post_type' => 'user_post',
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

    // Delete the current post (original or repost)
    if (wp_delete_post($post_id)) {
        return new WP_REST_Response(array('success' => true, 'post_id' => $post_id), 200);
    } else {
        return new WP_Error('delete_failed', 'Failed to delete post', array('status' => 500));
    }
}

function return_error($code, $message, $status)
{
    return new WP_Error($code, $message, array('status' => $status));
}

function send_new_post_notification($follower_id, $post_id, $post_author_id)
{
    $notification_data = [
        'user_id' => $follower_id,      // The follower to notify
        'item_id' => $post_id,          // Post ID
        'secondary_item_id' => $post_author_id,  // The post author
        'component_name' => 'post',     // Component name for post notification
        'component_action' => 'new_post', // Unique action key for the new post notification
        'date_notified' => bp_core_current_time(),
        'is_new' => 1,                  // Mark as new
    ];

    // Add a notification for the follower
    bp_notifications_add_notification($notification_data);
    send_realtime_notification_to_node($follower_id);
}