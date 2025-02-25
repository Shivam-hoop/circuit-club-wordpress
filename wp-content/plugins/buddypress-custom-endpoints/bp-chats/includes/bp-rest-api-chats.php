<?php

add_action('rest_api_init', function() {
    register_rest_route('buddypress/v1', '/chat-list', [
        'methods'  => 'GET',
        'callback' => 'get_combined_messages',
        'permission_callback' => function() {
            return is_user_logged_in();
        }
    ]);
    register_rest_route('buddypress/v1', '/check-chat/', array(
        'methods' => 'GET',
        'callback' => 'check_user_chat',
        'permission_callback' => function() {
            return is_user_logged_in(); // Ensure the user is logged in
        },
    ));
    register_rest_route( 'buddypress/v1', '/group-chat', array(
        'methods' => 'POST',
        'callback' => 'create_group_chat',
        'permission_callback' => function() {
            return is_user_logged_in();
        }
    ));

    // register_rest_route( 'buddypress/v1', '/group-chat/(?P<thread_id>\d+)/members', array(
    //     'methods' => 'POST',
    //     'callback' => 'add_members_to_group_chat',
    //     'permission_callback' => function() {
    //         return is_user_logged_in();
    //     }
    // ));
});


//working code : - 
// function get_combined_messages() {
//     $user_id = get_current_user_id();

//     if (!$user_id) {
//         return new WP_Error('rest_forbidden', __('You must be logged in to view messages.'), ['status' => 403]);
//     }

//     // Get all message threads where the user is a participant
//     $threads = BP_Messages_Thread::get_current_threads_for_user($user_id, 'all', [
//         'page' => 1,
//         'per_page' => 20
//     ]);
//     // Ensure 'threads' data is available
//     if (empty($threads) || empty($threads['threads'])) {
//         return rest_ensure_response([]);
//     }

//     $filtered_threads = [];
//     foreach ($threads['threads'] as $thread) {
//         // Check if the logged-in user is a participant in the thread
//         if (isset($thread->recipients) && array_key_exists($user_id, $thread->recipients)) {
//             // Reformat recipients data
//             $recipients = [];
//             foreach ($thread->recipients as $recipient_id => $recipient) {
//                 $user_info = get_userdata($recipient->user_id);
//                 $avatar_url = bp_core_fetch_avatar([
//                     'item_id' => $recipient->user_id,
//                     'type'    => 'full',
//                     'html'    => false,
//                 ]);
//                 $thumb_avatar_url = bp_core_fetch_avatar([
//                     'item_id' => $recipient->user_id,
//                     'type'    => 'thumb',
//                     'html'    => false,
//                 ]);

//                 $recipients[] = [
//                     'id'            => $recipient->id,
//                     'is_deleted'    => $recipient->is_deleted,
//                     'name'          => $user_info->display_name,
//                     'sender_only'   => $recipient->sender_only,
//                     'thread_id'     => $recipient->thread_id,
//                     'unread_count'  => $recipient->unread_count,
//                     'user_id'       => $recipient->user_id,
//                     'user_link'     => bp_core_get_userlink($recipient->user_id, false, true),
//                     'user_avatars'  => [
//                         'full'   => $avatar_url,
//                         'thumb'  => $thumb_avatar_url,
//                     ]
//                 ];
//             }

//             // Update thread with formatted recipients
//             $thread->recipients = $recipients;
//             $filtered_threads[] = $thread; // Add each thread
//         }
//     }

//     // Sort threads by last message date
//     usort($filtered_threads, function($a, $b) {
//         return strtotime($b->last_message_date) - strtotime($a->last_message_date);
//     });

//     return rest_ensure_response($filtered_threads);
// }

//custom response : - 
// function get_combined_messages() {
//     $user_id = get_current_user_id();

//     if (!$user_id) {
//         return new WP_Error('rest_forbidden', __('You must be logged in to view messages.'), ['status' => 403]);
//     }

//     // Get all message threads where the user is a participant
//     $threads = BP_Messages_Thread::get_current_threads_for_user($user_id, 'all', [
//         'page' => 1,
//         'per_page' => 20
//     ]);

//     // Ensure 'threads' data is available
//     if (empty($threads) || empty($threads['threads'])) {
//         return rest_ensure_response([]);
//     }

