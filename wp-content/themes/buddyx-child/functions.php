<?php
@ini_set('upload_max_size', '20M');
@ini_set('post_max_size', '20M');
@ini_set('max_execution_time', '300');
function my_theme_enqueue_styles()
{
    $parent_style = 'parent-style';

    wp_enqueue_style($parent_style, get_template_directory_uri() . '/style.css');
    wp_enqueue_style(
        'child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array($parent_style),
        wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'my_theme_enqueue_styles');


add_action('bp_core_activated_user', 'custom_redirect_after_activation', 10, 3);

function custom_redirect_after_activation($user_id, $key, $user)
{

    $redirect_url = FRONT_APP_URL . '/success';

    // Check if the user activation key is valid
    if (bp_core_activate_signup($key)) {
        // Redirect to the custom URL
        wp_redirect($redirect_url);
        exit;
    }

}



// Hook into the before_delete_post action
function delete_vehicle_posts_when_vehicle_is_deleted($post_id)
{
    // Check if the post being deleted is a vehicle
    if (get_post_type($post_id) == 'vehicle') {
        // Get all vehicle posts associated with this vehicle
        $vehicle_posts = get_posts(
            array(
                'post_type' => 'vehicle_post',
                'meta_query' => array(
                    array(
                        'key' => 'vehicle_id', // Assuming you have a custom field 'vehicle_id' in vehicle_post
                        'value' => $post_id,
                        'compare' => '='
                    )
                ),
                'numberposts' => -1 // To get all posts
            )
        );

        // Loop through and delete each vehicle post
        foreach ($vehicle_posts as $vehicle_post) {
            wp_delete_post($vehicle_post->ID, true);
        }
    }
}
add_action('before_delete_post', 'delete_vehicle_posts_when_vehicle_is_deleted');

function custom_bp_avatar_upload_size()
{
    return 10485760; // 10MB in bytes
}
add_filter('bp_core_avatar_original_max_filesize', 'custom_bp_avatar_upload_size');

function custom_bp_member_cover_image_upload_size($settings = array())
{
    return 10485760; // 10MB in bytes
}
add_filter('bp_attachments_get_max_upload_file_size', 'custom_bp_member_cover_image_upload_size');

// Disable the default BuddyPress activation email
add_filter('bp_core_signup_send_validation_email_to', function ($user_email, $user_id) {
    return ''; // Prevent default email
}, 10, 2);


// Set BP to use wp_mail
add_filter('bp_email_use_wp_mail', '__return_true');

// Set messages to HTML for BP sent emails.
add_filter('wp_mail_content_type', function ($default) {
    if (did_action('bp_send_email')) {
        return 'text/html';
    }
    return $default;
});

// Use HTML template
add_filter(
    'bp_email_get_content_plaintext',
    function ($content, $property, $transform, $bp_email) {
        if (!did_action('bp_send_email')) {
            return $content;
        }
        return $bp_email->get_template('add-content');
    },
    10,
    4
);

function disable_duplicate_comment_check($dupe_id, $commentdata)
{
    return null; // Returning null bypasses the duplicate comment check.
}
add_filter('duplicate_comment_id', 'disable_duplicate_comment_check', 10, 2);

function reduce_comment_flood_time($seconds)
{
    return null; // set flood time to 1 second
}
add_filter('comment_flood_filter', 'reduce_comment_flood_time', 10, 1);

// function log_event_activity($event_id) {
//     if ($event_id) {
//         $event_title = get_the_title($event_id);

//         bp_activity_add(array(
//             'action'  => sprintf(__('%s created a new event: %s', 'textdomain'), bp_core_get_userlink(get_current_user_id()), $event_title),
//             'component' => 'events',
//             'type' => 'new_event',
//             'primary_link' => get_permalink($event_id),
//             'item_id' => $event_id,
//             'user_id' => get_current_user_id(),
//         ));
//     }
// }
// add_action('save_post_event', 'log_event_activity');


function custom_buddypress_default_avatar_url($url, $params)
{
    // Define your custom avatar URL
    $custom_avatar_url = '';

    // Check if the URL is using Gravatar and if no custom avatar has been set
    if (strpos($url, 'gravatar.com/avatar/') !== false && isset($params['object']) && $params['object'] === 'user') {
        // Return the custom avatar URL
        return $custom_avatar_url;
    }

    return $url;
}
add_filter('bp_core_fetch_avatar_url', 'custom_buddypress_default_avatar_url', 10, 2);



add_filter('bp_rest_messages_prepare_value', 'customize_message_response', 10, 3);

function customize_message_response($response, $item, $request)
{
    $thread_id = $request->thread_id; // ID of the thread
    $last_sender_id = $request->last_sender_id; // Last sender's ID

    // Fetch the group_name (or meta data related to the conversation)
    $group_name = get_post_meta($thread_id, 'group_name', true);
    if (!empty($group_name)) {
        // If group_name is present, it's a group conversation
        $response->data['group'] = true;
        $response->data['title'] = $group_name;
    } else {
        $response->data['group'] = false;

    }
    // Fetch sender's profile data
    $sender_data = get_userdata($last_sender_id);
    if ($sender_data) {
        $sender_name = $sender_data->display_name;

        // Get sender's avatar (using BuddyPress functions)
        $avatar_url = bp_core_fetch_avatar(array(
            'item_id' => $last_sender_id,
            'type' => 'full',
            'html' => false
        ));

        // Add group_name, sender_name, and avatar to the response
        // $response->data['group_name'] = $group_name;
        // $response->data['sender_name'] = $sender_name;
        // $response->data['sender_avatar'] = $avatar_url;

    }

    return $response;
}

// Disable private message email notifications in BuddyPress
function disable_private_message_email_notifications()
{
    // Remove the action that sends the private message email
    remove_action('messages_message_sent', 'messages_notification_new_message', 10);
}
add_action('bp_init', 'disable_private_message_email_notifications');

/**
 * Modify JWT Auth Error Response for Incorrect Password
 */
function modify_jwt_auth_incorrect_password_error($user, $username, $password)
{
    // Check if the user authentication failed
    if (is_wp_error($user)) {
        $error_code = $user->get_error_code();

        // Modify the error message only if the error is for an incorrect password
        if ($error_code === 'incorrect_password') {
            $custom_lost_password_url = FRONT_APP_URL . '/forget-password'; // Replace with your custom URL

            $new_message = sprintf(
                '<strong>Error:</strong> The password you entered for the email address <strong>%s</strong> is incorrect. <a href="%s">Lost your password?</a>',
                esc_html($username),
                esc_url($custom_lost_password_url)
            );

            // Return a new WP_Error object with the modified message
            return new WP_Error(
                'incorrect_password',
                $new_message,
                ['status' => 403]
            );
        }
    }

    return $user;
}
add_filter('authenticate', 'modify_jwt_auth_incorrect_password_error', 30, 3);


function custom_comment_response($data, $comment, $request)
{
    // Get the comment author information
    error_log(json_encode($data));
    $author_id = $comment->user_id;
    $profile_picture_url = get_avatar_url($author_id, array('size' => 96));
    // Create the custom response structure
    $custom_data = array(
        'comment_ID' => $data->data['id'],
        'comment_post_ID' => $data->data['post'],
        'comment_author' => $data->data['author_name'],
        'comment_author_email' => $data->data['author_email'],
        'comment_author_url' => $data->data['author_url'],
        'comment_author_IP' => $data->data['author_ip'] ?? '',
        'comment_date' => $data->data['date'],
        'comment_date_gmt' => $data->data['date_gmt'],
        'comment_content' => $data->data['content']['rendered'],
        'comment_karma' => 0,
        'comment_approved' => $data->data['status'] === 'approved' ? 1 : 0,
        'comment_agent' => $data->data['author_user_agent'],
        'comment_type' => $data->data['type'],
        'comment_parent' => $data->data['parent'],
        'user_id' => $data->data['author'],
        'profile_picture_url' => $profile_picture_url,
        'replies' => [],  // This will be empty for the newly added comment
        'replies_count' => 0,   // No replies at the time of creation
    );

    // Replace the original response data with the custom data
    $data->data = $custom_data;

    return $data;
}
add_filter('rest_prepare_comment', 'custom_comment_response', 10, 3);

add_action('admin_init', 'allow_subscriber_to_access_countries_only');

function allow_subscriber_to_access_countries_only()
{
    // Get the 'subscriber' role
    $role = get_role('subscriber');

    if ($role) {
        // Add custom capability for accessing countries data
        $role->add_cap('view_woocommerce_countries_data'); // Custom capability for countries data
    }
}

add_action('bp_rest_notifications_get_items', function ($notifications, $response, $request) {
    // Clone the query arguments used in the original function
    $args = array(
        'user_id'           => $request->get_param('user_id'),
        'user_ids'          => $request->get_param('user_ids'),
        'item_id'           => $request->get_param('item_id'),
        'secondary_item_id' => $request->get_param('secondary_item_id'),
        'component_name'    => $request->get_param('component_name'),
        'component_action'  => $request->get_param('component_action'),
        'order_by'          => $request->get_param('order_by'),
        'sort_order'        => strtoupper($request->get_param('sort_order')),
        'is_new'            => $request->get_param('is_new'),
    );

    if (empty($request->get_param('component_action'))) {
        $args['component_action'] = false;
    }

    if (!empty($args['user_ids'])) {
        $args['user_id'] = $args['user_ids'];
    } elseif (empty($args['user_id'])) {
        $args['user_id'] = bp_loggedin_user_id();
    }

    if (empty($request->get_param('component_name'))) {
        $args['component_name'] = false;
    }

    // Count total notifications with filters applied
    $total_notifications = BP_Notifications_Notification::get_total_count($args);

    // Structure the response properly
    $response_data = array(
        'notifications' => $response->get_data(), // Original notifications array
        'count'         => $total_notifications,  // Total count of notifications
    );

    // Set the modified response
    $response->set_data($response_data);
}, 10, 3);




