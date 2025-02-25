<?php
/**
 * 
 * helpers functions
 * 
 */

/**
 * Format event data into a structured array.
 *
 * @param WP_Post $post Event post object.
 * @return array Formatted event data.
 */
function format_event_data($post)
{
    global $wpdb;
    $event_banner_id = get_post_meta($post->ID, 'event_banner', true);
    $event_banner = get_field('event_banner', $post->ID); // This should be the S3 URL or attachment ID
    // If using S3 URLs directly, assign as is; otherwise, use AWS SDK to get URL if needed
    if (is_numeric($event_banner)) {
        $event_banner_url = wp_get_attachment_url($event_banner_id);
    } else {
        $event_banner_url = $event_banner; // S3 URL saved directly in the meta field
    }
    // Retrieve and format the event dates
    $event_start_date = get_field('event_start_date', $post->ID);
    $event_end_date = get_field('event_end_date', $post->ID);
    $formatted_event_start_date = $event_start_date ? format_date_to_ymd($event_start_date) : '';
    $formatted_event_end_date = $event_end_date ? format_date_to_ymd($event_end_date) : '';
    // Retrieve race_track_id (using the dynamic relationship now)
    $race_track_id = get_field('race_track', $post->ID);  // This will fetch the race_track_id (dynamic value)
    if ($race_track_id) {
        // Optionally, fetch additional race track details by joining your custom race track table
        $race_track = $wpdb->get_row($wpdb->prepare("
            SELECT track_name FROM {$wpdb->prefix}race_tracks WHERE id = %d
        ", $race_track_id));

        $race_track_name = $race_track ? $race_track->track_name : 'Unknown Track';
    } else {
        $race_track_name = 'N.A.';
    }    // Retrieve creator information
    $author_id = $post->post_author;


    // Format the event data
    return array(
        'id' => $post->ID,
        'title' => $post->post_title,
        'event_location' => get_field('event_location', $post->ID),
        'event_start_date' => $formatted_event_start_date,
        'event_end_date' => $formatted_event_end_date,
        'event_banner' => $event_banner_url,
        'event_type' => get_field('event_type', $post->ID),
        'race_track' => $race_track_name,
        'race_type' => get_field('race_type', $post->ID),
        'event_booking_link' => get_field('event_booking_link', $post->ID),
        'event_description' => $post->post_content,
        'created_by' => get_author_info($author_id),
    );
}
function get_author_info($author_id)
{
    $author_info = get_userdata($author_id);
    $author_profile_image = get_avatar_url($author_id);
    return array(
        'id' => $author_id,
        'name' => $author_info ? $author_info->display_name : 'Unknown',
        'profile_image' => $author_profile_image,
    );
}

function format_date_to_ymd($date)
{
    // Check if the date is in d/m/Y format
    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
        // Convert date from d/m/Y to Y-m-d
        $date = DateTime::createFromFormat('d/m/Y', $date);
        return $date ? $date->format('Y-m-d') : '';
    }

    // If the date is already in Y-m-d or other valid format for strtotime
    $timestamp = strtotime($date);
    return $timestamp ? date('Y-m-d', $timestamp) : '';
}

// Function to notify all users
function notify_all_users_about_event($post_id, $event_creator_id)
{
    // Get all users
    $users = get_users();

    foreach ($users as $user) {
        $user_id = $user->ID;

        // Ensure not to send notification to the event creator
        if ($user_id != $event_creator_id) {
            bp_notifications_add_notification([
                'user_id' => $user_id,
                'item_id' => $post_id, // Event post ID
                'secondary_item_id' => $event_creator_id, // The event creator
                'component_name' => 'events', // Custom component for events
                'component_action' => 'new_event', // Action name for the notification
                'date_notified' => bp_core_current_time(),
                'is_new' => 1,
            ]);
            send_realtime_notification_to_node($user_id);
            // emit_websocket_event($user_id);
            // send_websocket_notification($user_id);

        }
    }
}


function update_old_event_entries()
{
    global $wpdb;

    // Fetch all events that have a static race_track value (adjust the query based on your custom field format)
    $old_events = $wpdb->get_results("
        SELECT p.ID, pm.meta_value as race_track_name
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
        WHERE pm.meta_key = 'race_track' AND p.post_type = 'event'
    ");
    // Loop through each event and update the race_track field
    foreach ($old_events as $event) {
        // Get the race track name from the old static field
        $race_track_name = sanitize_text_field($event->race_track_name);

        // Query your custom race track table to get the race_track_id based on the name
        $race_track_id = $wpdb->get_var($wpdb->prepare("
            SELECT id FROM {$wpdb->prefix}race_tracks WHERE track_name = %s
        ", $race_track_name));

        if ($race_track_id) {
            // Update the event post with the correct race_track_id
            update_field('race_track', $race_track_id, $event->ID);
        } else {
            // Optionally, log an error if no matching race track is found
            error_log("No matching race track found for event ID: {$event->ID} with name: {$race_track_name}");
        }
    }
}

// Run the update process, this can be triggered once manually or via a WP Cron job
// update_old_event_entries();


function format_event_response_data($event, $logged_in_user_id)
{
    $author = get_userdata($event->post_author);
    $creator_info = [
        'id' => $author->ID,
        'name' => $author->display_name,
        'profile_image' => get_avatar_url($author->ID),
    ];

    return [
        'id' => $event->event_id,
        'title' => $event->post_title,
        'event_location' => get_post_meta($event->event_id, 'event_location', true),
        'event_start_date' => get_post_meta($event->event_id, 'event_start_date', true),
        'event_end_date' => get_post_meta($event->event_id, 'event_end_date', true),
        'event_banner' => get_post_meta($event->event_id, 'event_banner', true),
        'event_type' => get_post_meta($event->event_id, 'event_type', true),
        'race_track' => $event->track_name ?? '',
        'race_type' => get_post_meta($event->event_id, 'race_type', true),
        'event_booking_link' => get_post_meta($event->event_id, 'event_booking_link', true),
        'event_description' => get_post_meta($event->event_id, 'event_description', true),
        'created_by' => $creator_info,
        'is_joined' => is_user_joined_event($event->event_id, $logged_in_user_id),
        'is_user_core_member_of_event' => is_user_core_member_of_event($event->event_id, $logged_in_user_id),

    ];
}

// Schedule the event if it hasn't been scheduled yet
if (!wp_next_scheduled('daily_event_cleanup')) {
    wp_schedule_event(time(), 'daily', 'daily_event_cleanup');
}

// Hook into the scheduled action
add_action('daily_event_cleanup', 'soft_delete_expired_events');

function soft_delete_expired_events()
{
    $today_date = date('Y-m-d'); // Today's date in Y-m-d format

    // Query all events with an end date of today or earlier
    $query = new WP_Query(array(
        'post_type' => 'event',
        'meta_query' => array(
            array(
                'key' => 'event_end_date',
                'value' => $today_date,
                'compare' => '<=',
                'type' => 'DATE'
            ),
        ),
        'post_status' => 'publish', // Only published events
        'posts_per_page' => -1, // No limit
    ));

    // Soft delete the events
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();

            // Update post status to 'draft' or custom status for soft delete
            wp_update_post(array(
                'ID' => $post_id,
                'post_status' => 'trash',
            ));

        }
    }

    // Reset the post data
    wp_reset_postdata();
}
function is_user_core_member_of_event($event_id, $user_id = null)
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

    // Query the database to check if the user is a core member of the event
    $core_member = $wpdb->get_row($wpdb->prepare("
        SELECT role 
        FROM {$wpdb->prefix}event_organizers 
        WHERE event_id = %d AND user_id = %d AND is_deleted = 0
    ", $event_id, $user_id));

    // If a record is found, return the role; otherwise, return false
    return $core_member ? $core_member->role : false;
}