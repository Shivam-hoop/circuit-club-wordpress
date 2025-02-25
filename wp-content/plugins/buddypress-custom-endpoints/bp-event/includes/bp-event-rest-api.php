<?php

function register_events_rest_routes()
{
    register_rest_route('buddypress/v1', '/events', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'handle_get_events',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ));
    register_rest_route('buddypress/v1', '/events/all', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'get_events',
    ));
    register_rest_route('buddypress/v1', '/event/(?P<event_id>\d+)', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'get_specific_event',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ));

    register_rest_route('buddypress/v1', '/events', array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'create_event',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ));

    register_rest_route('buddypress/v1', '/events/(?P<event_id>\d+)', array(
        'methods' => WP_REST_Server::EDITABLE,
        'callback' => 'update_event',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ));

    register_rest_route('buddypress/v1', '/events/(?P<id>\d+)', array(
        'methods' => 'DELETE',
        'callback' => 'delete_event',
        'permission_callback' => 'user_can_delete_event',
    ));
    register_rest_route('buddypress/v1', '/event-gallery/add', array(
        'methods' => 'POST',
        'callback' => 'add_event_gallery_images',
        'permission_callback' => function () {
            return is_user_logged_in();
        },

    ));
    register_rest_route('buddypress/v1', '/event-gallery/view', array(
        'methods' => 'GET',
        'callback' => 'get_event_gallery',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ));
    register_rest_route('buddypress/v1', '/event-gallery/edit', array(
        'methods' => 'POST',
        'callback' => 'edit_event_gallery',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ));
    register_rest_route('buddypress/v1', '/events/mine', array(
        'methods' => 'GET',
        'callback' => 'handle_get_user_events',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ));
}

add_action('rest_api_init', 'register_events_rest_routes');


/**
 * 
 * event crud operations start
 * 
 */

function create_event(WP_REST_Request $request)
{
    // Get the current user's ID
    $user_id = get_current_user_id();

    if (!$user_id) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'User not logged in',
        ), 401);
    }

    // Check if the user is a Business member
    if (!is_user_business_member($user_id)) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'Only Business members can create events',
        ), 403);
    }

    // Get the parameters from the request
    $event_data = [
        'title' => sanitize_text_field($request->get_param('title')),
        'event_type' => sanitize_text_field($request->get_param('event_type')),
        'event_start_date' => sanitize_text_field($request->get_param('event_start_date')),
        'event_end_date' => sanitize_text_field($request->get_param('event_end_date')),
        'race_type' => sanitize_text_field($request->get_param('race_type')),
        'race_track' => sanitize_text_field($request->get_param('race_track_id')),
        'event_booking_link' => esc_url_raw($request->get_param('event_booking_link')),
        'event_location' => sanitize_text_field($request->get_param('event_location')),
        'event_description' => wp_kses_post($request->get_param('event_description')),
        'event_address' => sanitize_text_field($request->get_param('event_address')), // New field
        'event_address_zip' => sanitize_text_field($request->get_param('event_address_zip')), // New field
        'status' => $request->get_param('status') ? sanitize_text_field($request->get_param('status')) : 'publish',
    ];

    // Perform validation for all event fields
    $validation_result = validate_event_fields($event_data);
    if (is_wp_error($validation_result)) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => $validation_result->get_error_message(),
        ), 400); // Bad request
    }

    // ACF Fields and image handling
    $event_banner = $request->get_file_params()['event_banner'] ?? null;
    if ($event_banner) {
        $event_banner_url = upload_single_media_to_s3($event_banner, 'events', 'circuit-club');
        if (is_wp_error($event_banner_url)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Failed to upload banner to S3',
                'error' => $event_banner_url->get_error_message(),
            ), 500);
        }
    } else {
        $event_banner_url = null; // Handle no image scenario
    }

    // Create the event post
    $post_id = wp_insert_post(array(
        'post_title' => $event_data['title'],
        'post_content' => $event_data['event_description'],
        'post_type' => 'event',
        'post_status' => $event_data['status'],
    ));

    if (!is_wp_error($post_id)) {
        // Update ACF fields
        update_field('event_location', $event_data['event_location'], $post_id);
        update_field('event_start_date', $event_data['event_start_date'], $post_id);
        update_field('event_end_date', $event_data['event_end_date'], $post_id);
        if ($event_banner_url) {
            update_field('event_banner', $event_banner_url, $post_id);
        }
        update_field('event_type', $event_data['event_type'], $post_id);
        update_field('race_type', $event_data['race_type'], $post_id);
        update_field('event_booking_link', $event_data['event_booking_link'], $post_id);
        update_field('event_description', $event_data['event_description'], $post_id);
        update_field('event_address', $event_data['event_address'], $post_id); // Save new field
        update_field('event_address_zip', $event_data['event_address_zip'], $post_id);
        update_field('race_track', $event_data['race_track'], $post_id);
        // Notify all users about the new event

        // Assign event creator as the organizer
        $add_organizer_result = add_event_organizer($post_id, $user_id, 'organizer');
        if (is_wp_error($add_organizer_result)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Event created, but failed to assign organizer role',
                'error' => $add_organizer_result->get_error_message(),
            ), 500);
        }
        // notify_all_users_about_event($post_id, $user_id);

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Event created successfully!',
            'event_id' => $post_id,
        ), 200);
    } else {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'Failed to create event',
            'error' => $post_id->get_error_message(),
        ), 500);
    }
}
/***
 * get all events
 * 
 */
function get_events(WP_REST_Request $request)
{
    $page = $request->get_param('page') ?: 1;
    $per_page = $request->get_param('per_page') ?: 10;

    $args = array(
        'post_type' => 'event',
        'post_status' => 'publish',
        'posts_per_page' => $per_page,
        'paged' => $page,
        'orderby' => 'date',
        'order' => 'DESC',
    );

    $query = new WP_Query($args);
    $events = [];

    foreach ($query->posts as $post) {
        $events[] = format_event_data($post);
    }

    return new WP_REST_Response(array(
        'success' => true,
        'data' => $events,
        'pagination' => array(
            'total' => $query->found_posts,
            'total_pages' => $query->max_num_pages,
            'current_page' => (int) $page,
            'per_page' => (int) $per_page,
        ),
    ), 200);
}

