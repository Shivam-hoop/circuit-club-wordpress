<?php

add_action('rest_api_init', function () {
    // Register the forgot password endpoint
    register_rest_route('buddypress/v1', '/forgot-password', array(
        'methods' => 'POST',
        'callback' => 'send_reset_password_email',
        'permission_callback' => '__return_true',
        'args' => array(
            'email' => array(
                'required' => true,
                'validate_callback' => 'is_email'
            )
        ),
    ));
    // Register the resend reset password link endpoint
    register_rest_route('buddypress/v1', '/resend-reset-link', array(
        'methods' => 'POST',
        'callback' => 'resend_reset_password_email',
        'permission_callback' => '__return_true',
        'args' => array(
            'email' => array(
                'required' => true,
                'validate_callback' => 'is_email'
            )
        ),
    ));
    // Register the reset password endpoint
    register_rest_route('buddypress/v1', '/reset-password', array(
        'methods' => 'POST',
        'callback' => 'reset_user_password',
        'permission_callback' => '__return_true',
        'args' => array(
            'key' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'login' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'password' => array(
                'required' => true,
                'validate_callback' => function($param, $request, $key) {
                    return strlen($param) >= 8;
                }
            ),
            'confirm_password' => array(
                'required' => true,
                'validate_callback' => function($param, $request, $key) {
                    return strlen($param) >= 8;
                }
            )
        ),
    ));
});

/**
 * Send reset password email
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function send_reset_password_email(WP_REST_Request $request) {
    $email = sanitize_email($request->get_param('email'));
    if (empty($email) || !is_email($email)) {
        return new WP_Error('invalid_email', __('Invalid email address', 'buddypress'), array('status' => 400));
    }

    $user = get_user_by('email', $email);
    if (!$user) {
        return new WP_REST_Response(array(
            'code' => 'user_not_found',
            'message' => __('There is no account with that email address.', 'buddypress'),
            'data' => array('status' => 404)
        ), 404);
    }

    // Rate limiting implementation using transients
    $transient_name = 'password_reset_' . $user->ID;
    if (get_transient($transient_name)) {
        return new WP_Error('too_many_requests', __('Too many requests. Please try again later.', 'buddypress'), array('status' => 429));
    }
    set_transient($transient_name, true, HOUR_IN_SECONDS);

    $reset_key = get_password_reset_key($user);
    if (is_wp_error($reset_key)) {
        return new WP_Error('reset_key_error', $reset_key->get_error_message(), array('status' => 500));
    }

    // $frontend_app_url = "https://circuit-club.com/reset-password";
    $frontend_app_url = FRONT_APP_URL."/reset-password";
    $reset_link = add_query_arg(array('key' => $reset_key, 'login' => rawurlencode($user->user_login)), $frontend_app_url);

    wp_mail($email, __('Password Reset', 'buddypress'), sprintf(__('Click this link to reset your password: %s', 'buddypress'), esc_url($reset_link)));

    return new WP_REST_Response(array(
        'code' => 'bp_email_sent',
        'message' => __('If an account with that email exists, you will receive a password reset email shortly.', 'buddypress'),
        'data' => array('status' => 200)
    ));
}

/**
 * Resend reset password email
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function resend_reset_password_email(WP_REST_Request $request) {
    return send_reset_password_email($request);
}

/**
 * Reset user password
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function reset_user_password(WP_REST_Request $request) {
    $key = sanitize_text_field($request->get_param('key'));
    $login = sanitize_text_field($request->get_param('login'));
    $new_password = $request->get_param('password');
    $confirm_password = $request->get_param('confirm_password');

    if ($new_password !== $confirm_password) {
        return new WP_REST_Response(array(
            'code' => 'password_mismatch',
            'message' => __('Passwords do not match', 'buddypress'),
            'data' => array('status' => 400)
        ), 400);
    }

    $user = check_password_reset_key($key, $login);
    if (is_wp_error($user)) {
        return new WP_REST_Response(array(
            'code' => 'invalid_key',
            'message' => __('The password reset link has expired or is invalid. Please request a new one.', 'buddypress'),
            'data' => array('status' => 400)
        ), 400);
    }

    reset_password($user, $new_password);

    return new WP_REST_Response(array(
        'code' => 'bp_password_reset_success',
        'message' => __('Password has been reset', 'buddypress'),
        'data' => array('status' => 200)
    ), 200);
}
