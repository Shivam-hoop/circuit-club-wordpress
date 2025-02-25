<?php
require '/var/www/html/circuit-club/vendor/autoload.php';

use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use WebSocket\Client;
use Ratchet\Client\connect;

// Get the base path of the BuddyPress custom endpoints plugin
$pluginBasePath = dirname(__DIR__, 2); // Adjust levels as needed

// Define the relative path to the fire-base-auth.php file
$fireBaseAuthPath = $pluginBasePath . '/bp-firebase-integration/utils/fire-base-auth.php';

// Dynamically include the file
if (file_exists($fireBaseAuthPath)) {
    require_once $fireBaseAuthPath;
} else {
    error_log("File not found: " . $fireBaseAuthPath);
}
function send_realtime_notification_to_node($user_id)
{
    // Fetch stored notifications for the user
    $notifications_data = fetch_user_notifications($user_id);
    // error_log("notifications_data  ". $notifications_data);
    // If there are no new notifications, skip
    if (empty($notifications_data)) {
        return;
    }

    // Node.js server URL (adjust to your actual Node.js server address)
    $node_server_url = FRONT_APP_URL . "/notify";

    // JSON encode the payload
    $json_data = json_encode([
        'user_id' => $user_id,
        'data' => $notifications_data
    ]);


    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $node_server_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $json_data,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);


    if ($response === false) {
        error_log('Error sending notification to Node.js: ' . curl_error($curl));
    }
}