// function handle_get_events(WP_REST_Request $request)
// {
//     global $wpdb;
//     $logged_in_user_id = get_current_user_id();
//     // Get parameters from the request
//     $user_id = intval($request->get_param('user_id'));
//     $page = intval($request->get_param('page')) ?: 1;
//     $per_page = intval($request->get_param('per_page')) ?: 10;
//     $sort_by = sanitize_text_field($request->get_param('sort_by')) ?: 'post_date';
//     $sort_order = sanitize_text_field($request->get_param('sort_order')) ?: 'DESC'; // Default sorting order
//     $location = sanitize_text_field($request->get_param('location'));
//     $start_date = sanitize_text_field($request->get_param('start_date'));
//     $end_date = sanitize_text_field($request->get_param('end_date'));
//     $race_tracks = $request->get_param('race_tracks'); // Expecting a comma-separated string
//     $organizers = $request->get_param('organizers'); // Expecting a comma-separated string
//     $event_type = sanitize_text_field($request->get_param('event_type')); // New parameter for event type
//     $joined = $request->get_param('joined') === 'true'; // New parameter to filter joined events

//     // Convert comma-separated lists to arrays
//     if ($race_tracks) {
//         $race_tracks = array_map('intval', explode(',', $race_tracks));  // Convert to an array of integers
//     }
//     if ($organizers) {
//         $organizers = array_map('intval', explode(',', $organizers));  // Convert to an array of integers
//     }

//     // Allowed columns for sorting and their respective table aliases
//     $allowed_sort_columns = [
//         'post_date' => 'p',
//         'post_title' => 'p',
//         'post_modified' => 'p',
//         'track_name' => 'rt',
//         'event_location' => 'pm_loc',
//         'display_name' => 'u',
//         'event_start_date' => 'pm_start_date',
//     ];
//     if (!array_key_exists($sort_by, $allowed_sort_columns)) {
//         return rest_ensure_response([
//             'success' => false,
//             'message' => 'Invalid sort_by parameter',
//         ]);
//     }

//     // Get the appropriate table alias for the sort column
//     $table_alias = $allowed_sort_columns[$sort_by];
//     if ($sort_by === 'event_location' || $sort_by === 'event_start_date') {
//         $sort_by = 'meta_value'; // Change the sort column to meta_value for event_location and event_start_date
//     }

//     // Prepare the base SQL query with necessary joins and filters
//     $sql = "
//         SELECT p.ID AS event_id, p.post_title, p.post_author, rt.track_name, eo.user_id AS organizer_id, pm_start_date.meta_value AS event_start_date
//         FROM {$wpdb->posts} p
//         LEFT JOIN {$wpdb->prefix}event_organizers eo ON p.ID = eo.event_id
//         LEFT JOIN {$wpdb->prefix}postmeta pm_race_track ON p.ID = pm_race_track.post_id AND pm_race_track.meta_key = 'race_track'
//         LEFT JOIN {$wpdb->prefix}race_tracks rt ON rt.id = pm_race_track.meta_value
//         LEFT JOIN {$wpdb->users} u ON u.ID = eo.user_id
//         LEFT JOIN {$wpdb->prefix}postmeta pm_start_date ON p.ID = pm_start_date.post_id AND pm_start_date.meta_key = 'event_start_date'
//         LEFT JOIN {$wpdb->prefix}postmeta pm_loc ON p.ID = pm_loc.post_id AND pm_loc.meta_key = 'event_location'
//     ";

//     // Add a join for the event_joined_users table if filtering by joined events
//     if ($joined) {
//         $sql .= " INNER JOIN {$wpdb->prefix}event_joined_users ej ON ej.event_id = p.ID AND ej.is_deleted = 0 AND ej.user_id = %d";
//     }

//     $sql .= " WHERE p.post_type = 'event' AND p.post_status = 'publish'";

//     // Add filters to the SQL query
//     if ($joined) {
//         $sql = $wpdb->prepare($sql, $logged_in_user_id); // Bind the user ID if filtering by joined events
//     }

//     if ($user_id) {
//         $sql .= $wpdb->prepare(" AND p.post_author = %d", $user_id);
//     }
//     if ($location) {
//         $sql .= $wpdb->prepare(" AND EXISTS (SELECT 1 FROM {$wpdb->postmeta} pm WHERE pm.post_id = p.ID AND pm.meta_key = 'event_location' AND pm.meta_value LIKE %s)", '%' . $wpdb->esc_like($location) . '%');
//     }
//     if ($start_date) {
//         $sql .= $wpdb->prepare(" AND EXISTS (SELECT 1 FROM {$wpdb->postmeta} pm WHERE pm.post_id = p.ID AND pm.meta_key = 'event_start_date' AND pm.meta_value >= %s)", $start_date);
//     }
//     if ($end_date) {
//         $sql .= $wpdb->prepare(" AND EXISTS (SELECT 1 FROM {$wpdb->postmeta} pm WHERE pm.post_id = p.ID AND pm.meta_key = 'event_end_date' AND pm.meta_value <= %s)", $end_date);
//     }
//     if ($race_tracks) {
//         $placeholders = implode(',', array_fill(0, count($race_tracks), '%d'));
//         $sql .= $wpdb->prepare(" AND pm_race_track.meta_value IN ($placeholders)", ...$race_tracks);
//     }
//     if ($organizers) {
//         $placeholders = implode(',', array_fill(0, count($organizers), '%d'));
//         $sql .= $wpdb->prepare(" AND eo.user_id IN ($placeholders)", ...$organizers);
//     }
//     if ($event_type) {
//         $sql .= $wpdb->prepare(" AND EXISTS (SELECT 1 FROM {$wpdb->postmeta} pm WHERE pm.post_id = p.ID AND pm.meta_key = 'event_type' AND pm.meta_value LIKE %s)", '%' . $wpdb->esc_like($event_type) . '%');
//     }

//     // Adjust ORDER BY to use the correct table alias for sorting
//     $sql .= " GROUP BY p.ID ORDER BY $table_alias.$sort_by $sort_order";

//     // Apply pagination
//     $offset = ($page - 1) * $per_page;
//     $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $per_page, $offset);

//     // Execute the query
//     $events = $wpdb->get_results($sql);

//     // Count total events for pagination
//     $total_events_sql = "
//         SELECT COUNT(*) 
//         FROM {$wpdb->posts} p
//         WHERE p.post_type = 'event' AND p.post_status = 'publish'
//     ";
//     $total_events = $wpdb->get_var($total_events_sql);
//     $event_data = array_map(function ($event) use ($logged_in_user_id) {
//         return format_event_response_data($event, $logged_in_user_id);
//     }, $events);

//     // // Prepare the response data
//     // $event_data = [];
//     // foreach ($events as $event) {
//     //     $author = get_userdata($event->post_author);
//     //     $creator_info = [
//     //         'id' => $author->ID,
//     //         'name' => $author->display_name,
//     //         'profile_image' => get_avatar_url($author->ID)
//     //     ];

//     //     $event_location = get_post_meta($event->event_id, 'event_location', true);
//     //     $event_start_date = get_post_meta($event->event_id, 'event_start_date', true);
//     //     $event_end_date = get_post_meta($event->event_id, 'event_end_date', true);
//     //     $event_banner = get_post_meta($event->event_id, 'event_banner', true);
//     //     $event_type = get_post_meta($event->event_id, 'event_type', true);
//     //     $event_description = get_post_meta($event->event_id, 'event_description', true);
//     //     $event_booking_link = get_post_meta($event->event_id, 'event_booking_link', true);
//     //     $race_type = get_post_meta($event->event_id, 'race_type', true);

