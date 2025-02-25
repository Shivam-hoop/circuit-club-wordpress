<?php
// function prepare_post_response($post)
// {
//     $post_id = $post->ID;
//     $post_type = $post->post_type;
//     $user_id = get_current_user_id();
//     // Default counts
//     $comments_count = get_comments_count_on_post($post_id);
//     $likes_count = get_likes_count_on_post($post_id);
//     $like = $user_id ? has_user_liked_post($post_id, $user_id) : false;
//     $share_count = (int) get_post_meta($post_id, 'share_count', true);

//     // Determine if the user has liked the post
//     $like = $user_id ? has_user_liked_post($post_id, $user_id) : false;

//     // Get media and profile info
//     $media = get_post_media($post_id, $post_type);
//     $profileInfo = get_profile_info($post_id, $post_type);

//     // Initialize repost parameters
//     $repost_post_id = "";
//     $repost = false;

//     // Check if the post is a repost
//     $shared_post_id = get_post_meta($post_id, 'shared_post_id', true);
//     $original_post_details = '';
//     $shared_by = '';
//     $share_at = '';

//     if ($shared_post_id) {
//         $repost_post_id = $shared_post_id;
//         $repost = true;

//         // Fetch details of the original post
//         $original_post = get_post($shared_post_id);
//         $original_post_details = prepare_post_response($original_post);

//         // Override the counts with the original post's counts
//         $comments_count = get_comments_count_on_post($shared_post_id);
//         $likes_count = get_likes_count_on_post($shared_post_id);
//         $like = $user_id ? has_user_liked_post($shared_post_id, $user_id) : false;  // Update $like to reflect the original post's like status
//         $share_count = (int) get_post_meta($shared_post_id, 'share_count', true);

//         // Get the user who shared the post and the share time
//         $shared_by = get_user_by('id', $post->post_author);
//         $share_at = get_post_meta($post_id, 'shared_at', true);
//     }
//     // Fetch the latest two comments for the post
//     $latest_comments = get_comments(array(
//         'post_id' => $post_id,
//         'number' => 2,  // Fetch only the latest 2 comments
//         'status' => 'approve',
//         'order' => 'DESC',  // Latest comments first
//     ));

//     // Format the comments for the response
//     $comments = array_map(function ($comment) {
//         return array(
//             'comment_ID' => $comment->comment_ID,
//             'comment_author' => $comment->comment_author,
//             'comment_content' => $comment->comment_content,
//             'comment_date' => $comment->comment_date,
//         );
//     }, $latest_comments);

//     return array(
//         'id' => $post_id,
//         'title' => get_the_title($post_id),
//         'content' => get_the_content(null, false, $post_id),
//         'date' => get_the_date('Y-m-d H:i:s', $post_id),
//         'media' => $media,
//         'post_type' => $post_type,
//         'comments_count' => $comments_count,
//         'likes_count' => $likes_count,
//         'like' => $like,
//         'profile_info' => $profileInfo,
//         'share_count' => $share_count,
//         'original_post_details' => $original_post_details,
//         'shared_by' => $shared_by ? array(
//             'user_id' => $shared_by->ID,
//             'user_name' => $shared_by->user_nicename,
//             'profile_pic' => get_avatar_url($shared_by->ID),
//         ) : '',
//         'share_at' => $share_at,
//         'repost_post_id' => $repost_post_id,
//         'repost' => $repost,
//         'latest_comments' => $comments,
//     );
// }

