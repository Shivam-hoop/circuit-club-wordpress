<?php

/**
 * 
 * validation functions
 * 
 */

/**
 * Check if the current user can delete the specified event.
 *
 * @param WP_REST_Request $request The request object.
 * @return bool True if the user has permission, false otherwise.
 */
function user_can_delete_event(WP_REST_Request $request)
{
    $event_id = intval($request['id']);
    $user_id = get_current_user_id();

    // Check if user is logged in
    if (!$user_id) {
        return false;
    }

    // Check if the event exists
    if (get_post_type($event_id) !== 'event') {
        return false;
    }

    // Check if the current user is the author of the event
    $event_author_id = get_post_field('post_author', $event_id);
    if ($event_author_id == $user_id || current_user_can('delete_post', $event_id)) {
        return true;
    }

    return false;
}
function is_user_business_member($user_id, $profile_field_id = 2, $required_member_type = 'Business')
{
    // Get the value of the profile field for the user
    $member_type = xprofile_get_field_data($profile_field_id, $user_id);

    // Check if the user has the required member type
    return $member_type === $required_member_type;
}


function validate_event_type($event_type)
{
    $valid_types = ['Race', 'Other'];
    if (!in_array($event_type, $valid_types, true)) {
        return new WP_Error('invalid_event_type', 'Event type must be "Race" or "Other".');
    }
    return true;
}


/**
 * Validate event dates to ensure the start date is less than or equal to the end date.
 */
function validate_event_dates($start_date, $end_date)
{
    $start = strtotime($start_date);
    $end = strtotime($end_date);

    if ($start === false || $end === false) {
        return new WP_Error('invalid_dates', 'Invalid date format.');
    }

    if ($start >= $end) {
        return new WP_Error('invalid_dates', 'Event start date must be less than the event end date.');
    }

    return true;
}

function validate_required_fields($event_type, $race_type, $event_booking_link)
{
    if ($event_type === 'race') {
        if (empty($race_type) || empty($event_booking_link)) {
            return new WP_Error('missing_required_fields', 'Race type, and event booking link are required for race events.');
        }
    }
    return true;
}

function validate_event_fields($event_data)
{
    // Validate event type
    $event_type = sanitize_text_field($event_data['event_type']);
    $event_type_validation = validate_event_type($event_type);
    if (is_wp_error($event_type_validation)) {
        return $event_type_validation; // Return the error
    }

    // Validate event dates
    $start_date = sanitize_text_field($event_data['event_start_date']);
    $end_date = sanitize_text_field($event_data['event_end_date']);
    $date_validation = validate_event_dates($start_date, $end_date);
    if (is_wp_error($date_validation)) {
        return $date_validation; // Return the error
    }

    // Validate required fields based on event type
    $race_type = sanitize_text_field($event_data['race_type']);
    $event_booking_link = esc_url_raw($event_data['event_booking_link']);
    $required_fields_validation = validate_required_fields($event_type, $race_type, $event_booking_link);
    if (is_wp_error($required_fields_validation)) {
        return $required_fields_validation; // Return the error
    }

    return true; // If no errors, return true
}
