<?php
/****
 * start the helper functions
 */

function get_user_profile_picture_url($user_id)
{
    // Assuming you use BuddyPress or a similar plugin that sets user avatars
    $avatar_url = bp_core_fetch_avatar(array(
        'item_id' => $user_id,
        'html' => false // Return only the URL, not the HTML tag
    ));

    return $avatar_url ?: ''; // Return an empty string if no avatar exists
}

function get_vehicle_profile_picture_url($vehicle_id)
{
    // Assuming the vehicle's profile picture URL is stored in a meta field named 'vehicle_profile_picture'
    $profile_picture_url = get_post_meta($vehicle_id, 'profile_image', true);

    return $profile_picture_url ?: ''; // Return an empty string if no profile picture exists
}

// new approch
// Helper function to follow a user
// function follow_user($user_id, $target_user_id)
// {
//     // Retrieve the current list of followed users
//     $followed_users = get_user_meta($user_id, 'followed_users', true);
//     if (empty($followed_users) || !is_array($followed_users)) {
//         $followed_users = [];
//     }
//     // Add the follower if not already in the list
//     if (!in_array($user_id, $followed_users)) {
//         $followers[] = $user_id;
//         update_user_meta($target_user_id, 'followed_users', $followers);
//     }
//     // Add the target user to the list if not already followed
//     if (!in_array($target_user_id, $followed_users)) {
//         $followed_users[] = $target_user_id;
//         update_user_meta($user_id, 'followed_users', $followed_users);

//         // Optionally, update the target user's followers count
//         $followers_count = get_user_meta($target_user_id, 'followers_count', true);
//         update_user_meta($target_user_id, 'followers_count', (int) $followers_count + 1);
//     }
// }
function follow_user($user_id, $target_user_id)
{
    // Retrieve the current list of users followed by the logged-in user
    $followed_users = get_user_meta($user_id, 'followed_users', true);
    if (empty($followed_users) || !is_array($followed_users)) {
        $followed_users = [];
    }

    // Retrieve the current list of followers for the target user
    $followers = get_user_meta($target_user_id, 'followers', true);
    if (empty($followers) || !is_array($followers)) {
        $followers = [];
    }

    // Check if the target user is already followed by the logged-in user
    if (!in_array($target_user_id, $followed_users)) {
        // Add the target user to the logged-in user's 'followed_users' list
        $followed_users[] = $target_user_id;
        update_user_meta($user_id, 'followed_users', $followed_users);

        // Add the logged-in user to the target user's 'followers' list
        $followers[] = $user_id;
        update_user_meta($target_user_id, 'followers', $followers);

        // Optionally, update the target user's followers count
        $followers_count = get_user_meta($target_user_id, 'followers_count', true);
        update_user_meta($target_user_id, 'followers_count', (int) $followers_count + 1);
    }
}

// Helper function to unfollow a user
function unfollow_user($user_id, $target_user_id)
{
    // Retrieve the current list of followed users
    $followed_users = get_user_meta($user_id, 'followed_users', true);
    if (empty($followed_users) || !is_array($followed_users)) {
        $followed_users = [];
    }

    // Remove the target user from the list if followed
    if (($key = array_search($target_user_id, $followed_users)) !== false) {
        unset($followed_users[$key]);
        update_user_meta($user_id, 'followed_users', array_values($followed_users));

        // Optionally, update the target user's followers count
        $followers_count = get_user_meta($target_user_id, 'followers_count', true);
        update_user_meta($target_user_id, 'followers_count', (int) $followers_count - 1);
    }
}

function follow_vehicle($user_id, $vehicle_id)
{
    // Retrieve the current list of vehicles followed by the user
    $followed_vehicles = get_user_meta($user_id, 'followed_vehicles', true);
    if (empty($followed_vehicles) || !is_array($followed_vehicles)) {
        $followed_vehicles = [];
    }

    // Add the vehicle to the user's followed vehicles list if not already followed
    if (!in_array($vehicle_id, $followed_vehicles)) {
        $followed_vehicles[] = $vehicle_id;
        update_user_meta($user_id, 'followed_vehicles', $followed_vehicles);
    }

    // Now store the user in the vehicle's meta (vehicle_followers)
    $vehicle_followers = get_post_meta($vehicle_id, 'vehicle_followers', true);
    if (empty($vehicle_followers) || !is_array($vehicle_followers)) {
        $vehicle_followers = [];
    }

    // Add the user to the vehicle's followers if not already following
    if (!in_array($user_id, $vehicle_followers)) {
        $vehicle_followers[] = $user_id;
        update_post_meta($vehicle_id, 'vehicle_followers', $vehicle_followers);

        // Optionally, update the vehicle's followers count
        $followers_count = get_post_meta($vehicle_id, 'vehicle_followers_count', true);
        update_post_meta($vehicle_id, 'vehicle_followers_count', (int) $followers_count + 1);
    }
}

function unfollow_vehicle($user_id, $vehicle_id)
{
    // Retrieve the current list of followed vehicles by the user
    $followed_vehicles = get_user_meta($user_id, 'followed_vehicles', true);
    if (empty($followed_vehicles) || !is_array($followed_vehicles)) {
        $followed_vehicles = [];
    }

    // Remove the vehicle from the user's followed vehicles list if followed
    if (($key = array_search($vehicle_id, $followed_vehicles)) !== false) {
        unset($followed_vehicles[$key]);
        update_user_meta($user_id, 'followed_vehicles', array_values($followed_vehicles));
    }

    // Now remove the user from the vehicle's followers list
    $vehicle_followers = get_post_meta($vehicle_id, 'vehicle_followers', true);
    if (empty($vehicle_followers) || !is_array($vehicle_followers)) {
        $vehicle_followers = [];
    }

    // Remove the user from the vehicle's followers if they are in the list
    if (($key = array_search($user_id, $vehicle_followers)) !== false) {
        unset($vehicle_followers[$key]);
        update_post_meta($vehicle_id, 'vehicle_followers', array_values($vehicle_followers));

        // Optionally, update the vehicle's followers count
        $followers_count = get_post_meta($vehicle_id, 'vehicle_followers_count', true);
        update_post_meta($vehicle_id, 'vehicle_followers_count', max((int) $followers_count - 1, 0));
    }
}



