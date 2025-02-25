<?php
function register_vehicle_post_post_type() {
    $labels = array(
        'name' => _x('Vehicle Posts', 'Post type general name', 'textdomain'),
        'singular_name' => _x('Vehicle Post', 'Post type singular name', 'textdomain'),
        'menu_name' => _x('Vehicle Posts', 'Admin Menu text', 'textdomain'),
        'name_admin_bar' => _x('Vehicle Post', 'Add New on Toolbar', 'textdomain'),
        'add_new' => __('Add New', 'textdomain'),
        'add_new_item' => __('Add New Vehicle Post', 'textdomain'),
        'new_item' => __('New Vehicle Post', 'textdomain'),
        'edit_item' => __('Edit Vehicle Post', 'textdomain'),
        'view_item' => __('View Vehicle Post', 'textdomain'),
        'all_items' => __('All Vehicle Posts', 'textdomain'),
        'search_items' => __('Search Vehicle Posts', 'textdomain'),
        'parent_item_colon' => __('Parent Vehicle Posts:', 'textdomain'),
        'not_found' => __('No vehicle posts found.', 'textdomain'),
        'not_found_in_trash' => __('No vehicle posts found in Trash.', 'textdomain'),
        'featured_image' => _x('Vehicle Post Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'textdomain'),
        'set_featured_image' => _x('Set post image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'textdomain'),
        'remove_featured_image' => _x('Remove post image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'textdomain'),
        'use_featured_image' => _x('Use as post image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'textdomain'),
        'archives' => _x('Vehicle Post archives', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'textdomain'),
        'insert_into_item' => _x('Insert into vehicle post', 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', 'textdomain'),
        'uploaded_to_this_item' => _x('Uploaded to this vehicle post', 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4', 'textdomain'),
        'filter_items_list' => _x('Filter vehicle posts list', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', 'textdomain'),
        'items_list_navigation' => _x('Vehicle posts list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', 'textdomain'),
        'items_list' => _x('Vehicle posts list', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', 'textdomain'),
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'vehicle-post'),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => null,
        'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments'),
        'show_in_rest' => true,

    );

    register_post_type('vehicle_post', $args);
}

add_action('init', 'register_vehicle_post_post_type');
