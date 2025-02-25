<?php

global $wpdb;

$table_name = $wpdb->prefix . 'race_track_posts';
$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE $table_name (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_title VARCHAR(255) NOT NULL,
    post_description TEXT,
    web_url VARCHAR(255),
    image_url VARCHAR(255),
    race_track_id BIGINT(20) UNSIGNED NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
    is_deleted TINYINT(1) DEFAULT 0
) $charset_collate;";


require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
dbDelta( $sql );
