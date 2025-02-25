<?php

class Event_Organizer_Controller
{

    const NAMESPACE = 'buddypress/v1';
    const ROUTE_BASE = '/event-organizers';
    private $table_name;
    public function __construct()
    {
        global $wpdb;

        $this->table_name = "{$wpdb->prefix}event_organizers";

        // Register the routes when the API is initialized
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes()
    {
        // Define the CRUD routes
        // register_rest_route(self::NAMESPACE , self::ROUTE_BASE . '/add', [
        //     'methods' => WP_REST_Server::CREATABLE,
        //     'callback' => [$this, 'add_event_organizers'],
        //     'permission_callback' => [$this, 'check_permissions'],
        // ]);
        register_rest_route(self::NAMESPACE , self::ROUTE_BASE . '/add', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'add_event_roles'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
        register_rest_route(self::NAMESPACE , self::ROUTE_BASE . '/all-organizers', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_all_organizers'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
        register_rest_route(self::NAMESPACE , self::ROUTE_BASE . '/(?P<event_id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_event_organizers'],
            'permission_callback' => [$this, 'check_permissions']
        ]);
        // register_rest_route(self::NAMESPACE , self::ROUTE_BASE . '/update', [
        //     'methods' => WP_REST_Server::EDITABLE,
        //     'callback' => [$this, 'update_event_organizers'],
        //     'permission_callback' => [$this, 'check_permissions'],
        // ]);
        register_rest_route(self::NAMESPACE , self::ROUTE_BASE . '/update', [
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => [$this, 'edit_event_roles'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
    }

    // Check permissions for API access
    public function check_permissions($request)
    {
        return is_user_logged_in();  // Adjust based on your requirements
    }
    // function add_event_organizers(WP_REST_Request $request)
    // {
    //     global $wpdb;

    //     // Get the current user ID
    //     $user_id = get_current_user_id();
    //     if (!$user_id) {
    //         return new WP_REST_Response(array(
    //             'success' => false,
    //             'message' => 'User not logged in',
    //         ), 401);
    //     }

    //     // Get the event ID and selected organizer IDs from the request
    //     $event_id = intval($request->get_param('event_id'));
    //     $organizer_ids = $request->get_param('organizers'); // Array of organizer IDs

    //     // Validate the event ID
    //     if (get_post_status($event_id) === false || get_post_type($event_id) !== 'event') {
    //         return new WP_REST_Response(array(
    //             'success' => false,
    //             'message' => 'Invalid event ID',
    //         ), 404);
    //     }

    //     // Check if the current user is allowed to add organizers (i.e., they are the event creator)
    //     $event_author = get_post_field('post_author', $event_id);
    //     if ($event_author != $user_id) {
    //         return new WP_REST_Response(array(
    //             'success' => false,
    //             'message' => 'You are not the event creator',
    //         ), 403);
    //     }

    //     // Ensure the organizer IDs data is valid
    //     if (empty($organizer_ids) || !is_array($organizer_ids)) {
    //         return new WP_REST_Response(array(
    //             'success' => false,
    //             'message' => 'Invalid organizers data',
    //         ), 400);
    //     }

    //     // Enforce the two organizers limit for the event
    //     $existing_organizers_count = $wpdb->get_var(
    //         $wpdb->prepare("SELECT COUNT(*) FROM $this->table_name WHERE event_id = %d", $event_id)
    //     );

    //     if ($existing_organizers_count >= 2) {
    //         return new WP_REST_Response(array(
    //             'success' => false,
    //             'message' => 'This event already has two organizers',
    //         ), 400);
    //     }

    //     // Prepare organizers array
    //     $organizers = [];

    //     // Add the event creator as an organizer if not already in the list
    //     $organizers[] = $user_id;

    //     // Add other organizers, ensuring no duplicates
    //     foreach ($organizer_ids as $organizer_id) {
    //         $organizer_id = intval($organizer_id);
    //         if ($organizer_id == $user_id || in_array($organizer_id, $organizers)) {
    //             continue; // Skip if the organizer is already added or is the event creator
    //         }
    //         $organizers[] = $organizer_id;
    //     }

    //     // Prepare the values for batch insert
    //     $current_time = current_time('mysql');
    //     $insert_values = [];
    //     foreach ($organizers as $organizer) {
    //         $insert_values[] = [
    //             $event_id,
    //             $organizer,
    //             'organizer',
    //             $current_time,
    //             $current_time,
    //             0, // 'is_deleted' is 0 by default
    //         ];
    //     }

    //     // Prepare the query for inserting multiple organizers at once
    //     $placeholders = array_fill(0, count($insert_values), "(%d, %d, %s, %s, %s, %d)");
    //     $query = "INSERT INTO $this->table_name (event_id, user_id, role, created_at, updated_at, is_deleted) VALUES " . implode(', ', $placeholders);

    //     // Flatten the insert values array
    //     $flattened_values = [];
    //     foreach ($insert_values as $values) {
    //         $flattened_values = array_merge($flattened_values, $values);
    //     }

    //     // Execute the query
    //     $result = $wpdb->query($wpdb->prepare($query, ...$flattened_values));

    //     // Check if the query failed
    //     if (false === $result) {
    //         return new WP_REST_Response(array(
    //             'success' => false,
    //             'message' => 'Failed to insert organizer data',
    //         ), 500);
    //     }

    //     // Return success response
    //     return rest_ensure_response(array(
    //         'success' => true,
    //         'message' => 'Organizers added successfully',
    //         'event_id' => $event_id,
    //         'organizers' => $organizers,
    //     ));
    // }
//old partner and organizer 
    // function add_event_roles(WP_REST_Request $request)
    // {
    //     global $wpdb;

    //     // Get the current user ID
    //     $user_id = get_current_user_id();
    //     if (!$user_id) {
    //         return new WP_REST_Response([
    //             'success' => false,
    //             'message' => 'User not logged in',
    //         ], 401);
    //     }

    //     // Extract parameters from the request
    //     $event_id = intval($request->get_param('event_id'));
    //     $organizer_id = intval($request->get_param('organizer')); // Single organizer (optional)
    //     $partner_ids = $request->get_param('partners'); // Array of partner IDs (optional)

    //     // Validate the event
    //     if (get_post_status($event_id) === false || get_post_type($event_id) !== 'event') {
    //         return new WP_REST_Response([
    //             'success' => false,
    //             'message' => 'Invalid event ID',
    //         ], 404);
    //     }

    //     // Ensure the current user is the event creator
    //     $event_author = get_post_field('post_author', $event_id);
    //     if ($event_author != $user_id) {
    //         return new WP_REST_Response([
    //             'success' => false,
    //             'message' => 'You are not authorized to add roles for this event',
    //         ], 403);
    //     }

    //     // Handle missing organizer and partners
    //     if (!$organizer_id && (empty($partner_ids) || !is_array($partner_ids))) {
    //         $organizer_id = $event_author; // Default to the event creator as the organizer
    //     }

    //     // Validate partners input
    //     if (!empty($partner_ids) && (!is_array($partner_ids) || count($partner_ids) > 2)) {
    //         return new WP_REST_Response([
    //             'success' => false,
    //             'message' => 'Invalid partners data. You can only add up to 2 partners.',
    //         ], 400);
    //     }

    //     // Ensure each user has only one role (either organizer or partner)
    //     $all_user_ids = array_merge($organizer_id ? [$organizer_id] : [], $partner_ids ?: []);
    //     $duplicates = $wpdb->get_results(
    //         $wpdb->prepare(
    //             "SELECT user_id FROM $this->table_name WHERE event_id = %d AND user_id IN (" . implode(',', array_fill(0, count($all_user_ids), '%d')) . ")",
    //             array_merge([$event_id], $all_user_ids)
    //         ),
    //         ARRAY_A
    //     );

    //     if (!empty($duplicates)) {
    //         $duplicate_ids = array_column($duplicates, 'user_id');
    //         return new WP_REST_Response([
    //             'success' => false,
    //             'message' => 'Some users already have a role in this event: ' . implode(', ', $duplicate_ids),
    //         ], 400);
    //     }

    //     // Check existing partners count
    //     $existing_partners_count = $wpdb->get_var(
    //         $wpdb->prepare(
    //             "SELECT COUNT(*) FROM $this->table_name WHERE event_id = %d AND role = %s",
    //             $event_id,
    //             'partner'
    //         )
    //     );

    //     if (($existing_partners_count + count($partner_ids ?: [])) > 2) {
    //         return new WP_REST_Response([
    //             'success' => false,
    //             'message' => 'This event already has two partners',
    //         ], 400);
    //     }

    //     // Prepare data for insertion
    //     $current_time = current_time('mysql');
    //     $insert_values = [];

    //     // Add organizer (if provided or default to event creator)
    //     if ($organizer_id) {
    //         $insert_values[] = [$event_id, $organizer_id, 'organizer', $current_time, $current_time, 0];
    //     }

    //     // Add partners (if any)
    //     foreach ($partner_ids ?: [] as $partner_id) {
    //         $partner_id = intval($partner_id);
    //         if (!$partner_id) {
    //             continue; // Skip invalid IDs
    //         }
    //         $insert_values[] = [$event_id, $partner_id, 'partner', $current_time, $current_time, 0];
    //     }

    //     // Insert data in batch
    //     $placeholders = array_fill(0, count($insert_values), "(%d, %d, %s, %s, %s, %d)");
    //     $query = "INSERT INTO $this->table_name (event_id, user_id, role, created_at, updated_at, is_deleted) VALUES " . implode(', ', $placeholders);

    //     $flattened_values = [];
    //     foreach ($insert_values as $values) {
    //         $flattened_values = array_merge($flattened_values, $values);
    //     }

    //     $result = $wpdb->query($wpdb->prepare($query, ...$flattened_values));

    //     if (false === $result) {
    //         return new WP_REST_Response([
    //             'success' => false,
    //             'message' => 'Failed to add roles',
    //         ], 500);
    //     }

    //     // Success response
    //     return rest_ensure_response([
    //         'success' => true,
    //         'message' => 'Roles added successfully',
    //         'event_id' => $event_id,
    //     ]);
    // }
    function add_event_roles(WP_REST_Request $request)
    {
        global $wpdb;

        // Get the current user ID
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'User not logged in',
            ], 401);
        }

        // Extract parameters from the request
        $event_id = intval($request->get_param('event_id'));
        $co_organizer_id = intval($request->get_param('co_organizer')); // Single co-organizer (optional)
        $partner_ids = $request->get_param('partners'); // Array of partner IDs (optional)

        // Validate the event
        if (get_post_status($event_id) === false || get_post_type($event_id) !== 'event') {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Invalid event ID',
            ], 404);
        }