//     //     // Check if the logged-in user has joined the event
//     //     $is_joined = is_user_joined_event($event->event_id, $logged_in_user_id);

//     //     $event_data[] = [
//     //         'id' => $event->event_id,
//     //         'title' => $event->post_title,
//     //         'event_location' => $event_location,
//     //         'event_start_date' => $event_start_date,
//     //         'event_end_date' => $event_end_date,
//     //         'event_banner' => $event_banner,
//     //         'event_type' => $event_type,
//     //         'race_track' => $event->track_name ?? '',
//     //         'race_type' => $race_type,
//     //         'event_booking_link' => $event_booking_link,
//     //         'event_description' => $event_description,
//     //         'created_by' => $creator_info,
//     //         'is_joined' => $is_joined
//     //     ];
//     // }

//     // Pagination details
//     $pagination = [
//         'total' => $total_events,
//         'total_pages' => ceil($total_events / $per_page),
//         'current_page' => $page,
//         'per_page' => $per_page
//     ];

//     return rest_ensure_response([
//         'success' => true,
//         'data' => $event_data,
//         'pagination' => $pagination
//     ]);
// }

function handle_get_events(WP_REST_Request $request)
{
    global $wpdb;
    $logged_in_user_id = get_current_user_id();

    // Get parameters from the request
    $user_id = intval($request->get_param('user_id'));
    $page = intval($request->get_param('page')) ?: 1;
    $per_page = intval($request->get_param('per_page')) ?: 10;
    $sort_by = sanitize_text_field($request->get_param('sort_by')) ?: 'post_date';
    $sort_order = sanitize_text_field($request->get_param('sort_order')) ?: 'DESC';
    $location = sanitize_text_field($request->get_param('location'));
    $start_date = sanitize_text_field($request->get_param('start_date'));
    $end_date = sanitize_text_field($request->get_param('end_date'));
    $race_tracks = $request->get_param('race_tracks');
    $organizers = $request->get_param('organizers');
    $is_event_race = $request->get_param('is_event_race') === 'true';
    $is_event_other = $request->get_param('is_event_other') === 'true';
    $joined = $request->get_param('joined') === 'true';

    // Convert comma-separated lists to arrays
    if ($race_tracks) {
        $race_tracks = array_map('intval', explode(',', $race_tracks));
    }
    if ($organizers) {
        $organizers = array_map('intval', explode(',', $organizers));
    }

    // Allowed columns for sorting and their respective table aliases
    $allowed_sort_columns = [
        'post_date' => 'p',
        'post_title' => 'p',
        'post_modified' => 'p',
        'track_name' => 'rt',
        'event_location' => 'pm_loc',
        'display_name' => 'u',
        'event_start_date' => 'pm_start_date',
    ];
    if (!array_key_exists($sort_by, $allowed_sort_columns)) {
        return rest_ensure_response([
            'success' => false,
            'message' => 'Invalid sort_by parameter',
        ]);
    }

    // Get the appropriate table alias for the sort column
    $table_alias = $allowed_sort_columns[$sort_by];
    if ($sort_by === 'event_location' || $sort_by === 'event_start_date') {
        $sort_by = 'meta_value';
    }

    // Prepare the base SQL query
    $sql = "
        SELECT p.ID AS event_id, p.post_title, p.post_author, rt.track_name, eo.user_id AS organizer_id, pm_start_date.meta_value AS event_start_date
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->prefix}event_organizers eo ON p.ID = eo.event_id
        LEFT JOIN {$wpdb->prefix}postmeta pm_race_track ON p.ID = pm_race_track.post_id AND pm_race_track.meta_key = 'race_track'
        LEFT JOIN {$wpdb->prefix}race_tracks rt ON rt.id = pm_race_track.meta_value
        LEFT JOIN {$wpdb->users} u ON u.ID = eo.user_id
        LEFT JOIN {$wpdb->prefix}postmeta pm_start_date ON p.ID = pm_start_date.post_id AND pm_start_date.meta_key = 'event_start_date'
        LEFT JOIN {$wpdb->prefix}postmeta pm_loc ON p.ID = pm_loc.post_id AND pm_loc.meta_key = 'event_location'
    ";

    if ($joined) {
        $sql .= " INNER JOIN {$wpdb->prefix}event_joined_users ej ON ej.event_id = p.ID AND ej.is_deleted = 0 AND ej.user_id = %d";
    }

    $sql .= " WHERE p.post_type = 'event' AND p.post_status = 'publish'";

    if ($joined) {
        $sql = $wpdb->prepare($sql, $logged_in_user_id);
    }

    if ($user_id) {
        $sql .= $wpdb->prepare(" AND p.post_author = %d", $user_id);
    }
    if ($location) {
        $sql .= $wpdb->prepare(" AND EXISTS (SELECT 1 FROM {$wpdb->postmeta} pm WHERE pm.post_id = p.ID AND pm.meta_key = 'event_location' AND pm.meta_value LIKE %s)", '%' . $wpdb->esc_like($location) . '%');
    }
    if ($start_date) {
        $sql .= $wpdb->prepare(" AND EXISTS (SELECT 1 FROM {$wpdb->postmeta} pm WHERE pm.post_id = p.ID AND pm.meta_key = 'event_start_date' AND pm.meta_value >= %s)", $start_date);
    }
    if ($end_date) {
        $sql .= $wpdb->prepare(" AND EXISTS (SELECT 1 FROM {$wpdb->postmeta} pm WHERE pm.post_id = p.ID AND pm.meta_key = 'event_start_date' AND pm.meta_value <= %s)", $end_date);
    }
    if ($race_tracks) {
        $placeholders = implode(',', array_fill(0, count($race_tracks), '%d'));
        $sql .= $wpdb->prepare(" AND pm_race_track.meta_value IN ($placeholders)", ...$race_tracks);
    }
    if ($organizers) {
        $placeholders = implode(',', array_fill(0, count($organizers), '%d'));
        $sql .= $wpdb->prepare(" AND eo.user_id IN ($placeholders)", ...$organizers);
    }

    // Add event type filtering
    if ($is_event_race && $is_event_other) {
        $sql .= " AND EXISTS (
            SELECT 1 FROM {$wpdb->postmeta} pm 
            WHERE pm.post_id = p.ID 
            AND pm.meta_key = 'event_type' 
            AND pm.meta_value IN ('Race', 'Other')
        )";
    } elseif ($is_event_race) {
        $sql .= " AND EXISTS (
            SELECT 1 FROM {$wpdb->postmeta} pm 
            WHERE pm.post_id = p.ID 
            AND pm.meta_key = 'event_type' 
            AND pm.meta_value = 'Race'
        )";
    } elseif ($is_event_other) {
        $sql .= " AND EXISTS (
            SELECT 1 FROM {$wpdb->postmeta} pm 
            WHERE pm.post_id = p.ID 
            AND pm.meta_key = 'event_type' 
            AND pm.meta_value = 'Other'
        )";
    }

    $sql .= " GROUP BY p.ID ORDER BY $table_alias.$sort_by $sort_order";

    // Pagination
    $offset = ($page - 1) * $per_page;
    $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $per_page, $offset);

    $events = $wpdb->get_results($sql);

    // Count total events for pagination
    $total_events_sql = "
        SELECT COUNT(*) 
        FROM {$wpdb->posts} p
        WHERE p.post_type = 'event' AND p.post_status = 'publish'
    ";
    $total_events = $wpdb->get_var($total_events_sql);

    $event_data = array_map(function ($event) use ($logged_in_user_id) {
        return format_event_response_data($event, $logged_in_user_id);
    }, $events);

    $pagination = [
        'total' => $total_events,
        'total_pages' => ceil($total_events / $per_page),
        'current_page' => $page,
        'per_page' => $per_page
    ];

    return rest_ensure_response([
        'success' => true,
        'data' => $event_data,
        'pagination' => $pagination
    ]);
}



