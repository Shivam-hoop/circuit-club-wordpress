<?php
/*
Plugin Name: BuddyPress Extend Profile Multi Field Update
Plugin URI:  https://yourwebsite.com/
Description: A custom plugin to update multiple BuddyPress profile fields in one go.
Version:     1.0
Author:      Shivam Jha
Author URI:  https://yourwebsite.com/
License:     GPL2
*/


// Register the custom REST API endpoint
add_action('rest_api_init', function () {
    register_rest_route(
        'buddypress/v1',
        '/members/(?P<id>\d+)/update/profile/fields',
        array(
            'methods' => 'POST',
            'callback' => 'update_multiple_profile_fields',
            'permission_callback' => function ($request) {
                $user_id = $request['id'];
                return is_user_logged_in() && get_current_user_id() == $user_id;
            }
        )
    );
    register_rest_route(
        'buddypress/v1',
        '/members/(?P<id>\d+)/profile/fields',
        array(
            'methods' => 'GET',
            'callback' => 'get_extended_profile_fields',
            // 'permission_callback' => function ($request) {
            //     $user_id = $request['id'];
            //     return is_user_logged_in() && get_current_user_id() == $user_id;
            // }
            'permission_callback' => '__return_true', // Adjust permissions as needed

        )
    );
    register_rest_route('buddypress/v1', '/business-members', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'get_business_members',
        'permission_callback' => '__return_true', // Adjust permissions as needed
        'args' => array(
            'page' => array(
                'default' => 1
            ),
            'per_page' => array(
                'default' => 10
            ),
            'search' => array(
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ));
    register_rest_route('buddypress/v1', '/update-user-profile', [
        'methods' => 'POST',
        'callback' => 'custom_update_user_profile',
        'permission_callback' => function () {
            return is_user_logged_in(); // Ensure user is logged in
        }
    ]);
    
});
// Callback function to update multiple profile fields
function update_multiple_profile_fields($request)
{
    $user_id = $request['id'];
    $fields_data = $request->get_param('fields');

    if (!is_array($fields_data)) {
        return new WP_Error('invalid_data', 'Fields data should be an array.', array('status' => 400));
    }

    foreach ($fields_data as $key => $field_value) {
        $parts = explode('_', $key);
        if (count($parts) < 2) {
            return new WP_Error('invalid_key', 'Invalid field key format.', array('status' => 400));
        }
        $field_id = end($parts); // Get the last part, which should be the field ID

        if (!is_numeric($field_id)) {
            return new WP_Error('invalid_field_id', 'Field ID should be numeric.', array('status' => 400));
        }
        xprofile_set_field_data($field_id, $user_id, $field_value);
    }

    return new WP_REST_Response(array('message' => 'Profile fields updated successfully.'), 200);
}


function get_extended_profile_fields($request)
{
    $user_id = $request['id'];

    if (!is_numeric($user_id)) {
        return new WP_Error('invalid_user_id', 'User ID should be numeric.', array('status' => 400));
    }

    $profile_data = array();
    $profile_groups = bp_xprofile_get_groups(array('fetch_fields' => true));

    foreach ($profile_groups as $group) {
        foreach ($group->fields as $field) {
            $field_id = $field->id;
            $field_name = toCamelCase($field->name);
            $field_value = xprofile_get_field_data($field_id, $user_id);
            $profile_data[$field_name . '_' . $field_id] = $field_value;
        }
    }

    return new WP_REST_Response(array('fields' => $profile_data), 200);
}
function toCamelCase($string)
{
    // Convert underscores to spaces
    $string = str_replace('_', ' ', $string);

    // Convert the string to lowercase
    $string = strtolower($string);

    // Convert the string to camel case
    $string = lcfirst(str_replace(' ', '', ucwords($string)));

    return $string;
}

// function get_business_members(WP_REST_Request $request) {
//     global $wpdb;

//     // Get request parameters
//     $page = (int) $request->get_param('page');
//     $per_page = (int) $request->get_param('per_page');
//     $search = $request->get_param('search');
//     $offset = ($page - 1) * $per_page;

//     // Define the xProfile field ID for member type
//     $bp_xprofile_field_id = 2; // Replace with your actual field ID

//     // Base SQL query for filtering by member type
//     $query = "
//         SELECT u.ID, u.display_name, u.user_email
//         FROM {$wpdb->prefix}bp_xprofile_data AS xpd
//         INNER JOIN {$wpdb->users} AS u ON xpd.user_id = u.ID
//         WHERE xpd.field_id = %d
//           AND xpd.value = %s
//     ";

//     // Add search condition
//     if (!empty($search)) {
//         $query .= " AND (u.display_name LIKE %s OR u.user_email LIKE %s)";
//         $search_like = '%' . $wpdb->esc_like($search) . '%';
//     }

//     // Add sorting and pagination
//     $query .= " ORDER BY u.display_name ASC LIMIT %d OFFSET %d";

//     // Prepare the SQL query with the search condition if applicable
//     if (!empty($search)) {
//         $prepared_query = $wpdb->prepare($query, $bp_xprofile_field_id, 'business', $search_like, $search_like, $per_page, $offset);
//     } else {
//         $prepared_query = $wpdb->prepare($query, $bp_xprofile_field_id, 'business', $per_page, $offset);
//     }

//     // Execute the query
//     $results = $wpdb->get_results($prepared_query);

//     // Check if any users are found
//     if (empty($results)) {
//         return new WP_Error('no_business_members', __('No business members found'), array('status' => 404));
//     }

//     // Format the response
//     $data = array();
//     foreach ($results as $user) {
//         $data[] = array(
//             'ID'           => $user->ID,
//             'display_name' => $user->display_name,
//             'user_email'   => $user->user_email,
//             'avatar'       => bp_core_fetch_avatar(array(
//                 'item_id' => $user->ID,
//                 'html'    => false, // Return the URL, not the HTML
//             )),
//         );
//     }

//     // Pagination information
//     $total_count_query = "
//         SELECT COUNT(*)
//         FROM {$wpdb->prefix}bp_xprofile_data AS xpd
//         INNER JOIN {$wpdb->users} AS u ON xpd.user_id = u.ID
//         WHERE xpd.field_id = %d
//           AND xpd.value = %s
//     ";
//     if (!empty($search)) {
//         $total_count_query .= " AND (u.display_name LIKE %s OR u.user_email LIKE %s)";
//         $prepared_total_count_query = $wpdb->prepare($total_count_query, $bp_xprofile_field_id, 'business', $search_like, $search_like);
//     } else {
//         $prepared_total_count_query = $wpdb->prepare($total_count_query, $bp_xprofile_field_id, 'business');
//     }

//     $total_count = $wpdb->get_var($prepared_total_count_query);

//     $response = array(
//         'data' => $data,
//         'pagination' => array(
//             'total_items' => (int) $total_count,
//             'total_pages' => ceil($total_count / $per_page),
//             'current_page' => $page,
//             'per_page' => $per_page,
//         ),
//     );

//     return rest_ensure_response($response);
// }

function get_business_members(WP_REST_Request $request)
{
    global $wpdb;

    // Get request parameters
    $page = (int) $request->get_param('page');
    $per_page = (int) $request->get_param('per_page');
    $search = $request->get_param('search');
    $offset = ($page - 1) * $per_page;

    // Define the xProfile field IDs
    $bp_xprofile_field_id = 2; // Member type field ID
    $website_field_id = 17;   // Company website field ID

    // Base SQL query for filtering by member type and including website data
    $query = "
        SELECT u.ID, u.display_name, u.user_email, website_data.value AS company_website
        FROM {$wpdb->prefix}bp_xprofile_data AS xpd
        INNER JOIN {$wpdb->users} AS u ON xpd.user_id = u.ID
        LEFT JOIN {$wpdb->prefix}bp_xprofile_data AS website_data 
            ON website_data.user_id = u.ID AND website_data.field_id = %d
        WHERE xpd.field_id = %d
          AND xpd.value = %s
    ";

    // Add search condition
    if (!empty($search)) {
        $query .= " AND (u.display_name LIKE %s OR u.user_email LIKE %s)";
        $search_like = '%' . $wpdb->esc_like($search) . '%';
    }

    // Add sorting and pagination
    $query .= " ORDER BY u.display_name ASC LIMIT %d OFFSET %d";

    // Prepare the SQL query with the search condition if applicable
    if (!empty($search)) {
        $prepared_query = $wpdb->prepare($query, $website_field_id, $bp_xprofile_field_id, 'business', $search_like, $search_like, $per_page, $offset);
    } else {
        $prepared_query = $wpdb->prepare($query, $website_field_id, $bp_xprofile_field_id, 'business', $per_page, $offset);
    }

    // Execute the query
    $results = $wpdb->get_results($prepared_query);

    // // Check if any users are found
    // if (empty($results)) {
    //     return new WP_Error('no_business_members', __('No business members found'), array('status' => 404));
    // }

    // Format the response
    $data = array();
    foreach ($results as $user) {
        $data[] = array(
            'ID' => $user->ID,
            'display_name' => $user->display_name,
            'user_email' => $user->user_email,
            'company_website' => $user->company_website ? $user->company_website : '',
            'avatar' => bp_core_fetch_avatar(array(
                'item_id' => $user->ID,
                'html' => false, // Return the URL, not the HTML
            )),
        );
    }

    // Pagination information
    $total_count_query = "
        SELECT COUNT(*)
        FROM {$wpdb->prefix}bp_xprofile_data AS xpd
        INNER JOIN {$wpdb->users} AS u ON xpd.user_id = u.ID
        WHERE xpd.field_id = %d
          AND xpd.value = %s
    ";
    if (!empty($search)) {
        $total_count_query .= " AND (u.display_name LIKE %s OR u.user_email LIKE %s)";
        $prepared_total_count_query = $wpdb->prepare($total_count_query, $bp_xprofile_field_id, 'business', $search_like, $search_like);
    } else {
        $prepared_total_count_query = $wpdb->prepare($total_count_query, $bp_xprofile_field_id, 'business');
    }

    $total_count = $wpdb->get_var($prepared_total_count_query);

    $response = array(
        'data' => $data,
        'pagination' => array(
            'total_items' => (int) $total_count,
            'total_pages' => ceil($total_count / $per_page),
            'current_page' => $page,
            'per_page' => $per_page,
        ),
    );

    return rest_ensure_response($response);
}
function custom_update_user_profile($request) {
    $user_id = $request['user_id']; // User ID being updated
    $current_user_id = get_current_user_id(); // Logged-in user ID

    // Check if the logged-in user matches the user_id
    if ($current_user_id !== intval($user_id)) {
        return new WP_Error('unauthorized', 'You are not allowed to edit this profile.', ['status' => 403]);
    }

    $cover_image = $request->get_file_params()['cover_image']; // Cover image file
    $avatar_image = $request->get_file_params()['avatar_image']; // Avatar image file
    $profile_description = $request['profile_description']; // Description field value

    // Load BuddyPress API functions if not already loaded
    if (!function_exists('bp_rest_members_cover_upload')) {
        require_once ABSPATH . 'wp-content/plugins/buddypress/bp-members/classes/class-bp-rest-members-endpoint.php';
    }

    // Update Cover Image
    if (!empty($cover_image)) {
        $_FILES['file'] = $cover_image;
        $result_cover = bp_rest_members_cover_upload(['user_id' => $user_id]);
        if (is_wp_error($result_cover)) {
            return new WP_Error('cover_update_failed', $result_cover->get_error_message(), ['status' => 400]);
        }
    }

    // Update Avatar
    if (!empty($avatar_image)) {
        $_FILES['file'] = $avatar_image;
        $result_avatar = bp_rest_members_avatar_upload(['user_id' => $user_id]);
        if (is_wp_error($result_avatar)) {
            return new WP_Error('avatar_update_failed', $result_avatar->get_error_message(), ['status' => 400]);
        }
    }

    // Update Profile Description (Field ID 16)
    if (!empty($profile_description)) {
        $field_id = 16; // Profile description field ID
        $field_update = xprofile_set_field_data($field_id, $user_id, $profile_description);
        if (!$field_update) {
            return new WP_Error('profile_update_failed', 'Failed to update profile description.', ['status' => 400]);
        }
    }

    return rest_ensure_response(['success' => true, 'message' => 'Profile updated successfully.']);
}