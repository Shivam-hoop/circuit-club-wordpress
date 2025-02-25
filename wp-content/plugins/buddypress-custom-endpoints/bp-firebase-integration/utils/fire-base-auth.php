<?php
use Google\Client;
use GuzzleHttp\Exception\RequestException;

function getAccessToken($serviceAccountPath)
{
    error_log('serviceAccountPath: ' . $serviceAccountPath);
    $client = new Client();
    $client->setAuthConfig($serviceAccountPath);
    $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
    $client->useApplicationDefaultCredentials();
    $token = $client->fetchAccessTokenWithAssertion();
    return $token['access_token'];
}

function sendMessage($accessToken, $projectId, $message)
{
    $url = 'https://fcm.googleapis.com/v1/projects/' . $projectId . '/messages:send';

    $client = new GuzzleHttp\Client();

    try {
        $response = $client->post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'message' => $message
            ],
        ]);

        // Decode and return the response body
        return json_decode($response->getBody()->getContents(), true);

    } catch (RequestException $e) {
        // Log error message for debugging
        error_log('Request error: ' . $e->getMessage());
        if ($e->hasResponse()) {
            error_log('Response body: ' . $e->getResponse()->getBody()->getContents());
        }
        return null;
    }
}