<?php
/**
 * Plugin Name: Event organizer API
 * Description: add organizer in event.
 * Version: 1.0.0
 * Author: Shivam Jha
 * License: GPL2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include the necessary classes
require_once plugin_dir_path( __FILE__ ) . 'includes/class-event-joinned-users-controller.php';


/**
 * Register API routes for a given controller class.
 *
 * @param string $controller_class The controller class name.
 */
function register_api_event_joinned_routes( $controller_class ) {
    $controller = new $controller_class();
    $controller->register_routes();
}

// Register both controllers' routes using the same function
add_action( 'rest_api_init', function() {
    register_api_event_joinned_routes( 'Event_Joinned_Users_Controller' );
});
