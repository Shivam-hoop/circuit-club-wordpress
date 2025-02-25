<?php

add_action('rest_api_init', function () {
    register_rest_route(
        'buddypress/v1',
        '/post/(?P<post_id>\d+)/like',
        array(
            'methods' => 'POST',
            'callback' => 'handle_like_unlike',
            'permission_callback' => 'is_user_logged_in',
        )
    );
    register_rest_route(
        'buddypress/v1',
        '/post/(?P<post_id>\d+)/likes',
        array(
            'methods' => 'GET',
            'callback' => 'get_post_likes',
            'permission_callback' => 'is_user_logged_in',
        )
    );
    // register_rest_route('buddypress/v1', '/post/(?P<post_id>\d+)/unlike', array(
    //     'methods' => 'POST',
    //     'callback' => 'remove_like_from_post',
    //     'permission_callback' => 'is_user_logged_in',
    // )
    // );
    register_rest_route(
        'buddypress/v1',
        '/post/(?P<post_id>\d+)/like-status',
        array(
            'methods' => 'GET',
            'callback' => 'check_user_like_status',
            'permission_callback' => 'is_user_logged_in',
        )
    );
    register_rest_route(
        'buddypress/v1',
        '/liked/users',
        array(
            'methods' => 'GET',
            'callback' => 'get_users_who_liked_post',
            'permission_callback' => 'is_user_logged_in',
        )
    );
});

// function like_post(WP_REST_Request $request) {
//     $user_id = get_current_user_id();
//     $post_id = (int) $request->get_param('post_id');
//     $rate_limit_check = rate_limit_check($user_id, 'like_post');

//     if (is_wp_error($rate_limit_check)) {
//         return $rate_limit_check;
//     }
//     if (empty($post_id) || !get_post($post_id)) {
//         return new WP_Error('invalid_post', 'Invalid post ID', array('status' => 400));
//     }

//     $likes = get_post_meta($post_id, 'post_likes', true);
//     $likes = !empty($likes) ? $likes : array();

//     if (in_array($user_id, $likes)) {
//         return new WP_Error('already_liked', 'You have already liked this post', array('status' => 200));
//     }

//     $likes[] = $user_id;
//     update_post_meta($post_id, 'post_likes', $likes);

//     return new WP_REST_Response(array('success' => true, 'likes' => count($likes)), 200);
// }
// function handle_like_unlike(WP_REST_Request $request)
// {
//     $user_id = get_current_user_id();
//     $post_id = (int) $request->get_param('post_id');
//     $action = $request->get_param('like'); // 'like' or 'unlike'
//     // $rate_limit_check = rate_limit_check($user_id,$post_id, 'like_post');
//     $likeStatus = null;
//     // if (is_wp_error($rate_limit_check)) {
//     //     return $rate_limit_check;
//     // }
//     if (empty($post_id) || !get_post($post_id)) {
//         return new WP_Error('invalid_post', 'Invalid post ID', array('status' => 400));
//     }

//     $likes = get_post_meta($post_id, 'post_likes', true);
//     $likes = !empty($likes) ? $likes : array();
//     //if like is false, thats mean user want to like the post
//     if ($action == 'false') {
//         if (in_array($user_id, $likes)) {
//             return new WP_Error('already_liked', 'You have already liked this post', array('status' => 200));
//         }
//         $likes[] = $user_id;
//         $message = 'Post liked';
//         $likeStatus = true;
//     }
//     //if like is ture, thats mean user want to unlike the post
//     elseif ($action == 'true') {
//         if (!in_array($user_id, $likes)) {
//             return new WP_REST_Response(array('message' => 'You have not liked this post'), 200);
//         }
//         $likes = array_diff($likes, array($user_id));
//         $message = 'Like removed';
//         $likeStatus = false;
//     } else {
//         return new WP_Error('invalid_action', 'Invalid action', array('status' => 400));
//     }