function get_followers_list($target_id, $type = 'user', $search = '')
{
    $followers = [];

    if ($type === 'user') {
        // Retrieve all users who follow this user
        $all_users = get_users(); // Retrieves all users, but this could be inefficient on larger sites

        // Loop through all users to find those following the target user
        foreach ($all_users as $user) {
            $followed_users = get_user_meta($user->ID, 'followed_users', true);
            if (!empty($followed_users) && in_array($target_id, $followed_users)) {
                $profile_picture_url = get_user_profile_picture_url($user->ID); // Define your own function to get profile picture URL

                $follower = [
                    'id' => $user->ID,
                    'name' => $user->display_name,
                    'type' => 'user',
                    'profile_picture_url' => $profile_picture_url
                ];

                // Apply the search filter (if provided)
                if (empty($search) || stripos($follower['name'], $search) !== false) {
                    $followers[] = $follower;
                }
            }
        }
    } elseif ($type == 'vehicle') {
        // Fetch the followers from the vehicle's meta data
        $vehicle_followers = get_post_meta($target_id, 'vehicle_followers', true);

        if (!empty($vehicle_followers) && is_array($vehicle_followers)) {
            foreach ($vehicle_followers as $user_id) {
                $user_info = get_userdata($user_id);
                if ($user_info) {
                    $profile_picture_url = get_user_profile_picture_url($user_id); // Define your own function to get profile picture URL

                    $follower = [
                        'id' => $user_id,
                        'name' => $user_info->display_name,
                        'profile_picture_url' => $profile_picture_url
                    ];
                    // Apply the search filter (if provided)
                    if (empty($search) || stripos($follower['name'], $search) !== false) {
                        $followers[] = $follower;
                    }
                }
            }
        }
    }

    return $followers;
}

function get_user_following_list($user_id, $search = '')
{
    $following = [];

    // Retrieve the list of users the user is following
    $followed_users = get_user_meta($user_id, 'followed_users', true);
    if (!empty($followed_users) && is_array($followed_users)) {
        foreach ($followed_users as $followed_user_id) {
            $user_data = get_userdata($followed_user_id);
            if ($user_data) {
                $profile_picture_url = get_user_profile_picture_url($followed_user_id); // Define your own function to get profile picture URL

                // Apply the search filter (if provided)
                if (empty($search) || stripos($user_data->display_name, $search) !== false) {
                    $following[] = [
                        'id' => $followed_user_id,
                        'name' => $user_data->display_name,
                        'type' => 'user',
                        'profile_picture_url' => $profile_picture_url
                    ];
                }
            }
        }
    }

    // Retrieve the list of vehicles the user is following
    $followed_vehicles = get_user_meta($user_id, 'followed_vehicles', true);
    if (!empty($followed_vehicles) && is_array($followed_vehicles)) {
        foreach ($followed_vehicles as $followed_vehicle_id) {
            $vehicle_name = get_post_meta($followed_vehicle_id, 'vehicle_nickname', true);

            if ($vehicle_name) {
                $profile_picture_url = get_vehicle_profile_picture_url($followed_vehicle_id); // Define your own function to get vehicle picture URL

                $following[] = [
                    'id' => $followed_vehicle_id,
                    'name' => $vehicle_name,
                    'type' => 'vehicle',
                    'profile_picture_url' => wp_get_attachment_url($profile_picture_url)
                ];
            }
        }
    }

    return $following;
}

// Function to check if a user is following another user or vehicle
function is_following($user_id, $target_id, $type)
{
    if ($type === 'user') {
        $followed_users = get_user_meta($user_id, 'followed_users', true);
        return !empty($followed_users) && in_array($target_id, $followed_users);
    } elseif ($type === 'vehicle') {
        $followed_vehicles = get_user_meta($user_id, 'followed_vehicles', true);
        return !empty($followed_vehicles) && in_array($target_id, $followed_vehicles);
    }
    return false;
}

function send_follow_notification($user_id, $target_id, $type)
{
    // BuddyPress Notifications API
    bp_notifications_add_notification([
        'user_id' => $target_id, // The user to notify
        'item_id' => $user_id,   // The follower's user ID
        'secondary_item_id' => $type === 'user' ? $user_id : $target_id, // Custom for vehicle or user
        'component_name' => 'follow', // Component name, can be custom if you like
        'component_action' => 'new_follow_request', // Unique key for this notification action
        'date_notified' => bp_core_current_time(),
        'is_new' => 1, // Mark as new
    ]);
    send_realtime_notification_to_node($target_id);

}
// // Callback function to display notifications (in case you want to customize it)
// function bp_custom_format_follow_notification($action, $item_id, $secondary_item_id, $total_items) {
//     $follower_user = bp_core_get_user_displayname($item_id);

//     if ($total_items > 1) {
//         return sprintf(__('%s users followed you', 'text-domain'), $total_items);
//     } else {
//         return sprintf(__('%s followed you', 'text-domain'), $follower_user);
//     }
// }

// add_filter('bp_notifications_get_notifications_for_user', 'bp_custom_format_follow_notification', 10, 5);


/****
 * end the helper functions

 */
