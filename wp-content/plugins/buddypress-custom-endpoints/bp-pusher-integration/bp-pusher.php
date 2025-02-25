<?php
/*
Plugin Name: BP pusher integration
Description: A plugin to integrate pusher
Version: 1.0
Author: Shivam Jha
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}


// Include your custom post type and REST API endpoint functions here.
require_once ABSPATH . 'vendor/autoload.php';

function trigger_pusher_notification($user_id, $message) {
    error_log("init");
    error_log($user_id);
    error_log($message);
    try {
    $pusher = new Pusher\Pusher(
        PUSHER_KEY,
        PUSHER_SECRET,
        PUSHER_APP_ID,
        array(
            'cluster' => PUSHER_CLUSTER,
            'useTLS' => true
        )
    );

        $pusher->trigger('private-notifications-1', 'new-notification', [
            'message' => $message
        ]);
      }
      catch(Exception $e)
      {
        error_log("Error writing to database:". $e->getMessage(), "\n");
      }

}
