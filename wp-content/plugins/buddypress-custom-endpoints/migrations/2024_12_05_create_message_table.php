<?php

global $wpdb;
$table_name = $wpdb->prefix . 'messages'; // Prefix for handling WordPress table prefix (e.g., wp_)
$charset_collate = $wpdb->get_charset_collate(); // Fetch the charset/collation for your WordPress database

$sql = "
CREATE TABLE $table_name (
    message_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    chat_id BIGINT(20) UNSIGNED NOT NULL,
    sender_id BIGINT(20) UNSIGNED NOT NULL,
    message_content TEXT NULL,
    message_type ENUM('text', 'image', 'video', 'audio', 'file') NOT NULL DEFAULT 'text',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_broadcast TINYINT(1) DEFAULT 0,
    PRIMARY KEY (message_id),
    KEY chat_id (chat_id),
    KEY sender_id (sender_id),
    KEY message_type (message_type),
    KEY created_at (created_at),
    KEY is_broadcast (is_broadcast),
    FOREIGN KEY (chat_id) REFERENCES {$wpdb->prefix}chats(chat_id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
) $charset_collate;
";

// Execute the SQL query to create the table
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql);
