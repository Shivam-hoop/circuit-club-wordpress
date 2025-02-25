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
require_once ABSPATH . 'vendor/autoload.php';

require_once dirname(__DIR__) . '/aws-sdk-utils.php';
require_once dirname(__DIR__) . '/post-utils.php';
require_once dirname(__DIR__) . '/bp-media-upload/media-upload.php';
require_once(plugin_dir_path(__FILE__) . 'includes/post-response.php');
require_once(plugin_dir_path(__FILE__) . 'includes/user-posts-custom-post-type.php');
require_once(plugin_dir_path(__FILE__) . 'includes/user-posts-rest-api.php');
require_once(plugin_dir_path(__FILE__) . 'includes/vehicle-posts-custom-post-type.php');
require_once(plugin_dir_path(__FILE__) . 'includes/vehicle-posts-rest-api.php');
require_once(plugin_dir_path(__FILE__) . 'includes/trending-posts-rest-api.php');