//     update_post_meta($post_id, 'post_likes', $likes);

//     return new WP_REST_Response(array('message' => $message, 'likes' => count($likes), 'likeStatus' => $likeStatus), 200);
// }

//original post count add 
// function handle_like_unlike(WP_REST_Request $request) {
//     $user_id = get_current_user_id();
//     $post_id = (int) $request->get_param('post_id');
//     $action = $request->get_param('like'); // 'like' or 'unlike'

//     // Check if the post is a repost and get the original post ID
//     $original_post_id = get_post_meta($post_id, 'shared_post_id', true);
//     if ($original_post_id) {
//         $post_id = $original_post_id; // Redirect like to the original post
//     }

//     if (empty($post_id) || !get_post($post_id)) {
//         return new WP_Error('invalid_post', 'Invalid post ID', array('status' => 400));
//     }

//     $likes = get_post_meta($post_id, 'post_likes', true);
//     $likes = !empty($likes) ? $likes : array();

//     if ($action == 'false') { // Like action
//         if (in_array($user_id, $likes)) {
//             return new WP_Error('already_liked', 'You have already liked this post', array('status' => 200));
//         }
//         $likes[] = $user_id;
//         $message = 'Post liked';
//         $likeStatus = true;
//     } elseif ($action == 'true') { // Unlike action
//         if (!in_array($user_id, $likes)) {
//             return new WP_REST_Response(array('message' => 'You have not liked this post'), 200);
//         }
//         $likes = array_diff($likes, array($user_id));
//         $message = 'Like removed';
//         $likeStatus = false;
//     } else {
//         return new WP_Error('invalid_action', 'Invalid action', array('status' => 400));
//     }

//     update_post_meta($post_id, 'post_likes', $likes);

//     return new WP_REST_Response(array('message' => $message, 'likes' => count($likes), 'likeStatus' => $likeStatus), 200);
// }
function handle_like_unlike(WP_REST_Request $request)
{
    $user_id = get_current_user_id();
    $post_id = (int) $request->get_param('post_id');
    $action = $request->get_param('like'); // 'like' or 'unlike'




    // Check if the post is a repost and get the original post ID
    $original_post_id = get_post_meta($post_id, 'shared_post_id', true);
    if ($original_post_id) {
        $post_id = $original_post_id; // Redirect like to the original post
    }
    // Check if the post exists
    if (empty($post_id) || !get_post($post_id)) {
        return new WP_Error('invalid_post', 'Invalid post ID or post does not exist', array('status' => 404));
    }

    if (empty($post_id) || !get_post($post_id)) {
        return new WP_Error('invalid_post', 'Invalid post ID', array('status' => 400));
    }

    $likes = get_post_meta($post_id, 'post_likes', true);
    $likes = !empty($likes) ? $likes : array();

    if ($action == 'false') { // Like action
        if (in_array($user_id, $likes)) {
            return new WP_Error('already_liked', 'You have already liked this post', array('status' => 200));
        }
        $likes[] = $user_id;
        // Send notification to post author when their post is liked
        $post_author_id = get_post_field('post_author', $post_id);
        if ($post_author_id != $user_id) {
            send_like_notification($user_id, $post_id, $post_author_id);
        }

        $message = 'Post liked';
        $likeStatus = true;
    } elseif ($action == 'true') { // Unlike action
        if (!in_array($user_id, $likes)) {
            return new WP_REST_Response(array('message' => 'You have not liked this post'), 200);
        }
        // Remove the like and re-index the array
        $likes = array_values(array_diff($likes, array($user_id)));
        $message = 'Like removed';
        $likeStatus = false;
    } else {
        return new WP_Error('invalid_action', 'Invalid action', array('status' => 400));
    }

    update_post_meta($post_id, 'post_likes', $likes);

    return new WP_REST_Response(array('message' => $message, 'likes' => count($likes), 'likeStatus' => $likeStatus), 200);
}