//     $filtered_threads = [];
//     foreach ($threads['threads'] as $thread) {
//         // Check if the logged-in user is a participant in the thread
//         if (isset($thread->recipients) && array_key_exists($user_id, $thread->recipients)) {
//             // Check if this thread is a group chat by checking for 'group_name' meta
//             $group_name = bp_messages_get_meta($thread->thread_id, 'group_name', true);

//             if ($group_name) {
//                 // This is a group chat, so display the group name
//                 $thread_title = $group_name;
//                 $group = true;
//             } else {
//                 // For one-to-one chats, display the recipient's name
//                 $thread_title = [];
//                 foreach ($thread->recipients as $recipient) {
//                     if ($recipient->user_id != $user_id) {
//                         $user_info = get_userdata($recipient->user_id);
//                         $thread_title[] = $user_info->display_name;
//                     }
//                 }
//                 $thread_title = implode(', ', $thread_title);
//                 $group = false;

//             }

//             // Reformat recipients data
//             $recipients = [];
//             foreach ($thread->recipients as $recipient) {
//                 $user_info = get_userdata($recipient->user_id);
//                 $avatar_url = bp_core_fetch_avatar([
//                     'item_id' => $recipient->user_id,
//                     'type'    => 'full',
//                     'html'    => false,
//                 ]);
//                 $thumb_avatar_url = bp_core_fetch_avatar([
//                     'item_id' => $recipient->user_id,
//                     'type'    => 'thumb',
//                     'html'    => false,
//                 ]);

//                 $recipients[] = [
//                     'id'            => $recipient->id,
//                     'is_deleted'    => $recipient->is_deleted,
//                     'name'          => $user_info->display_name,
//                     'sender_only'   => $recipient->sender_only,
//                     'thread_id'     => $recipient->thread_id,
//                     'unread_count'  => $recipient->unread_count,
//                     'user_id'       => $recipient->user_id,
//                     'user_link'     => bp_core_get_userlink($recipient->user_id, false, true),
//                     'user_avatars'  => [
//                         'full'   => $avatar_url,
//                         'thumb'  => $thumb_avatar_url,
//                     ]
//                 ];
//             }

//             // Update thread with formatted recipients and thread title (group name or user names)
//             $thread->recipients = $recipients;
//             $thread->title = $thread_title;
//             $thread->group = $group;
//             $filtered_threads[] = $thread;
//         }
//     }

//     // Sort threads by last message date
//     usort($filtered_threads, function($a, $b) {
//         return strtotime($b->last_message_date) - strtotime($a->last_message_date);
//     });

//     return rest_ensure_response($filtered_threads);
// }

function get_combined_messages() {
    $user_id = get_current_user_id();

    if (!$user_id) {
        return new WP_Error('rest_forbidden', __('You must be logged in to view messages.'), ['status' => 403]);
    }

    // Get all message threads where the user is a participant
    $threads = BP_Messages_Thread::get_current_threads_for_user($user_id, 'all', [
        'page' => 1,
        'per_page' => 20
    ]);

    // Ensure 'threads' data is available
    if (empty($threads) || empty($threads['threads'])) {
        return rest_ensure_response([]);
    }

    $filtered_threads = [];
    foreach ($threads['threads'] as $thread) {
        // Check if the logged-in user is a participant in the thread
        if (isset($thread->recipients) && array_key_exists($user_id, $thread->recipients)) {
            // Check if this thread is a group chat by checking for 'group_name' meta
            $group_name = get_post_meta($thread->thread_id, 'group_name', true);

            if ($group_name) {
                // This is a group chat, so display the group name
                $thread_title = $group_name;
                $group = true;
                $sender_avatar = '';
            } else {
                // For one-to-one chats, display the recipient's name and sender's avatar
                $thread_title = [];
                $sender_avatar = ''; // Initialize sender's avatar URL

                foreach ($thread->recipients as $recipient) {
                    if ($recipient->user_id != $user_id) {
                        $user_info = get_userdata($recipient->user_id);
                        $thread_title[] = $user_info->display_name;

                        // Fetch sender's avatar (the other participant in the one-to-one chat)
                        $sender_avatar = bp_core_fetch_avatar([
                            'item_id' => $recipient->user_id,
                            'type'    => 'thumb',
                            'html'    => false,
                        ]);
                    }
                }
                $thread_title = implode(', ', $thread_title);
                $group = false;
            }

            // Reformat recipients data
            $recipients = [];
            foreach ($thread->recipients as $recipient) {
                $user_info = get_userdata($recipient->user_id);
                $avatar_url = bp_core_fetch_avatar([
                    'item_id' => $recipient->user_id,
                    'type'    => 'full',
                    'html'    => false,
                ]);
                $thumb_avatar_url = bp_core_fetch_avatar([
                    'item_id' => $recipient->user_id,
                    'type'    => 'thumb',
                    'html'    => false,
                ]);

                $recipients[] = [
                    'id'            => $recipient->id,
                    'is_deleted'    => $recipient->is_deleted,
                    'name'          => $user_info->display_name??'',
                    'sender_only'   => $recipient->sender_only,
                    'thread_id'     => $recipient->thread_id,
                    'unread_count'  => $recipient->unread_count,
                    'user_id'       => $recipient->user_id,
                    'user_link'     => bp_core_get_userlink($recipient->user_id, false, true),
                    'user_avatars'  => [
                        'full'   => $avatar_url,
                        'thumb'  => $thumb_avatar_url,
                    ]
                ];
            }

            // Update thread with formatted recipients, thread title, and sender's avatar
            $thread->recipients = $recipients;
            $thread->title = $thread_title;
            $thread->group = $group;
            $thread->sender_avatar = $sender_avatar; // Add sender's avatar URL if it's a one-to-one chat
            $filtered_threads[] = $thread;
        }
    }

    // Sort threads by last message date
    usort($filtered_threads, function($a, $b) {
        return strtotime($b->last_message_date) - strtotime($a->last_message_date);
    });

    return rest_ensure_response($filtered_threads);
}

