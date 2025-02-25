<?php

class Race_Track_Controller
{

    const NAMESPACE = 'buddypress/v1';
    const ROUTE_BASE = '/race-tracks';
    private $table_name;
    private $user_pin_race_track_table;
    private $race_track_posts_table;
    public function __construct()
    {
        global $wpdb;

        $this->table_name = "{$wpdb->prefix}race_tracks";
        $this->user_pin_race_track_table = "{$wpdb->prefix}user_pinned_tracks";
        $this->race_track_posts_table = "{$wpdb->prefix}race_track_posts";

        // Register the routes when the API is initialized
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes()
    {
        // Define the CRUD routes
        register_rest_route(self::NAMESPACE , self::ROUTE_BASE, [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_race_tracks'],
            'permission_callback' => [$this, 'loggedInUserCan'],
        ]);

        register_rest_route(self::NAMESPACE , self::ROUTE_BASE . '/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_race_track_with_posts'],
            'permission_callback' => [$this, 'loggedInUserCan'],
        ]);

        register_rest_route(self::NAMESPACE , self::ROUTE_BASE, [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'create_race_track'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);

        register_rest_route(self::NAMESPACE , self::ROUTE_BASE . '/(?P<id>\d+)', [
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => [$this, 'update_race_track'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);

        register_rest_route(self::NAMESPACE , self::ROUTE_BASE . '/(?P<id>\d+)', [
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => [$this, 'delete_race_track'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
        register_rest_route(self::NAMESPACE , self::ROUTE_BASE . '/pin', [
            'methods' => 'POST',
            'callback' => [$this, 'pin_race_track'],
            'permission_callback' => [$this, 'loggedInUserCan'],
            'args' => [
                'track_id' => [
                    'required' => true,
                    'validate_callback' => function ($param, $request, $key) {
                        return is_int($param);
                    }
                ],
                'is_pinned' => [
                    'required' => true,
                    'validate_callback' => function ($param, $request, $key) {
                        return $param == 0 || $param == 1;
                    }
                ]
            ]
        ]);
    }

    // Check permissions for API access
    public function check_permissions($request)
    {
        return current_user_can('edit_posts');  // Adjust based on your requirements
    }
    public function loggedInUserCan($request)
    {
        return is_user_logged_in();  // Adjust based on your requirements
    }
    // public function get_race_tracks($request)
    // {
    //     global $wpdb;

    //     // Prepare base SQL query
    //     $sql = "SELECT id, track_name, country, address, city, zip, track_length_km, right_hand_curve, left_hand_curve, is_pinned, cover, map_attachment FROM $this->table_name WHERE 1=1";

    //     // Check for search query (track_name)
    //     if (!empty($request['search'])) {
    //         $search = sanitize_text_field($request['search']);
    //         $sql .= $wpdb->prepare(" AND track_name LIKE %s", "%" . $search . "%");
    //     }

    //     // Check for country filter
    //     if (!empty($request['country'])) {
    //         $country = sanitize_text_field($request['country']);
    //         $sql .= $wpdb->prepare(" AND country = %s", $country);
    //     }

    //     // Check for city filter
    //     if (!empty($request['city'])) {
    //         $city = sanitize_text_field($request['city']);
    //         $sql .= $wpdb->prepare(" AND city = %s", $city);
    //     }

    //     // Check for pinned filter (only show pinned tracks if 'pinned' parameter is true)
    //     if (isset($request['pinned']) && $request['pinned'] == 'true') {
    //         $sql .= " AND is_pinned = 1";  // Only show pinned tracks
    //     }

    //     // Check for orderby parameter (default is alphabetically by track_name)
    //     $orderby = !empty($request['orderby']) ? sanitize_text_field($request['orderby']) : 'track_name';
    //     $order = (!empty($request['order']) && in_array(strtolower($request['order']), ['asc', 'desc'])) ? strtoupper($request['order']) : 'ASC';

    //     // Modify the query to prioritize pinned tracks
    //     $sql .= " ORDER BY is_pinned DESC, $orderby $order";

    //     // Add pagination
    //     $page = !empty($request['page']) ? intval($request['page']) : 1;
    //     $per_page = !empty($request['per_page']) ? intval($request['per_page']) : 10;
    //     $offset = ($page - 1) * $per_page;

    //     $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $per_page, $offset);

    //     // Execute query and get results
    //     $race_tracks = $wpdb->get_results($sql);

    //     // If no race tracks found, return error
    //     if (empty($race_tracks)) {
    //         return rest_ensure_response([
    //             'tracks' => [],
    //             'total' => 0,
    //             'page' => $page,
    //             'per_page' => $per_page,
    //         ]);
    //     }

    //     // Get total number of race tracks for pagination
    //     $total_tracks_sql = $sql;  // Make a copy of the query for total count, but without LIMIT and OFFSET
    //     $total_tracks_sql = preg_replace('/ LIMIT \d+ OFFSET \d+/', '', $total_tracks_sql);  // Remove LIMIT and OFFSET from total count query
    //     $total_tracks = $wpdb->get_var($total_tracks_sql);

    //     return rest_ensure_response([
    //         'tracks' => $race_tracks,
    //         'total' => $total_tracks,
    //         'page' => $page,
    //         'per_page' => $per_page,
    //     ]);
    // }

    public function get_race_tracks($request)
    {
        global $wpdb;

        // Get the logged-in user ID
        $user_id = get_current_user_id();

        // Prepare base SQL query
        $sql = "
        SELECT 
            rt.id, rt.track_name, rt.country, rt.address, rt.city, rt.zip, 
            rt.track_length_km, rt.right_hand_curve, rt.left_hand_curve, 
            rt.cover, rt.map_attachment,
            IF(pt.id IS NOT NULL, 1, 0) AS is_pinned
        FROM $this->table_name AS rt
        LEFT JOIN wp_user_pinned_tracks AS pt 
            ON rt.id = pt.track_id AND pt.user_id = %d
        WHERE 1=1
    ";

        // Include the user ID in the query
        $sql = $wpdb->prepare($sql, $user_id);

        // Check for search query (track_name)
        if (!empty($request['search'])) {
            $search = sanitize_text_field($request['search']);
            $sql .= $wpdb->prepare(" AND rt.track_name LIKE %s", "%" . $search . "%");
        }

        // Check for country filter
        if (!empty($request['country'])) {
            $country = sanitize_text_field($request['country']);
            $sql .= $wpdb->prepare(" AND rt.country = %s", $country);
        }

        // Check for city filter
        if (!empty($request['city'])) {
            $city = sanitize_text_field($request['city']);
            $sql .= $wpdb->prepare(" AND rt.city = %s", $city);
        }

        // Check for pinned filter (only show pinned tracks if 'pinned' parameter is true)
        if (isset($request['pinned']) && $request['pinned'] == 'true') {
            $sql .= " AND pt.id IS NOT NULL";  // Only show tracks pinned by the current user
        }

        // Check for orderby parameter (default is alphabetically by track_name)
        $orderby = !empty($request['orderby']) ? sanitize_text_field($request['orderby']) : 'track_name';
        $order = (!empty($request['order']) && in_array(strtolower($request['order']), ['asc', 'desc'])) ? strtoupper($request['order']) : 'ASC';

        // Modify the query to prioritize pinned tracks
        $sql .= " ORDER BY is_pinned DESC, $orderby $order";

        // Add pagination
        $page = !empty($request['page']) ? intval($request['page']) : 1;
        $per_page = !empty($request['per_page']) ? intval($request['per_page']) : 10;
        $offset = ($page - 1) * $per_page;

        $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $per_page, $offset);

        // Execute query and get results
        $race_tracks = $wpdb->get_results($sql);

        // If no race tracks found, return empty response
        if (empty($race_tracks)) {
            return rest_ensure_response([
                'tracks' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $per_page,
            ]);
        }

        // Get total number of race tracks for pagination
        $total_sql = "
        SELECT COUNT(*)
        FROM $this->table_name AS rt
        LEFT JOIN wp_user_pinned_tracks AS pt 
            ON rt.id = pt.track_id AND pt.user_id = %d
        WHERE 1=1
    ";
        $total_sql = $wpdb->prepare($total_sql, $user_id);

        // Apply filters for total count (same as above, but without LIMIT and OFFSET)
        if (!empty($request['search'])) {
            $total_sql .= $wpdb->prepare(" AND rt.track_name LIKE %s", "%" . $search . "%");
        }
        if (!empty($request['country'])) {
            $total_sql .= $wpdb->prepare(" AND rt.country = %s", $country);
        }
        if (!empty($request['city'])) {
            $total_sql .= $wpdb->prepare(" AND rt.city = %s", $city);
        }
        if (isset($request['pinned']) && $request['pinned'] == 'true') {
            $total_sql .= " AND pt.id IS NOT NULL";
        }

        $total_tracks = $wpdb->get_var($total_sql);

        return rest_ensure_response([
            'tracks' => $race_tracks,
            'total' => $total_tracks,
            'page' => $page,
            'per_page' => $per_page,
        ]);
    }


    // // GET a single race track by ID
    public function get_race_track_with_posts($request)
    {
        global $wpdb;

        $track_id = $request['id'];
        $logged_in_user_id = get_current_user_id();

        // Fetch race track and its posts
        $race_track = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM  $this->table_name WHERE id = %d AND is_deleted = 0",
            $track_id
        ));

        if (!$race_track) {
            return new WP_Error('track_not_found', 'Race track not found', ['status' => 404]);
        }
        // Check if the track is pinned by the logged-in user
        $is_pinned = $wpdb->get_var($wpdb->prepare(
            "SELECT 1 FROM $this->user_pin_race_track_table WHERE track_id = %d AND user_id = %d",
            $track_id,
            $logged_in_user_id
        ));

        // Set the `is_pinned` status (1 if pinned, 0 otherwise)
        $race_track->is_pinned = $is_pinned ? "1" : "0";

        // Fetch posts associated with the race track
        $posts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $this->race_track_posts_table WHERE race_track_id = %d AND is_deleted = 0",
            $track_id
        ));

        // Add posts to the race track data
        $race_track->race_track_posts = $posts;

        return rest_ensure_response($race_track);
    }