// function get_post_likes(WP_REST_Request $request) {
//     $post_id = (int) $request->get_param('post_id');
//     $likes = get_post_meta($post_id, 'post_likes', true);

//     if (empty($likes)) {
//         return new WP_REST_Response(array('success' => true, 'likes' => 0), 200);
//     }

//     return new WP_REST_Response(array('success' => true, 'likes' => count($likes)), 200);
// }
function get_post_likes(WP_REST_Request $request)
{
    $post_id = (int) $request->get_param('post_id');
    $cache_key = "post_likes_{$post_id}";
    $likes = get_transient($cache_key);

    if (false === $likes) {
        $likes = get_post_meta($post_id, 'post_likes', true);
        $likes = !empty($likes) ? count($likes) : 0;
        set_transient($cache_key, $likes, HOUR_IN_SECONDS);
    }

    return new WP_REST_Response(array('success' => true, 'likes' => $likes), 200);
}

// function remove_like_from_post(WP_REST_Request $request)
// {
//     $post_id = (int) $request->get_param('post_id');
//     $user_id = get_current_user_id();

//     if (empty($post_id) || !get_post($post_id)) {
//         return new WP_Error('invalid_post', 'Invalid post ID', array('status' => 400));
//     }

//     $liked_users = get_post_meta($post_id, 'post_likes', true) ?: array();

//     if (!in_array($user_id, $liked_users)) {
//         return new WP_REST_Response(array('message' => 'You have not liked this post'), 200);
//     }

//     $liked_users = array_diff($liked_users, array($user_id));
//     update_post_meta($post_id, 'post_likes', $liked_users);

//     return new WP_REST_Response(array('message' => 'Like removed'), 200);
// }

function check_user_like_status(WP_REST_Request $request)
{
    $post_id = (int) $request->get_param('post_id');
    $user_id = get_current_user_id();

    if (empty($post_id) || !get_post($post_id)) {
        return new WP_Error('invalid_post', 'Invalid post ID', array('status' => 400));
    }

    $liked_users = get_post_meta($post_id, 'post_likes', true) ?: array();

    return new WP_REST_Response(array('liked' => in_array($user_id, $liked_users)), 200);
}

// function rate_limit_check($user_id,$post_id,$action) {
//     $transient_key = "{$action}_{$user_id}_{$post_id}";
//     $rate_limit = get_transient($transient_key);

//     if ($rate_limit) {
//         return new WP_Error('rate_limited', 'You are performing this action too frequently. Please wait a while.', array('status' => 429));
//     }

//     set_transient($transient_key, true, 10); // 10 seconds rate limit
//     return true;
// }

