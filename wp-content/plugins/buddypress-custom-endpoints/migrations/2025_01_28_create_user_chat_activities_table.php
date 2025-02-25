<?php 

global $wpdb;
$table_name = $wpdb->prefix . 'user_chat_activities';
$charset_collate = $wpdb->get_charset_collate();

$sql = "
CREATE TABLE $table_name (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    is_online TINYINT(1) NOT NULL DEFAULT 0,
    last_seen DATETIME DEFAULT NULL,
    last_activity DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
) $charset_collate;
";

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql);