function fetch_user_notifications($user_id)
{
    global $wpdb;

    $bp_notifications_table = $wpdb->prefix . 'bp_notifications';

    // Query to fetch the latest notifications for the user
    $query = $wpdb->prepare(
        "SELECT id, item_id, secondary_item_id, component_name, component_action, date_notified 
        FROM $bp_notifications_table 
        WHERE user_id = %d AND is_new = 1
        ORDER BY date_notified DESC 
        LIMIT 10",
        $user_id
    );

    $notifications = $wpdb->get_results($query);

    if (empty($notifications)) {
        return []; // Return an empty array if no notifications
    }

    // Initialize an array to store the formatted notifications
    $formatted_notifications = [];
    $preferred_language = get_user_language_preference($user_id);
    $cap_preferred_language = strtoupper($preferred_language);

    switch_to_locale($preferred_language . '_' . $cap_preferred_language); // or another locale based on your needs
    // Loop through each notification and map it to the custom structure
    foreach ($notifications as $notification) {
        // Extract necessary information
        $item_id = intval($notification->item_id);
        $secondary_item_id = intval($notification->secondary_item_id);
        $component = sanitize_text_field($notification->component_name);
        $action = sanitize_text_field($notification->component_action);

        // Fetch user display name safely
        $user_name = esc_html(bp_core_get_user_displayname($item_id));

        // Initialize custom message and avatar URL
        $message = '';
        $avatar_url = '';
        $redirectUrl = '';
        $textDomain = 'default';


        switch ($component) {
            case 'friends':
                if ($action === 'friendship_accepted') {
                    $message = sprintf(__('accepted your friendship request', $textDomain));
                    $title = sprintf(__('%s', $user_name));
                }
                break;
            case 'messages':
                if ($action === 'new_message') {
                    $user_name = esc_html(bp_core_get_user_displayname(user_id_or_username: $secondary_item_id));
                    $avatar_url = bp_core_fetch_avatar(array(
                        'item_id' => $secondary_item_id,
                        'type' => 'thumb', // You can use 'full' if you want a larger avatar
                        'html' => false  // Set to false to get the URL instead of HTML <img>
                    ));
                    // Query the database to get the thread_id using the message_id ($item_id)
                    global $wpdb;
                    $table = $wpdb->prefix . 'bp_messages_messages';
                    $thread_id = $wpdb->get_var($wpdb->prepare(
                        "SELECT thread_id FROM $table WHERE id = %d",
                        $item_id // $item_id contains the message_id
                    ));
                    // Ensure the thread_id was found
                    if (!empty($thread_id)) {
                        $redirectUrl = FRONT_APP_URL . "/chats?room=" . $thread_id; // Create the URL to redirect to the message thread
                    }

                    $message = sprintf(__('sent you a new private message', $textDomain));
                    $title = sprintf('%s', $user_name);
                }
                break;
            case 'groups':
                if ($action === 'group_invite') {
                    $message = sprintf(__('invited you to join a group', $textDomain));
                    $title = sprintf('%s', $user_name);

                }
                break;
            case 'activity':
                if ($action === 'activity_reply') {
                    $message = sprintf(__('replied to your activity.', $textDomain));
                    $title = sprintf('%s', $user_name);

                }
                break;
            case 'follow':
                if ($action === 'new_follow_request') {
                    $avatar_url = bp_core_fetch_avatar(array(
                        'item_id' => $item_id,
                        'type' => 'thumb', // You can use 'full' if you want a larger avatar
                        'html' => false  // Set to false to get the URL instead of HTML <img>
                    ));
                    $redirectUrl = FRONT_APP_URL . "/profile?user_id=" . $item_id;
                    $message = sprintf(__('follows you now.', $textDomain));
                    $title = sprintf('%s', $user_name);
                }
                break;
            case 'like':
                if ($action === 'post_like') {
                    $user_name = esc_html(bp_core_get_user_displayname($secondary_item_id));
                    $avatar_url = bp_core_fetch_avatar(array(
                        'item_id' => $secondary_item_id,
                        'type' => 'thumb', // You can use 'full' if you want a larger avatar
                        'html' => false  // Set to false to get the URL instead of HTML <img>
                    ));
                    $redirectUrl = getSinglePost($item_id);

                    // $redirectUrl = FRONT_APP_URL . "/profile?user_id=" . $secondary_item_id;

                    $message = sprintf(__('liked your post', $textDomain));
                    $title = sprintf('%s', $user_name);

                }
                break;
            case 'wp_comment':
                if ($action === 'post_comment') {
                    $user_name = esc_html(bp_core_get_user_displayname($secondary_item_id));
                    $avatar_url = bp_core_fetch_avatar(array(
                        'item_id' => $secondary_item_id,
                        'type' => 'thumb', // You can use 'full' if you want a larger avatar
                        'html' => false  // Set to false to get the URL instead of HTML <img>
                    ));
                    // $redirectUrl = FRONT_APP_URL . "/profile?user_id=" . $secondary_item_id;
                    $redirectUrl = getSinglePost($item_id);

                    $message = sprintf(__('commented on your post', $textDomain));
                    $title = sprintf('%s', $user_name);

                }
                break;
            case 'events':
                if ($action === 'new_event') {
                    $user_name = esc_html(bp_core_get_user_displayname($secondary_item_id));
                    $avatar_url = bp_core_fetch_avatar(array(
                        'item_id' => $secondary_item_id,
                        'type' => 'thumb', // You can use 'full' if you want a larger avatar
                        'html' => false  // Set to false to get the URL instead of HTML <img>
                    ));
                    // $redirectUrl = FRONT_APP_URL . "/events/details/" . $item_id;
                    $redirectUrl = FRONT_APP_URL . "/events/details/" . $item_id . "?user_id=" . $secondary_item_id;

                    $message = sprintf(__('created a new event', $textDomain));
                    $title = sprintf('%s', $user_name);

                }
                break;
            case 'post':
                if ($action === 'new_post') {
                    $user_name = esc_html(bp_core_get_user_displayname($secondary_item_id));
                    $avatar_url = bp_core_fetch_avatar(array(
                        'item_id' => $secondary_item_id,
                        'type' => 'thumb', // You can use 'full' if you want a larger avatar
                        'html' => false  // Set to false to get the URL instead of HTML <img>
                    ));
                    $redirectUrl = getSinglePost($item_id);

                    $message = sprintf(__('Created new post.', $textDomain));
                    $title = sprintf('%s', $user_name);
                }
                break;
            case 'vehicle_post':
                if ($action === 'new_post') {
                    $user_name = esc_html(bp_core_get_user_displayname($secondary_item_id));
                    $avatar_url = bp_core_fetch_avatar(array(
                        'item_id' => $secondary_item_id,
                        'type' => 'thumb', // You can use 'full' if you want a larger avatar
                        'html' => false  // Set to false to get the URL instead of HTML <img>
                    ));
                    $redirectUrl = getSinglePost($item_id);

                    $message = sprintf(__('Created new vehicle post.', $textDomain));
                    $title = sprintf('%s', $user_name);
                }
                break;
            default:
                $message = 'You have a new notification';
                break;
        }


        // Format the notification in the custom structure
        $formatted_notifications[] = array(
            'id' => $notification->id,
            'user_id' => $user_id,
            'title' => sprintf('%s', $user_name),
            'custom_message' => $message,
            'sender_avatar' => $avatar_url ? esc_url($avatar_url) : '',
            'redirect_url' => $redirectUrl ? esc_url($redirectUrl) : ''
        );
    }

    return $formatted_notifications;
}

