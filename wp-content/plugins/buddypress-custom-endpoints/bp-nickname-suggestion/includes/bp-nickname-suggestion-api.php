<?php

add_action('rest_api_init', function() {
    register_rest_route('buddypress/v1', 'nickname/suggestions', array(
        'methods' => 'POST',
        'callback' => 'nickname_suggestions',
        'permission_callback' => '__return_true',
    ));
});


function nickname_suggestions(WP_REST_Request $request) {
    $desired_nickname = sanitize_text_field($request->get_param('nickname'));

    if (empty($desired_nickname)) {
        return new WP_REST_Response(array(
            'code' => 'empty_nickname',
            'message' => __('Nickname cannot be empty', 'buddypress'),
            'data' => array('status' => 400)
        ), 400);
    }

    // Check if the desired nickname exists
    if (!nickname_exists($desired_nickname)) {
        return new WP_REST_Response(array(
            'code' => 'nickname_available',
            'message' => __('Nickname is available', 'buddypress'),
            'suggestions' => array($desired_nickname),
            'data' => array('status' => 200)
        ), 200);
    }

    // Generate suggestions
    $suggestions = generate_unique_nickname_suggestions($desired_nickname);

    return new WP_REST_Response(array(
        'code' => 'nickname_taken',
        'message' => __('Nickname is already taken. Here are some suggestions.', 'buddypress'),
        'suggestions' => $suggestions,
        'data' => array('status' => 200)
    ), 200);
}

function generate_unique_nickname_suggestions($desired_nickname) {
    $suggestions = array();
    $attempts = 0;
    $max_attempts = 100; // Limit the number of attempts to avoid infinite loops
    $how_many_suggestion = 3;
    while (count($suggestions) < $how_many_suggestion && $attempts < $max_attempts) {
        $suffix = '-' . wp_generate_password(3, false, false); // Generate a random suffix
        $suggested_nickname = $desired_nickname . $suffix;

        if (!nickname_exists($suggested_nickname)) {
            $suggestions[] = $suggested_nickname;
        }

        $attempts++;
    }

    return $suggestions;
}

function nickname_exists($nickname) {
    // Check if the nickname exists in vehicle nicknames
    $existing_vehicles = get_posts(
        array(
            'post_type' => 'vehicle',
            'meta_query' => array(
                array(
                    'key' => 'vehicle_nickname',
                    'value' => $nickname,
                    'compare' => '='
                )
            )
        )
    );

    if (!empty($existing_vehicles)) {
        return true;
    }

    // Check if the nickname exists in user logins
    if (username_exists($nickname)) {
        return true;
    }

    return false;
}