<?php
function is_user_admin_of_chat($chat_id, $user_id, $wpdb, $chat_participants_table)
{
    // Check if the user is an admin of the group
    return $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$chat_participants_table} 
        WHERE chat_id = %d AND user_id = %d AND role = 'admin'",
        $chat_id,
        $user_id
    ));
}
