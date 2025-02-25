<?php

class Chat_Controller
{

    const NAMESPACE = 'buddypress/v1';
    const ROUTE_BASE = '/chats';
    private $messages_table;
    private $chat_participants_table;
    private $chat_media_attachments_table;
    private $message_reads_table;
    private $user_chat_activities_table;
    public function __construct()
    {
        global $wpdb;

        $this->messages_table = "{$wpdb->prefix}messages"; // Table name with the WordPress prefix
        $this->chat_participants_table = "{$wpdb->prefix}chat_participants"; // Table name with the WordPress prefix
        $this->chat_media_attachments_table = "{$wpdb->prefix}chat_media_attachments"; // Table name with the WordPress prefix
        $this->message_reads_table = "{$wpdb->prefix}message_reads"; // Table name with the WordPress prefix
        $this->user_chat_activities_table = "{$wpdb->prefix}user_chat_activities"; // Table name with the WordPress prefix

        // Register the routes when the API is initialized
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes()
    {
        // Define the CRUD routes
        register_rest_route(self::NAMESPACE , self::ROUTE_BASE . '/new', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'start_new_chat'],
            'permission_callback' => [$this, 'check_permissions']
        ]);
        register_rest_route(self::NAMESPACE , self::ROUTE_BASE . '/messages', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'send_message'],
            'permission_callback' => [$this, 'check_permissions']
        ]);
        register_rest_route(self::NAMESPACE , self::ROUTE_BASE . '/messages', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_messages'],
            'permission_callback' => [$this, 'check_permissions']
        ]);
        // Register the REST API endpoint for fetching chat lists
        register_rest_route(self::NAMESPACE , self::ROUTE_BASE . '/list', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_chat_list'],
            'permission_callback' => [$this, 'check_permissions'],
            'args' => [
                'page' => [
                    'required' => false,
                    'default' => 1,
                    'validate_callback' => function ($param) {
                        return is_numeric($param) && $param > 0;
                    },
                ],
                'per_page' => [
                    'required' => false,
                    'default' => 10,
                    'validate_callback' => function ($param) {
                        return is_numeric($param) && $param > 0 && $param <= 50; // Max limit for per_page
                    },
                ],
                'search' => [
                    'required' => false,
                    'default' => '',
                    'validate_callback' => function ($param) {
                        return is_string($param);
                    },
                ],
            ],
        ]);
        register_rest_route(self::NAMESPACE , self::ROUTE_BASE . '/mark-as-read', [
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => [$this, 'mark_messages_as_read'],
            'permission_callback' => [$this, 'check_permissions'],
            'args' => [
                'chat_id' => [
                    'required' => true,
                    // 'validate_callback' => 'is_numeric',
                ],
                'message_id' => [
                    'required' => false,
                    // 'validate_callback' => 'is_numeric',
                ],
            ],
        ]);
        // register_rest_route(
        //     self::NAMESPACE ,
        //     self::ROUTE_BASE . '/user-status',
        //     [
        //         'methods' => WP_REST_Server::CREATABLE,
        //         'callback' => [$this, 'update_user_chat_status'],
        //         'permission_callback' => [$this, 'check_permissions'],
        //     ]
        // );
        // register_rest_route(
        //     self::NAMESPACE,
        //     self::ROUTE_BASE .'/user-status',
        //     [
        //         'methods' => WP_REST_Server::READABLE,
        //         'callback' => [$this, 'get_user_chat_status'],
        //         'permission_callback' => [$this, 'check_permissions'],
        //     ]
        // );
        register_rest_route(self::NAMESPACE , self::ROUTE_BASE . '/user-status', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'update_user_status'],
            'permission_callback' => [$this, 'check_permissions']
        ]);

        register_rest_route(self::NAMESPACE , self::ROUTE_BASE . '/user-status', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_user_status'],
            'permission_callback' => [$this, 'check_permissions']
        ]);
        register_rest_route(self::NAMESPACE , self::ROUTE_BASE . '/unread-messages', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_unread_message_count'],
            'permission_callback' => [$this, 'check_permissions']
        ]);
    }

    // Check permissions for API access
    public function check_permissions($request)
    {
        return is_user_logged_in();  // Adjust based on your requirements
    }
    // Send a new message


    // function send_message(WP_REST_Request $request)
    // {
    //     global $wpdb;

    //     $chat_id = $request->get_param('chat_id');
    //     $sender_id = get_current_user_id();
    //     $message_content = $request->get_param('message_content');
    //     $message_type = $request->get_param('message_type') ?: 'text';
    //     $media_files = isset($_FILES['media_files']) ? $_FILES['media_files'] : null;

    //     // Validate input
    //     if (empty($chat_id) || (!$message_content && !$media_files)) {
    //         return new WP_Error('missing_params', 'Chat ID and either message content or media files are required.', ['status' => 400]);
    //     }

    //     $table_name = $this->messages_table;
    //     $media_table = $wpdb->prefix . 'chat_media_attachments';

    //     // Insert the message (initially without media)
    //     $wpdb->insert($table_name, [
    //         'chat_id' => $chat_id,
    //         'sender_id' => $sender_id,
    //         'message_content' => $message_content,
    //         'message_type' => $message_type,
    //         'created_at' => current_time('mysql'),
    //     ]);

    //     $message_id = $wpdb->insert_id;

    //     if (!$message_id) {
    //         return new WP_Error('db_insert_error', 'Failed to send message.', ['status' => 500]);
    //     }

    //     // Handle media upload if files are provided
    //     if ($media_files) {
    //         $upload_results = handle_media_upload($message_id, $media_files, 'chat-attachment');
    //         if (is_wp_error($upload_results)) {
    //             return $upload_results; // Return error if upload fails
    //         }
    //         // Save uploaded media details to the database
    //         foreach ($upload_results as $key => $media) {
    //             $wpdb->insert($media_table, [
    //                 'message_id' => $message_id,
    //                 'file_url' => $media['url'] ?? '',
    //                 'file_type' => $media['type'] ?? '',
    //                 'file_size' => $media_files['size'][$key] ?? '',
    //                 'created_at' => current_time('mysql'),
    //             ]);
    //             // Optionally update the message type to reflect media attachments
    //             $wpdb->update($this->messages_table, ['message_type' => $media['type']], ['message_id' => $message_id]);
    //         }


    //     }

    //     return rest_ensure_response([
    //         'success' => true,
    //         'message' => 'Message sent successfully.',
    //         'data' => [
    //             'message_id' => $message_id,
    //         ],
    //     ]);
    // }

    function send_message(WP_REST_Request $request)
    {
        global $wpdb;

        $chat_id = $request->get_param('chat_id');
        $sender_id = get_current_user_id();
        $message_content = $request->get_param('message_content');
        $message_type = $request->get_param('message_type') ?: 'text';
        $media_files = isset($_FILES['media_files']) ? $_FILES['media_files'] : null;

        // Validate input
        if (empty($chat_id) || (!$message_content && !$media_files)) {
            return new WP_Error('missing_params', 'Chat ID and either message content or media files are required.', ['status' => 400]);
        }

        $table_name = $this->messages_table;
        $media_table = $wpdb->prefix . 'chat_media_attachments';

        // Insert the message (initially without media)
        $wpdb->insert($table_name, [
            'chat_id' => $chat_id,
            'sender_id' => $sender_id,
            'message_content' => $message_content,
            'message_type' => $message_type,
            'created_at' => current_time('mysql'),
        ]);

        $message_id = $wpdb->insert_id;


        if (!$message_id) {
            return new WP_Error('db_insert_error', 'Failed to send message.', ['status' => 500]);
        }

        $media_urls = []; // Initialize array to store media URLs

        // Handle media upload if files are provided
        if ($media_files) {
            $upload_results = handle_media_upload($message_id, $media_files, 'chat-attachment');
            if (is_wp_error($upload_results)) {
                return $upload_results; // Return error if upload fails
            }

            // Save uploaded media details to the database and collect media URLs
            foreach ($upload_results as $key => $media) {
                $media_urls[] = $media['url'] ?? ''; // Add media URL to the array
                $wpdb->insert($media_table, [
                    'message_id' => $message_id,
                    'file_url' => $media['url'] ?? '',
                    'file_type' => $media['type'] ?? '',
                    'file_size' => $media_files['size'][$key] ?? '',
                    'created_at' => current_time('mysql'),
                ]);

                // Optionally update the message type to reflect media attachments
                $wpdb->update($this->messages_table, ['message_type' => $media['type']], ['message_id' => $message_id]);
            }
        }
        // Get recipient details (assuming chat participants schema is available)
        $recipient_id = $this->get_recipient_id($chat_id, $sender_id);
        error_log("recipient_id : - " . $recipient_id);

        if ($recipient_id) {
            $this->send_message_notification($recipient_id, $chat_id, $sender_id);
        }

        // Prepare the response
        $response_data = [
            'success' => true,
            'message' => 'Message sent successfully.',
            'data' => [
                'message_id' => $message_id,
            ],
        ];

        // Add media URLs to the response if media files were uploaded
        if (!empty($media_urls)) {
            $response_data['data']['media_urls'] = $media_urls;
        }

        return rest_ensure_response($response_data);
    }

    function start_new_chat(WP_REST_Request $request)
    {
        global $wpdb;

        $current_user_id = get_current_user_id();
        $target_user_id = $request->get_param('recipient_id');

        // Validate input
        if (empty($target_user_id) || $current_user_id == $target_user_id) {
            return new WP_Error('invalid_target', 'Invalid target user ID.', array('status' => 400));
        }

        // Check if a one-to-one chat already exists
        $chat_id = $wpdb->get_var($wpdb->prepare("
            SELECT c.chat_id
            FROM {$wpdb->prefix}chats c
            JOIN {$wpdb->prefix}chat_participants cp1 ON c.chat_id = cp1.chat_id
            JOIN {$wpdb->prefix}chat_participants cp2 ON c.chat_id = cp2.chat_id
            WHERE c.chat_type = 'one-to-one'
              AND cp1.user_id = %d
              AND cp2.user_id = %d
        ", $current_user_id, $target_user_id));

        if ($chat_id) {
            return rest_ensure_response(array(
                'success' => true,
                'message' => 'Chat already exists.',
                'chat_id' => $chat_id,
            ));
        }

        // Create a new chat
        $wpdb->insert("{$wpdb->prefix}chats", array(
            'chat_type' => 'one-to-one',
            'creator_id' => $current_user_id,
            'created_at' => current_time('mysql'),
        ));

        $new_chat_id = $wpdb->insert_id;

        if (!$new_chat_id) {
            return new WP_Error('db_error', 'Failed to create a new chat.', array('status' => 500));
        }

        // Add participants
        $wpdb->insert($this->chat_participants_table, array(
            'chat_id' => $new_chat_id,
            'user_id' => $current_user_id,
            'role' => 'participant',
            'joined_at' => current_time('mysql'),
        ));

        $wpdb->insert($this->chat_participants_table, array(
            'chat_id' => $new_chat_id,
            'user_id' => $target_user_id,
            'role' => 'participant',
            'joined_at' => current_time('mysql'),
        ));

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'New chat started successfully.',
            'chat_id' => $new_chat_id,
        ));
    }

    function get_messages_old_offset_pagination(WP_REST_Request $request)
    {
        global $wpdb;

        $chat_id = $request->get_param('chat_id');
        $page = (int) $request->get_param('page') ?: 1;
        $per_page = (int) $request->get_param('per_page') ?: 20;
        $offset = ($page - 1) * $per_page;
        $logged_in_user = get_current_user_id();

        // Validate input
        if (empty($chat_id)) {
            return new WP_Error('missing_params', 'Chat ID is required.', array('status' => 400));
        }

        // Validate chat participation
        $participant_table = $wpdb->prefix . 'chat_participants';
        $is_participant = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) 
        FROM $participant_table 
        WHERE chat_id = %d AND user_id = %d
    ", $chat_id, $logged_in_user));

        if (!$is_participant) {
            return new WP_Error('not_authorized', 'You are not a participant of this chat.', array('status' => 403));
        }

        // Fetch chat type
        $chats_table = $wpdb->prefix . 'chats';
        $chat_details = $wpdb->get_row($wpdb->prepare("
        SELECT chat_type, group_name, group_icon_url, is_broadcast 
        FROM $chats_table 
        WHERE chat_id = %d
    ", $chat_id), ARRAY_A);

        if (empty($chat_details)) {
            return new WP_Error('not_found', 'Chat not found.', array('status' => 404));
        }

        // Fetch total messages count
        $total_messages = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) 
        FROM $this->messages_table 
        WHERE chat_id = %d
    ", $chat_id));

        if (!$total_messages) {
            return rest_ensure_response(array(
                'success' => true,
                'chat_id' => $chat_id,
                'chat_type' => $chat_details['chat_type'],
                'group_name' => $chat_details['group_name'],
                'group_icon_url' => $chat_details['group_icon_url'],
                'is_broadcast' => (bool) $chat_details['is_broadcast'],
                'messages' => [],
                'meta' => [
                    'page' => $page,
                    'per_page' => $per_page,
                    'total_pages' => 0,
                    'total_items' => 0,
                ],
            ));
        }

        // Fetch messages with pagination
        $messages = $wpdb->get_results($wpdb->prepare("
        SELECT m.*, u.display_name AS sender_name, u.user_email, u.ID AS user_id
        FROM $this->messages_table m
        LEFT JOIN {$wpdb->users} u ON m.sender_id = u.ID
        WHERE m.chat_id = %d 
        ORDER BY m.created_at ASC
        LIMIT %d OFFSET %d
    ", $chat_id, $per_page, $offset), ARRAY_A);

        // Fetch participants details
        $participants = $wpdb->get_results($wpdb->prepare("
        SELECT u.ID AS user_id, u.display_name AS name, u.user_email, u.user_registered, cp.role
        FROM $participant_table cp
        LEFT JOIN {$wpdb->users} u ON cp.user_id = u.ID
        WHERE cp.chat_id = %d
    ", $chat_id), ARRAY_A);

        // Enhance message data with sender avatars
        foreach ($messages as &$message) {
            $message['sender_avatar'] = get_avatar_url($message['sender_id']);

            // Fetch associated media for the message
            $media = $wpdb->get_results($wpdb->prepare("SELECT file_url, file_type, file_size, created_at
             FROM $this->chat_media_attachments_table 
             WHERE message_id = %d", $message['message_id']), ARRAY_A);

            $message['media'] = $media ?: [];
        }

        // Pagination metadata
        $total_pages = ceil($total_messages / $per_page);

        return rest_ensure_response(array(
            'success' => true,
            'chat_id' => $chat_id,
            'chat_type' => $chat_details['chat_type'],
            'group_name' => $chat_details['group_name'],
            'group_icon_url' => $chat_details['group_icon_url'],
            'is_broadcast' => (bool) $chat_details['is_broadcast'],
            'participants' => $participants,
            'messages' => $messages,
            'meta' => [
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => $total_pages,
                'total_items' => $total_messages,
            ],
        ));
    }
    function get_messages(WP_REST_Request $request)
    {
        global $wpdb;

        $chat_id = $request->get_param('chat_id');
        $cursor = $request->get_param('cursor'); // Cursor for pagination (datetime stamp)
        $direction = $request->get_param('direction') ?: 'next'; // Direction: 'next' or 'prev'
        $per_page = (int) $request->get_param('per_page') ?: 20;
        $logged_in_user = get_current_user_id();

        // Validate input
        if (empty($chat_id)) {
            return new WP_Error('missing_params', 'Chat ID is required.', array('status' => 400));
        }

        // Validate chat participation
        $participant_table = $wpdb->prefix . 'chat_participants';
        $is_participant = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) 
        FROM $participant_table 
        WHERE chat_id = %d AND user_id = %d
    ", $chat_id, $logged_in_user));

        if (!$is_participant) {
            return new WP_Error('not_authorized', 'You are not a participant of this chat.', array('status' => 403));
        }

        // Fetch chat details
        $chats_table = $wpdb->prefix . 'chats';
        $chat_details = $wpdb->get_row($wpdb->prepare("
        SELECT chat_type, group_name, group_icon_url, is_broadcast 
        FROM $chats_table 
        WHERE chat_id = %d
    ", $chat_id), ARRAY_A);

        if (empty($chat_details)) {
            return new WP_Error('not_found', 'Chat not found.', array('status' => 404));
        }

        // Build the query for messages
        $messages_query = "
        SELECT m.*, u.display_name AS sender_name, u.user_email, u.ID AS user_id
        FROM $this->messages_table m
        LEFT JOIN {$wpdb->users} u ON m.sender_id = u.ID
        WHERE m.chat_id = %d 
    ";

        // Add cursor condition
        if (!empty($cursor)) {
            if ($direction === 'next') {
                $messages_query .= "AND m.created_at > %s ";
            } elseif ($direction === 'prev') {
                $messages_query .= "AND m.created_at < %s ";
            }
        } else {
            // No cursor provided; fetch the most recent messages
            $direction = 'prev'; // Default to fetching the latest messages
        }
        // Order and limit
        $order = $direction === 'prev' ? 'DESC' : 'ASC';
        $messages_query .= "ORDER BY m.created_at $order LIMIT %d";

        // Prepare query parameters
        $query_params = [$chat_id];
        if (!empty($cursor)) {
            $query_params[] = $cursor;
        }
        $query_params[] = $per_page;

        // Execute the query
        $messages = $wpdb->get_results($wpdb->prepare($messages_query, ...$query_params), ARRAY_A);

        // Reverse messages if fetching previous
        if ($direction === 'prev') {
            $messages = array_reverse($messages);
        }

        // Enhance message data with sender avatars and media
        foreach ($messages as &$message) {
            $message['sender_avatar'] = get_avatar_url($message['sender_id']);
            $media = $wpdb->get_results($wpdb->prepare("
            SELECT file_url, file_type, file_size, created_at
            FROM $this->chat_media_attachments_table 
            WHERE message_id = %d
        ", $message['message_id']), ARRAY_A);
            $message['media'] = $media ?: [];
        }

        // Fetch participants details
        $participants = $wpdb->get_results($wpdb->prepare("
        SELECT u.ID AS user_id, u.display_name AS name, u.user_email, u.user_registered, cp.role
        FROM $participant_table cp
        LEFT JOIN {$wpdb->users} u ON cp.user_id = u.ID
        WHERE cp.chat_id = %d
    ", $chat_id), ARRAY_A);
        // Add avatar URL to each participant
        foreach ($participants as &$participant) {
            $participant['avatar'] = get_avatar_url($participant['user_id']);
            $participant['is_business_user'] = is_user_business_member($participant['user_id']);
        }
        // Get next and previous cursors
        $next_cursor = !empty($messages) ? end($messages)['created_at'] : null;
        $prev_cursor = !empty($messages) ? $messages[0]['created_at'] : null;

        return rest_ensure_response(array(
            'success' => true,
            'chat_id' => $chat_id,
            'chat_type' => $chat_details['chat_type'],
            'group_name' => $chat_details['group_name'],
            'group_icon_url' => $chat_details['group_icon_url'],
            'is_broadcast' => (bool) $chat_details['is_broadcast'],
            'participants' => $participants,
            'messages' => $messages,
            'meta' => [
                'per_page' => $per_page,
                'next_cursor' => $next_cursor,
                'prev_cursor' => $prev_cursor,
            ],
        ));
    }


    public function get_group_members($request)
    {
        global $wpdb;
        $user_id = get_current_user_id(); // Get the current logged-in user ID
        $group_id = $request['group_id']; // Get the group ID from the request

        // Check if group_id is provided and is valid
        if (empty($group_id) || !is_numeric($group_id)) {
            return new WP_Error('invalid_group_id', 'Invalid group ID provided', ['status' => 400]);
        }

        // Check if the logged-in user is a member of the group
        $is_member = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}chat_participants WHERE user_id = %d AND chat_id = %d AND left_at IS NULL",
            $user_id,
            $group_id
        ));

        if ($is_member == 0) {
            // User is not a member of the group
            return new WP_Error('not_member', 'You are not a member of this group', ['status' => 403]);
        }

        // Fetch the list of members in the group excluding the current user
        $members = $wpdb->get_results($wpdb->prepare(
            "SELECT u.ID as user_id, u.user_login, u.user_email, p.role, p.joined_at
            FROM {$wpdb->prefix}chat_participants p
            JOIN {$wpdb->prefix}users u ON u.ID = p.user_id
            WHERE p.chat_id = %d AND p.left_at IS NULL AND p.user_id != %d",
            $group_id,
            $user_id
        ));

        if (empty($members)) {
            // No members found (it could happen if there are no members or all members have left)
            return new WP_Error('no_members', 'No members found in this group', ['status' => 404]);
        }

        // Return the members list excluding the current user
        return rest_ensure_response([
            'group_id' => $group_id,
            'members' => $members
        ]);
    }
    /**
     * Fetch chat list for the current user
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */



    // function get_chat_list(WP_REST_Request $request)
    // {
    //     global $wpdb;

    //     $current_user_id = get_current_user_id();

    //     // Get pagination and search parameters
    //     $page = (int) $request->get_param('page') ?: 1;
    //     $per_page = (int) $request->get_param('per_page') ?: 10;
    //     $search = trim($request->get_param('search'));
    //     $offset = ($page - 1) * $per_page;
    //     $search_condition = '';
    //     if (!empty($search)) {
    //         $search_term = '%' . $wpdb->esc_like($search) . '%';
    //         $search_condition = $wpdb->prepare("
    //         AND (
    //             (lm.message_content LIKE %s AND lm.message_id = (
    //                 SELECT MAX(message_id)
    //                 FROM {$this->messages_table}
    //                 WHERE chat_id = c.chat_id
    //             ))
    //             OR u.display_name LIKE %s
    //         )
    //     ", $search_term, $search_term);
    //     }

    //     // Count total chats for the user (with search and message existence check)
    //     $total_chats = $wpdb->get_var($wpdb->prepare("
    //     SELECT COUNT(DISTINCT c.chat_id)
    //     FROM {$wpdb->prefix}chats c
    //     JOIN {$this->chat_participants_table} cp ON c.chat_id = cp.chat_id
    //     LEFT JOIN {$this->messages_table} lm ON c.chat_id = lm.chat_id
    //     LEFT JOIN {$this->chat_participants_table} cp_other ON c.chat_id = cp_other.chat_id
    //     LEFT JOIN {$wpdb->users} u ON cp_other.user_id = u.ID
    //     WHERE cp.user_id = %d
    //     AND cp_other.user_id != %d
    //     AND EXISTS (
    //         SELECT 1
    //         FROM {$this->messages_table}
    //         WHERE chat_id = c.chat_id
    //     )
    //     $search_condition
    // ", $current_user_id, $current_user_id));

    //     if (!$total_chats) {
    //         return rest_ensure_response([
    //             'success' => true,
    //             'message' => 'No chats found.',
    //             'data' => [],
    //             'meta' => [
    //                 'page' => $page,
    //                 'per_page' => $per_page,
    //                 'total_pages' => 0,
    //                 'total_items' => 0,
    //             ],
    //         ]);
    //     }

    //     // Fetch chats with pagination and search
    //     $chats = $wpdb->get_results($wpdb->prepare("
    //     SELECT c.chat_id, c.chat_type, c.creator_id, c.group_name, c.group_icon_url, c.is_broadcast, c.created_at, c.updated_at,
    //     (
    //         SELECT COUNT(message_id)
    //         FROM {$this->messages_table}
    //         WHERE chat_id = c.chat_id
    //         AND sender_id != %d
    //         AND is_read = 0
    //     ) AS unread_count
    //     FROM {$wpdb->prefix}chats c
    //     JOIN {$this->chat_participants_table} cp ON c.chat_id = cp.chat_id
    //     LEFT JOIN {$this->messages_table} lm ON c.chat_id = lm.chat_id
    //     LEFT JOIN {$this->chat_participants_table} cp_other ON c.chat_id = cp_other.chat_id
    //     LEFT JOIN {$wpdb->users} u ON cp_other.user_id = u.ID
    //     WHERE cp.user_id = %d
    //     AND cp_other.user_id != %d
    //     AND EXISTS (
    //         SELECT 1
    //         FROM {$this->messages_table}
    //         WHERE chat_id = c.chat_id
    //     )
    //     $search_condition
    //     GROUP BY c.chat_id
    //     ORDER BY c.updated_at DESC
    //     LIMIT %d OFFSET %d
    // ", $current_user_id, $current_user_id, $current_user_id, $per_page, $offset), ARRAY_A);

    //     // Fetch additional details for each chat
    //     $chat_list = [];
    //     foreach ($chats as $chat) {
    //         $recipient_details = $this->get_recipient_details($chat['chat_id'], $current_user_id);
    //         $last_message = $this->get_last_message($chat['chat_id']);

    //         $chat_list[] = [
    //             'chat_id' => $chat['chat_id'],
    //             'chat_type' => $chat['chat_type'],
    //             'creator_id' => $chat['creator_id'],
    //             'group_name' => $chat['group_name'],
    //             'group_icon_url' => $chat['group_icon_url'],
    //             'is_broadcast' => (bool) $chat['is_broadcast'],
    //             'created_at' => $chat['created_at'],
    //             'updated_at' => $chat['updated_at'],
    //             'last_message' => $last_message,
    //             'recipient' => $recipient_details,
    //             'unread_count' => (int) $chat['unread_count'], // Add unread count here
    //         ];
    //     }

    //     // Calculate total pages
    //     $total_pages = ceil($total_chats / $per_page);

    //     // Return response with pagination metadata
    //     return rest_ensure_response([
    //         'success' => true,
    //         'message' => 'Chats fetched successfully.',
    //         'data' => $chat_list,
    //         'meta' => [
    //             'page' => $page,
    //             'per_page' => $per_page,
    //             'total_pages' => $total_pages,
    //             'total_items' => $total_chats,
    //         ],
    //     ]);
    // }

    //     function get_chat_list(WP_REST_Request $request)
//     {
//         global $wpdb;

    //         $current_user_id = get_current_user_id();

    //         // Get pagination and search parameters
//         $page = (int) $request->get_param('page') ?: 1;
//         $per_page = (int) $request->get_param('per_page') ?: 10;
//         $search = trim($request->get_param('search'));
//         $offset = ($page - 1) * $per_page;

    //         $search_condition = '';
//         if (!empty($search)) {
//             $search_term = '%' . $wpdb->esc_like($search) . '%';
//             $search_condition = $wpdb->prepare("
//                 AND (
//                     (lm.message_content LIKE %s AND lm.message_id = (
//                         SELECT MAX(message_id)
//                         FROM {$this->messages_table}
//                         WHERE chat_id = c.chat_id
//                     ))
//                     OR u.display_name LIKE %s
//                 )
//             ", $search_term, $search_term);
//         }

    //         // Count total chats for the user (with search and message existence check)
//         $total_chats = $wpdb->get_var($wpdb->prepare("
//             SELECT COUNT(DISTINCT c.chat_id)
//             FROM {$wpdb->prefix}chats c
//             JOIN {$this->chat_participants_table} cp ON c.chat_id = cp.chat_id
//             LEFT JOIN {$this->messages_table} lm ON c.chat_id = lm.chat_id
//             LEFT JOIN {$this->chat_participants_table} cp_other ON c.chat_id = cp_other.chat_id
//             LEFT JOIN {$wpdb->users} u ON cp_other.user_id = u.ID
//             WHERE cp.user_id = %d
//             AND cp_other.user_id != %d
//             AND EXISTS (
//                 SELECT 1
//                 FROM {$this->messages_table}
//                 WHERE chat_id = c.chat_id
//             )
//             $search_condition
//         ", $current_user_id, $current_user_id));

    //         if (!$total_chats) {
//             return rest_ensure_response([
//                 'success' => true,
//                 'message' => 'No chats found.',
//                 'data' => [],
//                 'meta' => [
//                     'page' => $page,
//                     'per_page' => $per_page,
//                     'total_pages' => 0,
//                     'total_items' => 0,
//                 ],
//             ]);
//         }

    //         // // Fetch chats with pagination and search
//         // $chats = $wpdb->get_results($wpdb->prepare("
//         //     SELECT c.chat_id, c.chat_type, c.creator_id, c.group_name, c.group_icon_url, c.is_broadcast, c.created_at, c.updated_at,
//         //     (
//         //         SELECT COUNT(m.message_id)
//         //         FROM {$this->messages_table} m
//         //         LEFT JOIN {$wpdb->prefix}message_reads mr 
//         //         ON m.message_id = mr.message_id AND mr.user_id = %d
//         //         WHERE m.chat_id = c.chat_id
//         //         AND m.sender_id != %d
//         //         AND mr.message_id IS NULL
//         //     ) AS unread_count
//         //     FROM {$wpdb->prefix}chats c
//         //     JOIN {$this->chat_participants_table} cp ON c.chat_id = cp.chat_id
//         //     LEFT JOIN {$this->messages_table} lm ON c.chat_id = lm.chat_id
//         //     LEFT JOIN {$this->chat_participants_table} cp_other ON c.chat_id = cp_other.chat_id
//         //     LEFT JOIN {$wpdb->users} u ON cp_other.user_id = u.ID
//         //     WHERE cp.user_id = %d
//         //     AND cp_other.user_id != %d
//         //     AND EXISTS (
//         //         SELECT 1
//         //         FROM {$this->messages_table}
//         //         WHERE chat_id = c.chat_id
//         //     )
//         //     $search_condition
//         //     GROUP BY c.chat_id
//         //     ORDER BY c.updated_at DESC
//         //     LIMIT %d OFFSET %d
//         // ", $current_user_id, $current_user_id, $current_user_id, $current_user_id, $per_page, $offset), ARRAY_A);
//         // Fetch chats with pagination and search
//         $chats = $wpdb->get_results($wpdb->prepare("
// SELECT c.chat_id, c.chat_type, c.creator_id, c.group_name, c.group_icon_url, c.is_broadcast, c.created_at, 
// MAX(lm.created_at) AS last_message_date, -- Fetch the latest message date for proper ordering
// (
//     SELECT COUNT(m.message_id)
//     FROM {$this->messages_table} m
//     LEFT JOIN {$wpdb->prefix}message_reads mr 
//     ON m.message_id = mr.message_id AND mr.user_id = %d
//     WHERE m.chat_id = c.chat_id
//     AND m.sender_id != %d
//     AND mr.message_id IS NULL
// ) AS unread_count
// FROM {$wpdb->prefix}chats c
// JOIN {$this->chat_participants_table} cp ON c.chat_id = cp.chat_id
// LEFT JOIN {$this->messages_table} lm ON c.chat_id = lm.chat_id
// LEFT JOIN {$this->chat_participants_table} cp_other ON c.chat_id = cp_other.chat_id
// LEFT JOIN {$wpdb->users} u ON cp_other.user_id = u.ID
// WHERE cp.user_id = %d
// AND cp_other.user_id != %d
// AND EXISTS (
//     SELECT 1
//     FROM {$this->messages_table}
//     WHERE chat_id = c.chat_id
// )
// $search_condition
// GROUP BY c.chat_id
// ORDER BY last_message_date DESC -- Order by the latest message date in descending order
// LIMIT %d OFFSET %d
// ", $current_user_id, $current_user_id, $current_user_id, $current_user_id, $per_page, $offset), ARRAY_A);
//         // Fetch additional details for each chat
//         $chat_list = [];
//         foreach ($chats as $chat) {
//             $recipient_details = $this->get_recipient_details($chat['chat_id'], $current_user_id);
//             $last_message = $this->get_last_message($chat['chat_id']);

    //             $chat_list[] = [
//                 'chat_id' => $chat['chat_id'],
//                 'chat_type' => $chat['chat_type'],
//                 'creator_id' => $chat['creator_id'],
//                 'group_name' => $chat['group_name'],
//                 'group_icon_url' => $chat['group_icon_url'],
//                 'is_broadcast' => (bool) $chat['is_broadcast'],
//                 'created_at' => $chat['created_at'],
//                 'updated_at' => $chat['last_message_date'],
//                 'last_message' => $last_message,
//                 'recipient' => $recipient_details,
//                 'unread_count' => (int) $chat['unread_count'], // Add unread count here
//             ];
//         }

    //         // Calculate total pages
//         $total_pages = ceil($total_chats / $per_page);

    //         // Return response with pagination metadata
//         return rest_ensure_response([
//             'success' => true,
//             'message' => 'Chats fetched successfully.',
//             'data' => $chat_list,
//             'meta' => [
//                 'page' => $page,
//                 'per_page' => $per_page,
//                 'total_pages' => $total_pages,
//                 'total_items' => $total_chats,
//             ],
//         ]);
//     }
    public function get_chat_list(WP_REST_Request $request)
    {
        global $wpdb;

        $current_user_id = get_current_user_id();

        // Get pagination and search parameters
        $page = (int) $request->get_param('page') ?: 1;
        $per_page = (int) $request->get_param('per_page') ?: 10;
        $search = trim($request->get_param('search'));  // Search term from the request
        $offset = ($page - 1) * $per_page;

        // Default search condition is empty if no search term is provided
        $search_condition = '';
        if (!empty($search)) {
            // If there's a search term, add the condition to search in messages and user display names
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $search_condition = $wpdb->prepare("
            AND (
                -- Search in message content
                lm.message_content LIKE %s
                OR
                -- Search in recipient's display name
                u.display_name LIKE %s
            )
        ", $search_term, $search_term);
        }

        // Count total chats for the user (with search and message existence check)
        $total_chats = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT c.chat_id)
        FROM {$wpdb->prefix}chats c
        JOIN {$this->chat_participants_table} cp ON c.chat_id = cp.chat_id
        LEFT JOIN {$this->messages_table} lm ON c.chat_id = lm.chat_id
        LEFT JOIN {$this->chat_participants_table} cp_other ON c.chat_id = cp_other.chat_id
        LEFT JOIN {$wpdb->users} u ON cp_other.user_id = u.ID
        WHERE cp.user_id = %d
        AND cp_other.user_id != %d
        AND EXISTS (
            SELECT 1
            FROM {$this->messages_table}
            WHERE chat_id = c.chat_id
        )
        $search_condition  -- Only added if there's a search term
    ", $current_user_id, $current_user_id));

        if (!$total_chats) {
            return rest_ensure_response([
                'success' => true,
                'message' => 'No chats found.',
                'data' => [],
                'meta' => [
                    'page' => $page,
                    'per_page' => $per_page,
                    'total_pages' => 0,
                    'total_items' => 0,
                ],
            ]);
        }

        // Fetch chats with pagination and search (if search term is provided)
        $chats = $wpdb->get_results($wpdb->prepare("
        SELECT c.chat_id, c.chat_type, c.creator_id, c.group_name, c.group_icon_url, c.is_broadcast, c.created_at, 
        MAX(lm.created_at) AS last_message_date, -- Latest message date for proper ordering
        (
            SELECT COUNT(m.message_id)
            FROM {$this->messages_table} m
            LEFT JOIN {$wpdb->prefix}message_reads mr 
            ON m.message_id = mr.message_id AND mr.user_id = %d
            WHERE m.chat_id = c.chat_id
            AND m.sender_id != %d
            AND mr.message_id IS NULL
        ) AS unread_count
        FROM {$wpdb->prefix}chats c
        JOIN {$this->chat_participants_table} cp ON c.chat_id = cp.chat_id
        LEFT JOIN {$this->messages_table} lm ON c.chat_id = lm.chat_id
        LEFT JOIN {$this->chat_participants_table} cp_other ON c.chat_id = cp_other.chat_id
        LEFT JOIN {$wpdb->users} u ON cp_other.user_id = u.ID
        WHERE cp.user_id = %d
        AND cp_other.user_id != %d
        AND EXISTS (
            SELECT 1
            FROM {$this->messages_table}
            WHERE chat_id = c.chat_id
        )
        $search_condition  -- Only added if there's a search term
        GROUP BY c.chat_id
        ORDER BY last_message_date DESC -- Order by the latest message date in descending order
        LIMIT %d OFFSET %d
    ", $current_user_id, $current_user_id, $current_user_id, $current_user_id, $per_page, $offset), ARRAY_A);

        // Fetch additional details for each chat
        $chat_list = [];
        foreach ($chats as $chat) {
            $recipient_details = $this->get_recipient_details($chat['chat_id'], $current_user_id);
            $last_message = $this->get_last_message($chat['chat_id']);

            // If search is provided, fetch matching messages to highlight
            $highlighted_messages = [];
            if (!empty($search)) {
                // Fetch all messages matching the search term in this chat (for highlighting purposes)
                $messages = $wpdb->get_results($wpdb->prepare("
                SELECT m.message_id, m.message_content, m.created_at
                FROM {$this->messages_table} m
                WHERE m.chat_id = %d
                AND m.message_content LIKE %s
                ORDER BY m.created_at DESC
            ", $chat['chat_id'], '%' . $wpdb->esc_like($search) . '%'));

                // Prepare the highlighted messages list
                foreach ($messages as $message) {
                    $highlighted_messages[] = [
                        'message_id' => $message->message_id,
                        'message_content' => $message->message_content,
                        'created_at' => $message->created_at,
                    ];
                }
            }

            // Add chat details with highlighted messages (if any)
            $chat_list[] = [
                'chat_id' => $chat['chat_id'],
                'chat_type' => $chat['chat_type'],
                'creator_id' => $chat['creator_id'],
                'group_name' => $chat['group_name'],
                'group_icon_url' => $chat['group_icon_url'],
                'is_broadcast' => (bool) $chat['is_broadcast'],
                'created_at' => $chat['created_at'],
                'updated_at' => $chat['last_message_date'],
                'last_message' => $last_message,
                'recipient' => $recipient_details,
                'unread_count' => (int) $chat['unread_count'], // Add unread count here
                'highlighted_messages' => $highlighted_messages, // Include highlighted messages
            ];
        }

        // Calculate total pages
        $total_pages = ceil($total_chats / $per_page);

        // Return response with pagination metadata
        return rest_ensure_response([
            'success' => true,
            'message' => 'Chats fetched successfully.',
            'data' => $chat_list,
            'meta' => [
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => $total_pages,
                'total_items' => $total_chats,
            ],
        ]);
    }



    /**
     * Fetch the last message for a chat
     *
     * @param int $chat_id
     * @return array|null
     */
    private function get_last_message($chat_id)
    {
        global $wpdb;

        $last_message = $wpdb->get_row($wpdb->prepare("
        SELECT m.message_id, m.message_content, m.sender_id, m.created_at, m.message_type
        FROM {$this->messages_table} m
        WHERE m.chat_id = %d
        ORDER BY m.created_at DESC
        LIMIT 1
    ", $chat_id), ARRAY_A);

        if ($last_message) {
            return [
                'message_id' => $last_message['message_id'],
                'message_content' => $last_message['message_content'],
                'message_type' => $last_message['message_type'],
                'sender_id' => $last_message['sender_id'],
                'created_at' => $last_message['created_at'],
            ];
        }

        return null;
    }

    /**
     * Fetch recipient details for a chat
     *
     * @param int $chat_id
     * @param int $current_user_id
     * @return array|null
     */
    private function get_recipient_details($chat_id, $current_user_id)
    {
        global $wpdb;

        // Fetch the other participant in a one-to-one chat
        $recipient_id = $wpdb->get_var($wpdb->prepare("
        SELECT user_id
        FROM {$this->chat_participants_table}
        WHERE chat_id = %d
          AND user_id != %d
        LIMIT 1
    ", $chat_id, $current_user_id));

        if (!$recipient_id) {
            return null;
        }

        // Fetch user data and avatar
        $user_data = get_userdata($recipient_id);

        if (!$user_data) {
            return null;
        }

        return [
            'id' => $recipient_id,
            'name' => $user_data->display_name,
            'avatar_url' => get_avatar_url($recipient_id),
        ];
    }

    function mark_messages_as_read(WP_REST_Request $request)
    {
        $chat_id = (int) $request->get_param('chat_id');
        $message_id = (int) $request->get_param('message_id');
        $user_id = get_current_user_id();

        if ($message_id) {
            $this->mark_message_as_read($message_id, $user_id);
        } else {
            $this->mark_all_messages_as_read($chat_id, $user_id);
        }

        return rest_ensure_response([
            'success' => true,
            'message' => 'Messages marked as read.',
        ]);
    }


    // Bulk mark messages as read when user opens the chat
    function mark_all_messages_as_read($chat_id, $user_id)
    {
        global $wpdb;

        // Insert read entries for all messages in the chat
        $sql = $wpdb->prepare("
            INSERT INTO $this->message_reads_table (message_id, user_id, read_at)
            SELECT m.message_id, %d, NOW()
            FROM {$wpdb->prefix}messages m
            WHERE m.chat_id = %d
            ON DUPLICATE KEY UPDATE read_at = VALUES(read_at)
        ", $user_id, $chat_id);

        $wpdb->query($sql);
    }
    function mark_message_as_read($message_id, $user_id)
    {
        global $wpdb;

        $sql = $wpdb->prepare("
            INSERT INTO $this->message_reads_table (message_id, user_id, read_at)
            VALUES (%d, %d, NOW())
            ON DUPLICATE KEY UPDATE read_at = VALUES(read_at)
        ", $message_id, $user_id);

        $wpdb->query($sql);
    }

    function send_message_notification($recipient_id, $chat_id, $sender_id)
    {
        // $fcm_token = get_user_meta($post_author_id, 'fcm_token', true);
        $fcm_token = get_user_meta($recipient_id, 'firebase_token', true);

        $notification_data = [
            'user_id' => $recipient_id, // The post author to notify
            'item_id' => $chat_id,        // Post ID
            'secondary_item_id' => $sender_id,        // The user who liked the post
            'component_name' => 'chat_messages',    // Component name
            'component_action' => 'one_to_one_message',     // Unique action key for the like notification
            'date_notified' => bp_core_current_time(),
            'is_new' => 1,               // Mark as new
        ];
        // Add a notification for the post author
        bp_notifications_add_notification($notification_data);
        /***optimize section start */
        $getNotificationDetails = get_notification_details($notification_data['component_name'], $notification_data['component_action'], $notification_data['item_id'], $sender_id);
        error_log("notfication current fcm in chat" . print_r($getNotificationDetails, true));
        $notification_message = $getNotificationDetails['message'] ?? '';
        $title = $getNotificationDetails['title'] ?? '';
        $slug = $getNotificationDetails['slug'] ?? '';
        /***optimize section end */

        $keyFilePath = '/var/www/html/circuit-club/circuit-club-fcm-firebase-adminsdk-h8ibi-f11140baff.json'; // Replace with the correct path to your service account JSON file

        // Path to your service account JSON key file
        $serviceAccountPath = $keyFilePath;

        // Your Firebase project ID
        $projectId = 'circuit-club-fcm';
        // $title = 'New Like on Your Post';
        // $message = get_user_by('id', $user_id)->display_name . ' liked your post.';
        // Example message payload
        $message = [
            'token' => $fcm_token,
            'notification' => [
                'title' => $title, // Use the title from the reusable function
                'body' => $notification_message  // Use the body from the reusable function
            ],
            'data' => [
                'title' => $title, // Custom data
                'custom_message' => $notification_message, // Replace with your custom message
                'date' => $notification_data['date_notified'], // Replace with your date or custom field
                'sender_avatar' => 'assets/images/profile_default.png', // Replace with the correct avatar path
                'slug' => $slug, // Add your slug
                'secondary_item_id' => (string) $sender_id, // Add your slug
            ],
            // 'webpush' => [
            //     'fcm_options' => [
            //         'link' => $slug, // Replace with the URL for web push
            //     ],
            // ],
        ];
        send_realtime_notification($serviceAccountPath, $projectId, $message);
    }

    function get_recipient_id($chat_id, $sender_id)
    {
        global $wpdb;
        $chat_table = $wpdb->prefix . 'chat_participants';

        $recipient_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM $chat_table WHERE chat_id = %d AND user_id != %d",
            $chat_id,
            $sender_id
        ));

        return $recipient_id;
    }

    // function update_user_chat_status(WP_REST_Request $request)
    // {
    //     global $wpdb;

    //     $user_id = $request->get_param('user_id');
    //     $is_online = $request->get_param('is_online');
    //     $current_time = current_time('mysql');

    //     if (!$user_id) {
    //         return new WP_Error('missing_params', 'User ID is required', ['status' => 400]);
    //     }

    //     $wpdb->replace(
    //         $this->user_chat_activities_table,
    //         [
    //             'user_id' => $user_id,
    //             'is_online' => (int) $is_online,
    //             'last_seen' => $is_online ? null : $current_time,
    //             'last_activity' => $current_time,
    //         ],
    //         ['%d', '%d', '%s', '%s']
    //     );

    //     return rest_ensure_response(['message' => 'User status updated successfully']);
    // }

    // public function get_user_chat_status(WP_REST_Request $request) {
    //     global $wpdb;

    //     $user_ids = $request->get_param('user_ids');
    //     if (empty($user_ids)) {
    //         return new WP_Error('missing_params', 'User IDs are required', ['status' => 400]);
    //     }

    //     $table_name = $wpdb->prefix . 'user_chat_activities';
    //     $placeholders = implode(',', array_fill(0, count($user_ids), '%d'));

    //     $query = $wpdb->prepare(
    //         "SELECT user_id, is_online, last_activity 
    //          FROM $table_name 
    //          WHERE user_id IN ($placeholders)",
    //         $user_ids
    //     );

    //     $results = $wpdb->get_results($query);

    //     return rest_ensure_response($results);
    // }


    function update_user_chat_status($user_id, $is_online)
    {
        global $wpdb;

        // $table_name = $wpdb->prefix . 'user_chat_activities';
        $status = $is_online ? 1 : 0;
        $last_activity = current_time('mysql');
        $last_seen = $is_online ? null : $last_activity;

        $data = [
            'user_id' => $user_id,
            'is_online' => $status,
            'last_seen' => $last_seen,
            'last_activity' => $last_activity
        ];

        // Insert or replace the record
        $result = $wpdb->replace($this->user_chat_activities_table, $data);

        if ($result === false) {
            error_log("Failed to update user status for user $user_id");
        } else {
            error_log("User $user_id status updated successfully.");
        }
    }
    function update_user_status(WP_REST_Request $request)
    {
        $user_id = get_current_user_id();
        $is_online = $request->get_param('is_online') ? 1 : 0;

        $this->update_user_chat_status($user_id, $is_online);

        return rest_ensure_response(['success' => true, 'message' => 'Status updated']);
    }
    function get_user_status(WP_REST_Request $request)
    {
        global $wpdb;

        $user_id = (int) $request->get_param('user_id');

        // Validate user ID
        if (empty($user_id)) {
            return new WP_Error('invalid_user_id', 'User ID is required.', ['status' => 400]);
        }

        // $table_name = $wpdb->prefix . 'user_chat_activities';
        $result = $wpdb->get_row($wpdb->prepare(
            "
            SELECT user_id, is_online, last_seen, last_activity 
            FROM $this->user_chat_activities_table 
            WHERE user_id = %d",
            $user_id
        ));

        if (!$result) {
            return new WP_Error('user_not_found', 'No chat status found for the user.', ['status' => 404]);
        }

        return rest_ensure_response([
            'user_id' => $result->user_id,
            'is_online' => (bool) $result->is_online,
            'last_seen' => $result->last_seen,
            'last_activity' => $result->last_activity,
        ]);
    }

    public function get_unread_message_count()
    {
        $user_id = get_current_user_id();

        if (!$user_id) {
            return new WP_Error('no_user', 'User not logged in', ['status' => 401]);
        }

        global $wpdb;
        $unread_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}messages m
             LEFT JOIN {$wpdb->prefix}message_reads mr
             ON m.message_id = mr.message_id AND mr.user_id = %d
             WHERE mr.message_id IS NULL AND m.sender_id != %d AND m.chat_id IN (
                 SELECT chat_id FROM {$wpdb->prefix}chat_participants WHERE user_id = %d
             )",
            $user_id, $user_id, $user_id
        ));

        return rest_ensure_response([
            'user_id' => $user_id,
            'unread_messages_count' => (int) $unread_count,
        ]);
    }
}
