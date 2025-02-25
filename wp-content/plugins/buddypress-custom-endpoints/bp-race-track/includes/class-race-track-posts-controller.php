<?php

class Race_Track_Posts_Controller
{

    const API_NAMESPACE = 'buddypress/v1';
    const ROUTE_BASE = '/race-track-posts';

    private $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->table_name = "{$wpdb->prefix}race_track_posts";
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes()
    {
        register_rest_route(self::API_NAMESPACE, self::ROUTE_BASE, [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_posts'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);

        register_rest_route(self::API_NAMESPACE, self::ROUTE_BASE . '/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_post'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);

        register_rest_route(self::API_NAMESPACE, self::ROUTE_BASE, [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'add_bulk_posts_to_race_track'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);


        register_rest_route(self::API_NAMESPACE, self::ROUTE_BASE . '/(?P<id>\d+)', [
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => [$this, 'update_post'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);

        register_rest_route(self::API_NAMESPACE, self::ROUTE_BASE . '/(?P<id>\d+)', [
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => [$this, 'delete_post'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
    }

    public function check_permissions($request)
    {
        return current_user_can('edit_posts');
    }

    public function get_posts($request)
    {
        global $wpdb;

        $posts = $wpdb->get_results("SELECT * FROM {$this->table_name} WHERE is_deleted = 0");

        if (empty($posts)) {
            return new WP_Error('no_posts', 'No posts found', ['status' => 404]);
        }

        return rest_ensure_response($posts);
    }

    public function get_post($request)
    {
        global $wpdb;

        $post_id = $request['id'];
        $post = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d AND is_deleted = 0", $post_id));

        if (!$post) {
            return new WP_Error('post_not_found', 'Post not found', ['status' => 404]);
        }

        return rest_ensure_response($post);
    }
    public function update_post($request)
    {
        global $wpdb;

        $post_id = $request['id'];
        $data = $request->get_json_params();

        if (empty($data['post_title'])) {
            return new WP_Error('missing_fields', 'post_title is required', ['status' => 400]);
        }

        $updated = $wpdb->update(
            $this->table_name,
            [
                'post_title' => sanitize_text_field($data['post_title']),
                'post_description' => sanitize_textarea_field($data['post_description']),
                'web_url' => esc_url($data['web_url']),
                'image_url' => esc_url($data['image_url']),
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $post_id]
        );

        if (!$updated) {
            return new WP_Error('update_failed', 'Failed to update the post', ['status' => 500]);
        }

        return rest_ensure_response(['message' => 'Post updated successfully']);
    }

    public function delete_post($request)
    {
        global $wpdb;

        $post_id = $request['id'];

        $deleted = $wpdb->update(
            $this->table_name,
            ['is_deleted' => 1],
            ['id' => $post_id]
        );

        if (!$deleted) {
            return new WP_Error('delete_failed', 'Failed to delete the post', ['status' => 500]);
        }

        return rest_ensure_response(['message' => 'Post deleted successfully']);
    }
    public function add_bulk_posts_to_race_track($request)
    {
        global $wpdb;

        // Retrieve data from the request
        $posts_data = $request->get_param('posts'); // Posts array
        $race_track_id = $request->get_param('race_track_id'); // Race track ID


        // Validate the required parameters
        if (empty($posts_data) || !is_array($posts_data) || empty($race_track_id)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Posts array and race track ID are required.',
            ], 400);
        }

        // Initialize result arrays
        $inserted_posts = [];
        $failed_posts = [];

        // Prepare the table name
        $table_name = $wpdb->prefix . 'race_track_posts';

        // Loop through each post and process it
        foreach ($posts_data as $index => $post) {
            // Validate post fields
            $title = sanitize_text_field($post['post_title'] ?? '');
            $description = sanitize_textarea_field($post['post_description'] ?? '');
            $web_url = esc_url_raw($post['web_url'] ?? '');

            if (empty($title)) {
                $failed_posts[] = [
                    'index' => $index,
                    'error' => 'Post title is required.',
                ];
                continue;
            }

            // Handle file upload if an image is provided
            $image_url = '';

            // Check if a file attachment exists for this post
            $attachment = [
                'name' => $_FILES['posts']['name'][$index]['attachment'] ?? null,
                'type' => $_FILES['posts']['type'][$index]['attachment'] ?? null,
                'tmp_name' => $_FILES['posts']['tmp_name'][$index]['attachment'] ?? null,
                'error' => $_FILES['posts']['error'][$index]['attachment'] ?? null,
                'size' => $_FILES['posts']['size'][$index]['attachment'] ?? null,
            ];

            if (!empty($attachment['tmp_name']) && $attachment['error'] === UPLOAD_ERR_OK) {
                error_log("Initiating image upload for post $index");

                // Optimize the image before uploading (if required)
                $optimized_path = optimize_image($attachment['tmp_name']);
                if (!$optimized_path || !file_exists($optimized_path)) {
                    $failed_posts[] = [
                        'index' => $index,
                        'error' => 'Image optimization failed.',
                    ];
                    continue;
                }

                // Replace the original path with the optimized one
                $attachment['tmp_name'] = $optimized_path;

                // Upload the image to S3 (or handle it otherwise)
                $image_url = upload_single_media_to_s3($attachment, 'race_track_post');
                if (is_wp_error($image_url)) {
                    $failed_posts[] = [
                        'index' => $index,
                        'error' => $image_url->get_error_message(),
                    ];
                    continue;
                }
            }


            // Prepare the data for insertion
            $post_data = [
                'post_title' => $title,
                'post_description' => $description,
                'web_url' => $web_url,
                'image_url' => $image_url,
                'race_track_id' => intval($race_track_id),
            ];

            // Insert the post into the database
            $result = $wpdb->insert($table_name, $post_data);

            if ($result === false) {
                $failed_posts[] = [
                    'index' => $index,
                    'error' => $wpdb->last_error,
                ];
            } else {
                $inserted_posts[] = [
                    'id' => $wpdb->insert_id,
                    'title' => $title,
                ];
            }
        }

        // Return the response with details
        return rest_ensure_response([
            'success' => true,
            'inserted_posts' => $inserted_posts,
            'failed_posts' => $failed_posts,
            'message' => 'Bulk insertion completed.',
        ]);
    }


}