// function get_specific_event(WP_REST_Request $request)
// {
//     global $wpdb;

//     $post_id = intval($request->get_param('event_id'));
//     $logged_in_user_id = get_current_user_id();

//     // Check if the post exists
//     $post = get_post($post_id);

//     // Check if the post is in trash
//     if ($post && $post->post_status === 'trash') {
//         return new WP_REST_Response(array(
//             'success' => false,
//             'message' => 'Event is deleted and no longer available',
//         ), 410); // HTTP 410 Gone status code
//     }

//     // If the post doesn't exist or it's not published, return a not found message
//     if (!$post || get_post_status($post_id) === false) {
//         return new WP_REST_Response(array(
//             'success' => false,
//             'message' => 'Event not found',
//         ), 404);
//     }
//     $event_banner_id = get_post_meta($post->ID, 'event_banner', true);
//     $event_banner = get_field('event_banner', $post_id); // S3 URL or attachment ID

//     // Resolve event banner URL
//     if (is_numeric($event_banner)) {
//         $event_banner_url = wp_get_attachment_url($event_banner_id);
//     } else {
//         $event_banner_url = $event_banner;
//     }

//     // Retrieve and format the event dates
//     $event_start_date = get_field('event_start_date', $post->ID);
//     $event_end_date = get_field('event_end_date', $post->ID);
//     $formatted_event_start_date = $event_start_date ? format_date_to_ymd($event_start_date) : '';
//     $formatted_event_end_date = $event_end_date ? format_date_to_ymd($event_end_date) : '';

//     // Retrieve creator information
//     $author_id = $post->post_author;

//     // Fetch organizers, co-organizers, and partners from the custom table
//     $roles = ['organizer', 'co-organizer', 'partner'];
//     $formatted_roles = [];

//     foreach ($roles as $role) {
//         $results = $wpdb->get_results(
//             $wpdb->prepare(
//                 "SELECT user_id, website FROM {$wpdb->prefix}event_organizers WHERE event_id = %d AND role = %s AND is_deleted = 0",
//                 $post_id,
//                 $role
//             )
//         );

//         // Format user data
//         $formatted_roles[$role] = array_map(function ($result) use ($role) {
//             $user_info = get_userdata($result->user_id);
//             $company_website = xprofile_get_field_data('Company Website', $result->user_id); // Replace with actual field name/ID

//             return [
//                 'ID' => (int) $result->user_id,
//                 'display_name' => $user_info ? $user_info->display_name : 'Unknown',
//                 'email' => $user_info ? $user_info->user_email : '',
//                 'company_website' => $company_website ?? '',
//                 'avatar' => get_avatar_url($result->user_id),
//                 'role' => $role,  // Add role field
//             ];
//         }, $results);
//     }

//     // Merge all roles into a single array for the 'organizer_co_org_partner' field
//     $organizer_co_org_partner = array_merge(
//         $formatted_roles['organizer'],
//         $formatted_roles['co-organizer'],
//         $formatted_roles['partner']
//     );

//     // Reindex the array
//     $organizer_co_org_partner = array_values($organizer_co_org_partner);

//     // Get the count of users who joined the event
//     $joined_users_count = $wpdb->get_var(
//         $wpdb->prepare(
//             "SELECT COUNT(*) FROM {$wpdb->prefix}event_joined_users WHERE event_id = %d AND is_deleted = 0",
//             $post_id
//         )
//     );
//     $race_track_id = get_field('race_track', $post->ID);
//     $race_track_name = $wpdb->get_var(
//         $wpdb->prepare(
//             "SELECT track_name FROM {$wpdb->prefix}race_tracks WHERE id = %d",
//             $race_track_id
//         )
//     );

//     $event = array(
//         'id' => $post->ID,
//         'title' => $post->post_title,
//         'event_location' => get_field('event_location', $post->ID),
//         'event_start_date' => $formatted_event_start_date,
//         'event_end_date' => $formatted_event_end_date,
//         'event_banner' => $event_banner_url ?? '',
//         'event_type' => get_field('event_type', $post->ID),
//         'race_track' => $race_track_name,
//         'race_type' => get_field('race_type', $post->ID),
//         'event_address' => get_field('event_address', $post->ID),
//         'event_address_zip' => get_field('event_address_zip', $post->ID),
//         'event_booking_link' => get_field('event_booking_link', $post->ID),
//         'event_description' => $post->post_content,
//         'created_by' => get_author_info($author_id),
//         'organizer_co_org_partner' => $organizer_co_org_partner,  // Single merged array
//         'joined_users_count' => (int) $joined_users_count, // New count of users joined
//         'is_joined' => is_user_joined_event($post->ID, $logged_in_user_id),

//     );

//     return new WP_REST_Response(array(
//         'success' => true,
//         'data' => $event,
//     ), 200);
// }


