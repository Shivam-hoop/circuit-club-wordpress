<?php
/*
Plugin Name: Buddypress get cover image
Plugin URI:  https://yourwebsite.com/
Description: A custom plugin to create vehicle information.
Version:     1.0
Author:      Shivam Jha
Author URI:  https://yourwebsite.com/
License:     GPL2
*/
add_action('rest_api_init', function () {
    register_rest_route('buddypress/v1', '/members/(?P<id>\d+)/cover', array(
        'methods' => 'GET',
        'callback' => 'get_custom_member_cover',
        'permission_callback' => '__return_true',
    )
    );
});

function get_custom_member_cover($data)
{
    $user_id = $data['id'];
    $cover_image_url = bp_attachments_get_attachment('url', array(
        'object_dir' => 'members',
        'item_id' => $user_id,
        'type' => 'cover-image',
    )
    );

    if (empty($cover_image_url)) {
        $cover_image_url = ''; // Default cover image URL
    }

    return new WP_REST_Response([
        array(
            'image' => $cover_image_url,
        )
    ], 200);
}