    // POST - Create a new race track
    public function create_race_track($request)
    {
        global $wpdb;
        $data = $request->get_params();

        // Retrieve file parameters
        $race_track_map_image = $request->get_file_params()['race_track_map_image'] ?? null;
        $cover = $request->get_file_params()['cover'] ?? null;

        // Validation
        $validation = $this->validate_fields($data);
        if (is_wp_error($validation)) {
            return $validation;
        }

        // Handle file uploads
        $race_track_map_image_url = '';
        $race_track_cover_url = '';

        if ($race_track_map_image) {
            $optimized_path = optimize_image($race_track_map_image['tmp_name']);
            if (!$optimized_path || !file_exists($optimized_path)) {
                return new WP_Error('optimization_failed', "Image optimization failed or file path is invalid.", ['status' => 500]);
            }
            $race_track_map_image['tmp_name'] = $optimized_path;
            $race_track_map_image_url = upload_single_media_to_s3($race_track_map_image, 'race_track', 'circuit-club');
            if (is_wp_error($race_track_map_image_url)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Failed to upload map image to S3',
                    'error' => $race_track_map_image_url->get_error_message(),
                ], 500);
            }
        }

        if ($cover) {

            $optimized_path = optimize_image($cover['tmp_name']);
            if (!$optimized_path || !file_exists($optimized_path)) {
                return new WP_Error('optimization_failed', "Image optimization failed or file path is invalid.", ['status' => 500]);
            }
            $cover['tmp_name'] = $optimized_path;
            $race_track_cover_url = upload_single_media_to_s3($cover, 'race_track', 'circuit-club');
            if (is_wp_error($race_track_cover_url)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Failed to upload cover image to S3',
                    'error' => $race_track_cover_url->get_error_message(),
                ], 500);
            }
        }

        // Insert into database
        $result = $wpdb->insert(
            $this->table_name,
            [
                'track_name' => sanitize_text_field($data['track_name']),
                'country' => sanitize_text_field($data['country']),
                'address' => sanitize_textarea_field($data['address']),
                'city' => sanitize_textarea_field($data['city']),
                'zip' => intval($data['zip']),
                'track_length_km' => floatval($data['track_length_km']),
                'right_hand_curve' => intval($data['right_hand_curve']),
                'left_hand_curve' => intval($data['left_hand_curve']),
                'expected_costs' => floatval($data['expected_costs']),
                'expected_cost_description' => sanitize_textarea_field($data['expected_cost_description']),
                'route' => floatval($data['route']),
                'toll' => floatval($data['toll']),
                'fuel' => floatval($data['fuel']),
                'cover' => $race_track_cover_url,
                'map_attachment' => $race_track_map_image_url,
            ]
        );

        // Handle database insertion failure
        if ($result === false) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Failed to create race track',
                'error' => $wpdb->last_error,
            ], 500);
        }

        // Return success response
        $race_track_id = $wpdb->insert_id;

        return rest_ensure_response([
            'id' => $race_track_id,
            'name' => $data['track_name'],
        ]);
    }

    public function update_race_track($request)
    {
        global $wpdb;
        $data = $request->get_params();

        // Retrieve race track ID
        $race_track_id = intval($request->get_param('id'));
        if (!$race_track_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Invalid race track ID.',
            ], 400);
        }

        // Retrieve file parameters
        $race_track_map_image = $request->get_file_params()['race_track_map_image'] ?? null;
        $cover = $request->get_file_params()['cover'] ?? null;

        // Validate fields
        $validation = $this->edit_validate_fields($data); // `false` to allow partial updates
        if (is_wp_error($validation)) {
            return $validation;
        }

        // Handle file uploads
        $race_track_map_image_url = null;
        $race_track_cover_url = null;

        if ($race_track_map_image) {
            $optimized_path = optimize_image($race_track_map_image['tmp_name']);
            if (!$optimized_path || !file_exists($optimized_path)) {
                return new WP_Error('optimization_failed', "Image optimization failed or file path is invalid.", ['status' => 500]);
            }
            $race_track_map_image['tmp_name'] = $optimized_path;
            $race_track_map_image_url = upload_single_media_to_s3($race_track_map_image, 'race_track', 'circuit-club');
            if (is_wp_error($race_track_map_image_url)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Failed to upload map image to S3',
                    'error' => $race_track_map_image_url->get_error_message(),
                ], 500);
            }
        }

        if ($cover) {
            $optimized_path = optimize_image($cover['tmp_name']);
            if (!$optimized_path || !file_exists($optimized_path)) {
                return new WP_Error('optimization_failed', "Image optimization failed or file path is invalid.", ['status' => 500]);
            }
            $cover['tmp_name'] = $optimized_path;
            $race_track_cover_url = upload_single_media_to_s3($cover, 'race_track', 'circuit-club');
            if (is_wp_error($race_track_cover_url)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Failed to upload cover image to S3',
                    'error' => $race_track_cover_url->get_error_message(),
                ], 500);
            }
        }

        // Prepare updated data
        $update_data = array_filter([
            'track_name' => !empty($data['track_name']) ? sanitize_text_field($data['track_name']) : null,
            'country' => !empty($data['country']) ? sanitize_text_field($data['country']) : null,
            'address' => !empty($data['address']) ? sanitize_textarea_field($data['address']) : null,
            'city' => !empty($data['city']) ? sanitize_text_field($data['city']) : null,
            'zip' => isset($data['zip']) ? intval($data['zip']) : null,
            'track_length_km' => isset($data['track_length_km']) ? floatval($data['track_length_km']) : null,
            'right_hand_curve' => isset($data['right_hand_curve']) ? intval($data['right_hand_curve']) : null,
            'left_hand_curve' => isset($data['left_hand_curve']) ? intval($data['left_hand_curve']) : null,
            'expected_costs' => isset($data['expected_costs']) ? floatval($data['expected_costs']) : null,
            'expected_cost_description' => !empty($data['expected_cost_description']) ? sanitize_textarea_field($data['expected_cost_description']) : null,
            'route' => isset($data['route']) ? floatval($data['route']) : null,
            'toll' => isset($data['toll']) ? floatval($data['toll']) : null,
            'fuel' => isset($data['fuel']) ? floatval($data['fuel']) : null,
            'cover' => $race_track_cover_url,
            'map_attachment' => $race_track_map_image_url,
        ], function ($value) {
            return !is_null($value);
        });

        if (empty($update_data)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'No valid fields provided to update.',
            ], 400);
        }

        // Update the database
        $result = $wpdb->update(
            $this->table_name,
            $update_data,
            ['id' => $race_track_id]
        );

        if ($result === false) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Failed to update race track.',
                'error' => $wpdb->last_error,
            ], 500);
        }

        // Return success response
        return rest_ensure_response([
            'success' => true,
            'message' => 'Race track updated successfully.',
            'data' => [
                'id' => $race_track_id,
                'updated_fields' => array_keys($update_data),
            ],
        ]);
    }


    // DELETE - Delete a race track
    public function delete_race_track($request)
    {
        global $wpdb;

        $race_track_id = $request['id'];

        // Delete the race track
        $deleted = $wpdb->delete($this->table_name, ['id' => $race_track_id]);

        if (!$deleted) {
            return new WP_Error('race_track_delete_failed', 'Failed to delete the race track', ['status' => 500]);
        }

        return rest_ensure_response(['message' => 'Race track deleted successfully']);
    }
    // private function validate_fields($data)
    // {
    //     if (empty($data['track_name'])) {
    //         return new WP_Error('missing_fields', 'Race track name is required', ['status' => 400]);
    //     }
    //     // Add more validations as needed
    //     return true;
    // }
    private function validate_fields($data)
    {
        global $wpdb;

        $required_fields = [
            'track_name' => 'Race track name is required',
            'country' => 'Country is required',
            'address' => 'Address is required',
            'zip' => 'ZIP code is required',
            'city' => 'City is required',
            'track_length_km' => 'Track length is required',
            'right_hand_curve' => 'Right hand curve count is required',
            'left_hand_curve' => 'Left hand curve count is required',
            'expected_costs' => 'Expected costs are required',
            'route' => 'Route is required',
        ];

        foreach ($required_fields as $field => $error_message) {
            if (empty($data[$field])) {
                return new WP_Error('missing_fields', $error_message, ['status' => 400]);
            }
        }

        // Check if track_name is unique
        $track_name = sanitize_text_field($data['track_name']);
        $existing_track = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $this->table_name WHERE track_name = %s",
            $track_name
        ));

        if ($existing_track > 0) {
            return new WP_Error('duplicate_track_name', 'Race track name must be unique', ['status' => 400]);
        }

        return true;
    }
    private function edit_validate_fields($data)
    {
        global $wpdb;

        $required_fields = [
            'track_name' => 'Race track name is required',
            'country' => 'Country is required',
            'address' => 'Address is required',
            'zip' => 'ZIP code is required',
            'city' => 'City is required',
            'track_length_km' => 'Track length is required',
            'right_hand_curve' => 'Right hand curve count is required',
            'left_hand_curve' => 'Left hand curve count is required',
            'expected_costs' => 'Expected costs are required',
            'route' => 'Route is required',
        ];

        foreach ($required_fields as $field => $error_message) {
            if (empty($data[$field])) {
                return new WP_Error('missing_fields', $error_message, ['status' => 400]);
            }
        }

        // // Check if track_name is unique
        // $track_name = sanitize_text_field($data['track_name']);
        // $existing_track = $wpdb->get_var($wpdb->prepare(
        //     "SELECT COUNT(*) FROM $this->table_name WHERE track_name = %s",
        //     $track_name
        // ));

        // if ($existing_track > 0) {
        //     return new WP_Error('duplicate_track_name', 'Race track name must be unique', ['status' => 400]);
        // }

        return true;
    }

    // public function pin_race_track($request)
    // {
    //     global $wpdb;

    //     // Get track ID and pin status from the request
    //     $track_id = (int) $request['track_id'];
    //     $is_pinned = (int) $request['is_pinned']; // 1 to pin, 0 to unpin

    //     // Validate that the track exists
    //     $track = $wpdb->get_row($wpdb->prepare(
    //         "SELECT id FROM $this->table_name WHERE id = %d",
    //         $track_id
    //     ));

    //     if (!$track) {
    //         // Track doesn't exist, return an error
    //         return new WP_Error('track_not_found', 'The specified race track does not exist.', ['status' => 404]);
    //     }

    //     // Update the 'is_pinned' field in the database
    //     $updated = $wpdb->update(
    //         $this->table_name,
    //         ['is_pinned' => $is_pinned],
    //         ['id' => $track_id],
    //         ['%d'],
    //         ['%d']
    //     );

    //     // If the update fails, return an error
    //     if (false === $updated) {
    //         return new WP_Error('pin_failed', 'Failed to update pinned status', ['status' => 500]);
    //     }

    //     return rest_ensure_response(['message' => 'Pin status updated successfully']);
    // }
    public function pin_race_track($request)
    {
        global $wpdb;

        $logged_in_user_id = get_current_user_id();
        if (!$logged_in_user_id) {
            return new WP_Error('unauthorized', 'User not logged in.', ['status' => 401]);
        }

        $track_id = (int) $request['track_id'];
        $is_pinned = (int) $request['is_pinned']; // 1 to pin, 0 to unpin

        // Validate that the track exists
        $track = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM  $this->table_name WHERE id = %d",
            $track_id
        ));

        if (!$track) {
            return new WP_Error('track_not_found', 'The specified race track does not exist.', ['status' => 404]);
        }

        // Pin or unpin the track for the user
        if ($is_pinned === 1) {
            // Check if the track is already pinned
            $already_pinned = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM  $this->user_pin_race_track_table WHERE user_id = %d AND track_id = %d",
                $logged_in_user_id,
                $track_id
            ));

            if ($already_pinned) {
                return rest_ensure_response(['message' => 'Track is already pinned.']);
            }

            // Insert a new record to pin the track
            $wpdb->insert(
                $this->user_pin_race_track_table,
                [
                    'user_id' => $logged_in_user_id,
                    'track_id' => $track_id,
                    'created_at' => current_time('mysql')
                ],
                ['%d', '%d', '%s']
            );

            if (!$wpdb->insert_id) {
                return new WP_Error('pin_failed', 'Failed to pin the track.', ['status' => 500]);
            }

            return rest_ensure_response(['message' => 'Track pinned successfully.']);
        } else {
            // Unpin the track
            $deleted = $wpdb->delete(
                $this->user_pin_race_track_table,
                [
                    'user_id' => $logged_in_user_id,
                    'track_id' => $track_id
                ],
                ['%d', '%d']
            );

            if (false === $deleted) {
                return new WP_Error('unpin_failed', 'Failed to unpin the track.', ['status' => 500]);
            }

            return rest_ensure_response(['message' => 'Track unpinned successfully.']);
        }
    }


}