function get_specific_event(WP_REST_Request $request)
{
    global $wpdb;

    $post_id = intval($request->get_param('event_id'));
    $logged_in_user_id = get_current_user_id();

    // Check if the post exists
    $post = get_post($post_id);

    // Check if the post is in trash
    if ($post && $post->post_status === 'trash') {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'Event is deleted and no longer available',
        ), 410); // HTTP 410 Gone status code
    }

    // If the post doesn't exist or it's not published, return a not found message
    if (!$post || get_post_status($post_id) === false) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'Event not found',
        ), 404);
    }

    $event_banner_id = get_post_meta($post->ID, 'event_banner', true);
    $event_banner = get_field('event_banner', $post_id); // S3 URL or attachment ID

    // Resolve event banner URL
    if (is_numeric($event_banner)) {
        $event_banner_url = wp_get_attachment_url($event_banner_id);
    } else {
        $event_banner_url = $event_banner;
    }

    // Retrieve and format the event dates
    $event_start_date = get_field('event_start_date', $post->ID);
    $event_end_date = get_field('event_end_date', $post->ID);
    $formatted_event_start_date = $event_start_date ? format_date_to_ymd($event_start_date) : '';
    $formatted_event_end_date = $event_end_date ? format_date_to_ymd($event_end_date) : '';

    // Retrieve creator information
    $author_id = $post->post_author;

    // Fetch organizers, co-organizers, and partners from the custom table
    $roles = ['organizer', 'co-organizer', 'partner'];
    $formatted_roles = [];

    foreach ($roles as $role) {
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT user_id, website FROM {$wpdb->prefix}event_organizers WHERE event_id = %d AND role = %s AND is_deleted = 0",
                $post_id,
                $role
            )
        );

        // Format user data
        $formatted_roles[$role] = array_map(function ($result) use ($role) {
            $user_info = get_userdata($result->user_id);
            $company_website = xprofile_get_field_data('Company Website', $result->user_id); // Replace with actual field name/ID

            return [
                'ID' => (int) $result->user_id,
                'display_name' => $user_info ? $user_info->display_name : 'Unknown',
                'email' => $user_info ? $user_info->user_email : '',
                'company_website' => $company_website ?? '',
                'avatar' => get_avatar_url($result->user_id),
                'role' => $role,  // Add role field
            ];
        }, $results);
    }

    // Merge all roles into a single array for the 'organizer_co_org_partner' field
    $organizer_co_org_partner = array_merge(
        $formatted_roles['organizer'],
        $formatted_roles['co-organizer'],
        $formatted_roles['partner']
    );

    // Reindex the array
    $organizer_co_org_partner = array_values($organizer_co_org_partner);

    // Get the count of users who joined the event
    $joined_users_count = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}event_joined_users WHERE event_id = %d AND is_deleted = 0",
            $post_id
        )
    );

    // Initialize the latest_users_data array
    $latest_users_data = [];

    // Fetch the latest 5 users who joined the event only if there are users who joined
    if ($joined_users_count > 0) {
        $latest_joined_users = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->prefix}event_joined_users WHERE event_id = %d AND is_deleted = 0 ORDER BY joined_at DESC LIMIT 3",
                $post_id
            )
        );

        // Get the details (ID and display name) of the latest 5 users
        foreach ($latest_joined_users as $user) {
            $user_info = get_userdata($user->user_id);
            if ($user_info) {
                $latest_users_data[] = [
                    'ID' => $user_info->ID,
                    'display_name' => $user_info->display_name
                ];
            }
        }
    }

    $race_track_id = get_field('race_track', $post->ID);
    $race_track_name = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT track_name FROM {$wpdb->prefix}race_tracks WHERE id = %d",
            $race_track_id
        )
    );

    $event = array(
        'id' => $post->ID,
        'title' => $post->post_title,
        'event_location' => get_field('event_location', $post->ID),
        'event_start_date' => $formatted_event_start_date,
        'event_end_date' => $formatted_event_end_date,
        'event_banner' => $event_banner_url ?? '',
        'event_type' => get_field('event_type', $post->ID),
        'race_track' => $race_track_name,
        'race_type' => get_field('race_type', $post->ID),
        'event_address' => get_field('event_address', $post->ID),
        'event_address_zip' => get_field('event_address_zip', $post->ID),
        'event_booking_link' => get_field('event_booking_link', $post->ID),
        'event_description' => $post->post_content,
        'created_by' => get_author_info($author_id),
        'organizer_co_org_partner' => $organizer_co_org_partner,  // Single merged array
        'joined_users_count' => (int) $joined_users_count, // New count of users joined
        'is_joined' => is_user_joined_event($post->ID, $logged_in_user_id),
        'latest_joined_users' => $latest_users_data, // Add the latest 5 users if any
        'is_user_core_member_of_event' => is_user_core_member_of_event($post->ID, $logged_in_user_id),

    );

    return new WP_REST_Response(array(
        'success' => true,
        'data' => $event,
    ), 200);
}


function update_event(WP_REST_Request $request)
{
    $post_id = intval($request->get_param('event_id'));

    // Check if the post exists
    if (get_post_status($post_id) === false) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'Event not found',
        ), 404);
    }

    // Get parameters from the request
    $title = sanitize_text_field($request->get_param('title'));
    $status = $request->get_param('status') ? sanitize_text_field($request->get_param('status')) : 'publish';

    $event_location = sanitize_text_field($request->get_param('event_location'));
    $event_start_date = sanitize_text_field($request->get_param('event_start_date'));
    $event_end_date = sanitize_text_field($request->get_param('event_end_date'));

    // Handle S3 upload
    $event_banner = $request->get_file_params()['event_banner'] ?? null;
    if ($event_banner) {
        $event_banner_url = upload_single_media_to_s3($event_banner, 'events', 'circuit-club');
        if (is_wp_error($event_banner_url)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Failed to upload banner to S3',
                'error' => $event_banner_url->get_error_message(),
            ), 500);
        }

    } else {
        $event_banner_url = get_field('event_banner', $post_id); // Use existing banner if no new upload
    }
    update_field('event_banner', $event_banner_url, $post_id);

    $event_type = sanitize_text_field($request->get_param('event_type'));
    $race_track = sanitize_text_field($request->get_param('race_track'));
    $race_type = sanitize_text_field($request->get_param('race_type'));
    $event_booking_link = esc_url_raw($request->get_param('event_booking_link'));
    $event_description = wp_kses_post($request->get_param('event_description'));
    $race_type = sanitize_text_field($request->get_param('race_type'));
    $event_address = sanitize_text_field($request->get_param('event_address')); // New field
    $event_address_zip = sanitize_text_field($request->get_param('event_address_zip')); // New field


    // Update the event post
    $update_post = array(
        'ID' => $post_id,
        'post_title' => $title,
        'post_status' => $status,
        'post_content' => $event_description,
    );

    $updated_post_id = wp_update_post($update_post);

    if (!is_wp_error($updated_post_id)) {
        update_field('event_location', $event_location, $updated_post_id);
        update_field('event_start_date', $event_start_date, $updated_post_id);
        update_field('event_end_date', $event_end_date, $updated_post_id);
        update_field('event_type', $event_type, $updated_post_id);
        update_field('race_track', $race_track, $updated_post_id);
        update_field('race_type', $race_type, $updated_post_id);
        update_field('event_address', $event_address, $updated_post_id);
        update_field('event_address_zip', $event_address_zip, $updated_post_id);
        update_field('event_booking_link', $event_booking_link, $updated_post_id);

        // Return the updated event data
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Event updated successfully!',
            'data' => array(
                'id' => $updated_post_id,
                'title' => $title,
                'event_location' => $event_location,
                'event_start_date' => $event_start_date,
                'event_end_date' => $event_end_date,
                'event_banner' => $event_banner_url, // Return URL of the banner
                'event_type' => $event_type,
                'race_track' => $race_track,
                'race_type' => $race_type,
                'event_address' => $event_address,
                'event_address_zip' => $event_address_zip,
                'event_booking_link' => $event_booking_link,
                'event_description' => $event_description,
            ),
        ), 200);
    } else {
        // Handle the error in case of failure
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'Failed to update event',
            'error' => $updated_post_id->get_error_message(),
        ), 500);
    }
}

