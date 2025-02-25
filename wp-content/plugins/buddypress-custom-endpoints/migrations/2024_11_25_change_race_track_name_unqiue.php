<?php 
global $wpdb;

$table_name = $wpdb->prefix . 'race_tracks';


// SQL query to modify the track_name column to be unique
$sql_modify_column = "ALTER TABLE $table_name 
        MODIFY COLUMN track_name varchar(255) UNIQUE NOT NULL;";

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

// Execute the queries
$wpdb->query($sql_add_column);
$wpdb->query($sql_modify_column);