// Function to send notification when post is liked
function send_like_notification($user_id, $post_id, $post_author_id)
{
    // $fcm_token = get_user_meta($post_author_id, 'fcm_token', true);
    $fcm_token = get_user_meta($post_author_id, 'firebase_token', true);

    $notification_data = [
        'user_id' => $post_author_id, // The post author to notify
        'item_id' => $post_id,        // Post ID
        'secondary_item_id' => $user_id,        // The user who liked the post
        'component_name' => 'like',    // Component name
        'component_action' => 'post_like',     // Unique action key for the like notification
        'date_notified' => bp_core_current_time(),
        'is_new' => 1,               // Mark as new
    ];
    // Add a notification for the post author
    bp_notifications_add_notification($notification_data);
    send_realtime_notification_to_node($post_author_id);
    /***optimize section start */
    $getNotificationDetails = get_notification_details($notification_data['component_name'], $notification_data['component_action'], $notification_data['item_id'], $user_id);
    error_log("notfication current fcm" . print_r($getNotificationDetails, true));
    $notification_message = $getNotificationDetails['message'] ?? '';
    $title = $getNotificationDetails['title'] ?? '';
    $slug = $getNotificationDetails['slug'] ?? '';
    /***optimize section end */

    $keyFilePath = '/var/www/html/circuit-club/circuit-club-fcm-firebase-adminsdk-h8ibi-f11140baff.json'; // Replace with the correct path to your service account JSON file

    // Path to your service account JSON key file
    $serviceAccountPath = $keyFilePath;

    // Your Firebase project ID
    $projectId = 'circuit-club-fcm';
    // $title = 'New Like on Your Post';
    // $message = get_user_by('id', $user_id)->display_name . ' liked your post.';
    // Example message payload
    $message = [
        'token' => $fcm_token,
        'notification' => [
            'title' => $title, // Use the title from the reusable function
            'body' => $notification_message,  // Use the body from the reusable function
            'image' => 'https://d2goql3w04c7pg.cloudfront.net/uploads/stephan-louis-7NodKI99GRQ-unsplash-1736153880-9NNd4zSe.jpg', // Replace with your image URL
        ],
        'data' => [
            'title' => $title, // Custom data
            'custom_message' => $notification_message, // Replace with your custom message
            'date' => $notification_data['date_notified'], // Replace with your date or custom field
            'sender_avatar' => 'assets/images/profile_default.png', // Replace with the correct avatar path
            'slug' => $slug, // Add your slug
            'secondary_item_id' => (string) $user_id, // Add your slug

        ],
        'webpush' => [
            'fcm_options' => [
                'link' => $slug, // Replace with the URL for web push
            ],
        ],
    ];
    send_realtime_notification($serviceAccountPath, $projectId, $message);
}

// function get_users_who_liked_post(WP_REST_Request $request)
// {
//     $post_id = (int) $request->get_param('post_id'); // Get the post ID from request
//     $search = $request->get_param('search'); // Get the search query from request


//     // Get the users who liked this post
//     $likes = get_post_meta($post_id, 'post_likes', true);
//     $likes = !empty($likes) ? $likes : array();

//     // If no likes found
//     if (empty($likes)) {
//         return new WP_REST_Response(array('message' => 'No likes found for this post', 'users' => array()), 200);
//     }

//     // Get user details of those who liked the post
//     $liked_users = array();
//     foreach ($likes as $user_id) {
//         $user_info = get_userdata($user_id);
//         if ($user_info) {
//             $liked_users[] = array(
//                 'ID' => $user_info->ID,
//                 'display_name' => $user_info->display_name,
//                 'user_login' => $user_info->user_login,
//                 'user_email' => $user_info->user_email,
//                 'avatar_url' => get_avatar_url($user_info->ID)
//             );
//         }
//     }

//     return new WP_REST_Response(array('message' => 'Users who liked the post', 'users' => $liked_users), 200);
// }
// function get_users_who_liked_post(WP_REST_Request $request)
// {
//     $post_id = (int) $request->get_param('post_id'); // Get the post ID from request
//     $search = $request->get_param('search'); // Get the search query from request

//     // Get the users who liked this post
//     $likes = get_post_meta($post_id, 'post_likes', true);
//     $likes = !empty($likes) ? $likes : array();

//     // If no likes found
//     if (empty($likes)) {
//         return new WP_REST_Response(array('message' => 'No likes found for this post', 'users' => array()), 200);
//     }

//     // Get user details of those who liked the post
//     $liked_users = array();
//     foreach ($likes as $user_id) {
//         $user_info = get_userdata($user_id);
//         if ($user_info) {
//             // Filter users based on the search query if provided
//             if ($search) {
//                 $search = strtolower($search);
//                 $display_name = strtolower($user_info->display_name);
//                 $user_login = strtolower($user_info->user_login);
//                 $user_email = strtolower($user_info->user_email);

//                 // Check if the search term matches any of the user's data fields
//                 if (strpos($display_name, $search) === false &&
//                     strpos($user_login, $search) === false &&
//                     strpos($user_email, $search) === false) {
//                     continue; // Skip if no match found
//                 }
//             }

