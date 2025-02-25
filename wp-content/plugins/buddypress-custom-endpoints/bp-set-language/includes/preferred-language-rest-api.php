<?php
add_action('rest_api_init', function () {
    register_rest_route('buddypress/v1', '/set-language', array(
        'methods' => 'POST',
        'callback' => 'set_user_language_preference',
        'permission_callback' => function () {
            return is_user_logged_in();
        }
    )
    );
    register_rest_route('buddypress/v1', '/get-language', array(
        'methods' => 'GET',
        'callback' => 'get_user_language',
        'permission_callback' => function () {
            return is_user_logged_in();
        }
    )
    );
});
function set_user_language_preference(WP_REST_Request $request)
{

    $user_id = get_current_user_id();
    $language = sanitize_text_field($request->get_param('language'));
    error_log($language);
    if (!in_array($language, array('en', 'de'))) {
        return new WP_Error('invalid_language', 'Invalid language code', array('status' => 400));
    }

    save_user_language_preference($user_id, $language);
    return new WP_REST_Response(array('success' => true), 200);
}
function get_user_language(WP_REST_Request $request)
{

    $user_id = get_current_user_id();
    $language = get_user_language_preference($user_id);

    // save_user_language_preference($user_id, $language);
    return new WP_REST_Response(array('success' => true, 'language' => $language), 200);
}