// function check_user_chat(WP_REST_Request $request) {
//     global $wpdb;

//     // Get the ID of the logged-in user
//     $logged_in_user_id = get_current_user_id();

//     // Get the ID of the user to check against
//     $user_id = $request->get_param('user_id');

//     if (empty($logged_in_user_id) || empty($user_id)) {
//         return new WP_Error('missing_user_id', 'Both the logged-in user ID and the user ID to check are required', array('status' => 400));
//     }

//     // Table names
//     $messages_table = $wpdb->prefix . 'bp_messages_messages';
//     $recipients_table = $wpdb->prefix . 'bp_messages_recipients';

//     // SQL query to check for messages between the logged-in user and the specified user and get thread_id
//     $sql = $wpdb->prepare("
//         SELECT m.thread_id
//         FROM $messages_table AS m
//         INNER JOIN $recipients_table AS r ON m.thread_id = r.thread_id
//         WHERE (m.sender_id = %d AND r.user_id = %d) OR (m.sender_id = %d AND r.user_id = %d)
//         LIMIT 1
//     ", $logged_in_user_id, $user_id, $user_id, $logged_in_user_id);

//     $thread_id = $wpdb->get_var($sql);

//     // Return the result
//     if ($thread_id) {
//         return new WP_REST_Response(array('chatted' => true, 'thread_id' => $thread_id), 200); // Users have chatted before
//     }

//     return new WP_REST_Response(array('chatted' => false), 200); // No chat found
// }

// function check_user_chat(WP_REST_Request $request) {
//     global $wpdb;

//     // Get the ID of the logged-in user
//     $logged_in_user_id = get_current_user_id();

//     // Get the ID of the user to check against
//     $user_id = $request->get_param('user_id');

//     if (empty($logged_in_user_id) || empty($user_id)) {
//         return new WP_Error('missing_user_id', 'Both the logged-in user ID and the user ID to check are required', array('status' => 400));
//     }

//     // Table names
//     $messages_table = $wpdb->prefix . 'bp_messages_messages';
//     $recipients_table = $wpdb->prefix . 'bp_messages_recipients';
//     $meta_table = $wpdb->prefix . 'bp_messages_meta';

//     // SQL query to check for messages between the logged-in user and the specified user and get thread_id
//     $sql = $wpdb->prepare("
//         SELECT m.thread_id
//         FROM $messages_table AS m
//         INNER JOIN $recipients_table AS r ON m.thread_id = r.thread_id
//         WHERE (m.sender_id = %d AND r.user_id = %d) OR (m.sender_id = %d AND r.user_id = %d)
//         LIMIT 1
//     ", $logged_in_user_id, $user_id, $user_id, $logged_in_user_id);

//     $thread_id = $wpdb->get_var($sql);

//     if ($thread_id) {
//         // Check if the thread is a group chat by checking for the 'group_name' meta
//         $group_name_sql = $wpdb->prepare("
//             SELECT meta_value
//             FROM $meta_table
//             WHERE message_id = %d AND meta_key = 'group_name'
//             LIMIT 1
//         ", $thread_id);
//         $group_name = $wpdb->get_var($group_name_sql);

