<?php
add_action('rest_api_init', function () {
    register_rest_route(
        'buddypress/v1',
        '/trending-feed',
        array(
            'methods' => 'GET',
            'callback' => 'get_latest_posts',
            'permission_callback' => '__return_true'
        )
    );
    register_rest_route('custom/v1', '/check-chat/', array(
        'methods' => 'GET',
        'callback' => 'check_user_chat',
        'permission_callback' => '__return_true', // Adjust permission if needed
    ));
});

// function get_latest_posts(WP_REST_Request $request)
// {
//     global $wpdb;
//     $user_id = get_current_user_id();
//     switch_to_user_language($user_id);

//     $page = $request->get_param('page') ? (int) $request->get_param('page') : 1;
//     $per_page = $request->get_param('per_page') ? (int) $request->get_param('per_page') : 10;
//     $search = $request->get_param('search') ? trim($request->get_param('search')) : '';

//     // Generate a unique cache key based on the request parameters
//     $cache_key = 'latest_posts_' . md5("page={$page}&per_page={$per_page}&search={$search}");

//     // Check if cached data exists
//     $cached_data = get_transient($cache_key);
//     if ($cached_data !== false) {
//         return new WP_REST_Response($cached_data, 200);
//     }

//     // Calculate the offset
//     $offset = ($page - 1) * $per_page;

//     // Prepare the base query
//     $query = "
//         SELECT p.ID, p.post_type, p.post_author, p.post_date,
//         u.user_nicename, um.meta_value as profile_pic
//         FROM $wpdb->posts p
//         LEFT JOIN $wpdb->users u ON p.post_author = u.ID
//         LEFT JOIN $wpdb->usermeta um ON u.ID = um.user_id AND um.meta_key = 'profile_pic'
//         WHERE p.post_type IN ('user_post', 'vehicle_post') AND p.post_status = 'publish'
//     ";

//     // Add search condition if a search term is provided
//     if (!empty($search)) {
//         $like_search = '%' . $wpdb->esc_like($search) . '%';
//         $query .= $wpdb->prepare(" AND (p.post_content LIKE %s OR u.user_nicename LIKE %s)", $like_search, $like_search);
//     }

//     // Add order and limit conditions
//     $query .= " ORDER BY p.post_date DESC LIMIT %d OFFSET %d";
//     $prepared_query = $wpdb->prepare($query, $per_page, $offset);

//     // Execute the query
//     $posts = $wpdb->get_results($prepared_query);

//     // Count total posts for pagination
//     $total_posts_query = "
//         SELECT COUNT(*)
//         FROM $wpdb->posts p
//         LEFT JOIN $wpdb->users u ON p.post_author = u.ID
//         WHERE p.post_type IN ('user_post', 'vehicle_post') AND p.post_status = 'publish'
//     ";

//     if (!empty($search)) {
//         $total_posts_query .= $wpdb->prepare(" AND (p.post_content LIKE %s OR u.user_nicename LIKE %s)", $like_search, $like_search);
//     }

//     $total_posts = $wpdb->get_var($total_posts_query);

//     // Prepare the response data
//     $response_posts = array_map('prepare_post_response', $posts);
//     $response_data = array(
//         'success' => true,
//         'posts' => $response_posts,
//         'total_posts' => (int) $total_posts,
//         'total_pages' => ceil($total_posts / $per_page),
//     );

//     // Store the response in cache for 5 minutes (300 seconds)
//     set_transient($cache_key, $response_data, 1);

//     return new WP_REST_Response($response_data, 200);
// }
function get_latest_posts(WP_REST_Request $request)
{
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        error_log('Authorization Header: ' . $_SERVER['HTTP_AUTHORIZATION']);
    } else {
        error_log('Authorization header not found!');
    }
    global $wpdb;
    $user_id = get_current_user_id();
    switch_to_user_language($user_id);

    $page = $request->get_param('page') ? (int) $request->get_param('page') : 1;
    $per_page = $request->get_param('per_page') ? (int) $request->get_param('per_page') : 10;
    $search = $request->get_param('search') ? trim($request->get_param('search')) : '';

    $cache_key = 'latest_posts_' . md5("page={$page}&per_page={$per_page}&search={$search}");
    $cached_data = get_transient($cache_key);

    if ($cached_data !== false) {
        return new WP_REST_Response($cached_data, 200);
    }

    $offset = ($page - 1) * $per_page;

    $query = "
        SELECT p.ID, p.post_type, p.post_author, p.post_date,
        u.user_nicename, um.meta_value as profile_pic, COUNT(*) OVER() AS total_count
        FROM $wpdb->posts p
        LEFT JOIN $wpdb->users u ON p.post_author = u.ID
        LEFT JOIN $wpdb->usermeta um ON u.ID = um.user_id AND um.meta_key = 'profile_pic'
        WHERE p.post_type IN ('user_post', 'vehicle_post') AND p.post_status = 'publish'
    ";

    if (!empty($search)) {
        $like_search = '%' . $wpdb->esc_like($search) . '%';
        $query .= $wpdb->prepare(" AND (p.post_content LIKE %s OR u.user_nicename LIKE %s)", $like_search, $like_search);
    }

    $query .= " ORDER BY p.post_date DESC LIMIT %d OFFSET %d";
    $prepared_query = $wpdb->prepare($query, $per_page, $offset);

    $posts = $wpdb->get_results($prepared_query);
    $total_posts = isset($posts[0]) ? $posts[0]->total_count : 0;

    $response_posts = array_map('prepare_post_response', $posts);
    $response_data = array(
        'success' => true,
        'posts' => $response_posts,
        'total_posts' => (int) $total_posts,
        'total_pages' => ceil($total_posts / $per_page),
    );

    set_transient($cache_key, $response_data, 300);
    // error_log( "heder logs : ---".json_encode(getallheaders()));

    return new WP_REST_Response($response_data, 200);
}


function get_profile_info($post_id, $post_type)
{
    $profile_id = "";
    $vehicle_id = "";
    $user_id = get_post_field('post_author', $post_id);
    $member_type = "";

    if ($post_type === 'user_post') {
        $author_id = get_post_field('post_author', $post_id);
        $nickname = get_the_author_meta('display_name', $author_id);
        $profile_pic = bp_core_fetch_avatar(array('item_id' => $author_id, 'html' => false));
        $profile_id = $author_id;
        $member_type = xprofile_get_field_data('Member Type', $author_id); // Replace 'Member Type' with the actual field name in your profile setup
        // Check if the avatar returned by BuddyPress is a Gravatar URL
        if (strpos($profile_pic, 'gravatar.com') !== false) {
            $profile_pic = ""; // Set to null if it's a Gravatar URL
        }
    } elseif ($post_type === 'vehicle_post') {
        $vehicle_id = get_post_meta($post_id, 'vehicle_id', true);
        if ($vehicle_id) {
            $nickname = get_post_meta($vehicle_id, 'vehicle_nickname', true);
            $profile_pic_id = get_post_meta($vehicle_id, 'profile_image', true);
            $profile_pic = wp_get_attachment_url($profile_pic_id);
            $profile_id = $vehicle_id;
        } else {
            $nickname = '';
            $profile_pic = '';
        }
    }

    return array(
        'profile_id' => $profile_id,
        'nickname' => $nickname,
        'profile_pic' => $profile_pic,
        'user_id' => $user_id, // Include vehicle_id in the profile info
        'member_type' => $member_type // Add member type to the response

    );
}