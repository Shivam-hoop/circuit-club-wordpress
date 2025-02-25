<?php
// File: wp-content/plugins/custom-video-processing/video-processing.php

if (!defined('ABSPATH')) exit;

require_once 'vendor/autoload.php'; // for AWS SDK, if required

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class VideoProcessor {
    private $s3_client;

    public function __construct() {
        $this->s3_client = $this->get_s3_client();
    }

    /**
     * Enqueue video processing background job
     */
    public function enqueue_video_processing_job($file_path) {
        as_enqueue_async_action('process_video_in_background', [$file_path]);
    }

    /**
     * Process video: compress and upload
     */
    public function process_video_in_background($file_path) {
        try {
            // Step 1: Compress the video
            $compressed_path = $this->compress_video($file_path);
            if (!$compressed_path) {
                $this->log_error("Video compression failed for {$file_path}");
                return;
            }

            // Step 2: Upload to S3
            $s3_url = $this->upload_to_s3($compressed_path);
            if (!$s3_url) {
                $this->log_error("S3 upload failed for {$compressed_path}");
                return;
            }

            // Step 3: Update database or media library metadata (optional)
            $this->update_video_status($file_path, $s3_url);

            // Step 4: Clean up local files
            unlink($compressed_path);

        } catch (Exception $e) {
            $this->log_error("Error processing video: " . $e->getMessage());
        }
    }

    /**
     * Compress the video
     */
    private function compress_video($file_path) {
        $compressed_path = str_replace('.mp4', '-compressed.mp4', $file_path);

        // Example compression command using FFmpeg
        $command = "ffmpeg -i " . escapeshellarg($file_path) . " -vcodec libx264 -crf 28 " . escapeshellarg($compressed_path);
        exec($command, $output, $return_var);

        if ($return_var !== 0) {
            $this->log_error("Compression command failed: " . implode("\n", $output));
            return false;
        }
        return $compressed_path;
    }

    /**
     * Upload compressed video to S3
     */
    private function upload_to_s3($file_path) {
        try {
            $bucket = 'circuit-club';
            $key = 'uploads/' . basename($file_path);

            $result = $this->s3_client->putObject([
                'Bucket' => $bucket,
                'Key' => $key,
                'SourceFile' => $file_path,
            ]);

            return $result['ObjectURL'] ?? false;
        } catch (AwsException $e) {
            $this->log_error("S3 Upload Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Initialize and return S3 client
     */
    private function get_s3_client() {
        return new S3Client([
            'version' => 'latest',
            'region'  => 'us-west-2',
            'credentials' => [
                'key'    => 'your-access-key',
                'secret' => 'your-secret-key',
            ],
        ]);
    }

    /**
     * Update video status in the database or media library (optional)
     */
    private function update_video_status($original_path, $s3_url) {
        $this->log_message("Video uploaded and processed successfully: {$s3_url}");
    }

    /**
     * Log error messages for debugging
     */
    private function log_error($message) {
        error_log("[VideoProcessor Error] " . $message);
    }

    /**
     * Log informational messages
     */
    private function log_message($message) {
        error_log("[VideoProcessor Info] " . $message);
    }
}

$video_processor = new VideoProcessor();

// Hook for background processing action
add_action('process_video_in_background', [$video_processor, 'process_video_in_background']);