/**
 * Deletes an event and its associated gallery images.
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response The response object.
 */
function delete_event(WP_REST_Request $request)
{
    $event_id = intval($request['id']);

    // Check if the event exists
    if (get_post_type($event_id) !== 'event') {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'Event not found',
        ), 404);
    }

    // Check if the current user can delete the event
    if (!current_user_can('delete_post', $event_id)) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'You do not have permission to delete this event',
        ), 403);
    }

    // Delete the gallery images associated with the event
    delete_event_gallery_images($event_id);

    // Delete the event post
    $deleted = wp_delete_post($event_id, true); // true indicates a force delete

    if ($deleted) {
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Event and its gallery deleted successfully',
        ), 200);
    } else {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'Failed to delete the event',
        ), 500);
    }
}

/**
 * Check if the logged-in user has joined a specific event.
 *
 * @param int $event_id The ID of the event to check.
 * @param int|null $user_id The ID of the user (optional, defaults to the current logged-in user).
 * @return bool True if the user has joined the event, false otherwise.
 */
function is_user_joined_event($event_id, $user_id = null)
{
    global $wpdb;

    // Use the current logged-in user if no user ID is provided
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    // If no user is logged in, return false
    if (!$user_id) {
        return false;
    }

    // Query the database to check if the user has joined the event
    $is_joined = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) 
        FROM {$wpdb->prefix}event_joined_users 
        WHERE event_id = %d AND user_id = %d AND is_deleted = 0
    ", $event_id, $user_id)) > 0;

    return $is_joined;
}


// function handle_get_user_events(WP_REST_Request $request)
// {
//     global $wpdb;

//     // Get the current user's ID
//     $logged_in_user_id = get_current_user_id();

//     if (!$logged_in_user_id) {
//         return new WP_REST_Response(array(
//             'success' => false,
//             'message' => 'User not logged in',
//         ), 401);
//     }

//     // Check if the user is a business user
//     $is_business_user = is_user_business_member($logged_in_user_id);

//     // Get pagination parameters
//     $page = intval($request->get_param('page')) ?: 1;
//     $per_page = intval($request->get_param('per_page')) ?: 10;
//     $offset = ($page - 1) * $per_page;

//     // Fetch events the user has joined
//     $joined_events_sql = "
//         SELECT 
//             p.ID AS event_id, 
//             p.post_title, 
//             p.post_author, 
//             pm_start_date.meta_value AS event_start_date,
//             rt.track_name AS race_track
//         FROM 
//             {$wpdb->posts} p
//         LEFT JOIN 
//             {$wpdb->prefix}postmeta pm_start_date 
//             ON p.ID = pm_start_date.post_id 
//             AND pm_start_date.meta_key = 'event_start_date'
//         LEFT JOIN {$wpdb->prefix}postmeta pm_race_track
//             ON p.ID = pm_race_track.post_id 
//             AND pm_race_track.meta_key = 'race_track'
//         LEFT JOIN {$wpdb->prefix}race_tracks rt 
//             ON rt.id = pm_race_track.meta_value
//         LEFT JOIN 
//             {$wpdb->prefix}event_joined_users ep 
//             ON p.ID = ep.event_id
//         WHERE 
//             p.post_type = 'event'
//             AND p.post_status = 'publish'
//             AND ep.user_id = %d
//             AND ep.is_deleted = 0
//         LIMIT %d OFFSET %d
//     ";
//     $joined_events = $wpdb->get_results($wpdb->prepare($joined_events_sql, $logged_in_user_id, $per_page, $offset));

//     // Fetch events the user has created if they are a business user
//     $created_events = [];
//     if ($is_business_user) {
//         $created_events_sql = "
//             SELECT 
//                 p.ID AS event_id,
//                 p.post_title, 
//                 p.post_author, 
//                 pm_start_date.meta_value AS event_start_date,
//                 rt.track_name AS race_track
//             FROM 
//                 {$wpdb->posts} p
//             LEFT JOIN 
//                 {$wpdb->prefix}postmeta pm_start_date 
//                 ON p.ID = pm_start_date.post_id 
//                 AND pm_start_date.meta_key = 'event_start_date'
//             LEFT JOIN {$wpdb->prefix}postmeta pm_race_track 
//                 ON p.ID = pm_race_track.post_id 
//                 AND pm_race_track.meta_key = 'race_track'
//             LEFT JOIN {$wpdb->prefix}race_tracks rt 
//                 ON rt.id = pm_race_track.meta_value
//             WHERE 
//                 p.post_type = 'event'
//                 AND p.post_status = 'publish'
//                 AND p.post_author = %d
//             LIMIT %d OFFSET %d
//         ";
//         $created_events = $wpdb->get_results($wpdb->prepare($created_events_sql, $logged_in_user_id, $per_page, $offset));
//     }

//     // Fetch events where the user is an organizer, co-organizer, or partner
//     $organized_events_sql = "
//     SELECT 
//         p.ID AS event_id,
//         p.post_title,
//         p.post_author,
//         pm_start_date.meta_value AS event_start_date,
//         rt.track_name AS race_track
//     FROM 
//         {$wpdb->posts} p
//     LEFT JOIN 
//         {$wpdb->prefix}postmeta pm_start_date 
//         ON p.ID = pm_start_date.post_id 
//         AND pm_start_date.meta_key = 'event_start_date'
//     LEFT JOIN {$wpdb->prefix}postmeta pm_race_track 
//         ON p.ID = pm_race_track.post_id 
//         AND pm_race_track.meta_key = 'race_track'
//     LEFT JOIN {$wpdb->prefix}race_tracks rt 
//         ON rt.id = pm_race_track.meta_value
//     LEFT JOIN {$wpdb->prefix}event_organizers eo 
//         ON p.ID = eo.event_id
//     WHERE 
//         p.post_type = 'event'
//         AND p.post_status = 'publish'
//         AND eo.user_id = %d
//         AND eo.role IN ('co-organizer', 'partner')
//         AND eo.is_deleted = 0
//     LIMIT %d OFFSET %d
// ";

