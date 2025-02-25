<?php


//register add vehicle endpoint.
if (defined('ICL_SITEPRESS_VERSION')) {
    add_action("rest_api_init", function () {
        $namespace = 'buddypress/v1';
        register_rest_route(
            $namespace,
            '/vehicle',
            array(
                'methods' => 'POST',
                'callback' => 'store_vehicle_information',
                'permission_callback' => function ($request) {
                    return is_user_logged_in();
                }
            )
        );
        register_rest_route(
            $namespace,
            '/user/vehicles',
            array(
                'methods' => 'GET',
                'callback' => 'get_user_vehicles',
                'permission_callback' => '__return_true'
            )
        );
        register_rest_route(
            $namespace,
            '/vehicle/(?P<id>\d+)',
            array(
                'methods' => 'POST',
                'callback' => 'update_vehicle_information',
                'permission_callback' => function ($request) {
                    return is_user_logged_in();
                }
            )
        );
        register_rest_route(
            $namespace,
            '/vehicle/(?P<vehicle_id>\d+)',
            array(
                'methods' => 'GET',
                'callback' => 'get_specific_vehicle_information',
                // 'permission_callback' => function ($request) {
                //     return is_user_logged_in();
                // }
                'permission_callback' => '__return_true'

            )
        );
        register_rest_route(
            $namespace,
            '/vehicle/(?P<id>\d+)',
            array(
                'methods' => 'DELETE',
                'callback' => 'delete_vehicle_information',
                'permission_callback' => function ($request) {
                    return is_user_logged_in();
                }
            )
        );
        // register_rest_route(
        //     $namespace,
        //     '/suggest-nickname',
        //     array(
        //         'methods' => 'POST',
        //         'callback' => 'suggest_nickname',
        //         'permission_callback' => '__return_true' // No authentication required

        //     )
        // );
    });


    function store_vehicle_information(WP_REST_Request $request)
    {
        $user_id = get_current_user_id();
        $params = $request->get_params();
        // Switch to the user's preferred language
        switch_to_user_language($user_id);
        // Validate input data
        if (empty($params)) {
            return new WP_Error('missing_json_data', 'JSON data is required', array('status' => 400));
        }

        if (!isset($params['vehicle_nickname'])) {
            return new WP_Error('missing_field', 'Vehicle nickname is required', array('status' => 400));
        }

        // Check if vehicle nickname already exists
        $nickname = sanitize_text_field($params['vehicle_nickname']);
        $existing_vehicles = get_posts(
            array(
                'post_type' => 'vehicle',
                'meta_query' => array(
                    array(
                        'key' => 'vehicle_nickname',
                        'value' => $nickname,
                        'compare' => '='
                    )
                )
            )
        );

        if (!empty($existing_vehicles)) {
            return new WP_Error('nickname_exists', 'Vehicle nickname already exists', array('status' => 400));
        }

        // Validate numeric fields
        $numeric_fields = array('year_of_construction', 'ps');
        $numeric_validation_result = validate_numeric_fields($params, $numeric_fields);
        if (is_wp_error($numeric_validation_result)) {
            return $numeric_validation_result;
        }

        // Set default values for title and description
        $title = !empty($params['title']) ? sanitize_text_field($params['title']) : 'No Title';
        $description = !empty($params['description']) ? sanitize_textarea_field($params['description']) : '';

        // Prepare vehicle data for insertion
        $vehicle_data = array(
            'post_title' => $title,
            'post_content' => $description,
            'post_status' => 'publish',
            'post_type' => 'vehicle',
            'post_author' => $user_id,
        );

        // Insert the vehicle post
        $post_id = wp_insert_post($vehicle_data);

        if (is_wp_error($post_id)) {
            return new WP_Error('post_creation_failed', 'Failed to create post', array('status' => 500));
        }

        // Update meta fields for the vehicle
        $meta_keys = array('vehicle_nickname', 'manufacturer', 'model', 'type', 'year_of_construction', 'ps', 'cover_image', 'profile_image');
        foreach ($meta_keys as $key) {
            if (!empty($params[$key])) {
                update_post_meta($post_id, $key, sanitize_text_field($params[$key]));
            }
        }
        // Handle image uploads
        $file_fields = array('profile_image', 'cover_image');
        $upload_result = handle_vehicle_image_uploads($post_id, $file_fields);
        if (is_wp_error($upload_result)) {
            wp_delete_post($post_id, true);
            return $upload_result;
        }

        // // Handle file uploads
        // require_once (ABSPATH . 'wp-admin/includes/file.php');
        // require_once (ABSPATH . 'wp-admin/includes/media.php');
        // require_once (ABSPATH . 'wp-admin/includes/image.php');

        // $file_fields = array('profile_image', 'cover_image');
        // foreach ($file_fields as $file_field) {
        //     if (!empty($_FILES[$file_field]['name'])) {
        //         $attachment_id = media_handle_upload($file_field, $post_id);
        //         if (is_wp_error($attachment_id)) {
        //             // Rollback: Delete the created post if file upload fails
        //             wp_delete_post($post_id, true);
        //             return new WP_Error($file_field . '_upload_failed', ucfirst($file_field) . ' upload failed', array('status' => 500));
        //         } else {
        //             update_post_meta($post_id, $file_field, $attachment_id);
        //         }
        //     }
        // }

        // Success response
        return new WP_REST_Response(array('success' => true, 'post_id' => $post_id), 200);
    }

    function validate_numeric_fields($params, $fields)
    {
        foreach ($fields as $field) {
            if (isset($params[$field]) && !is_numeric($params[$field]) && $params[$field] !== "") {
                return new WP_Error('invalid_field', sprintf('%s must be a number or null', $field), array('status' => 400));
            }
        }
        return true;
    }

    //get user vehicle data

    function get_user_vehicles(WP_REST_Request $request)
    {
        // // $user_id = get_current_user_id();
        // $user_id = $request->get_param('user_id');

        // Get user_id from request or use the current logged-in user if not provided
        $user_id = $request->get_param('user_id') ? $request->get_param('user_id') : get_current_user_id();

        // Check if the user is logged in
        if (!$user_id) {
            return new WP_REST_Response(array('success' => false, 'message' => 'User not authenticated'), 401);
        }
        // Get pagination parameters

        $page = $request->get_param('page') ? (int) $request->get_param('page') : 1;
        $per_page = $request->get_param('per_page') ? (int) $request->get_param('per_page') : 10;


        // Switch to the user's preferred language
        switch_to_user_language($user_id);

        $args = array(
            'post_type' => 'vehicle',
            'post_status' => 'publish',
            'author' => $user_id,
            'posts_per_page' => $per_page,
            'paged' => $page,
            'suppress_filters' => false, // Needed for WPML
        );

        $query = new WP_Query($args);

        $vehicles = array();
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $vehicle_id = get_the_ID();
                $vehicle = array(
                    'id' => $vehicle_id,
                    'title' => get_the_title(),
                    'nickname' => get_post_meta($vehicle_id, 'vehicle_nickname', true),
                    'manufacturer' => get_post_meta($vehicle_id, 'manufacturer', true),
                    'model' => get_post_meta($vehicle_id, 'model', true),
                    'type' => get_post_meta($vehicle_id, 'type', true),
                    'year_of_construction' => get_post_meta($vehicle_id, 'year_of_construction', true),
                    'ps' => get_post_meta($vehicle_id, 'ps', true),
                    'cover_image' => '',
                    'profile_image' => ''
                );

                // Get URLs for cover_image and profile_image
                $cover_image_id = get_post_meta($vehicle_id, 'cover_image', true);
                $profile_image_id = get_post_meta($vehicle_id, 'profile_image', true);

                if ($cover_image_id) {
                    $cover_image_url = wp_get_attachment_url($cover_image_id);
                    if ($cover_image_url) {
                        $vehicle['cover_image'] = $cover_image_url;
                    }
                }

                if ($profile_image_id) {
                    $profile_image_url = wp_get_attachment_url($profile_image_id);
                    if ($profile_image_url) {
                        $vehicle['profile_image'] = $profile_image_url;
                    }
                }
                $vehicles[] = $vehicle;

            }
            wp_reset_postdata();
        }

        $total_posts = $query->found_posts;
        $total_pages = $query->max_num_pages;
        $response_data = array(
            "success" => true,
            'total_record_count' => $total_posts,
            'total_pages' => $total_pages,
            'current_page' => $page,
            'vehicles' => $vehicles
        );
        return new WP_REST_Response($response_data, 200);
    }

    //edit user's vehicle 

    function update_vehicle_information(WP_REST_Request $request)
    {
        $user_id = get_current_user_id();
        $post_id = $request->get_param('id');
        $params = $request->get_params();
        // Switch to the user's preferred language
        switch_to_user_language($user_id);
        // Validate input data
        if (empty($params)) {
            return new WP_Error('missing_json_data', 'JSON data is required', array('status' => 400));
        }

        // Check if the vehicle exists and belongs to the current user
        $vehicle_post = get_post($post_id);
        if (!$vehicle_post || $vehicle_post->post_type !== 'vehicle' || $vehicle_post->post_author != $user_id) {
            return new WP_Error('invalid_vehicle', 'Invalid vehicle ID or permission denied', array('status' => 403));
        }
        // Validate numeric fields
        $numeric_fields = array('year_of_construction', 'ps');
        $numeric_validation_result = validate_numeric_fields($params, $numeric_fields);
        if (is_wp_error($numeric_validation_result)) {
            return $numeric_validation_result;
        }
        // Update vehicle title and description
        $title = !empty($params['title']) ? sanitize_text_field($params['title']) : $vehicle_post->post_title;
        $description = !empty($params['description']) ? sanitize_textarea_field($params['description']) : $vehicle_post->post_content;

        // Prepare vehicle data for update
        $vehicle_data = array(
            'ID' => $post_id,
            'post_title' => $title,
            'post_content' => $description,
        );

        // Update the vehicle post
        $updated_post_id = wp_update_post($vehicle_data);

        if (is_wp_error($updated_post_id)) {
            return new WP_Error('post_update_failed', 'Failed to update post', array('status' => 500));
        }

        // Update meta fields for the vehicle
        $meta_keys = array('vehicle_nickname', 'manufacturer', 'model', 'type', 'year_of_construction', 'ps');
        foreach ($meta_keys as $key) {
            if (isset($params[$key])) {
                update_post_meta($post_id, $key, sanitize_text_field($params[$key]));
            }
        }

        // // Handle file uploads
        // require_once (ABSPATH . 'wp-admin/includes/file.php');
        // require_once (ABSPATH . 'wp-admin/includes/media.php');
        // require_once (ABSPATH . 'wp-admin/includes/image.php');

        // $file_fields = array('profile_image', 'cover_image');
        // foreach ($file_fields as $file_field) {
        //     if (!empty($_FILES[$file_field]['name'])) {
        //         // Delete the existing image if it exists
        //         $existing_attachment_id = get_post_meta($post_id, $file_field, true);
        //         if ($existing_attachment_id) {
        //             wp_delete_attachment($existing_attachment_id, true);
        //         }

        //         // Upload the new image
        //         $attachment_id = media_handle_upload($file_field, $post_id);
        //         if (is_wp_error($attachment_id)) {
        //             return new WP_Error($file_field . '_upload_failed', ucfirst($file_field) . ' upload failed', array('status' => 500));
        //         } else {
        //             update_post_meta($post_id, $file_field, $attachment_id);
        //         }
        //     }
        // }

        // Handle image uploads
        $file_fields = array('profile_image', 'cover_image');
        $upload_result = handle_vehicle_image_uploads($post_id, $file_fields);
        if (is_wp_error($upload_result)) {
            return $upload_result;
        }

        return new WP_REST_Response(array('success' => true, 'post_id' => $post_id), 200);



    }
    //get specifice user's vehicle
    function handle_vehicle_image_uploads($post_id, $file_fields)
    {
        require_once (ABSPATH . 'wp-admin/includes/file.php');
        require_once (ABSPATH . 'wp-admin/includes/media.php');
        require_once (ABSPATH . 'wp-admin/includes/image.php');

        foreach ($file_fields as $file_field) {
            if (!empty($_FILES[$file_field]['name'])) {
                // Delete the existing image if it exists
                $existing_attachment_id = get_post_meta($post_id, $file_field, true);
                if ($existing_attachment_id) {
                    wp_delete_attachment($existing_attachment_id, true);
                }

                // Upload the new image
                $attachment_id = media_handle_upload($file_field, $post_id);
                if (is_wp_error($attachment_id)) {
                    return $attachment_id;
                } else {
                    update_post_meta($post_id, $file_field, $attachment_id);
                }
            }
        }
        return true;
    }


    function get_specific_vehicle_information(WP_REST_Request $request)
    {
        $vehicle_id = $request->get_param('vehicle_id'); //get vehicle id
        $user_id = get_current_user_id();
        // Switch to the user's preferred language
        switch_to_user_language($user_id);

        // Verify that the vehicle belongs to the current user
        $vehicle_post = get_post($vehicle_id);
        // if (!$vehicle_post || $vehicle_post->post_type != 'vehicle' || $vehicle_post->post_author != $user_id) {
        //     return new WP_REST_Response(
        //         array(
        //             "success" => false,
        //             "message" => "Vehicle not found or you don't have permission to access it."
        //         ),
        //         404
        //     );
        // }

        $vehicle = array(
            'id' => $vehicle_id,
            'title' => get_the_title($vehicle_id),
            'nickname' => get_post_meta($vehicle_id, 'vehicle_nickname', true),
            'manufacturer' => get_post_meta($vehicle_id, 'manufacturer', true),
            'model' => get_post_meta($vehicle_id, 'model', true),
            'type' => get_post_meta($vehicle_id, 'type', true),
            'year_of_construction' => get_post_meta($vehicle_id, 'year_of_construction', true),
            'ps' => get_post_meta($vehicle_id, 'ps', true),
            'cover_image' => '',
            'profile_image' => '',
        );

        // Get URLs for cover_image and profile_image
        $cover_image_id = get_post_meta($vehicle_id, 'cover_image', true);
        $profile_image_id = get_post_meta($vehicle_id, 'profile_image', true);

        if ($cover_image_id) {
            $cover_image_url = wp_get_attachment_url($cover_image_id);
            if ($cover_image_url) {
                $vehicle['cover_image'] = $cover_image_url;
            }
        }

        if ($profile_image_id) {
            $profile_image_url = wp_get_attachment_url($profile_image_id);
            if ($profile_image_url) {
                $vehicle['profile_image'] = $profile_image_url;
            }
        }

        return new WP_REST_Response(
            array(
                "success" => true,
                "vehicle" => $vehicle,
            ),
            200
        );
    }


    //delete user's vehicle


    // function delete_vehicle_information(WP_REST_Request $request)
    // {
    //     $user_id = get_current_user_id();
    //     $post_id = $request->get_param('id');

    //     // Check if the vehicle exists and belongs to the current user
    //     $vehicle_post = get_post($post_id);
    //     if (!$vehicle_post || $vehicle_post->post_type !== 'vehicle' || $vehicle_post->post_author != $user_id) {
    //         return new WP_Error('invalid_vehicle', 'Invalid vehicle ID or permission denied', array('status' => 403));
    //     }

    //     // Get the meta fields for profile and cover images
    //     $file_fields = array('profile_image', 'cover_image');
    //     foreach ($file_fields as $file_field) {
    //         $attachment_id = get_post_meta($post_id, $file_field, true);
    //         if ($attachment_id) {
    //             wp_delete_attachment($attachment_id, true);
    //         }
    //     }

    //     // Delete the vehicle post
    //     $result = wp_delete_post($post_id, true);

    //     if (!$result) {
    //         return new WP_Error('post_deletion_failed', 'Failed to delete post', array('status' => 500));
    //     }

    //     // Success response
    //     return new WP_REST_Response(array('success' => true, 'post_id' => $post_id), 200);
    // }

    //if we use multiple language then it is better to delete all translation.
    function delete_vehicle_information(WP_REST_Request $request)
    {
        $user_id = get_current_user_id();
        $post_id = $request->get_param('id');

        // Check if the vehicle exists and belongs to the current user
        $vehicle_post = get_post($post_id);
        if (!$vehicle_post || $vehicle_post->post_type !== 'vehicle' || $vehicle_post->post_author != $user_id) {
            return new WP_Error('invalid_vehicle', 'Invalid vehicle ID or permission denied', array('status' => 403));
        }

        // Fetch all translations of the post
        $translations = apply_filters('wpml_get_element_translations', null, apply_filters('wpml_element_trid', null, $post_id, 'post_vehicle'), 'post_vehicle');

        // Iterate through all translations and delete them
        foreach ($translations as $translation) {
            // Get the translation post ID
            $translation_id = $translation->element_id;

            // Get the meta fields for profile and cover images
            $file_fields = array('profile_image', 'cover_image');
            foreach ($file_fields as $file_field) {
                $attachment_id = get_post_meta($translation_id, $file_field, true);
                if ($attachment_id) {
                    wp_delete_attachment($attachment_id, true);
                }
            }

            // Delete the translation post
            $result = wp_delete_post($translation_id, true);
            if (!$result) {
                return new WP_Error('post_deletion_failed', 'Failed to delete post', array('status' => 500));
            }
        }

        // Success response
        return new WP_REST_Response(array('success' => true, 'post_id' => $post_id), 200);
    }

    //vehicle nickname suggestion
    // function suggest_nickname(WP_REST_Request $request)
    // {
    //     $user_id = get_current_user_id();
    //     $params = $request->get_params();

    //     if (empty($params['vehicle_nickname'])) {
    //         return new WP_Error('missing_field', 'Vehicle nickname is required', array('status' => 400));
    //     }

    //     $nickname = sanitize_text_field($params['vehicle_nickname']);
    //     $suggested_nickname = generate_unique_nickname($nickname);

    //     return new WP_REST_Response(array('suggested_nickname' => $suggested_nickname), 200);
    // }

    // function generate_unique_nickname($nickname)
    // {
    //     $attempts = 0;
    //     $max_attempts = 10; // Limit the number of attempts to avoid infinite loops
    //     $suffix = ''; // Start without any suffix

    //     // Check if the nickname exists
    //     while (nickname_exists($nickname . $suffix) && $attempts < $max_attempts) {
    //         $suffix = '_' . wp_generate_password(3, false, false); // Generate a random suffix
    //         $attempts++;
    //     }

    //     return $nickname . $suffix;
    // }

    // function nickname_exists($nickname)
    // {
    //     $existing_vehicles = get_posts(
    //         array(
    //             'post_type' => 'vehicle',
    //             'meta_query' => array(
    //                 array(
    //                     'key' => 'vehicle_nickname',
    //                     'value' => $nickname,
    //                     'compare' => '='
    //                 )
    //             )
    //         )
    //     );

    //     return !empty($existing_vehicles);
    // }
    // function suggest_nickname(WP_REST_Request $request)
    // {
    //     $params = $request->get_params();

    //     if (empty($params['nickname'])) {
    //         return new WP_Error('missing_field', 'Vehicle nickname is required', array('status' => 400));
    //     }

    //     $nickname = sanitize_text_field($params['nickname']);
    //     $suggested_nickname = generate_unique_nickname($nickname);

    //     return new WP_REST_Response(array('suggested_nickname' => $suggested_nickname), 200);
    // }

    // function generate_unique_nickname($nickname)
    // {
    //     $attempts = 0;
    //     $max_attempts = 10; // Limit the number of attempts to avoid infinite loops
    //     $suffix = ''; // Start without any suffix

    //     // Check if the nickname exists
    //     while (nickname_exists($nickname . $suffix) && $attempts < $max_attempts) {
    //         $suffix = '_' . wp_generate_password(3, false, false); // Generate a random suffix
    //         $attempts++;
    //     }

    //     return $nickname . $suffix;
    // }

    // function nickname_exists($nickname)
    // {
    //     // Check if the nickname exists in vehicle nicknames
    //     $existing_vehicles = get_posts(
    //         array(
    //             'post_type' => 'vehicle',
    //             'meta_query' => array(
    //                 array(
    //                     'key' => 'vehicle_nickname',
    //                     'value' => $nickname,
    //                     'compare' => '='
    //                 )
    //             )
    //         )
    //     );

    //     if (!empty($existing_vehicles)) {
    //         return true;
    //     }

    //     // Check if the nickname exists in user logins
    //     if (username_exists($nickname)) {
    //         return true;
    //     }

    //     return false;
    // }

}