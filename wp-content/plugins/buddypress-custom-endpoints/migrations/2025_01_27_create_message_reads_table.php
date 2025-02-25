<?php 


global $wpdb;
$table_name = $wpdb->prefix . 'message_reads';
$charset_collate = $wpdb->get_charset_collate();

$sql = "
CREATE TABLE $table_name (
    read_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    message_id BIGINT(20) UNSIGNED NOT NULL,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    read_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (read_id),
    KEY message_id (message_id),
    KEY user_id (user_id),
    FOREIGN KEY (message_id) REFERENCES {$wpdb->prefix}messages(message_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
) $charset_collate;
";

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql);
