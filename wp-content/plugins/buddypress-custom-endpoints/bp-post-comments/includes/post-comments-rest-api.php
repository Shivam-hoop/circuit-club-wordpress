<?php
add_action('rest_api_init', function () {
    register_rest_route(
        'buddypress/v1',
        '/post/(?P<post_id>\d+)/comments',
        array(
            'methods' => 'GET',
            'callback' => 'get_post_comments',
            'permission_callback' => '__return_true',
        )
    );
    register_rest_route(
        'buddypress/v1',
        '/comment-replies/(?P<comment_id>\d+)',
        array(
            'methods' => 'GET',
            'callback' => 'get_comment_replies',
            'permission_callback' => '__return_true', // Adjust permissions as needed
        )
    );
});

//comments with tree

function get_post_comments(WP_REST_Request $request)
{
    $user_id = get_current_user_id();

    switch_to_user_language($user_id);

    $post_id = $request->get_param('post_id');
    $page = $request->get_param('page') ? intval($request->get_param('page')) : 1;
    $per_page = $request->get_param('per_page') ? intval($request->get_param('per_page')) : 20;

    if (empty($post_id) || !get_post($post_id)) {
        return new WP_Error('invalid_post', 'Invalid post ID', array('status' => 400));
    }

    $args = array(
        'post_id' => $post_id,
        'status' => 'approve',
        'number' => $per_page,
        'offset' => ($page - 1) * $per_page,
    );

    $comments = get_comments($args);

    // Build comment tree
    $comments_by_parent = array();
    error_log(json_encode($comments));
    foreach ($comments as $comment) {
        $comments_by_parent[$comment->comment_parent][] = $comment;
    }

    // Add replies count and build tree
    function build_comment_tree($comments_by_parent, $parent_id = 0)
    {
        $tree = array();
        if (!empty($comments_by_parent[$parent_id])) {
            foreach ($comments_by_parent[$parent_id] as $comment) {
                $comment->comment_content = apply_filters('the_content', $comment->comment_content);
                $comment->profile_picture_url = $comment->user_id ? bp_core_fetch_avatar(array('item_id' => $comment->user_id, 'html' => false)) : '';
                $comment->replies = build_comment_tree($comments_by_parent, $comment->comment_ID);
                $comment->replies_count = count($comment->replies);
                $tree[] = $comment;
            }
        }
        return $tree;
    }

    $comment_tree = build_comment_tree($comments_by_parent);

    return new WP_REST_Response($comment_tree, 200);
}
// function get_post_comments(WP_REST_Request $request)
// {
//     $post_id = $request->get_param('post_id');
//     $page = $request->get_param('page') ? intval($request->get_param('page')) : 1;
//     $per_page = $request->get_param('per_page') ? intval($request->get_param('per_page')) : 10;

//     if (empty($post_id) || !get_post($post_id)) {
//         return new WP_Error('invalid_post', 'Invalid post ID', array('status' => 400));
//     }

//     // $post_type = get_post_type($post_id);

//     $args = array(
//         'post_id' => $post_id,
//         'status' => 'approve',
//         'number' => $per_page,
//         'offset' => ($page - 1) * $per_page,
//     );

//     $comments = get_comments($args);

//     foreach ($comments as $comment) {
//         $comment->profile_picture_url = $comment->user_id ? bp_core_fetch_avatar(array('item_id' => $comment->user_id, 'html' => false)) : '';
//         // $comment->post_type = $post_type;
//         $comment->replies_count = 0; // Set default replies count as 0
//     }

//     return new WP_REST_Response($comments, 200);
// }


function get_comment_replies(WP_REST_Request $request)
{
    $user_id = get_current_user_id();

    switch_to_user_language($user_id);

    $comment_id = $request->get_param('comment_id');
    $page = $request->get_param('page') ? intval($request->get_param('page')) : 1;
    $per_page = $request->get_param('per_page') ? intval($request->get_param('per_page')) : 10;

    if (empty($comment_id) || !get_comment($comment_id)) {
        return new WP_Error('invalid_comment', 'Invalid comment ID', array('status' => 400));
    }

    $parent_comment = get_comment($comment_id);
    $post_id = $parent_comment->comment_post_ID;

    // Fetch replies (child comments) for the given comment
    $args = array(
        'post_id' => $post_id,
        'status' => 'approve',
        'number' => $per_page,
        'offset' => ($page - 1) * $per_page,
    );

    // Fetch all comments, not just direct children, to build the full tree
    $replies = get_comments($args);
    // error_log("replies: " . json_encode($replies));

    if (empty($replies)) {
        return new WP_REST_Response(array(), 200); // Return empty array if no replies found
    }

    // Organize replies by parent comment ID
    $replies_by_parent = array();
    foreach ($replies as $reply) {
        $replies_by_parent[$reply->comment_parent][] = $reply;
    }
    // error_log("replies_by_parent: " . json_encode($replies_by_parent));

    // Function to build a tree of replies with reply counts
    function build_reply_tree($replies_by_parent, $parent_id)
    {
        $tree = array();
        if (!empty($replies_by_parent[$parent_id])) {
            foreach ($replies_by_parent[$parent_id] as $reply) {
                $reply->profile_picture_url = $reply->user_id ? bp_core_fetch_avatar(array('item_id' => $reply->user_id, 'html' => false)) : '';
                $reply->replies = build_reply_tree($replies_by_parent, $reply->comment_ID); // Recursively build replies
                $reply->replies_count = count($reply->replies); // Count replies
                $tree[] = $reply;
            }
        }
        return $tree;
    }

    // Start building the reply tree from the parent comment
    $reply_tree = build_reply_tree($replies_by_parent, $comment_id);

    return new WP_REST_Response($reply_tree, 200);
}
