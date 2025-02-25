<?php
add_action('wp_insert_comment', 'notify_author_on_new_comment', 10, 2);

function notify_author_on_new_comment($comment_id, $comment_object) {
    // Get the post ID and author ID of the post being commented on
    $post_id = $comment_object->comment_post_ID;
    $post_author_id = get_post_field('post_author', $post_id);
    $comment_author_id = $comment_object->user_id;

    // Ensure the comment is not from the post author
    if ($post_author_id != $comment_author_id) {
        send_comment_notification($comment_author_id, $post_id, $post_author_id, $comment_id);
    }
}

// Function to send comment notification to the post author
function send_comment_notification($comment_author_id, $post_id, $post_author_id, $comment_id) {
    // Add a notification for the post author
    bp_notifications_add_notification(args: [
        'user_id'           => $post_author_id,   // The post author to notify
        'item_id'           => $post_id,          // Post ID
        'secondary_item_id' => $comment_author_id, // The user who commented
        'component_name'    => 'wp_comment',      // Component name
        'component_action'  => 'post_comment',    // Unique action key for the comment notification
        'date_notified'     => bp_core_current_time(),
        'is_new'            => 1,                 // Mark as new
    ]);
    send_realtime_notification_to_node($post_author_id);

}