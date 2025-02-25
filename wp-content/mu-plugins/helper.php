<?php
/*
Plugin Name: My  Helpers
Description: A plugin to define custom helper functions.
Version: 1.0
Author: Shivam Jha
*/

// Save user language preference
function save_user_language_preference($user_id, $language) {
    update_user_meta($user_id, 'preferred_language', $language);
}

// Get user language preference
function get_user_language_preference($user_id) {
    $language = get_user_meta($user_id, 'preferred_language', true);
    return $language ? $language : 'de'; // Default to 'de' if no preference set
}

// Switch to user preferred language
function switch_to_user_language($user_id) {
    $language = get_user_language_preference($user_id);
    do_action('wpml_switch_language', $language);
}
