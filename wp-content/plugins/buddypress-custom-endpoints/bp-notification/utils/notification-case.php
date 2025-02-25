<?php
function format_notification_details($item_id, $secondary_item_id, $component, $action)
{
    global $wpdb;

    $user_name = esc_html(bp_core_get_user_displayname($secondary_item_id));
    $avatar_url = '';
    $redirectUrl = '';
    $message = '';
    $title = '';

    switch ($component) {
        case 'friends':
            if ($action === 'friendship_accepted') {
                $message = 'accepted your friendship request';
                $title = sprintf('%s', $user_name);
            }
            break;
        case 'messages':
            if ($action === 'new_message') {
                $user_name = esc_html(bp_core_get_user_displayname($secondary_item_id));
                $avatar_url = bp_core_fetch_avatar(array(
                    'item_id' => $secondary_item_id,
                    'type' => 'thumb',
                    'html' => false
                ));

                $table = $wpdb->prefix . 'bp_messages_messages';
                $thread_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT thread_id FROM $table WHERE id = %d",
                    $item_id
                ));

                if (!empty($thread_id)) {
                    $redirectUrl = FRONT_APP_URL . "/chats?room=" . $thread_id;
                }

                $message = 'sent you a new private message';
                $title = sprintf('%s', $user_name);
            }
            break;
        case 'groups':
            if ($action === 'group_invite') {
                $message = 'invited you to join a group';
                $title = sprintf('%s', $user_name);
            }
            break;
        case 'activity':
            if ($action === 'activity_reply') {
                $message = 'replied to your activity.';
                $title = sprintf('%s', $user_name);
            }
            break;
        case 'follow':
            if ($action === 'new_follow_request') {
                $avatar_url = bp_core_fetch_avatar(array(
                    'item_id' => $item_id,
                    'type' => 'thumb',
                    'html' => false
                ));
                $redirectUrl = FRONT_APP_URL . "/profile?user_id=" . $item_id;
                $message = 'follows you now.';
                $title = sprintf('%s', $user_name);
            }
            break;
        case 'like':
            if ($action === 'post_like') {
                $avatar_url = bp_core_fetch_avatar(array(
                    'item_id' => $secondary_item_id,
                    'type' => 'thumb',
                    'html' => false
                ));
                $redirectUrl = getSinglePost($item_id);
                $message = 'liked your post';
                $title = sprintf('%s', $user_name);
            }
            break;
        case 'wp_comment':
            if ($action === 'post_comment') {
                $avatar_url = bp_core_fetch_avatar(array(
                    'item_id' => $secondary_item_id,
                    'type' => 'thumb',
                    'html' => false
                ));
                $redirectUrl = getSinglePost($item_id);
                $message = 'commented on your post';
                $title = sprintf('%s', $user_name);
            }
            break;
        case 'events':
            if ($action === 'new_event') {
                $avatar_url = bp_core_fetch_avatar(array(
                    'item_id' => $secondary_item_id,
                    'type' => 'thumb',
                    'html' => false
                ));
                $redirectUrl = FRONT_APP_URL . "/events/details/" . $item_id . "?user_id=" . $secondary_item_id;
                $message = 'created a new event';
                $title = sprintf('%s', $user_name);
            }
            break;
        case 'post':
            if ($action === 'new_post') {
                $avatar_url = bp_core_fetch_avatar(array(
                    'item_id' => $secondary_item_id,
                    'type' => 'thumb',
                    'html' => false
                ));
                $redirectUrl = getSinglePost($item_id);
                $message = 'Created a new post.';
                $title = sprintf('%s', $user_name);
            }
            break;
        default:
            $message = 'You have a new notification';
            break;
    }

    return [
        'message' => $message,
        'title' => $title,
        'avatar_url' => $avatar_url,
        'redirect_url' => $redirectUrl
    ];
}
