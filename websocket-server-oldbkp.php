<?php
// ini_set('display_errors', 1);
// error_reporting(E_ALL);
// ini_set('log_errors', 1);
// ini_set('error_log', '/var/www/html/circuit-club/wp-content/debug.log');

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

// Include the Composer autoload
require '/var/www/html/circuit-club/vendor/autoload.php';

class NotificationServer implements MessageComponentInterface {
    protected $clients;
    protected $userConnections;

    public function __construct() {
        $this->clients = new \SplObjectStorage;  // Stores all connections
        $this->userConnections = [];  // Maps user IDs to their WebSocket connection
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the connection
        $this->clients->attach($conn);

        // Store user information if sent (via query string)
        $queryParams = [];
        parse_str($conn->httpRequest->getUri()->getQuery(), $queryParams);
        if (isset($queryParams['user_id'])) {
            $user_id = (int) $queryParams['user_id'];
            $this->userConnections[$user_id] = $conn;
            echo "User $user_id connected\n";
        }
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);

        // Ensure the message has the correct structure
        if (isset($data['type'], $data['user_id'], $data['data']) && $data['type'] === 'notification') {
            $user_id = (int) $data['user_id'];
            $message = $data['data'];

            // Send the notification only to the specified user
            $this->sendNotification($user_id, $message);
        } else {
            echo "Invalid message format received: $msg\n";
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // Remove connection on close
        $this->clients->detach($conn);

        // Remove from userConnections
        $user_id = array_search($conn, $this->userConnections);
        if ($user_id !== false) {
            unset($this->userConnections[$user_id]);
            echo "User $user_id disconnected\n";
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }

    public function sendNotification($user_id, $message) {
        if (isset($this->userConnections[$user_id])) {
            $connection = $this->userConnections[$user_id];
            $connection->send(json_encode($message));
            echo "Notification sent to User $user_id\n";
        } else {
            echo "User $user_id is not connected\n";
        }
    }
}

// Create the WebSocket server and bind it to a port
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new NotificationServer()
        )
    ),
    5556  // WebSocket port
);

$server->run();
