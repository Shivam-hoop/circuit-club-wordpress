<?php

global $wpdb;
$table_name = $wpdb->prefix . 'chat_participants'; // Prefix for handling WordPress table prefix (e.g., wp_)
$charset_collate = $wpdb->get_charset_collate(); // Fetch the charset/collation for your WordPress database

$sql = "
CREATE TABLE $table_name (
    chat_participant_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    chat_id BIGINT(20) UNSIGNED NOT NULL,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    role ENUM('admin', 'member', 'participant') DEFAULT 'participant',
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    left_at DATETIME NULL,
    PRIMARY KEY (chat_participant_id),
    KEY chat_id (chat_id),
    KEY user_id (user_id),
    FOREIGN KEY (chat_id) REFERENCES {$wpdb->prefix}chats(chat_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
) $charset_collate;
";

// Execute the SQL query to create the table
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql);
