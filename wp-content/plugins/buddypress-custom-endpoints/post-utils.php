<?php
if (!function_exists('has_user_liked_post')) {
    function has_user_liked_post($post_id, $user_id) {
        $likes = get_post_meta($post_id, 'post_likes', true);
        return is_array($likes) && in_array($user_id, $likes);
    }
}
if (!function_exists('get_likes_count_on_post')) {
    function get_likes_count_on_post($post_id) {
        $likes = get_post_meta($post_id, 'post_likes', true);
        $likes_count = !empty($likes) ? count($likes) : 0;
        return $likes_count;
    }
}
if (!function_exists('get_comments_count_on_post')) {
    function get_comments_count_on_post($post_id) {
        $comments_count = (int) wp_count_comments($post_id)->total_comments;
        return $comments_count;
    }
}