<?php
/*
Plugin Name: Buddypress reset user's password 
Plugin URI:  https://yourwebsite.com/
Description: A custom plugin to reset user's password
Version:     1.0
Author:      Shivam Jha
Author URI:  https://yourwebsite.com/
License:     GPL2
*/
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Include your custom post type and REST API endpoint functions here.
require_once(plugin_dir_path(__FILE__) . 'includes/bp-forget-password-api.php');