// // Hook into when a private message is sent
// add_action('messages_message_sent', 'send_realtime_notification_on_message', 10, 2);

// function send_realtime_notification_on_message($message_id, $message) {
//     // Get the sender and recipient user IDs
//     $sender_id = $message->sender_id;
//     $recipient_id = $message->recipient_id;

//     // Send real-time notification to the recipient
//     send_realtime_notification_to_node($recipient_id);
// }
// function send_websocket_notification($user_id)
// {

//     $notifications_data = fetch_user_notifications($user_id);
//     // error_log("notifications_data  ". $notifications_data);
//     // If there are no new notifications, skip
//     if (empty($notifications_data)) {
//         return;
//     }
//     // Your WebSocket server URL (you might need to adjust this)
//     $websocket_url = 'ws://157.173.220.180:5556';

//     // Here, you send a message to the WebSocket server
//     // You can use a PHP WebSocket client library like Ratchet or a custom approach
//     try {
//         // Assuming you have a WebSocket connection handler already set up in your server
//         $client = new WebSocket\Client($websocket_url);

//         // Prepare the message
//         $message = json_encode($notifications_data);

//         // Send the message to the WebSocket server
//         $client->send($message);
//     } catch (Exception $e) {
//         error_log("Failed to send WebSocket message: " . $e->getMessage());
//     }
// }

function send_websocket_notification($user_id, $message)
{
    $url = 'ws://157.173.220.180:5556'; // WebSocket server URL

    try {
        // Create a new WebSocket client
        $client = new Client($url);

        // Prepare the message data to send
        $data = json_encode([
            'user_id' => $user_id,
            'message' => $message,
        ]);

        // Send the message over the WebSocket connection
        $client->send($data);

        // Log success
        error_log("Message sent successfully to WebSocket server.");

        // Close the connection
        $client->close();
    } catch (Exception $e) {
        // Handle errors
        error_log('Error sending WebSocket notification: ' . $e->getMessage());
    }
}

function send_realtime_notification($serviceAccountPath, $projectId, $message)
{
    try {
        $accessToken = getAccessToken($serviceAccountPath);
        $response = sendMessage($accessToken, $projectId, $message);
        error_log('Message sent successfully: ' . print_r($response, true));
    } catch (Exception $e) {
        error_log('Error: ' . $e->getMessage());
    }
}