//         // If group_name is found, it's a group chat, so return chatted as false
//         if (!empty($group_name)) {
//             return new WP_REST_Response(array('chatted' => false, 'is_group_chat' => true, 'group_name' => $group_name), 200);
//         }

//         // Otherwise, it's a regular chat between two users
//         return new WP_REST_Response(array('chatted' => true, 'thread_id' => $thread_id), 200);
//     }

//     return new WP_REST_Response(array('chatted' => false), 200); // No chat found
// }
function check_user_chat(WP_REST_Request $request) {
    global $wpdb;

    // Get the ID of the logged-in user
    $logged_in_user_id = get_current_user_id();

    // Get the ID of the user to check against
    $user_id = $request->get_param('user_id');

    if (empty($logged_in_user_id) || empty($user_id)) {
        return new WP_Error('missing_user_id', 'Both the logged-in user ID and the user ID to check are required', array('status' => 400));
    }

    // Table names
    $messages_table = $wpdb->prefix . 'bp_messages_messages';
    $recipients_table = $wpdb->prefix . 'bp_messages_recipients';
    $meta_table = $wpdb->prefix . 'bp_messages_meta';

    // SQL query to check for messages between the logged-in user and the specified user and get thread_id
    $sql = $wpdb->prepare("
        SELECT m.thread_id, m.id as message_id
        FROM $messages_table AS m
        INNER JOIN $recipients_table AS r ON m.thread_id = r.thread_id
        WHERE (m.sender_id = %d AND r.user_id = %d) OR (m.sender_id = %d AND r.user_id = %d)
        LIMIT 1
    ", $logged_in_user_id, $user_id, $user_id, $logged_in_user_id);

    $result = $wpdb->get_row($sql);

    if ($result && $result->thread_id) {
        $thread_id = $result->thread_id;
        $message_id = $result->message_id;

        // First check if this thread is a group chat
        // $group_name_sql = $wpdb->prepare("
        //     SELECT meta_value
        //     FROM $meta_table
        //     WHERE message_id = %d AND meta_key = 'group_name'
        //     LIMIT 1
        // ", $thread_id);
        // $group_name = $wpdb->get_var($group_name_sql);
        $group_name = get_post_meta($thread_id, 'group_name', true);


        // If group_name is found, it's a group chat, so return as group chat
        if (!empty($group_name)) {
            return new WP_REST_Response(array('chatted' => false, 'is_group_chat' => true, 'group_name' => $group_name), 200);
        }

        // Otherwise, it's a regular one-on-one chat between two users
        return new WP_REST_Response(array('chatted' => true, 'thread_id' => $thread_id, 'is_group_chat' => false), 200);
    }

    return new WP_REST_Response(array('chatted' => false), 200); // No chat found
}



function create_group_chat( $request ) {
    $group_name = sanitize_text_field( $request->get_param( 'group_name' ) );
    $recipients = $request->get_param( 'members' ); // Expecting an array of user IDs for group participants
    $creator_id = get_current_user_id();

    // Validate group name and recipients
    if ( empty( $group_name ) ) {
        return new WP_Error( 'missing_data', 'Group name is required', array( 'status' => 400 ) );
    }

    if ( empty( $recipients ) || !is_array( $recipients ) || count( $recipients ) < 1 ) {
        return new WP_Error( 'missing_recipients', 'At least one recipient is required', array( 'status' => 400 ) );
    }

    // Ensure the creator is added to the list of recipients
    if ( !in_array( $creator_id, $recipients ) ) {
        $recipients[] = $creator_id;
    }

    // Create a thread to represent the group chat
    $thread_id = messages_new_message( array(
        'sender_id'  => $creator_id,
        'subject'    => $group_name,  // Store the group name as the thread subject
        'content'    => "Welcome to $group_name",  // Initial content can be empty
        'recipients' => $recipients   // Recipients as an array
    ));
    error_log("try this". $thread_id);

    if ( is_wp_error( $thread_id ) ) {
        return $thread_id;
    }

    // Save group name in thread meta
    update_post_meta( $thread_id, 'group_name', $group_name );

    return new WP_REST_Response( array(
        'status' => 'success',
        'thread_id' => $thread_id,
        'group_name' => $group_name
    ), 200 );
}

