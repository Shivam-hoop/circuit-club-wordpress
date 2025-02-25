<?php

add_action('rest_api_init', function () {
    register_rest_route('buddypress/v1', '/activate', array(
        'methods' => 'GET',
        'callback' => 'handle_user_activation',
        'permission_callback' => '__return_true',
    ));
});

function handle_user_activation(WP_REST_Request $request) {
    $key = $request->get_param('key');
    $user_email = $request->get_param('user_email');

    if (bp_core_activate_signup($key)) {
        // Optionally, log the user in automatically after activation
        wp_set_auth_cookie($user_email, true);
        return new WP_REST_Response(['success' => true], 200);
    } else {
        return new WP_REST_Response(['success' => false, 'message' => 'Activation failed.'], 400);
    }
}