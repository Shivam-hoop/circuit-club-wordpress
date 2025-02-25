<?php

class Notification_Controller
{

    const NAMESPACE = 'buddypress/v1';
    const ROUTE_BASE = '/notifications';
    private $bp_notifications_table;
    public function __construct()
    {
        global $wpdb;

        $this->bp_notifications_table = "{$wpdb->prefix}bp_notifications";

        // Register the routes when the API is initialized
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes()
    {

        register_rest_route(self::NAMESPACE , self::ROUTE_BASE . '/mark-all-read', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'mark_all_notifications_as_read'],
            'permission_callback' => [$this, 'loggedInUserCan'],
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
    function mark_all_notifications_as_read(WP_REST_Request $request)
    {
        $user_id = get_current_user_id();

        if (!$user_id) {
            return new WP_Error('no_user', 'User not logged in', array('status' => 401));
        }


        global $wpdb;

        // Mark all notifications as read for the logged-in user
        $result = $wpdb->update(
            $this->bp_notifications_table,
            array('is_new' => 0), // Update `is_new` to 0 (read)
            array('user_id' => $user_id, 'is_new' => 1) // Only unread notifications
        );

        if ($result === false) {
            return new WP_Error('db_error', 'Could not update notifications', array('status' => 500));
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'All notifications marked as read',
            'updated_count' => $result,
        ));
    }


}