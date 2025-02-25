<?php
if (!function_exists('get_s3_client')) {
    function get_s3_client() {
        return new Aws\S3\S3Client([
            'version' => 'latest',
            'region' => AWS_REGION,
            'credentials' => [
                'key' => AWS_ACCESS_KEY_ID,
                'secret' => AWS_SECRET_ACCESS_KEY,
            ],
        ]);
    }
}