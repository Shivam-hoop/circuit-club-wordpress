<?php
function create_user_post_type() {
    $labels = array(
        'name'               => _x('User Posts', 'post type general name', 'textdomain'),
        'singular_name'      => _x('User Post', 'post type singular name', 'textdomain'),
        'menu_name'          => _x('User Posts', 'admin menu', 'textdomain'),
        'name_admin_bar'     => _x('User Post', 'add new on admin bar', 'textdomain'),
        'add_new'            => _x('Add New', 'user_post', 'textdomain'),
        'add_new_item'       => __('Add New User Post', 'textdomain'),
        'new_item'           => __('New User Post', 'textdomain'),
        'edit_item'          => __('Edit User Post', 'textdomain'),
        'view_item'          => __('View User Post', 'textdomain'),
        'all_items'          => __('All User Posts', 'textdomain'),
        'search_items'       => __('Search User Posts', 'textdomain'),
        'parent_item_colon'  => __('Parent User Posts:', 'textdomain'),
        'not_found'          => __('No user posts found.', 'textdomain'),
        'not_found_in_trash' => __('No user posts found in Trash.', 'textdomain'),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'user-posts'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 5,
        'supports'           => array('title', 'editor', 'thumbnail', 'author', 'excerpt', 'comments'),
        'show_in_rest'       => true, // Enable REST API support
    );

    register_post_type('user_post', $args);
}

add_action('init', 'create_user_post_type');
