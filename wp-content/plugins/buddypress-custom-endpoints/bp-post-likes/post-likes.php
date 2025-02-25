<?php
/*
Plugin Name: BP user Posts
Description: A plugin to manage user posts.
Version: 1.0
Author: Shivam Jha
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Include your custom post type and REST API endpoint functions here.
// require_once(plugin_dir_path(__FILE__) . 'includes/user-posts-custom-post-type.php');
require_once(plugin_dir_path(__FILE__) . 'includes/post-likes-rest-api.php');
