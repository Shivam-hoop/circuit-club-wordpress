<?php
/*
Plugin Name: BP Share Posts
Description: A plugin to share all type posts.
Version: 1.0
Author: Shivam Jha
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Include your custom post type and REST API endpoint functions here.
// require_once(plugin_dir_path(__FILE__) . 'includes/user-posts-custom-post-type.php');
require_once(plugin_dir_path(__FILE__) . 'includes/share-posts-rest-api.php');
