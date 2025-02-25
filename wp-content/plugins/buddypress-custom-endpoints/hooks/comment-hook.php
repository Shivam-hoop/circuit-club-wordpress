<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

add_action('wp_insert_comment', 'update_post_last_activity_on_comment', 10, 2);
function update_post_last_activity_on_comment($comment_id, $comment) {
    if ($comment->comment_approved && in_array(get_post_type($comment->comment_post_ID), array('user_post', 'vehicle_post'))) {
        update_post_meta($comment->comment_post_ID, 'last_activity', current_time('mysql'));
    }
}
