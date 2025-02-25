<?php
require_once(plugin_dir_path(__FILE__) . 'utils/follow-manager.php');

add_action('rest_api_init', function () {

    register_rest_route('buddypress/v1', '/vehicle/follow-count', [
        'methods' => 'GET',
        'callback' => 'vehicle_follower_count',
        'permission_callback' => 'follow_unfollow_permissions_check',
    ]);
    register_rest_route('buddypress/v1', '/follow', [
        'methods' => 'POST',
        'callback' => 'handle_follow_request',
        'permission_callback' => 'is_user_logged_in',
    ]);
    // Endpoint to unfollow a user or vehicle
    register_rest_route('buddypress/v1', '/unfollow', [
        'methods' => 'POST',
        'callback' => 'handle_unfollow_request',
        'permission_callback' => 'is_user_logged_in',
    ]);
    register_rest_route('buddypress/v1', '/followers/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => 'get_followers_list_endpoint',
        'permission_callback' => '__return_true',
    ]);

    // Endpoint to get a user's following list
    register_rest_route('buddypress/v1', '/following/(?P<user_id>\d+)', [
        'methods' => 'GET',
        'callback' => 'get_user_following_list_endpoint',
        'permission_callback' => '__return_true',
    ]);
    register_rest_route('buddypress/v1', '/follow-status', [
        'methods' => 'GET',
        'callback' => 'handle_is_following_request',
        'permission_callback' => 'is_user_logged_in',
    ]);

});

function follow_unfollow_permissions_check(WP_REST_Request $request)
{
    return is_user_logged_in();
}


function vehicle_follower_count(WP_REST_Request $request)
{
    // $current_user_id = get_current_user_id();
    $vehicle_id = intval($request->get_param('vehicle_id'));

    if (!$vehicle_id) {
        return new WP_Error('missing_user_id', 'Vehcile ID is required', array('status' => 400));
    }

    $followers_count = 0;

    // Get followers count from the user's meta
    $followers_count = (int) get_post_meta($vehicle_id, 'vehicle_followers_count', true);

    return new WP_REST_Response([
        // 'following_count' => $following_count,
        'followers_count' => $followers_count,
    ], 200);
}

function handle_follow_request($request)
{
    $user_id = get_current_user_id();
    $target_id = sanitize_text_field($request->get_param('id'));
    $type = sanitize_text_field($request->get_param('type')); // 'user' or 'vehicle'

    if (!$user_id || !$target_id || !in_array($type, ['user', 'vehicle'])) {
        return new WP_Error('invalid_request', 'Invalid request parameters', ['status' => 400]);
    }
    // Prevent users from following their own profile
    if ($type === 'user' && $user_id == $target_id) {
        return new WP_Error('invalid_request', 'You cannot follow your own profile.', ['status' => 400]);
    }
    // Check if the user is already following the target
    if (is_following($user_id, $target_id, $type)) {
        return new WP_Error('already_following', 'You are already following this account.', ['status' => 400]);
    }

    if ($type === 'user') {
        follow_user($user_id, $target_id);
        send_follow_notification($user_id, $target_id, 'user');

    } elseif ($type === 'vehicle') {
        follow_vehicle($user_id, $target_id);
        send_follow_notification($user_id, $target_id, 'vehicle');

    }

    return new WP_REST_Response(['status' => 'success', 'message' => 'Followed successfully'], 200);
}
function handle_unfollow_request($request)
{
    $user_id = get_current_user_id();
    $target_id = sanitize_text_field($request->get_param('id'));
    $type = sanitize_text_field($request->get_param('type')); // 'user' or 'vehicle'

    if (!$user_id || !$target_id || !in_array($type, ['user', 'vehicle'])) {
        return new WP_Error('invalid_request', 'Invalid request parameters', ['status' => 400]);
    }
    // Prevent users from following their own profile
    if ($type === 'user' && $user_id == $target_id) {
        return new WP_Error('invalid_request', 'You cannot unfollow your own profile.', ['status' => 400]);
    }
    // Prevent users from unfollowing their own profile


    // Check if the user is not following the target
    if (!is_following($user_id, $target_id, $type)) {
        return new WP_Error('not_following', 'You are not following this account.', ['status' => 400]);
    }

    if ($type === 'user') {
        unfollow_user($user_id, $target_id);
    } elseif ($type === 'vehicle') {
        unfollow_vehicle($user_id, $target_id);
    }

    return new WP_REST_Response(['status' => 'success', 'message' => 'Unfollowed successfully'], 200);
}

function get_followers_list_endpoint($request)
{
    $target_id = (int) $request['id']; // Could be user or vehicle ID
    $type = $request->get_param('type') ?: 'user'; // Default to 'user'
    $page = (int) $request->get_param('page') ?: 1; // Default to page 1
    $per_page = (int) $request->get_param('per_page') ?: 10; // Default to 10 items per page
    $search = $request->get_param('search'); // Search filter

    if (!$target_id) {
        return new WP_Error('invalid_id', 'Invalid ID', ['status' => 400]);
    }

    // Fetch followers based on the type (user or vehicle)
    $followers = get_followers_list($target_id, $type, $search);

    // Calculate total followers and apply pagination
    $total_followers = count($followers);
    $offset = ($page - 1) * $per_page;
    $paginated_followers = array_slice($followers, $offset, $per_page);

    return new WP_REST_Response([
        'data' => $paginated_followers,
        'total' => $total_followers,
        'page' => $page,
        'per_page' => $per_page,
        'total_pages' => ceil($total_followers / $per_page)
    ], 200);
}


function get_user_following_list_endpoint($request)
{
    $user_id = (int) $request['user_id'];
    $page = (int) $request->get_param('page') ?: 1; // Default to page 1
    $per_page = (int) $request->get_param('per_page') ?: 10; // Default to 10 items per page
    $search = $request->get_param('search'); // Search filter


    if (!$user_id) {
        return new WP_Error('invalid_user', 'Invalid user ID', ['status' => 400]);
    }

    $following = get_user_following_list($user_id, $search);

    // Calculate total following and apply pagination
    $total_following = count($following);
    $offset = ($page - 1) * $per_page;
    $paginated_following = array_slice($following, $offset, $per_page);

    return new WP_REST_Response([
        'data' => $paginated_following,
        'total' => $total_following,
        'page' => $page,
        'per_page' => $per_page,
        'total_pages' => ceil($total_following / $per_page)
    ], 200);

    // return new WP_REST_Response($following, 200);
}

function handle_is_following_request($request)
{
    $user_id = get_current_user_id();
    $target_id = sanitize_text_field($request->get_param('id'));
    $type = sanitize_text_field($request->get_param('type')); // 'user' or 'vehicle'

    if (!$user_id || !$target_id || !in_array($type, ['user', 'vehicle'])) {
        return new WP_Error('invalid_request', 'Invalid request parameters', ['status' => 400]);
    }

    $is_following = is_following($user_id, $target_id, $type);

    return new WP_REST_Response(['is_following' => $is_following], 200);
}