//     $organized_events = $wpdb->get_results($wpdb->prepare($organized_events_sql, $logged_in_user_id, $per_page, $offset));

//     // Combine joined, created, and organized events
//     $events = array_merge($joined_events, $created_events, $organized_events);

//     // Format the response data
//     $event_data = array_map(function ($event) use ($logged_in_user_id) {
//         $formatted_event = format_event_response_data($event, $logged_in_user_id);
//         $formatted_event['race_track'] = $event->race_track ?? '';
//         $formatted_event['is_joined'] = is_user_joined_event($event->event_id, $logged_in_user_id);
//         return $formatted_event;
//     }, $events);

//     // Get total count for pagination
//     $total_events_sql = "
//     SELECT COUNT(*)
//     FROM (
//         SELECT p.ID
//         FROM {$wpdb->posts} p
//         LEFT JOIN {$wpdb->prefix}event_joined_users ep ON p.ID = ep.event_id
//         WHERE p.post_type = 'event' AND p.post_status = 'publish' AND ep.user_id = %d
//         UNION
//         SELECT p.ID
//         FROM {$wpdb->posts} p
//         WHERE p.post_type = 'event' AND p.post_status = 'publish' AND p.post_author = %d
//         UNION
//         SELECT p.ID
//         FROM {$wpdb->posts} p
//         LEFT JOIN {$wpdb->prefix}event_organizers eo ON p.ID = eo.event_id
//         WHERE p.post_type = 'event' 
//             AND p.post_status = 'publish' 
//             AND eo.user_id = %d 
//             AND eo.role IN ('co-organizer', 'partner')
//             AND eo.is_deleted = 0
//     ) AS combined_events";


//     $total_events = $wpdb->get_var($wpdb->prepare($total_events_sql, $logged_in_user_id, $logged_in_user_id, $logged_in_user_id));

//     // Pagination details
//     $pagination = [
//         'total' => $total_events,
//         'total_pages' => ceil($total_events / $per_page),
//         'current_page' => $page,
//         'per_page' => $per_page
//     ];

//     return rest_ensure_response([
//         'success' => true,
//         'data' => $event_data,
//         'pagination' => $pagination
//     ]);
// }
function handle_get_user_events(WP_REST_Request $request)
{
    global $wpdb;

    // Get the current user's ID
    $logged_in_user_id = get_current_user_id();

    if (!$logged_in_user_id) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'User not logged in',
        ), 401);
    }

    // Check if the user is a business user
    $is_business_user = is_user_business_member($logged_in_user_id);

    // Get pagination parameters
    $page = intval($request->get_param('page')) ?: 1;
    $per_page = intval($request->get_param('per_page')) ?: 10;
    $offset = ($page - 1) * $per_page;

    // Fetch events the user has joined (with ordering by post date in descending order)
    $joined_events_sql = "
        SELECT 
            p.ID AS event_id, 
            p.post_title, 
            p.post_author, 
            p.post_date AS event_created_date, 
            rt.track_name AS race_track
        FROM 
            {$wpdb->posts} p
        LEFT JOIN {$wpdb->prefix}postmeta pm_race_track
            ON p.ID = pm_race_track.post_id 
            AND pm_race_track.meta_key = 'race_track'
        LEFT JOIN {$wpdb->prefix}race_tracks rt 
            ON rt.id = pm_race_track.meta_value
        LEFT JOIN 
            {$wpdb->prefix}event_joined_users ep 
            ON p.ID = ep.event_id
        WHERE 
            p.post_type = 'event'
            AND p.post_status = 'publish'
            AND ep.user_id = %d
            AND ep.is_deleted = 0
        ORDER BY p.post_date DESC
        LIMIT %d OFFSET %d
    ";
    $joined_events = $wpdb->get_results($wpdb->prepare($joined_events_sql, $logged_in_user_id, $per_page, $offset));

    // Fetch events the user has created if they are a business user (with ordering by post date in descending order)
    $created_events = [];
    if ($is_business_user) {
        $created_events_sql = "
            SELECT 
                p.ID AS event_id,
                p.post_title, 
                p.post_author, 
                p.post_date AS event_created_date, 
                rt.track_name AS race_track
            FROM 
                {$wpdb->posts} p
            LEFT JOIN {$wpdb->prefix}postmeta pm_race_track 
                ON p.ID = pm_race_track.post_id 
                AND pm_race_track.meta_key = 'race_track'
            LEFT JOIN {$wpdb->prefix}race_tracks rt 
                ON rt.id = pm_race_track.meta_value
            WHERE 
                p.post_type = 'event'
                AND p.post_status = 'publish'
                AND p.post_author = %d
            ORDER BY p.post_date DESC
            LIMIT %d OFFSET %d
        ";
        $created_events = $wpdb->get_results($wpdb->prepare($created_events_sql, $logged_in_user_id, $per_page, $offset));
    }

    // Fetch events where the user is an organizer, co-organizer, or partner (with ordering by post date in descending order)
    $organized_events_sql = "
    SELECT 
        p.ID AS event_id,
        p.post_title,
        p.post_author,
        p.post_date AS event_created_date,
        rt.track_name AS race_track
    FROM 
        {$wpdb->posts} p
    LEFT JOIN {$wpdb->prefix}postmeta pm_race_track 
        ON p.ID = pm_race_track.post_id 
        AND pm_race_track.meta_key = 'race_track'
    LEFT JOIN {$wpdb->prefix}race_tracks rt 
        ON rt.id = pm_race_track.meta_value
    LEFT JOIN {$wpdb->prefix}event_organizers eo 
        ON p.ID = eo.event_id
    WHERE 
        p.post_type = 'event'
        AND p.post_status = 'publish'
        AND eo.user_id = %d
        AND eo.role IN ('co-organizer', 'partner')
        AND eo.is_deleted = 0
    ORDER BY p.post_date DESC
    LIMIT %d OFFSET %d
