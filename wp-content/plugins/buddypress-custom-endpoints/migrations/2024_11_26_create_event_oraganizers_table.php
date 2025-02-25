<?php

global $wpdb;

// Define the table name with the WordPress prefix
$table_name = $wpdb->prefix . 'event_organizers';

// Get the charset and collation for the table
$charset_collate = $wpdb->get_charset_collate();

// Create the SQL query to create the custom table
$sql = "CREATE TABLE $table_name (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,     -- Unique ID for each organizer record
    event_id BIGINT(20) UNSIGNED NOT NULL,                  -- The ID of the event
    user_id BIGINT(20) UNSIGNED NOT NULL,                   -- The ID of the organizer (user)
    role VARCHAR(255) DEFAULT 'organizer',                  -- The role of the organizer (default to 'organizer')
    website VARCHAR(255),                                   -- The website URL of the organizer (can be null)
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, -- Timestamp when the record was created
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL, -- Timestamp when the record was last updated
    is_deleted TINYINT(1) DEFAULT 0,                        -- Soft delete flag (0 = not deleted, 1 = deleted)
    
    -- Foreign Key constraints to link the tables
    FOREIGN KEY (event_id) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE,  -- Ensure event exists and delete organizers when event is deleted
    FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,  -- Ensure user exists and delete organizers when user is deleted

    -- Indexes for fast filtering (on event_id, user_id, and id)
    INDEX event_id_index (event_id),    -- Index for filtering by event ID
    INDEX user_id_index (user_id),      -- Index for filtering by user ID
    INDEX id_index (id)                 -- Index for filtering by the primary key ID
) $charset_collate;";

// Include the required WordPress function to run the SQL query for table creation
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
dbDelta( $sql );

