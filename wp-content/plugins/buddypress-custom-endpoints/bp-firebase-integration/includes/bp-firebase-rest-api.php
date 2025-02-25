<?php 
add_action('rest_api_init', function () {
    register_rest_route('buddypress/v1', '/save-firebase-token', array(
        'methods' => 'POST',
        'callback' => 'save_firebase_token',
        'permission_callback' => function () {
            return is_user_logged_in();
        }
    ));
    register_rest_route('buddypress/v1', '/delete-firebase-token', array(
        'methods' => 'DELETE',
        'callback' => 'delete_firebase_token',
        'permission_callback' => function () {
            return is_user_logged_in();
        }
    ));
});

function save_firebase_token(WP_REST_Request $request) {
    $user_id = get_current_user_id();
    error_log("User ID: " . $user_id); // Log the user ID

    $firebase_token = sanitize_text_field($request->get_param('token'));

    error_log("Initializing the integration function...");

    if ($user_id && $firebase_token) {
        error_log("Firebase token: " . $firebase_token); // Log the firebase token

        update_user_meta($user_id, 'firebase_token', $firebase_token);
        return new WP_REST_Response('Token saved successfully', 200);
    } else {
        return new WP_REST_Response('Error saving token', 400);
    }
}

function delete_firebase_token(WP_REST_Request $request) {
    $user_id = get_current_user_id();

    if ($user_id) {
        delete_user_meta($user_id, 'firebase_token');
        return new WP_REST_Response('Token deleted successfully', 200);
    } else {
        return new WP_REST_Response('Error deleting token', 400);
    }
}
