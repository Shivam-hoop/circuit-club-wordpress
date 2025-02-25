<?php
function register_event_post_type() {
    $labels = array(
        'name'               => __('Events', 'textdomain'),
        'singular_name'      => __('Event', 'textdomain'),
        'add_new_item'       => __('Add New Event', 'textdomain'),
        'edit_item'          => __('Edit Event', 'textdomain'),
        'new_item'           => __('New Event', 'textdomain'),
        'view_item'          => __('View Event', 'textdomain'),
        'search_items'       => __('Search Events', 'textdomain'),
        'not_found'          => __('No events found', 'textdomain'),
        'not_found_in_trash' => __('No events found in Trash', 'textdomain'),
        'all_items'          => __('All Events', 'textdomain'),
        'menu_name'          => __('Events', 'textdomain'),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'supports'           => array('title', 'editor', 'author', 'thumbnail', 'custom-fields'),
        'rewrite'            => array('slug' => 'events'),
        'show_in_rest'       => true,
    );

    register_post_type('event', $args);
}
add_action('init', 'register_event_post_type');
