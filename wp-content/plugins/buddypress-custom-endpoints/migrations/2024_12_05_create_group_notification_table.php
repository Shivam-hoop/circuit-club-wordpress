<?php

global $wpdb;
$table_name_group_notifications = $wpdb->prefix . 'group_notifications'; // Table name for group notifications
$charset_collate = $wpdb->get_charset_collate(); // Charset and collation settings

$sql_group_notifications = "
CREATE TABLE $table_name_group_notifications (
    notification_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    chat_id BIGINT(20) UNSIGNED NOT NULL,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    action ENUM('joined', 'left', 'removed', 'added') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (notification_id),
    KEY chat_id (chat_id),
    KEY user_id (user_id),
    KEY action (action),
    KEY created_at (created_at)
) $charset_collate;
";

// Execute the SQL query to create the group notifications table
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql_group_notifications);