function get_notification_details($component, $action, $item_id, $secondary_item_id)
{

    $title = 'New Notification';
    $message = 'You have a new notification.';
    $textDomain = 'default';
    $user_name = esc_html(bp_core_get_user_displayname($item_id));


    switch ($component) {
        case 'friends':
            if ($action === 'friendship_accepted') {
                $message = sprintf(__('accepted your friendship request', $textDomain));
                $title = sprintf(__('%s', $user_name));
            }
            break;
        case 'messages':
            if ($action === 'new_message') {
                $user_name = esc_html(bp_core_get_user_displayname(user_id_or_username: $secondary_item_id));
                $avatar_url = bp_core_fetch_avatar(array(
                    'item_id' => $secondary_item_id,
                    'type' => 'thumb', // You can use 'full' if you want a larger avatar
                    'html' => false  // Set to false to get the URL instead of HTML <img>
                ));
                // Query the database to get the thread_id using the message_id ($item_id)
                global $wpdb;
                $table = $wpdb->prefix . 'bp_messages_messages';
                $thread_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT thread_id FROM $table WHERE id = %d",
                    $item_id // $item_id contains the message_id
                ));
                // Ensure the thread_id was found
                if (!empty($thread_id)) {
                    $redirectUrl = FRONT_APP_URL . "/chats?room=" . $thread_id; // Create the URL to redirect to the message thread
                }

                $message = sprintf(__('sent you a new private message', $textDomain));
                $title = sprintf('%s', $user_name);
            }
            break;
        case 'chat_messages':
            if ($action === 'one_to_one_message') {
                $user_name = esc_html(bp_core_get_user_displayname($secondary_item_id));
                $avatar_url = bp_core_fetch_avatar(array(
                    'item_id' => $secondary_item_id,
                    'type' => 'thumb', // You can use 'full' if you want a larger avatar
                    'html' => false  // Set to false to get the URL instead of HTML <img>
                ));


                $redirectUrl = "/chats?chatId=$item_id"; // Create the URL to redirect to the message thread


                $message = sprintf(__('sent you a new private message', $textDomain));
                $title = sprintf('%s', $user_name);
            }
            break;
        case 'groups':
            if ($action === 'group_invite') {
                $message = sprintf(__('invited you to join a group', $textDomain));
                $title = sprintf('%s', $user_name);

            }
            break;
        case 'activity':
            if ($action === 'activity_reply') {
                $message = sprintf(__('replied to your activity.', $textDomain));
                $title = sprintf('%s', $user_name);

            }
            break;
        case 'follow':
            if ($action === 'new_follow_request') {
                $avatar_url = bp_core_fetch_avatar(array(
                    'item_id' => $item_id,
                    'type' => 'thumb', // You can use 'full' if you want a larger avatar
                    'html' => false  // Set to false to get the URL instead of HTML <img>
                ));
                $redirectUrl = FRONT_APP_URL . "/profile?user_id=" . $item_id;
                $message = sprintf(__('follows you now.', $textDomain));
                $title = sprintf('%s', $user_name);
            }
            break;
        case 'like':
            if ($action === 'post_like') {
                error_log("like case executied");
                $user_name = esc_html(bp_core_get_user_displayname($secondary_item_id));
                $avatar_url = bp_core_fetch_avatar(array(
                    'item_id' => $secondary_item_id,
                    'type' => 'thumb', // You can use 'full' if you want a larger avatar
                    'html' => false  // Set to false to get the URL instead of HTML <img>
                ));
                // $redirectUrl = getSinglePost($item_id);

                $redirectUrl = "/posts/$item_id";

                $message = sprintf(__('liked your post', $textDomain));
                $title = sprintf('%s', $user_name);

            }
            break;
        case 'wp_comment':
            if ($action === 'post_comment') {
                $user_name = esc_html(bp_core_get_user_displayname($secondary_item_id));
                $avatar_url = bp_core_fetch_avatar(array(
                    'item_id' => $secondary_item_id,
                    'type' => 'thumb', // You can use 'full' if you want a larger avatar
                    'html' => false  // Set to false to get the URL instead of HTML <img>
                ));
                // $redirectUrl = FRONT_APP_URL . "/profile?user_id=" . $secondary_item_id;
                // $redirectUrl = getSinglePost($item_id);
                $redirectUrl = "/posts/$item_id";


                $message = sprintf(__('commented on your post', $textDomain));
                $title = sprintf('%s', $user_name);

            }
            break;
        case 'events':
            if ($action === 'new_event') {
                $user_name = esc_html(bp_core_get_user_displayname($secondary_item_id));
                $avatar_url = bp_core_fetch_avatar(array(
                    'item_id' => $secondary_item_id,
                    'type' => 'thumb', // You can use 'full' if you want a larger avatar
                    'html' => false  // Set to false to get the URL instead of HTML <img>
                ));
                // $redirectUrl = FRONT_APP_URL . "/events/details/" . $item_id;
                $redirectUrl = FRONT_APP_URL . "/events/details/" . $item_id . "?user_id=" . $secondary_item_id;

                $message = sprintf(__('created a new event', $textDomain));
                $title = sprintf('%s', $user_name);

            }
            break;
        case 'post':
            if ($action === 'new_post') {
                $user_name = esc_html(bp_core_get_user_displayname($secondary_item_id));
                $avatar_url = bp_core_fetch_avatar(array(
                    'item_id' => $secondary_item_id,
                    'type' => 'thumb', // You can use 'full' if you want a larger avatar
                    'html' => false  // Set to false to get the URL instead of HTML <img>
                ));
                // $redirectUrl = getSinglePost($item_id);
                $redirectUrl = "/posts/$item_id";

                $message = sprintf(__('Created new post.', $textDomain));
                $title = sprintf('%s', $user_name);
            }
            break;
        case 'vehicle_post':
            if ($action === 'new_post') {
                $user_name = esc_html(bp_core_get_user_displayname($secondary_item_id));
                $avatar_url = bp_core_fetch_avatar(array(
                    'item_id' => $secondary_item_id,
                    'type' => 'thumb', // You can use 'full' if you want a larger avatar
                    'html' => false  // Set to false to get the URL instead of HTML <img>
                ));
                // $redirectUrl = getSinglePost($item_id);
                $redirectUrl = "/posts/$item_id";

                $message = sprintf(__('Created new vehicle post.', $textDomain));
                $title = sprintf('%s', $user_name);
            }
            break;
        default:
            $message = 'You have a new notification';
            break;
    }
    error_log("title set : -" . $title);
    return [
        'title' => $title,
        'message' => $message,
        'slug' => $redirectUrl,
    ];
}


