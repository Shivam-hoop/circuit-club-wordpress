<?php

class Group_Chat_Controller
{

    const NAMESPACE = 'buddypress/v1';
    const ROUTE_BASE = '/group';
    private $messages_table;
    private $chat_participants_table;
    private $group_notifications_table;
    private $chats_table;


    public function __construct()
    {
        global $wpdb;

        $this->messages_table = "{$wpdb->prefix}messages"; // Table name with the WordPress prefix
        $this->chat_participants_table = "{$wpdb->prefix}chat_participants"; // Table name with the WordPress prefix
        $this->group_notifications_table = "{$wpdb->prefix}group_notifications"; // Table name with the WordPress prefix
        $this->chats_table = "{$wpdb->prefix}chats"; // Table name with the WordPress prefix

        // Register the routes when the API is initialized
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes()
    {
        // Define the CRUD routes
        register_rest_route(self::NAMESPACE , self::ROUTE_BASE . '/create', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'create_group_chat'],
            'permission_callback' => [$this, 'check_permissions']
        ]);
        register_rest_route(self::NAMESPACE , self::ROUTE_BASE . '/leave', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'leave_group'],
            'permission_callback' => [$this, 'check_permissions']
        ]);
        register_rest_route(self::NAMESPACE , self::ROUTE_BASE . '/list', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_groups_list'],
            'permission_callback' => [$this, 'check_permissions']
        ]);
        register_rest_route(self::NAMESPACE , self::ROUTE_BASE . '/(?P<group_id>\d+)/members', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_group_members'],
            'permission_callback' => [$this, 'check_permissions']
        ]);
        register_rest_route(self::NAMESPACE , self::ROUTE_BASE . '/(?P<chat_id>\d+)', [
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => [$this, 'edit_group'],
            'permission_callback' => [$this, 'check_permissions']
        ]);
        register_rest_route(self::NAMESPACE , self::ROUTE_BASE . '/edit-group-members', [
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => [$this, 'edit_group_members'],
            'permission_callback' => [$this, 'check_permissions']
        ]);
        register_rest_route(self::NAMESPACE , self::ROUTE_BASE . '/join', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'join_group_chat'],
            'permission_callback' => '__return_true', // Allow anyone to join groups
        ]);

    }

    // Check permissions for API access
    public function check_permissions($request)
    {
        return is_user_logged_in();  // Adjust based on your requirements
    }

    function create_group_chat(WP_REST_Request $request)
    {
        global $wpdb;

        $current_user_id = get_current_user_id();

        // Get request parameters
        $group_name = $request->get_param('group_name');
        $group_description = $request->get_param('group_description') ?? '';
        $group_icon_url = $request->get_param('group_icon_url') ?? '';
        $participants = $request->get_param('participants'); // Array of user IDs
        $is_broadcast = $request->get_param('is_broadcast') ?? false; // Boolean

        // Validate inputs
        if (empty($group_name)) {
            return new WP_Error('invalid_params', 'Group name is required.', array('status' => 400));
        }
        if (empty($participants) || !is_array($participants)) {
            return new WP_Error('invalid_params', 'Participants list is required and must be an array.', array('status' => 400));
        }

        // Step 1: Create the group in `wp_chats`
        $wpdb->insert("{$wpdb->prefix}chats", array(
            'chat_type' => 'group',
            'creator_id' => $current_user_id,
            'is_broadcast' => $is_broadcast ? 1 : 0,
            'group_name' => $group_name,
            'group_icon_url' => $group_icon_url,
            'group_description' => $group_description,
            'created_at' => current_time('mysql'),
        ));
        $chat_id = $wpdb->insert_id;

        if (!$chat_id) {
            return new WP_Error('db_error', 'Failed to create group.', array('status' => 500));
        }

        // Step 2: Add creator to the group as admin
        $wpdb->insert($this->chat_participants_table, array(
            'chat_id' => $chat_id,
            'user_id' => $current_user_id,
            'role' => 'admin',
            'joined_at' => current_time('mysql'),
        ));

        // Step 3: Add participants
        foreach ($participants as $participant_id) {
            if ($participant_id == $current_user_id) {
                continue; // Skip the creator (already added)
            }

            $wpdb->insert($this->chat_participants_table, array(
                'chat_id' => $chat_id,
                'user_id' => $participant_id,
                'role' => 'member', // Default role for participants
                'joined_at' => current_time('mysql'),
            ));
        }
        // Step 4: Auto-generate a welcome message
        $welcome_message = "Welcome to $group_name!";
        $wpdb->insert("{$wpdb->prefix}messages", array(
            'chat_id' => $chat_id,
            'sender_id' => $current_user_id,
            'message_content' => $welcome_message,
            'message_type' => 'text',
            'created_at' => current_time('mysql'),
            'is_broadcast' => $is_broadcast ? 1 : 0,
        ));

        // Step 5: Return success response
        return rest_ensure_response(array(
            'success' => true,
            'chat_id' => $chat_id,
            'message' => 'Group created successfully.',
            'is_broadcast' => $is_broadcast,
        ));
    }
    function leave_group(WP_REST_Request $request)
    {
        global $wpdb;

        $current_user_id = get_current_user_id();
        $chat_id = $request->get_param('chat_id');

        // Validate inputs
        if (empty($chat_id)) {
            return new WP_Error('invalid_params', 'Chat ID is required.', array('status' => 400));
        }

        // Check if the user is part of the chat
        $participant = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$this->chat_participants_table} 
            WHERE chat_id = %d AND user_id = %d AND left_at IS NULL
        ", $chat_id, $current_user_id));

        if (!$participant) {
            return new WP_Error('not_found', 'You are not an active member of this group.', array('status' => 404));
        }

        // Handle admin roles: prevent the last admin from leaving
        if ($participant->role === 'admin') {
            $admins_count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM {$this->chat_participants_table} 
                WHERE chat_id = %d AND role = 'admin' AND left_at IS NULL
            ", $chat_id));

            if ($admins_count <= 1) {
                return new WP_Error('last_admin', 'You cannot leave the group as the only admin.', array('status' => 400));
            }
        }

        // Perform soft delete by updating `left_at` column
        $updated = $wpdb->update(
            $this->chat_participants_table,
            array('left_at' => current_time('mysql')),
            array('chat_id' => $chat_id, 'user_id' => $current_user_id)
        );

        if ($updated === false) {
            return new WP_Error('db_error', 'Failed to leave group.', array('status' => 500));
        }

        // Log the event in the group_notifications table
        $wpdb->insert($this->group_notifications_table, array(
            'chat_id' => $chat_id,
            'user_id' => $current_user_id,
            'action' => 'left',
            'created_at' => current_time('mysql'),
        ));

        // Return success response
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'You have successfully left the group.',
            'chat_id' => $chat_id,
        ));
    }
    public function get_groups_list($request)
    {
        global $wpdb;
        $logged_in_user = get_current_user_id(); // Get the current logged-in user ID

        // Prepare the base SQL query to get all groups (chat_type = 'group')
        $sql = "
            SELECT c.chat_id, c.group_name, c.is_broadcast, c.group_icon_url, c.group_description
            FROM $this->chats_table c
            WHERE c.chat_type = 'group'
        ";

        // Check if a specific user_id filter is provided
        if (!empty($request['user_id'])) {
            $user_id = intval($request['user_id']);
            $sql .= $wpdb->prepare("
                AND EXISTS (
                    SELECT 1
                    FROM $this->chat_participants_table p
                    WHERE p.user_id = %d
                    AND p.chat_id = c.chat_id
                    AND p.left_at IS NULL
                )
            ", $user_id);
        }

        // Check for any search/filter parameters in the request
        if (!empty($request['search'])) {
            $search = sanitize_text_field($request['search']);
            $sql .= $wpdb->prepare(" AND c.group_name LIKE %s", "%" . $search . "%");
        }

        // Pagination parameters
        $page = !empty($request['page']) ? intval($request['page']) : 1;
        $per_page = !empty($request['per_page']) ? intval($request['per_page']) : 10;
        $offset = ($page - 1) * $per_page;

        // Add ordering by created_at DESC (latest records first), and pagination to the SQL query
        $sql .= " ORDER BY c.created_at DESC LIMIT %d OFFSET %d";
        $sql = $wpdb->prepare($sql, $per_page, $offset);

        // Execute the query to get the group data
        $groups = $wpdb->get_results($sql);

        // If no groups are found, return empty list with pagination info
        if (empty($groups)) {
            return rest_ensure_response([
                'groups' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $per_page,
            ]);
        }

        // Get the total number of groups for pagination
        $total_groups = $wpdb->get_var("SELECT COUNT(*) FROM $this->chats_table WHERE chat_type = 'group'");

        // Now check if the logged-in user is a member of each group
        foreach ($groups as &$group) {
            $group->is_member = false; // Default is not a member

            if ($logged_in_user) {
                // Check if the user is a member of this group
                $membership_check = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $this->chat_participants_table WHERE user_id = %d AND chat_id = %d AND left_at IS NULL",
                    $logged_in_user,
                    $group->chat_id
                ));

                // If the user is a member of this group, set `is_member` to true
                if ($membership_check > 0) {
                    $group->is_member = true;
                }
            }
        }

        // Return the groups with the total count and pagination
        return rest_ensure_response([
            'groups' => $groups,
            'total' => $total_groups,
            'page' => $page,
            'per_page' => $per_page,
        ]);
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

        // Add the avatar URL to each member
        foreach ($members as &$member) {
            // Get the user's Gravatar URL (avatar) based on their email
            $member->avatar_url = get_avatar_url($member->user_email, ['size' => 80]); // 80px size is typical for avatars
        }

        // Return the members list excluding the current user
        return rest_ensure_response([
            'group_id' => $group_id,
            'members' => $members
        ]);
    }
    function edit_group(WP_REST_Request $request)
    {
        global $wpdb;

        $current_user_id = get_current_user_id();
        $chat_id = $request->get_param('chat_id');
        $group_name = $request->get_param('group_name');
        $group_description = $request->get_param('group_description');
        $group_icon_url = $request->get_param('group_icon_url');
        $is_broadcast = $request->get_param('is_broadcast'); // Optional parameter, expects 0 or 1

        // Validate inputs
        if (empty($chat_id)) {
            return new WP_Error('invalid_params', 'Chat ID is required.', array('status' => 400));
        }
        if (empty($group_name)) {
            return new WP_Error('invalid_params', 'Group name is required.', array('status' => 400));
        }
        if (isset($is_broadcast) && !in_array($is_broadcast, [0, 1], true)) {
            return new WP_Error('invalid_params', 'Invalid value for is_broadcast. It must be 0 or 1.', array('status' => 400));
        }

        $is_admin = is_user_admin_of_chat($chat_id, $current_user_id, $wpdb, $this->chat_participants_table);


        if (!$is_admin) {
            return new WP_Error('forbidden', 'You do not have permission to edit this group.', array('status' => 403));
        }

        // Prepare data for update
        $update_data = array(
            'group_name' => $group_name,
            'group_description' => $group_description,
            'group_icon_url' => $group_icon_url,
            'updated_at' => current_time('mysql'),
        );

        if (isset($is_broadcast)) {
            $update_data['is_broadcast'] = $is_broadcast;
        }

        // Update group information
        $updated = $wpdb->update("{$wpdb->prefix}chats", $update_data, array('chat_id' => $chat_id));

        if ($updated === false) {
            return new WP_Error('db_error', 'Failed to update group.', array('status' => 500));
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Group updated successfully.',
            'chat_id' => $chat_id,
            'is_broadcast' => isset($is_broadcast) ? $is_broadcast : null,
        ));
    }

    // function edit_group_members(WP_REST_Request $request)
    // {
    //     global $wpdb;

    //     $current_user_id = get_current_user_id();
    //     $chat_id = $request->get_param('chat_id');
    //     $members = $request->get_param('members'); // Array of user IDs and their roles [{user_id, role}]

    //     // Validate inputs
    //     if (empty($chat_id) || !is_array($members)) {
    //         return new WP_Error('invalid_params', 'Chat ID and members are required.', array('status' => 400));
    //     }

    //     // Check if the user is an admin of the group
    //     $is_admin = $wpdb->get_var($wpdb->prepare(
    //         "SELECT COUNT(*) FROM {$this->chat_participants_table} 
    //         WHERE chat_id = %d AND user_id = %d AND role = 'admin'",
    //         $chat_id,
    //         $current_user_id
    //     ));

    //     if (!$is_admin) {
    //         return new WP_Error('forbidden', 'You do not have permission to manage group members.', array('status' => 403));
    //     }

    //     // Get current members of the group
    //     $current_members = $wpdb->get_results($wpdb->prepare(
    //         "SELECT user_id FROM {$this->chat_participants_table} WHERE chat_id = %d",
    //         $chat_id
    //     ), ARRAY_A);

    //     $current_member_ids = array_column($current_members, 'user_id');
    //     $new_member_ids = array_column($members, 'user_id');

    //     // Determine members to add, update, and remove
    //     $members_to_add = array_filter($members, function ($member) use ($current_member_ids) {
    //         return !in_array($member['user_id'], $current_member_ids);
    //     });

    //     $members_to_update = array_filter($members, function ($member) use ($current_member_ids) {
    //         return in_array($member['user_id'], $current_member_ids);
    //     });

    //     $members_to_remove = array_diff($current_member_ids, $new_member_ids);

    //     // Add new members
    //     foreach ($members_to_add as $member) {
    //         $wpdb->insert($this->chat_participants_table, array(
    //             'chat_id' => $chat_id,
    //             'user_id' => $member['user_id'],
    //             'role' => $member['role'] ?? 'member',
    //             'joined_at' => current_time('mysql'),
    //         ));
    //     }

    //     // Update existing members
    //     foreach ($members_to_update as $member) {
    //         $wpdb->update(
    //             $this->chat_participants_table,
    //             array('role' => $member['role'] ?? 'member'),
    //             array('chat_id' => $chat_id, 'user_id' => $member['user_id'])
    //         );
    //     }

    //     // Remove members no longer in the list
    //     foreach ($members_to_remove as $user_id) {
    //         $wpdb->delete($this->chat_participants_table, array(
    //             'chat_id' => $chat_id,
    //             'user_id' => $user_id,
    //         ));
    //     }

    //     return rest_ensure_response(array(
    //         'success' => true,
    //         'message' => 'Group members updated successfully.',
    //         'chat_id' => $chat_id,
    //     ));
    // }

    // function edit_group_members(WP_REST_Request $request)
    // {
    //     global $wpdb;

    //     $current_user_id = get_current_user_id();

    //     // Get request parameters
    //     $chat_id = $request->get_param('chat_id');
    //     $members = $request->get_param('members'); // Array of user IDs to be updated
    //     $members[] = $current_user_id;
    //     // Validate inputs
    //     if (empty($chat_id) || !is_numeric($chat_id)) {
    //         return new WP_Error('invalid_params', 'Chat ID is required and must be numeric.', array('status' => 400));
    //     }
    //     if (empty($members) || !is_array($members)) {
    //         return new WP_Error('invalid_params', 'Members list is required and must be an array.', array('status' => 400));
    //     }

    //     // Step 1: Check if the chat exists
    //     $chat = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}chats WHERE chat_id = %d", $chat_id));
    //     if (!$chat) {
    //         return new WP_Error('not_found', 'Chat not found.', array('status' => 404));
    //     }

    //     // Step 2: Check if the current user is the creator or an admin of the group
    //     $participant = $wpdb->get_row($wpdb->prepare(
    //         "SELECT * FROM {$this->chat_participants_table} WHERE chat_id = %d AND user_id = %d",
    //         $chat_id,
    //         $current_user_id
    //     ));
    //     if (!$participant || ($participant->role !== 'admin' && $participant->role !== 'creator')) {
    //         return new WP_Error('forbidden', 'You do not have permission to edit group members.', array('status' => 403));
    //     }

    //     // Step 3: Ensure the admin is not removed
    //     $admin_ids = $wpdb->get_results($wpdb->prepare(
    //         "SELECT user_id FROM {$this->chat_participants_table} WHERE chat_id = %d AND role IN ('admin', 'creator')",
    //         $chat_id
    //     ));
    //     $admin_ids = wp_list_pluck($admin_ids, 'user_id');

    //     // Check if the admin(s) are included in the members array (admins cannot be removed)
    //     $invalid_removal = array_diff($admin_ids, $members);
    //     if (!empty($invalid_removal)) {
    //         return new WP_Error('invalid_operation', 'You cannot remove the admin(s) from the group.', array('status' => 400));
    //     }

    //     // Step 4: Remove current members who are not in the new members list (except admins)
    //     $existing_members = $wpdb->get_results($wpdb->prepare(
    //         "SELECT user_id FROM {$this->chat_participants_table} WHERE chat_id = %d",
    //         $chat_id
    //     ));
    //     $existing_member_ids = wp_list_pluck($existing_members, 'user_id');

    //     // Users to remove: existing users who are not in the new members list
    //     $members_to_remove = array_diff($existing_member_ids, $members);
    //     $members_to_add = array_diff($members, $existing_member_ids);

    //     // Step 5: Remove users who are no longer part of the group (except admins)
    //     foreach ($members_to_remove as $user_id) {
    //         if (!in_array($user_id, $admin_ids)) {  // Skip if the user is an admin
    //             $wpdb->delete($this->chat_participants_table, [
    //                 'chat_id' => $chat_id,
    //                 'user_id' => $user_id
    //             ]);
    //         }
    //     }

    //     // Step 6: Add new members to the group
    //     foreach ($members_to_add as $user_id) {
    //         // Check if the user is not already a participant
    //         $wpdb->insert($this->chat_participants_table, [
    //             'chat_id' => $chat_id,
    //             'user_id' => $user_id,
    //             'role' => 'member', // Default role for participants
    //             'joined_at' => current_time('mysql'),
    //         ]);
    //     }

    //     // Step 7: Return success response
    //     return rest_ensure_response([
    //         'success' => true,
    //         'message' => 'Group members updated successfully.',
    //         'chat_id' => $chat_id,
    //     ]);
    // }

    function edit_group_members(WP_REST_Request $request)
    {
        global $wpdb;

        $current_user_id = get_current_user_id();

        // Get request parameters
        $chat_id = $request->get_param('chat_id');
        $members = $request->get_param('members'); // Array of user IDs to be updated
        $members[] = $current_user_id;

        // Validate inputs
        if (empty($chat_id) || !is_numeric($chat_id)) {
            return new WP_Error('invalid_params', 'Chat ID is required and must be numeric.', array('status' => 400));
        }
        if (empty($members) || !is_array($members)) {
            return new WP_Error('invalid_params', 'Members list is required and must be an array.', array('status' => 400));
        }

        // Step 1: Check if the chat exists
        $chat = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}chats WHERE chat_id = %d", $chat_id));
        if (!$chat) {
            return new WP_Error('not_found', 'Chat not found.', array('status' => 404));
        }

        // Step 2: Check if the current user is the creator or an admin of the group
        $participant = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->chat_participants_table} WHERE chat_id = %d AND user_id = %d",
            $chat_id,
            $current_user_id
        ));
        if (!$participant || ($participant->role !== 'admin' && $participant->role !== 'creator')) {
            return new WP_Error('forbidden', 'You do not have permission to edit group members.', array('status' => 403));
        }

        // Step 3: Ensure the admin is not removed
        $admin_ids = $wpdb->get_results($wpdb->prepare(
            "SELECT user_id FROM {$this->chat_participants_table} WHERE chat_id = %d AND role IN ('admin', 'creator')",
            $chat_id
        ));
        $admin_ids = wp_list_pluck($admin_ids, 'user_id');

        // Check if the admin(s) are included in the members array (admins cannot be removed)
        $invalid_removal = array_diff($admin_ids, $members);
        if (!empty($invalid_removal)) {
            return new WP_Error('invalid_operation', 'You cannot remove the admin(s) from the group.', array('status' => 400));
        }

        // Step 4: Remove current members who are not in the new members list (except admins)
        $existing_members = $wpdb->get_results($wpdb->prepare(
            "SELECT user_id FROM {$this->chat_participants_table} WHERE chat_id = %d",
            $chat_id
        ));
        $existing_member_ids = wp_list_pluck($existing_members, 'user_id');

        // Users to remove: existing users who are not in the new members list
        $members_to_remove = array_diff($existing_member_ids, $members);
        $members_to_add = array_diff($members, $existing_member_ids);

        // Step 5: Remove users who are no longer part of the group (except admins)
        foreach ($members_to_remove as $user_id) {
            if (!in_array($user_id, $admin_ids)) {  // Skip if the user is an admin
                $wpdb->delete($this->chat_participants_table, [
                    'chat_id' => $chat_id,
                    'user_id' => $user_id
                ]);

                // Add notification for removal
                $wpdb->insert($wpdb->prefix . 'group_notifications', [
                    'chat_id' => $chat_id,
                    'user_id' => $user_id,
                    'action' => 'removed',
                    'created_at' => current_time('mysql')
                ]);
            }
        }

        // Step 6: Add new members to the group
        foreach ($members_to_add as $user_id) {
            // Check if the user is not already a participant
            $wpdb->insert($this->chat_participants_table, [
                'chat_id' => $chat_id,
                'user_id' => $user_id,
                'role' => 'member', // Default role for participants
                'joined_at' => current_time('mysql'),
            ]);

            // Add notification for adding
            $wpdb->insert($wpdb->prefix . 'group_notifications', [
                'chat_id' => $chat_id,
                'user_id' => $user_id,
                'action' => 'added',
                'created_at' => current_time('mysql')
            ]);
        }

        // Step 7: Return success response
        return rest_ensure_response([
            'success' => true,
            'message' => 'Group members updated successfully.',
            'chat_id' => $chat_id,
        ]);
    }

    function join_group_chat(WP_REST_Request $request)
    {
        global $wpdb;

        $current_user_id = get_current_user_id();
        $chat_id = $request->get_param('chat_id'); // Group ID

        // Validate inputs
        if (empty($chat_id)) {
            return new WP_Error('invalid_params', 'Chat ID is required.', array('status' => 400));
        }

        // Check if the group exists
        $group = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}chats WHERE chat_id = %d AND chat_type = 'group'",
            $chat_id
        ));
        if (!$group) {
            return new WP_Error('not_found', 'Group not found.', array('status' => 404));
        }

        // Check if the user is already a member
        $existing_member = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}chat_participants WHERE chat_id = %d AND user_id = %d",
            $chat_id,
            $current_user_id
        ));
        if ($existing_member) {
            return new WP_Error('already_member', 'User is already a member of this group.', array('status' => 400));
        }

        // Add the user to the group as a participant
        $wpdb->insert("{$wpdb->prefix}chat_participants", array(
            'chat_id' => $chat_id,
            'user_id' => $current_user_id,
            'role' => 'member',
            'joined_at' => current_time('mysql'),
        ));

        // Notify all participants in the group
        $participants = $wpdb->get_col($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}chat_participants WHERE chat_id = %d",
            $chat_id
        ));

        foreach ($participants as $participant_id) {
            $wpdb->insert("{$wpdb->prefix}group_notifications", array(
                'chat_id' => $chat_id,
                'user_id' => $participant_id,
                'action' => 'joined',
                'created_at' => current_time('mysql'),
            ));
        }

        // Success response
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'You have successfully joined the group.',
            'chat_id' => $chat_id,
        ));
    }

}