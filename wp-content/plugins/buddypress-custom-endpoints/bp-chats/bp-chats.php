<?php 
/*
Plugin Name: Buddypress chats list
Plugin URI:  https://yourwebsite.com/
Description: A custom plugin to create vehicle information.
Version:     1.0
Author:      Shivam Jha
Author URI:  https://yourwebsite.com/
License:     GPL2
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}


require_once(plugin_dir_path(__FILE__) . 'includes/bp-rest-api-chats.php');