        // Ensure the current user is the event creator
        $event_author = get_post_field('post_author', $event_id);
        if ($event_author != $user_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'You are not authorized to add roles for this event',
            ], 403);
        }

        // Validate partner input
        if (!empty($partner_ids) && (!is_array($partner_ids) || count($partner_ids) > 2)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'You can only add up to 2 partners.',
            ], 400);
        }

        // Validate existing roles
        $existing_roles = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT user_id, role FROM $this->table_name WHERE event_id = %d AND is_deleted = 0",
                $event_id
            ),
            ARRAY_A
        );

        // Check for duplicate roles
        $existing_role_users = [];
        foreach ($existing_roles as $role_entry) {
            $existing_role_users[$role_entry['role']][] = $role_entry['user_id'];
        }

        // Check if a user already has any role in the event
        if (!empty($co_organizer_id) && in_array($co_organizer_id, array_merge($existing_role_users['co-organizer'] ?? [], $existing_role_users['partner'] ?? [], $existing_role_users['organizer'] ?? []))) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'The selected user is already assigned a role in this event.',
            ], 400);
        }

        // Check if the user is already assigned a role (either co-organizer or partner)
        foreach ($partner_ids ?: [] as $partner_id) {
            if (in_array($partner_id, array_merge($existing_role_users['co-organizer'] ?? [], $existing_role_users['partner'] ?? [], $existing_role_users['organizer'] ?? []))) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => "User ID $partner_id is already assigned a role in this event.",
                ], 400);
            }
        }

        // Check for duplicate roles
        if (!empty($co_organizer_id)) {
            // Check if a co-organizer already exists
            if (!empty($existing_role_users['co-organizer'])) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'A co-organizer already exists for this event.',
                ], 400);
            }
            // Check if the co-organizer is already assigned
            if (in_array($co_organizer_id, $existing_role_users['co-organizer'] ?? [])) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'The selected user is already a co-organizer for this event.',
                ], 400);
            }
        }

        // Check if adding the partners will exceed the limit of 2
        if (!empty($partner_ids)) {
            $existing_partner_count = count($existing_role_users['partner'] ?? []);
            if (($existing_partner_count + count($partner_ids)) > 2) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Adding these partners will exceed the limit of 2 partners for this event.',
                ], 400);
            }
            // Check for duplicate partner assignments
            foreach ($partner_ids as $partner_id) {
                if (in_array($partner_id, $existing_role_users['partner'] ?? [])) {
                    return new WP_REST_Response([
                        'success' => false,
                        'message' => "User ID $partner_id is already a partner for this event.",
                    ], 400);
                }
            }
        }

        // Prepare data for insertion
        $current_time = current_time('mysql');
        $insert_values = [];

        // Add co-organizer (if provided)
        if (!empty($co_organizer_id)) {
            $insert_values[] = [$event_id, $co_organizer_id, 'co-organizer', $current_time, $current_time, 0];
        }

        // Add partners (if any)
        foreach ($partner_ids ?: [] as $partner_id) {
            $partner_id = intval($partner_id);
            if (!$partner_id) {
                continue; // Skip invalid IDs
            }
            $insert_values[] = [$event_id, $partner_id, 'partner', $current_time, $current_time, 0];
        }

        // Insert data in batch
        if (!empty($insert_values)) {
            $placeholders = array_fill(0, count($insert_values), "(%d, %d, %s, %s, %s, %d)");
            $query = "INSERT INTO $this->table_name (event_id, user_id, role, created_at, updated_at, is_deleted) VALUES " . implode(', ', $placeholders);

            $flattened_values = [];
            foreach ($insert_values as $values) {
                $flattened_values = array_merge($flattened_values, $values);
            }

            $result = $wpdb->query($wpdb->prepare($query, ...$flattened_values));

            if (false === $result) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Failed to add roles',
                ], 500);
            }
        }

        // Success response
        return rest_ensure_response([
            'success' => true,
            'message' => 'Roles added successfully',
            'event_id' => $event_id,
        ]);
    }


    // function update_event_organizers(WP_REST_Request $request)
    // {
    //     global $wpdb;

    //     // Get the current user ID
    //     $user_id = get_current_user_id();
    //     if (!$user_id) {
    //         return new WP_REST_Response(array(
    //             'success' => false,
    //             'message' => 'User not logged in',
    //         ), 401);
    //     }

    //     // Get the event ID and updated organizer IDs from the request
    //     $event_id = intval($request->get_param('event_id'));
    //     $organizer_ids = $request->get_param('organizers'); // Array of organizer IDs

    //     // Validate the event ID
    //     if (get_post_status($event_id) === false || get_post_type($event_id) !== 'event') {
    //         return new WP_REST_Response(array(
    //             'success' => false,
    //             'message' => 'Invalid event ID',
    //         ), 404);
    //     }

    //     // Check if the current user is allowed to edit organizers
    //     $event_author = get_post_field('post_author', $event_id);
    //     if ($event_author != $user_id) {
    //         return new WP_REST_Response(array(
    //             'success' => false,
    //             'message' => 'You are not the event creator',
    //         ), 403);
    //     }

    //     // Ensure the organizer IDs data is valid
    //     if (!is_array($organizer_ids) || count($organizer_ids) > 2) {
    //         return new WP_REST_Response(array(
    //             'success' => false,
    //             'message' => 'Invalid organizers data. Maximum of 2 organizers allowed.',
    //         ), 400);
    //     }

    //     // Fetch existing organizers for the event
    //     $existing_organizers = $wpdb->get_col(
    //         $wpdb->prepare("SELECT user_id FROM $this->table_name WHERE event_id = %d AND is_deleted = 0", $event_id)
    //     );

    //     // Prepare organizers array
    //     $organizers = [];

    //     // Add new organizers, ensuring no duplicates
    //     foreach ($organizer_ids as $organizer_id) {
    //         $organizer_id = intval($organizer_id);
    //         if (!in_array($organizer_id, $organizers)) {
    //             $organizers[] = $organizer_id;
    //         }
    //     }

    //     // Check if the current user is included as an organizer
    //     if (!in_array($user_id, $organizers)) {
    //         $organizers[] = $user_id; // Ensure event creator is always an organizer
    //     }

    //     // Enforce the two organizers limit
    //     if (count($organizers) > 2) {
    //         return new WP_REST_Response(array(
    //             'success' => false,
    //             'message' => 'Only two organizers are allowed per event',
    //         ), 400);
    //     }

    //     // Begin database transaction
    //     $wpdb->query('START TRANSACTION');

    //     try {
    //         // Mark existing organizers as deleted if they are not in the updated list
    //         $organizers_to_remove = array_diff($existing_organizers, $organizers);
    //         if (!empty($organizers_to_remove)) {
    //             $wpdb->query(
    //                 $wpdb->prepare(
    //                     "UPDATE $this->table_name SET is_deleted = 1 WHERE event_id = %d AND user_id IN (" . implode(',', array_fill(0, count($organizers_to_remove), '%d')) . ")",
    //                     array_merge([$event_id], $organizers_to_remove)
    //                 )
    //             );
    //         }

    //         // Add new organizers that are not in the existing list
    //         $organizers_to_add = array_diff($organizers, $existing_organizers);
    //         if (!empty($organizers_to_add)) {
    //             $current_time = current_time('mysql');
    //             $insert_values = [];
    //             foreach ($organizers_to_add as $organizer) {
    //                 $insert_values[] = [
    //                     $event_id,
    //                     $organizer,
    //                     'organizer',
    //                     $current_time,
    //                     $current_time,
    //                     0, // 'is_deleted' is 0 by default
    //                 ];
    //             }

    //             $placeholders = array_fill(0, count($insert_values), "(%d, %d, %s, %s, %s, %d)");
    //             $query = "INSERT INTO $this->table_name (event_id, user_id, role, created_at, updated_at, is_deleted) VALUES " . implode(', ', $placeholders);

    //             $flattened_values = [];
    //             foreach ($insert_values as $values) {
    //                 $flattened_values = array_merge($flattened_values, $values);
    //             }

    //             $wpdb->query($wpdb->prepare($query, ...$flattened_values));
    //         }

    //         // Commit the transaction
    //         $wpdb->query('COMMIT');

    //         // Return success response
    //         return rest_ensure_response(array(
    //             'success' => true,
    //             'message' => 'Organizers updated successfully',
    //             'event_id' => $event_id,
    //             'organizers' => $organizers,
    //         ));
    //     } catch (Exception $e) {
    //         // Rollback transaction in case of error
    //         $wpdb->query('ROLLBACK');
    //         return new WP_REST_Response(array(
    //             'success' => false,
    //             'message' => 'Failed to update organizers',
    //         ), 500);
    //     }
    // }



    // function edit_event_roles(WP_REST_Request $request)
    // {
    //     global $wpdb;
    
    //     // Get the current user ID
    //     $user_id = get_current_user_id();
    //     if (!$user_id) {
    //         return new WP_REST_Response([
    //             'success' => false,
    //             'message' => 'User not logged in',
    //         ], 401);
    //     }
    
    //     // Extract parameters from the request
    //     $event_id = intval($request->get_param('event_id'));
    //     $co_organizer_id = intval($request->get_param('co_organizer')); // Single co-organizer (optional)
    //     $partner_ids = $request->get_param('partners'); // Array of partner IDs (optional)
    
    //     // Validate the event
    //     if (get_post_status($event_id) === false || get_post_type($event_id) !== 'event') {
    //         return new WP_REST_Response([
    //             'success' => false,
    //             'message' => 'Invalid event ID',
    //         ], 404);
    //     }
    
    //     // Ensure the current user is the event creator
    //     $event_author = get_post_field('post_author', $event_id);
    //     if ($event_author != $user_id) {
    //         return new WP_REST_Response([
    //             'success' => false,
    //             'message' => 'You are not authorized to edit roles for this event',
    //         ], 403);
    //     }
    
    //     // Validate partner input
    //     if (!empty($partner_ids) && (!is_array($partner_ids) || count($partner_ids) > 2)) {
    //         return new WP_REST_Response([
    //             'success' => false,
    //             'message' => 'You can only add up to 2 partners.',
    //         ], 400);
    //     }
    
    //     // Get existing roles for the event
    //     $existing_roles = $wpdb->get_results(
    //         $wpdb->prepare(
    //             "SELECT user_id, role FROM $this->table_name WHERE event_id = %d AND is_deleted = 0",
    //             $event_id
    //         ),
    //         ARRAY_A
    //     );
    
    //     $existing_role_users = [];
    //     foreach ($existing_roles as $role_entry) {
    //         $existing_role_users[$role_entry['role']][] = $role_entry['user_id'];
    //     }
    
    //     // Handle co-organizer update (if provided)
    //     if (!empty($co_organizer_id)) {
    //         // If a co-organizer already exists, delete the current one
    //         if (!empty($existing_role_users['co-organizer'])) {
    //             // Mark the current co-organizer as deleted
    //             $wpdb->update(
    //                 $this->table_name,
    //                 ['is_deleted' => 1],
    //                 ['event_id' => $event_id, 'role' => 'co-organizer'],
    //                 ['%d'],
    //                 ['%d', '%s']
    //             );
    //         }
    
    //         // Add the new co-organizer
    //         $wpdb->insert(
    //             $this->table_name,
    //             [
    //                 'event_id' => $event_id,
    //                 'user_id' => $co_organizer_id,
    //                 'role' => 'co-organizer',
    //                 'created_at' => current_time('mysql'),
    //                 'updated_at' => current_time('mysql'),
    //                 'is_deleted' => 0
    //             ],
    //             ['%d', '%d', '%s', '%s', '%s', '%d']
    //         );
    //     }
    
    //     // Handle partners update (if any)
    //     if (!empty($partner_ids)) {
    //         // 1. Mark old partners as deleted
    //         if (!empty($existing_role_users['partner'])) {
    //             // Mark the existing partners as deleted
    //             $wpdb->update(
    //                 $this->table_name,
    //                 ['is_deleted' => 1],
    //                 ['event_id' => $event_id, 'role' => 'partner'],
    //                 ['%d'],
    //                 ['%d', '%s']
    //             );
    //         }
    
    //         // 2. Add new partners
    //         foreach ($partner_ids ?: [] as $partner_id) {
    //             $partner_id = intval($partner_id);
    //             if (!$partner_id) {
    //                 continue; // Skip invalid IDs
    //             }
    
    //             // Check if the user is already a partner, if so, skip adding
    //             if (in_array($partner_id, $existing_role_users['partner'] ?? [])) {
    //                 continue;
    //             }
    
    //             // Add the new partner
    //             $wpdb->insert(
    //                 $this->table_name,
    //                 [
    //                     'event_id' => $event_id,
    //                     'user_id' => $partner_id,
    //                     'role' => 'partner',
    //                     'created_at' => current_time('mysql'),
    //                     'updated_at' => current_time('mysql'),
    //                     'is_deleted' => 0
    //                 ],
    //                 ['%d', '%d', '%s', '%s', '%s', '%d']
    //             );
    //         }
    //     }
    
    //     // Success response
    //     return rest_ensure_response([
    //         'success' => true,
    //         'message' => 'Roles updated successfully',
    //         'event_id' => $event_id,
    //     ]);
    // }
    
    
    
    function edit_event_roles(WP_REST_Request $request)
    {
        global $wpdb;
    
        // Get the current user ID
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'User not logged in',
            ], 401);
        }
    
        // Extract parameters from the request
        $event_id = intval($request->get_param('event_id'));
        $co_organizer_id = $request->get_param('co_organizer'); // Can be null or empty
        $partner_ids = $request->get_param('partners'); // Array of partner IDs (optional)
    
        // Validate the event
        if (get_post_status($event_id) === false || get_post_type($event_id) !== 'event') {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Invalid event ID',
            ], 404);
        }
    
        // Ensure the current user is the event creator
        $event_author = get_post_field('post_author', $event_id);
        if ($event_author != $user_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'You are not authorized to edit roles for this event',
            ], 403);
        }
    
        // Validate partner input
        if (!empty($partner_ids) && (!is_array($partner_ids) || count($partner_ids) > 2)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'You can only add up to 2 partners.',
            ], 400);
        }
    
        // Handle co-organizer update
        if (!empty($co_organizer_id)) {
            // If a co-organizer already exists, delete the current one
            $wpdb->update(
                $this->table_name,
                ['is_deleted' => 1],
                ['event_id' => $event_id, 'role' => 'co-organizer', 'is_deleted' => 0],
                ['%d', '%s', '%d']
            );
    
            // Add the new co-organizer
            $wpdb->insert(
                $this->table_name,
                [
                    'event_id' => $event_id,
                    'user_id' => intval($co_organizer_id),
                    'role' => 'co-organizer',
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                    'is_deleted' => 0
                ],
                ['%d', '%d', '%s', '%s', '%s', '%d']
            );
        } elseif ($co_organizer_id === null || $co_organizer_id === '') {
            // If co_organizer is blank, soft delete only co-organizers
            $wpdb->update(
                $this->table_name,
                ['is_deleted' => 1],
                ['event_id' => $event_id, 'role' => 'co-organizer', 'is_deleted' => 0],
                ['%d', '%s', '%d']
            );
        }
    
        // Handle partners update (if any)
        if (is_array($partner_ids) && !empty($partner_ids)) {
            // Mark old partners as deleted
            $wpdb->update(
                $this->table_name,
                ['is_deleted' => 1],
                ['event_id' => $event_id, 'role' => 'partner', 'is_deleted' => 0],
                ['%d', '%s', '%d']
            );
    
            // Add new partners
            foreach ($partner_ids ?: [] as $partner_id) {
                $partner_id = intval($partner_id);
                if (!$partner_id) {
                    continue; // Skip invalid IDs
                }
    
                // Check if the user is already a partner, if so, skip adding
                $wpdb->insert(
                    $this->table_name,
                    [
                        'event_id' => $event_id,
                        'user_id' => $partner_id,
                        'role' => 'partner',
                        'created_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql'),
                        'is_deleted' => 0
                    ],
                    ['%d', '%d', '%s', '%s', '%s', '%d']
                );
            }
        }elseif ($partner_ids === null || $partner_ids === '' || (is_array($partner_ids) && empty($partner_ids))) {
            // If partners is blank or empty, soft delete all partners
            $wpdb->update(
                $this->table_name,
                ['is_deleted' => 1],
                ['event_id' => $event_id, 'role' => 'partner', 'is_deleted' => 0],
                ['%d', '%s', '%d']
            );
        }
    
        // Success response
        return rest_ensure_response([
            'success' => true,
            'message' => 'Roles updated successfully',
            'event_id' => $event_id,
        ]);
    }
    
    

    /**
     * Validate event and organizer data
     */
    function validate_event_and_organizers($event_id, $organizer_data, $user_id)
    {
        global $wpdb;

        // Validate the event ID
        if (get_post_status($event_id) === false || get_post_type($event_id) !== 'event') {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Invalid event ID',
            ], 404);
        }

        // Check if the current user is allowed to update organizers (i.e., they are the event creator)
        $event_author = get_post_field('post_author', $event_id);
        if ($event_author != $user_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'You are not the event creator',
            ], 403);
        }

        // Ensure the organizers data is valid
        if (empty($organizer_data) || !is_array($organizer_data)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Invalid organizers data',
            ], 400);
        }

        // Check the number of current organizers
        $existing_organizers_count = $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM $this->table_name WHERE event_id = %d", $event_id)
        );

        // Enforce the two organizers limit for the event
        if ($existing_organizers_count > 2) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'This event already has two organizers',
            ], 400);
        }

        return true;
    }

    function get_all_organizers(WP_REST_Request $request)
    {
        global $wpdb;

        // Optional: Fetch query parameters for pagination
        $page = $request->get_param('page') ? intval($request->get_param('page')) : 1;
        $per_page = $request->get_param('per_page') ? intval($request->get_param('per_page')) : 10;

        // Calculate offset for pagination
        $offset = ($page - 1) * $per_page;

        // Fetch the organizers list with pagination
        $organizers = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT user_id FROM $this->table_name WHERE role = 'organizer' AND is_deleted = 0 LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );

        // Check if organizers are found
        if (empty($organizers)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'No organizers found',
            ), 404);
        }

        // Prepare the response data (only the necessary fields)
        $organizers_data = [];
        $bp_xprofile_field_id = '17'; // company website filed

        foreach ($organizers as $organizer) {
            // Get the user data for each organizer
            $companyWebsite = xprofile_get_field_data($bp_xprofile_field_id, $organizer->user_id);
            $user_data = get_userdata($organizer->user_id);
            $display_name = $user_data ? $user_data->display_name : 'Unknown User';
            $avatar_url = get_avatar_url($organizer->user_id); // Get avatar URL

            $organizers_data[] = [
                'user_id' => $organizer->user_id,
                'display_name' => $display_name,  // Add the display name
                'website' => esc_url($companyWebsite),
                'avatar_url' => esc_url($avatar_url),  // Add the avatar URL
            ];
        }

        // Return success response with the list of organizers
        return rest_ensure_response(array(
            'success' => true,
            'organizers' => $organizers_data,
            'page' => $page,
            'per_page' => $per_page,
        ));
    }

    // function get_event_organizers(WP_REST_Request $request)
    // {
    //     global $wpdb;

    //     // Get the event ID from the request
    //     $event_id = intval($request->get_param('event_id'));

    //     // Validate the event ID
    //     if (get_post_status($event_id) === false || get_post_type($event_id) !== 'event') {
    //         return new WP_REST_Response(array(
    //             'success' => false,
    //             'message' => 'Invalid event ID',
    //         ), 404);
    //     }

    //     // Query the database for both organizers and partners
    //     $roles = $wpdb->get_results(
    //         $wpdb->prepare(
    //             "SELECT user_id, role FROM $this->table_name WHERE event_id = %d AND is_deleted = 0",
    //             $event_id
    //         )
    //     );

    //     // Initialize arrays to store organizer and partner data
    //     $organizer_data = [];
    //     $partner_data = [];

    //     // Loop through the roles and separate organizers and partners
    //     foreach ($roles as $role) {
    //         $user = get_userdata($role->user_id);
    //         if ($user) {
    //             // Get the user's avatar URL
    //             $avatar_url = get_avatar_url($user->ID);

    //             // Get the company website if available
    //             $company_website = xprofile_get_field_data('Company Website', $user->ID); // Replace with actual field name/ID

    //             // Prepare user data
    //             $user_data = array(
    //                 'ID' => $user->ID,
    //                 'display_name' => $user->display_name,
    //                 'email' => $user->user_email,
    //                 'company_website' => $company_website ? esc_url($company_website) : null, // Ensure proper URL formatting
    //                 'avatar' => $avatar_url, // Add avatar URL
    //             );

    //             // Assign the user to the correct role array
    //             if ($role->role === 'organizer') {
    //                 $organizer_data[] = $user_data; // Add to organizers array
    //             } elseif ($role->role === 'partner') {
    //                 $partner_data[] = $user_data; // Add to partners array
    //             }
    //         }
    //     }

    //     // Return response with both organizers and partners
    //     return rest_ensure_response(array(
    //         'success' => true,
    //         'event_id' => $event_id,
    //         'organizers' => $organizer_data,
    //         'partners' => $partner_data,
    //     ));
    // }
    function get_event_organizers(WP_REST_Request $request)
    {
        global $wpdb;

        // Get the event ID from the request
        $event_id = intval($request->get_param('event_id'));

        // Validate the event ID
        if (get_post_status($event_id) === false || get_post_type($event_id) !== 'event') {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Invalid event ID',
            ), 404);
        }

        // Query the database for co-organizers and partners
        $roles = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT user_id, role FROM $this->table_name WHERE event_id = %d AND is_deleted = 0",
                $event_id
            )
        );

        // Initialize arrays to store co-organizer and partner data
        $co_organizer_data = null;
        $partner_data = [];

        // Loop through the roles and separate co-organizers and partners
        foreach ($roles as $role) {
            $user = get_userdata($role->user_id);
            if ($user) {
                // Get the user's avatar URL
                $avatar_url = get_avatar_url($user->ID);

                // Get the company website if available
                $company_website = xprofile_get_field_data('Company Website', $user->ID); // Replace with actual field name/ID

                // Prepare user data
                $user_data = array(
                    'ID' => $user->ID,
                    'display_name' => $user->display_name,
                    'email' => $user->user_email,
                    'company_website' => $company_website ? esc_url($company_website) : '', // Ensure proper URL formatting
                    'avatar' => $avatar_url, // Add avatar URL
                );

                // Assign the user to the correct role array
                if ($role->role === 'co-organizer') {
                    $co_organizer_data = $user_data; // Add to co-organizers array
                } elseif ($role->role === 'partner') {
                    $partner_data[] = $user_data; // Add to partners array
                }
            }
        }

        // Return response with both co-organizers and partners
        return rest_ensure_response(array(
            'success' => true,
            'event_id' => $event_id,
            'co_organizer' => $co_organizer_data,
            'partners' => $partner_data,
        ));
    }


}