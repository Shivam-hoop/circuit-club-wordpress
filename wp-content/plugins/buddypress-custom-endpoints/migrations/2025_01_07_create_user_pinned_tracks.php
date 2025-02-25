<?php

global $wpdb;
$table_name = $wpdb->prefix . 'user_pinned_tracks'; // Prefix for handling WordPress table prefix (e.g., wp_)
$charset_collate = $wpdb->get_charset_collate(); // Fetch the charset/collation for your WordPress database

$sql = "
CREATE TABLE $table_name (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,   -- Primary key
    user_id BIGINT UNSIGNED NOT NULL,               -- WordPress user ID
    track_id BIGINT UNSIGNED NOT NULL,              -- Race track ID
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, -- Timestamp for pinning

    UNIQUE KEY user_track_unique (user_id, track_id), -- Ensures a user can't pin the same track multiple times
    KEY user_id_index (user_id),                    -- Index for efficient querying by user
    KEY track_id_index (track_id)                   -- Index for efficient querying by track

) $charset_collate;
";

// Execute the SQL query to create the table
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql);
