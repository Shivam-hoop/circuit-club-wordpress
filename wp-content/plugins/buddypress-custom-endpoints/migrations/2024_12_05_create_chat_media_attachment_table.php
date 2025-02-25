<?php

global $wpdb;
$table_name_media = $wpdb->prefix . 'chat_media_attachments'; // Table name for media attachments
$charset_collate = $wpdb->get_charset_collate(); // Charset and collation settings

$sql_media = "
CREATE TABLE $table_name_media (
    media_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    message_id BIGINT(20) UNSIGNED NOT NULL,
    file_url VARCHAR(255) NOT NULL,
    file_type ENUM('image', 'video', 'audio', 'file') NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (media_id),
    KEY message_id (message_id)
) $charset_collate;
";

// Execute the SQL query to create the media table
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql_media);
