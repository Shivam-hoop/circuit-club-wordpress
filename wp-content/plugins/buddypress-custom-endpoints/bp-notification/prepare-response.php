<?php
add_filter('bp_rest_notifications_prepare_value', 'customize_bp_notifications_response', 10, 3);

function customize_bp_notifications_response($response, $notification, $request)
{
    // Retrieve the notification ID from the response
    $notification_id = $response->data['id'];
    $user_id = get_current_user_id();

    // Fetch the notification manually
    $notification = bp_notifications_get_notification($notification_id);
    $preferred_language = get_user_language_preference($user_id);
    $cap_preferred_language = strtoupper($preferred_language);

    switch_to_locale($preferred_language . '_' . $cap_preferred_language); // or another locale based on your needs

    // Ensure the notification object is valid
    if (empty($notification) || !isset($notification->user_id)) {
        return $response; // Avoid modifying the response if the notification is empty
    }

    // Sanitize data for security
    $user_id = intval($notification->user_id);
    $item_id = intval($notification->item_id);
    $secondary_item_id = intval($notification->secondary_item_id);
    $component = sanitize_text_field($response->data['component']);
    $action = sanitize_text_field($response->data['action']);
    // Fetch user display name safely
    // $user_name = esc_html(bp_core_get_user_displayname($item_id));

    // // Initialize custom message and avatar URL
    // $message = '';
    // $avatar_url = '';
    // $textDomain = 'default';
    // // Handle different types of notifications dynamically
    // switch ($component) {
    //     case 'friends':
    //         if ($action === 'friendship_accepted') {
    //             $message = sprintf(__('accepted your friendship request', $textDomain));
    //             $title = sprintf(__('%s', $user_name));
    //         }
    //         break;
    //     case 'messages':
    //         if ($action === 'new_message') {
    //             $user_name = esc_html(bp_core_get_user_displayname(user_id_or_username: $secondary_item_id));
    //             $avatar_url = bp_core_fetch_avatar(array(
    //                 'item_id' => $secondary_item_id,
    //                 'type' => 'thumb', // You can use 'full' if you want a larger avatar
    //                 'html' => false  // Set to false to get the URL instead of HTML <img>
    //             ));
    //             // Query the database to get the thread_id using the message_id ($item_id)
    //             global $wpdb;
    //             $table = $wpdb->prefix . 'bp_messages_messages';
    //             $thread_id = $wpdb->get_var($wpdb->prepare(
    //                 "SELECT thread_id FROM $table WHERE id = %d",
    //                 $item_id // $item_id contains the message_id
    //             ));
    //             // Ensure the thread_id was found
    //             if (!empty($thread_id)) {
    //                 $redirectUrl = FRONT_APP_URL . "/chats?room=" . $thread_id; // Create the URL to redirect to the message thread
    //             }

    //             $message = sprintf(__('sent you a new private message', $textDomain));
    //             $title = sprintf('%s', $user_name);
    //         }
    //         break;
    //     case 'groups':
    //         if ($action === 'group_invite') {
    //             $message = sprintf(__('invited you to join a group', $textDomain));
    //             $title = sprintf('%s', $user_name);

    //         }
    //         break;
    //     case 'activity':
    //         if ($action === 'activity_reply') {
    //             $message = sprintf(__('replied to your activity.', $textDomain));
    //             $title = sprintf('%s', $user_name);

    //         }
    //         break;
    //     case 'follow':
    //         if ($action === 'new_follow_request') {
    //             $avatar_url = bp_core_fetch_avatar(array(
    //                 'item_id' => $item_id,
    //                 'type' => 'thumb', // You can use 'full' if you want a larger avatar
    //                 'html' => false  // Set to false to get the URL instead of HTML <img>
    //             ));
    //             $redirectUrl = FRONT_APP_URL . "/profile?user_id=" . $item_id;
    //             $message = sprintf(__('follows you now.', $textDomain));
    //             $title = sprintf('%s', $user_name);
    //         }
    //         break;
    //     case 'like':
    //         if ($action === 'post_like') {
    //             $user_name = esc_html(bp_core_get_user_displayname($secondary_item_id));
    //             $avatar_url = bp_core_fetch_avatar(array(
    //                 'item_id' => $secondary_item_id,
    //                 'type' => 'thumb', // You can use 'full' if you want a larger avatar
    //                 'html' => false  // Set to false to get the URL instead of HTML <img>
    //             ));
    //             $redirectUrl = getSinglePost($item_id);

    //             // $redirectUrl = FRONT_APP_URL . "/profile?user_id=" . $secondary_item_id;

    //             $message = sprintf(__('liked your post', $textDomain));
    //             $title = sprintf('%s', $user_name);

    //         }
    //         break;
    //     case 'wp_comment':
    //         if ($action === 'post_comment') {
    //             $user_name = esc_html(bp_core_get_user_displayname($secondary_item_id));
    //             $avatar_url = bp_core_fetch_avatar(array(
    //                 'item_id' => $secondary_item_id,
    //                 'type' => 'thumb', // You can use 'full' if you want a larger avatar
    //                 'html' => false  // Set to false to get the URL instead of HTML <img>
    //             ));
    //             // $redirectUrl = FRONT_APP_URL . "/profile?user_id=" . $secondary_item_id;
    //             $redirectUrl = getSinglePost($item_id);

    //             $message = sprintf(__('commented on your post', $textDomain));
    //             $title = sprintf('%s', $user_name);

    //         }
    //         break;
    //     case 'events':
    //         if ($action === 'new_event') {
    //             $user_name = esc_html(bp_core_get_user_displayname($secondary_item_id));
    //             $avatar_url = bp_core_fetch_avatar(array(
    //                 'item_id' => $secondary_item_id,
    //                 'type' => 'thumb', // You can use 'full' if you want a larger avatar
    //                 'html' => false  // Set to false to get the URL instead of HTML <img>
    //             ));
    //             // $redirectUrl = FRONT_APP_URL . "/events/details/" . $item_id;
    //             $redirectUrl = FRONT_APP_URL . "/events/details/" . $item_id . "?user_id=" . $secondary_item_id;

    //             $message = sprintf(__('created a new event', $textDomain));
    //             $title = sprintf('%s', $user_name);

    //         }
    //         break;
    //     case 'post':
    //         if ($action === 'new_post') {
    //             $user_name = esc_html(bp_core_get_user_displayname($secondary_item_id));
    //             $avatar_url = bp_core_fetch_avatar(array(
    //                 'item_id' => $secondary_item_id,
    //                 'type' => 'thumb', // You can use 'full' if you want a larger avatar
    //                 'html' => false  // Set to false to get the URL instead of HTML <img>
    //             ));
    //             $redirectUrl = getSinglePost($item_id);

    //             $message = sprintf(__('Created new post.', $textDomain));
    //             $title = sprintf('%s', $user_name);
    //         }
    //         break;
    //     case 'vehicle_post':
    //         if ($action === 'new_post') {
    //             $user_name = esc_html(bp_core_get_user_displayname($secondary_item_id));
    //             $avatar_url = bp_core_fetch_avatar(array(
    //                 'item_id' => $secondary_item_id,
    //                 'type' => 'thumb', // You can use 'full' if you want a larger avatar
    //                 'html' => false  // Set to false to get the URL instead of HTML <img>
    //             ));
    //             $redirectUrl = getSinglePost($item_id);

    //             $message = sprintf(__('Created new vehicle post.', $textDomain));
    //             $title = sprintf('%s', $user_name);
    //         }
    //         break;
    //     default:
    //         $message = 'You have a new notification';
    //         break;
    // }
   $getNotificationDetails =  get_notification_details($component, $action, $item_id, $secondary_item_id);
    // Add the custom message to the response
    $message = $getNotificationDetails['message'] ?? '';
    $title = $getNotificationDetails['title'] ?? '';
    $slug = $getNotificationDetails['slug'] ?? '';
    
    $response->data['custom_message'] = esc_html($message); // Ensure the message is escaped
    if (!empty($avatar_url)) {
        $response->data['sender_avatar'] = esc_url($avatar_url); // Add the avatar URL to the response
    }
    if (!empty($slug)) {
        $response->data['slug'] = $slug;
    }
    if (!empty($title)) {
        $response->data['title'] = esc_html($title); // Ensure the message is escaped

    }
    // $response->data['slug'] = '/profile?userId=1&tab=posts';

    return $response;
}


function getSinglePost($post_id)
{
    // Get the post type for the liked post
    $post_type = get_post_type($post_id); // Assuming $item_id is the post ID

    // Determine the URL based on post type
    if ($post_type) {
        if ($post_type === 'vehicle_post') {
            $redirectUrl = FRONT_APP_URL . "/post/{$post_id}/type/vehicle_post";
        } elseif ($post_type === 'user_post') {
            $redirectUrl = FRONT_APP_URL . "/post/{$post_id}/type/user_post";
        }
    } else {
        $redirectUrl = FRONT_APP_URL;
    }
    return $redirectUrl;

}

