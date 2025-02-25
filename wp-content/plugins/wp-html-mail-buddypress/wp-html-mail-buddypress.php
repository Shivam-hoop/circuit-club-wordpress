<?php
/*
Plugin Name: WP HTML Mail – BuddyPress Email Designer
Plugin URI: https://wordpress.org/plugins/wp-html-mail-buddypress/
Description: Beautiful responsive mails for BuddyPress
Version: 1.0.1
Text Domain: wp-html-mail-buddypress
Author: Hannes Etzelstorfer // codemiq
Author URI: https://codemiq.com
License: GPLv2 or later
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
*/

/*  Copyright 2020 codemiq (email : support@codemiq.com) */

include_once( ABSPATH . 'wp-admin/includes/plugin.php' ); 

function wphtmlmail_buddypress_core_notice() {
    ?>
    <div class="updated">
        <p><?php printf( 
                    __( '<strong>Notice:</strong> To use the WP HTML Mail - BuddyPress integration please install the free WP HTML Mail plugin first. <a href="%s">Install Plugin</a>', 'wp-html-mail-buddypress' ), 
                    wp_nonce_url( network_admin_url( 'update.php?action=install-plugin&plugin=wp-html-mail' ), 'install-plugin_wp-html-mail' )
            ); ?></p>
    </div>
    <?php
}

function wphtmlmail_buddypress_init(){
    if(!is_plugin_active( 'wp-html-mail/wp-html-mail.php' )){
        add_action( 'admin_notices', 'wphtmlmail_buddypress_core_notice' );
    }else{

        define( 'HAET_MAIL_BUDDYPRESS_PATH', plugin_dir_path(__FILE__) );
        define( 'HAET_MAIL_BUDDYPRESS_URL', plugin_dir_url(__FILE__) );


        require HAET_MAIL_BUDDYPRESS_PATH . 'includes/class-haet-sender-plugin-buddypress.php';
    }
}
add_action( 'plugins_loaded', 'wphtmlmail_buddypress_init', 20 );



function haet_mail_register_plugin_buddypress($plugins){

    $plugins['buddypress']   =  array(
        'name'      =>  'buddypress',
        'file'      =>  'buddypress/bp-loader.php',
        'class'     =>  'Haet_Sender_Plugin_BuddyPress',
        'display_name' => 'BuddyPress',
    );
    return $plugins;
}




function wphtmlmail_buddypress_load() {
    load_plugin_textdomain('wp-html-mail-buddypress', false, dirname( plugin_basename( __FILE__ ) ) . '/translations' );

    add_filter( 'haet_mail_available_plugins', 'haet_mail_register_plugin_buddypress');
} 
add_action('plugins_loaded', 'wphtmlmail_buddypress_load');
