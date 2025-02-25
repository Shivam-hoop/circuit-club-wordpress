<?php

global $wpdb;

$table_name = $wpdb->prefix . 'race_tracks';
$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE $table_name (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    track_name varchar(255) NOT NULL,
    country varchar(255),
    address longtext,
    city varchar(255),
    zip int(10),
    track_length_km decimal(10,2),
    right_hand_curve int(11),
    left_hand_curve int(11),
    expected_costs decimal(10,2),
    route decimal(10,2),
    toll decimal(10,2),
    fuel decimal(10,2),
    cover varchar(255),
    map_attachment varchar(255),
    status tinyint(1) DEFAULT 1,
    is_deleted tinyint(1) DEFAULT 0,
    created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
    PRIMARY KEY (id)
) $charset_collate;";


require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
dbDelta( $sql );
