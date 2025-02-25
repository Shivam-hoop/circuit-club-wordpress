<?php

global $wpdb;

// Define the table name with the WordPress prefix
$table_name = $wpdb->prefix . 'event_joined_users';

// Get the charset and collation for the table
$charset_collate = $wpdb->get_charset_collate();

// Create the SQL query to create the custom table
$sql = "CREATE TABLE $table_name (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    event_id BIGINT(20) UNSIGNED NOT NULL,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,  -- Soft delete flag
    PRIMARY KEY (id),
    KEY event_id (event_id),
    KEY user_id (user_id),
    KEY joined_at (joined_at),
    KEY updated_at (updated_at),
    KEY is_deleted (is_deleted)
) $charset_collate;";

// Include the required WordPress function to run the SQL query for table creation
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
dbDelta( $sql );

