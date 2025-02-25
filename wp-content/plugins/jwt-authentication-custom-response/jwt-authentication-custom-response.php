<?php
/**
 * Plugin Name: JWT Custom Response
 * Description: Customizes the JWT token response to include user ID.
 * Version: 1.0
 * Author: Shivam Jha
 */

add_filter('jwt_auth_token_before_dispatch', 'custom_jwt_auth_token_before_dispatch', 10, 2);

function custom_jwt_auth_token_before_dispatch($data, $user)
{

    $roles = $user->roles; // this will return an array of roles
    $role = $roles[0]; // Assume the user has one role, get the first role
    $data['user_id'] = $user->ID;
    $data['role'] = $role;

    return $data;
}