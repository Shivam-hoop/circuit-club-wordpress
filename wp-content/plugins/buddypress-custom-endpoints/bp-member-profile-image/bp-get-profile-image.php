<?php
/*
Plugin Name: Buddypress get profile image
Plugin URI:  https://yourwebsite.com/
Description: A custom plugin to create vehicle information.
Version:     1.0
Author:      Shivam Jha
Author URI:  https://yourwebsite.com/
License:     GPL2
*/
add_action('rest_api_init', function () {
    register_rest_route(
        'buddypress/v1',
        '/members/(?P<id>\d+)/avatar',
        array(
            'methods' => 'GET',
            'callback' => 'get_custom_member_avatar',
            'permission_callback' => '__return_true',
        )
    );
});

function get_custom_member_avatar($data)
{
    $user_id = $data['id'];

    // Use BuddyPress function to get the avatar URL
    $avatar_image_url = bp_core_fetch_avatar(
        array(
            'item_id' => $user_id,
            'type' => 'full',
            'html' => false
        )
    );

    if (strpos($avatar_image_url, 'gravatar.com') !== false) {
        $avatar_image_url = "";
    }

    return new WP_REST_Response([
        array('image' => $avatar_image_url),
    ], 200);
}

