<?php
/**
 * Plugin Name: message API
 * Description: Send one-to-one messages to other users.
 * Version: 1.0.0
 * Author: Shivam Jha
 * License: GPL2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Include the necessary classes
require_once plugin_dir_path(__FILE__) . 'includes/class-chat-controller.php';


/**
 * Register API routes for a given controller class.
 *
 * @param string $controller_class The controller class name.
 */
function register_api_chats_routes($controller_class)
{
    $controller = new $controller_class();
    $controller->register_routes();
}

// Register both controllers' routes using the same function
add_action('rest_api_init', function () {
    register_api_chats_routes('Chat_Controller');
});
