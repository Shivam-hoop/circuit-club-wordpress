<?php
/**
 * Plugin Name: Race Track API
 * Description: Custom module for managing race tracks via BuddyPress REST API.
 * Version: 1.0.0
 * Author: Shivam Jha
 * License: GPL2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include the necessary classes
require_once plugin_dir_path( __FILE__ ) . 'includes/class-race-track-controller.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-race-track-posts-controller.php';

/**
 * Register API routes for a given controller class.
 *
 * @param string $controller_class The controller class name.
 */
function register_api_routes( $controller_class ) {
    $controller = new $controller_class();
    $controller->register_routes();
}

// Register both controllers' routes using the same function
add_action( 'rest_api_init', function() {
    register_api_routes( 'Race_Track_Controller' );
    register_api_routes( 'Race_Track_Posts_Controller' );
});
