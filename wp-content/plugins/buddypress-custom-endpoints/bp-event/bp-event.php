<?php
/*
Plugin Name: Buddypress custom event 
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

// Include your custom post type and REST API endpoint functions here.
require_once(plugin_dir_path(__FILE__) . 'includes/bp-event-post-type.php');
require_once(plugin_dir_path(__FILE__) . 'includes/helpers/event-helpers.php');
require_once(plugin_dir_path(__FILE__) . 'includes/helpers/event-validations.php');
require_once(plugin_dir_path(__FILE__) . 'includes/bp-event-rest-api.php');

