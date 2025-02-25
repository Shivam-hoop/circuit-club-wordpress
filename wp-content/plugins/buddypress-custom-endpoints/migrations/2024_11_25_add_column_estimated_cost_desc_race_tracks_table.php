<?php 
global $wpdb;

$table_name = $wpdb->prefix . 'race_tracks';

// SQL query to add the new column
$sql = "ALTER TABLE $table_name 
        ADD COLUMN expected_cost_description text AFTER expected_costs;";

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

// Execute the query
$wpdb->query($sql);
