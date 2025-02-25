<?php

class Event_Joinned_Users_Controller
{

    const NAMESPACE = 'buddypress/v1';
    const ROUTE_BASE = '/event';
    private $table_name;
    public function __construct()
    {
        global $wpdb;

        $this->table_name = "{$wpdb->prefix}event_joined_users";

        // Register the routes when the API is initialized
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes()
    {
        // Define the CRUD routes
        register_rest_route(self::NAMESPACE , self::ROUTE_BASE . '/join', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'join_event'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
        register_rest_route(self::NAMESPACE , self::ROUTE_BASE . '/leave', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'leave_event'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
    }

    // Check permissions for API access
    public function check_permissions($request)
    {
        return is_user_logged_in();  // Adjust based on your requirements
    }
    function join_event(WP_REST_Request $request)
    {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'User not logged in',
            ), 401);
        }

        $event_id = $request->get_param('event_id');
        if (empty($event_id) || !get_post($event_id)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Invalid event ID',
            ), 400);
        }
        // Check if the user is a core member of the event
        $is_core_member = is_user_core_member_of_event($event_id, $user_id);

        // If the user is not a core member, return an error
        if ($is_core_member != false) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => "You are a $is_core_member of this event, you cannot join it",
            ), 403); // Forbidden
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'event_joined_users';

        // Check if the user has already joined the event and not left
        $existing_join = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE event_id = %d AND user_id = %d AND is_deleted = 0",
            $event_id,
            $user_id
        ));

        // If user is already joined, return a message
        if ($existing_join) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'User is already joined the event',
            ), 400);
        }

        // Check if the user has left the event (is_deleted = 1)
        $left_event = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE event_id = %d AND user_id = %d AND is_deleted = 1",
            $event_id,
            $user_id
        ));

        if ($left_event) {
            // If the user has left, rejoin them by setting is_deleted to 0
            $wpdb->update(
                $table_name,
                array('is_deleted' => 0),  // Set is_deleted back to 0 to mark them as joined
                array('event_id' => $event_id, 'user_id' => $user_id),
                array('%d'),
                array('%d', '%d')
            );

            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'User successfully rejoined the event',
            ), 200);
        }

        // If the user has never joined, join them now
        $wpdb->insert(
            $table_name,
            array(
                'event_id' => $event_id,
                'user_id' => $user_id,
                'joined_at' => current_time('mysql'),
                'is_deleted' => 0,  // Not deleted, user is actively joined
            )
        );

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'User successfully joined the event',
        ), 200);
    }

    function leave_event(WP_REST_Request $request)
    {
        // Get current user ID
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'User not logged in',
            ), 401);
        }

        // Get event ID from request
        $event_id = $request->get_param('event_id');
        if (!$event_id) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Event ID is required',
            ), 400);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'event_joined_users';

        // Check if the user is already joined to the event
        $user_event = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d AND event_id = %d AND is_deleted = 0",
            $user_id,
            $event_id
        ));

        if (!$user_event) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'User is not registered for this event',
            ), 400);
        }

        // Mark the user as deleted (soft delete)
        $wpdb->update(
            $table_name,
            array('is_deleted' => 1),
            array('user_id' => $user_id, 'event_id' => $event_id),
            array('%d'),
            array('%d', '%d')
        );

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'You have successfully left the event.',
        ), 200);
    }



}