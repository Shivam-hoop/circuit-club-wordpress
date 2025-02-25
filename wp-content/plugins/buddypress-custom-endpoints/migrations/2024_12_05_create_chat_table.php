<?php

global $wpdb;
$table_name = $wpdb->prefix . 'chats'; // Prefix to handle WordPress table prefix (e.g., wp_)
$charset_collate = $wpdb->get_charset_collate(); // Fetches the charset/collation for your WordPress database

$sql = "
CREATE TABLE $table_name (
    chat_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    chat_type ENUM('one-to-one', 'group', 'broadcast') NOT NULL,
    creator_id BIGINT(20) UNSIGNED NOT NULL,
    is_broadcast TINYINT(1) DEFAULT 0,
    group_name VARCHAR(255) NULL,
    group_icon_url VARCHAR(255) NULL,
    group_description TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (chat_id),
    KEY creator_id (creator_id),
    KEY chat_type (chat_type),
    KEY is_broadcast (is_broadcast),
    KEY created_at (created_at),
    KEY updated_at (updated_at)
) $charset_collate;
";

// Execute the SQL query to create the table
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql);