";

    $organized_events = $wpdb->get_results($wpdb->prepare($organized_events_sql, $logged_in_user_id, $per_page, $offset));

    // Combine joined, created, and organized events
    $events = array_merge($joined_events, $created_events, $organized_events);

    // Format the response data
    $event_data = array_map(function ($event) use ($logged_in_user_id) {
        $formatted_event = format_event_response_data($event, $logged_in_user_id);
        $formatted_event['race_track'] = $event->race_track ?? '';
        $formatted_event['is_joined'] = is_user_joined_event($event->event_id, $logged_in_user_id);
        return $formatted_event;
    }, $events);

    // Get total count for pagination
    $total_events_sql = "
    SELECT COUNT(*)
    FROM (
        SELECT p.ID
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->prefix}event_joined_users ep ON p.ID = ep.event_id
        WHERE p.post_type = 'event' AND p.post_status = 'publish' AND ep.user_id = %d
        UNION
        SELECT p.ID
        FROM {$wpdb->posts} p
        WHERE p.post_type = 'event' AND p.post_status = 'publish' AND p.post_author = %d
        UNION
        SELECT p.ID
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->prefix}event_organizers eo ON p.ID = eo.event_id
        WHERE p.post_type = 'event' 
            AND p.post_status = 'publish' 
            AND eo.user_id = %d 
            AND eo.role IN ('co-organizer', 'partner')
            AND eo.is_deleted = 0
    ) AS combined_events";


    $total_events = $wpdb->get_var($wpdb->prepare($total_events_sql, $logged_in_user_id, $logged_in_user_id, $logged_in_user_id));

    // Pagination details
    $pagination = [
        'total' => $total_events,
        'total_pages' => ceil($total_events / $per_page),
        'current_page' => $page,
        'per_page' => $per_page
    ];

    return rest_ensure_response([
        'success' => true,
        'data' => $event_data,
        'pagination' => $pagination
    ]);
}

/**
 * 
 * event crud operations end
 * 
 */


/**
 * 
 * event gallery operations start
 * 
 */

/**
 * Deletes all gallery images associated with an event.
 *
 * @param int $event_id The event post ID.
 */
function delete_event_gallery_images($event_id)
{
    // Fetch gallery images associated with the event (assuming gallery images are stored as post meta)
    $gallery_images = get_post_meta($event_id, 'event_gallery_media', true);

    // if (is_array($gallery_images) && !empty($gallery_images)) {
    //     foreach ($gallery_images as $image_id) {
    //         // Delete each gallery image
    //         wp_delete_attachment($image_id, true); // true to force delete from the media library
    //     }
    // }

    // Optionally, delete the gallery meta entry
    delete_post_meta($event_id, 'event_gallery_media');
    delete_post_meta($event_id, 'event_gallery_media_type');
}


function add_event_gallery_images(WP_REST_Request $request)
{
    $post_id = intval($request->get_param('event_id'));

    // Check if the event exists
    if (get_post_status($post_id) === false) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'Event not found',
            'event_id' => $post_id
        ), 404);
    }

    // Handle multiple image uploads using the existing media upload function
    $files = $request->get_file_params();

    // Check if no images are provided
    if (empty($files['event_gallery']) || empty($files['event_gallery']['name'][0])) {
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'No images selected',
            'event_id' => $post_id
        ), 200); // Respond with 200 status code since this isn't an error
    }

    // Use the handle_media_upload function
    $uploaded_media = handle_media_upload($post_id, $files['event_gallery'], 'event_gallery');

    if (is_wp_error($uploaded_media)) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => $uploaded_media->get_error_message(),
        ), 500);
    }

    // Convert S3 URLs or other metadata to attachment IDs if necessary
    $gallery_images = array_map(function ($media) {
        // Additional logic may be needed if storing attachment data differently
        return $media['url'];
    }, $uploaded_media);

    if (!empty($gallery_images)) {
        // Get existing gallery images
        $existing_gallery = get_field('event_gallery', $post_id);
        if (!$existing_gallery) {
            $existing_gallery = array();
        }

        // Merge existing and new images
        $new_gallery = array_merge($existing_gallery, $gallery_images);

        // Update the ACF field with the gallery images
        update_field('event_gallery', $new_gallery, $post_id);

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Images added to gallery successfully!',
            // 'data' => $new_gallery,
            'event_id' => $post_id
        ), 200);
    } else {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'Failed to upload images',
        ), 500);
    }
}


function get_event_gallery(WP_REST_Request $request)
{
    $post_id = intval($request->get_param('event_id'));

    // Check if the event exists
    if (get_post_status($post_id) === false) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'Event not found',
        ), 404);
    }

    $gallery_images = get_field('event_gallery', $post_id);
    if (!empty($gallery_images)) {
        return new WP_REST_Response(array(
            'success' => true,
            // 'data' => array_map('wp_get_attachment_url', $gallery_images),
            'data' => $gallery_images,
        ), 200);
    } else {
        return new WP_REST_Response(array(
            'success' => true,
            // 'message' => 'No images found in the gallery',
            'data' => ''
        ), 200);
    }
}
function edit_event_gallery(WP_REST_Request $request)
{
    $post_id = intval($request->get_param('event_id'));

    // Check if the event exists
    if (get_post_status($post_id) === false) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'Event not found',
        ), 404);
    }

    // Check if the user is logged in
    if (!is_user_logged_in()) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'You must be logged in to edit this event gallery',
        ), 401);
    }

    // Check if the logged-in user is the author of the event
    $current_user_id = get_current_user_id();
    $event_author_id = get_post_field('post_author', $post_id);

    if ($current_user_id !== intval($event_author_id)) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'You do not have permission to edit this event gallery',
        ), 403);
    }

    // Handle multiple image uploads using the existing media upload function
    $files = $request->get_file_params();

    // Check if no images are provided
    if (empty($files['event_gallery']) || empty($files['event_gallery']['name'][0])) {
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'No images selected',
            'event_id' => $post_id
        ), 200); // Respond with 200 status code since this isn't an error
    }

    // Use the handle_media_upload function
    $uploaded_media = handle_media_upload($post_id, $files['event_gallery'], 'event_gallery');

    if (is_wp_error($uploaded_media)) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => $uploaded_media->get_error_message(),
        ), 500);
    }

    // Convert S3 URLs or other metadata to attachment IDs if necessary
    $gallery_images = array_map(function ($media) {
        // Additional logic may be needed if storing attachment data differently
        return $media['url'];
    }, $uploaded_media);

    // Replace existing gallery images with the new ones
    update_field('event_gallery', $gallery_images, $post_id);

    return new WP_REST_Response(array(
        'success' => true,
        'message' => 'Gallery updated successfully!',
        'event_id' => $post_id,
        'data' => $gallery_images
    ), 200);
}

function add_event_organizer($event_id, $user_id, $role)
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'event_organizers';
    $current_time = current_time('mysql');

    // Insert the organizer record
    $result = $wpdb->insert(
        $table_name,
        array(
            'event_id' => $event_id,
            'user_id' => $user_id,
            'role' => $role,
            'created_at' => $current_time,
            'updated_at' => $current_time,
            'is_deleted' => 0,
        ),
        array('%d', '%d', '%s', '%s', '%s', '%d')
    );

    // Handle errors
    if ($result === false) {
        return new WP_Error('db_insert_error', 'Failed to insert organizer into the database');
    }

    return true;
}


/**
 * 
 * event gallery operations end
 * 
 */