// Move the function outside and check if it exists
if (!function_exists('build_comment_tree_dashboard')) {
    function build_comment_tree_dashboard($comments_by_parent, $parent_id = 0)
    {
        $tree = array();
        if (!empty($comments_by_parent[$parent_id])) {
            foreach ($comments_by_parent[$parent_id] as $comment) {
                $comment->comment_content = apply_filters('the_content', $comment->comment_content);

                // Add profile picture URL
                $comment->profile_picture_url = $comment->user_id ? bp_core_fetch_avatar(array('item_id' => $comment->user_id, 'html' => false)) : '';

                // Build replies recursively
                $comment->replies = build_comment_tree_dashboard($comments_by_parent, $comment->comment_ID);
                $comment->replies_count = count($comment->replies);

                // Add the comment to the tree
                $tree[] = array(
                    'comment_ID' => $comment->comment_ID,
                    'comment_author' => $comment->comment_author,
                    'comment_content' => $comment->comment_content,
                    'comment_date' => $comment->comment_date,
                    'profile_picture_url' => $comment->profile_picture_url,
                    'replies_count' => $comment->replies_count,
                    'replies' => $comment->replies,
                    'user_id' => $comment->user_id
                );
            }
        }
        return $tree;
    }
}

function prepare_post_response($post)
{
    $post_id = $post->ID;
    $post_type = $post->post_type;
    $user_id = get_current_user_id();

    // Default counts
    $comments_count = get_comments_count_on_post($post_id);
    $likes_count = get_likes_count_on_post($post_id);
    $like = $user_id ? has_user_liked_post($post_id, $user_id) : false;
    $share_count = (int) get_post_meta($post_id, 'share_count', true);

    // Get media and profile info
    $media = get_post_media($post_id, $post_type);
    $profileInfo = get_profile_info($post_id, $post_type);

    // Initialize repost parameters
    $repost_post_id = "";
    $repost = false;

    // Check if the post is a repost
    $shared_post_id = get_post_meta($post_id, 'shared_post_id', true);
    $original_post_details = '';
    $shared_by = '';
    $share_at = '';
    // Fetch the latest two comments for the post
    $latest_comments_args = array(
        'post_id' => $post_id,
        'status' => 'approve',
        'number' => 2,  // Fetch only the latest 2 comments
        'order' => 'DESC',  // Latest comments first
    );
    if ($shared_post_id) {
        $repost_post_id = $shared_post_id;
        $repost = true;

        // Fetch details of the original post
        $original_post = get_post($shared_post_id);
        $original_post_details = prepare_post_response($original_post);

        // Override the counts with the original post's counts
        $comments_count = get_comments_count_on_post($shared_post_id);
        $likes_count = get_likes_count_on_post($shared_post_id);
        $like = $user_id ? has_user_liked_post($shared_post_id, $user_id) : false;  // Update $like to reflect the original post
        $share_count = (int) get_post_meta($shared_post_id, 'share_count', true);

        // Get the user who shared the post and the share time
        $shared_by = get_user_by('id', $post->post_author);
        $share_at = get_post_meta($post_id, 'shared_at', true);
        $latest_comments_args = array(
            'post_id' => $shared_post_id,
            'status' => 'approve',
            'number' => 2,  // Fetch only the latest 2 comments
            'order' => 'DESC',  // Latest comments first
        );
    }


    $latest_comments = get_comments($latest_comments_args);

    // Build the comment tree, similar to `get_post_comments`
    $comments_by_parent = array();
    foreach ($latest_comments as $comment) {

        $comments_by_parent[$comment->comment_parent][] = $comment;
    }

    // Build the latest comments tree using the previously declared function
    $latest_comments_tree = build_comment_tree_dashboard($comments_by_parent);

    return array(
        'id' => $post_id,
        'title' => get_the_title($post_id),
        'content' => get_the_content(null, false, $post_id),
        'date' => get_the_date('Y-m-d H:i:s', $post_id),
        'media' => $media,
        'post_type' => $post_type,
        'comments_count' => $comments_count,
        'likes_count' => $likes_count,
        'like' => $like,
        'profile_info' => $profileInfo,
        'share_count' => $share_count,
        'original_post_details' => $original_post_details,
        'shared_by' => $shared_by ? array(
            'user_id' => $shared_by->ID,
            'user_name' => $shared_by->user_nicename,
            'profile_pic' => get_avatar_url($shared_by->ID),
        ) : '',
        'share_at' => $share_at,
        'repost_post_id' => $repost_post_id,
        'repost' => $repost,
        'latest_comments' => $latest_comments_tree  // Add latest comments tree here
    );
}