//             // Add matched users to the result array
//             $liked_users[] = array(
//                 'ID' => $user_info->ID,
//                 'display_name' => $user_info->display_name,
//                 'user_login' => $user_info->user_login,
//                 'user_email' => $user_info->user_email,
//                 'avatar_url' => get_avatar_url($user_info->ID)
//             );
//         }
//     }

//     // If no users match the search criteria
//     if (empty($liked_users)) {
//         return new WP_REST_Response(array('message' => 'No matching users found', 'users' => array()), 200);
//     }

//     return new WP_REST_Response(array('message' => 'Users who liked the post', 'users' => $liked_users), 200);
// }
function get_users_who_liked_post(WP_REST_Request $request)
{
    $post_id = (int) $request->get_param('post_id'); // Get the post ID from the request
    $search = $request->get_param('search'); // Get the search query from request
    $page = (int) $request->get_param('page') ?: 1; // Get the current page or default to 1
    $per_page = (int) $request->get_param('per_page') ?: 10; // Get users per page or default to 10

    // Get the users who liked this post
    $likes = get_post_meta($post_id, 'post_likes', true);
    $likes = !empty($likes) ? $likes : array();

    // If no likes found
    if (empty($likes)) {
        return new WP_REST_Response(array('message' => 'No likes found for this post', 'users' => array()), 200);
    }

    // Get user details of those who liked the post
    $liked_users = array();
    foreach ($likes as $user_id) {
        $user_info = get_userdata($user_id);
        if ($user_info) {
            // Filter users based on the search query if provided
            if ($search) {
                $search = strtolower($search);
                $display_name = strtolower($user_info->display_name);
                $user_login = strtolower($user_info->user_login);
                $user_email = strtolower($user_info->user_email);

                // Check if the search term matches any of the user's data fields
                if (
                    strpos($display_name, $search) === false &&
                    strpos($user_login, $search) === false &&
                    strpos($user_email, $search) === false
                ) {
                    continue; // Skip if no match found
                }
            }

            // Add matched users to the result array
            $liked_users[] = array(
                'ID' => $user_info->ID,
                'display_name' => $user_info->display_name,
                'user_login' => $user_info->user_login,
                'user_email' => $user_info->user_email,
                'avatar_url' => get_avatar_url($user_info->ID)
            );
        }
    }

    // If no users match the search criteria
    if (empty($liked_users)) {
        return new WP_REST_Response(array('message' => 'No matching users found', 'users' => array()), 200);
    }

    // Implement pagination
    $total_users = count($liked_users);
    $total_pages = ceil($total_users / $per_page);
    $offset = ($page - 1) * $per_page;
    $paged_users = array_slice($liked_users, $offset, $per_page);

    // Pagination metadata
    $pagination = array(
        'total_users' => $total_users,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'per_page' => $per_page,
    );

    return new WP_REST_Response(array(
        'message' => 'Users who liked the post',
        'users' => $paged_users,
        // 'pagination' => $pagination
    ), 200);
}

// Register the endpoint for getting the users who liked a post with search and pagination functionality



// Register the endpoint for getting the users who liked a post with search functionality
// function register_likes_listing_endpoint()
// {
//     register_rest_route('custom-api/v1', '/post-likes', array(
//         'methods' => WP_REST_Server::READABLE,
//         'callback' => 'get_users_who_liked_post',
//         'permission_callback' => function () {
//             return is_user_logged_in(); // Ensure the user is logged in
//         },
//         'args' => array(
//             'post_id' => array(
//                 'required' => true,
//                 'validate_callback' => function ($param, $request, $key) {
//                     return is_numeric($param);
//                 }
//             ),
//             'search' => array(
//                 'required' => false,
//                 'validate_callback' => function ($param, $request, $key) {
//                     return is_string($param);
//                 }
//             ),
//         ),
//     ));
// }
// add_action('rest_api_init', 'register_likes_listing_endpoint');




