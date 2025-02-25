<?php
/*
 * Plugin Name:  BuddyPress Custom Endpoints
 * Description: A plugin to extend BuddyPress functionality with custom endpoints.
 * Version: 1.0
 * Author: Shivam Jha
 */
// Include Hook Files



require_once plugin_dir_path(__FILE__) . 'includes/class-migration.php';
// Run migrations on plugin activation
function cbe_run_migrations()
{
    $migration = new Migration();
    $migration->run_migrations(); // This will run any pending migrations
}
register_activation_hook(__FILE__, 'cbe_run_migrations');


require_once plugin_dir_path(__FILE__) . 'hooks/post-hook.php';
require_once plugin_dir_path(__FILE__) . 'hooks/comment-hook.php';
require_once ABSPATH . 'wp-content/plugins/buddypress-custom-endpoints/bp-notification/utils/notification-helper.php';
include_once plugin_dir_path(__FILE__) . 'buddypress-extend-profile-multi-field-update/buddypress-extend-profile-multi-field-update.php';
include_once plugin_dir_path(__FILE__) . 'bp-vehicles/bp-custom-vehicle-post-type.php';
include_once plugin_dir_path(__FILE__) . 'bp-member-cover-image/bp-get-cover-image.php';
include_once plugin_dir_path(__FILE__) . 'bp-member-profile-image/bp-get-profile-image.php';
include_once plugin_dir_path(__FILE__) . 'bp-posts/bp-posts.php';
include_once plugin_dir_path(__FILE__) . 'bp-forget-password/bp-forget-password.php';
include_once plugin_dir_path(__FILE__) . 'bp-set-language/bp-language.php';
include_once plugin_dir_path(__FILE__) . 'bp-nickname-suggestion/nickname-suggestion.php';
include_once plugin_dir_path(__FILE__) . 'bp-post-comments/post-comments.php';
include_once plugin_dir_path(__FILE__) . 'bp-post-likes/post-likes.php';
include_once plugin_dir_path(__FILE__) . 'bp-user-activation/user-activation.php';
include_once plugin_dir_path(__FILE__) . 'bp-upload-file-in-background/bp-upload-file-background.php';
include_once plugin_dir_path(__FILE__) . 'bp-share-post/share-posts.php';
include_once plugin_dir_path(__FILE__) . 'bp-follow-manager/follow-manager.php';
include_once plugin_dir_path(__FILE__) . 'bp-event/bp-event.php';
include_once plugin_dir_path(__FILE__) . 'bp-chats/bp-chats.php';
include_once plugin_dir_path(__FILE__) . 'bp-notification/prepare-response.php';
include_once plugin_dir_path(__FILE__) . 'bp-firebase-integration/bp-firebase-integration.php';
include_once plugin_dir_path(__FILE__) . 'bp-media-upload/bp-media-rest-api.php';
include_once plugin_dir_path(__FILE__) . 'bp-race-track/race-track.php';
include_once plugin_dir_path(__FILE__) . 'bp-event-organizer/event-organizer.php';
include_once plugin_dir_path(__FILE__) . 'bp-event-joinned-users/event-joinned-users.php';
include_once plugin_dir_path(__FILE__) . 'bp-chat/chat.php';
include_once plugin_dir_path(__FILE__) . 'bp-group/group.php';
include_once plugin_dir_path(__FILE__) . 'bp-notification/notification.php';


