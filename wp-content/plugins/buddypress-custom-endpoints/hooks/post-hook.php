<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

add_action('save_post', 'update_post_last_activity', 10, 3);
function update_post_last_activity($post_ID, $post, $update) {
    if ($post->post_type == 'user_post' || $post->post_type == 'vehicle_post') {
        update_post_meta($post_ID, 'last_activity', current_time('mysql'));
    }
}
