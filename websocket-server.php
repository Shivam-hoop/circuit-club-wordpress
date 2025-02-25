<?php

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

// Include the Composer autoload
require '/var/www/html/circuit-club/vendor/autoload.php';
require '/var/www/html/circuit-club/wp-load.php'; // Include WordPress functions

class ChatServer implements MessageComponentInterface
{
    protected $clients; // All connected WebSocket clients
    protected $chatConnections; // Maps chat IDs to connections
    protected $userConnections; // Maps user IDs to their connections

    public function __construct()
    {
        global $wpdb;
        $this->clients = new \SplObjectStorage;
        $this->chatConnections = [];
        $this->userConnections = [];
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);

        // Extract user ID and chat ID from the connection query string
        $queryParams = [];
        parse_str($conn->httpRequest->getUri()->getQuery(), $queryParams);

        if (isset($queryParams['user_id'], $queryParams['chat_id'])) {
            $user_id = (int) $queryParams['user_id'];
            $chat_id = (int) $queryParams['chat_id'];

            $this->notifyUserStatusChange($user_id, true);

            // Map user ID to connection
            $this->userConnections[$user_id] = $conn;

            // Map chat ID to connections
            if (!isset($this->chatConnections[$chat_id])) {
                $this->chatConnections[$chat_id] = [];
            }
            $this->chatConnections[$chat_id][$user_id] = $conn;

            echo "User $user_id connected to chat $chat_id\n";
        }
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);

        if (!isset($data['type'])) {
            echo "Invalid message format received: $msg\n";
            return;
        }

        switch ($data['type']) {
            case 'chat_message':
                $this->handleChatMessage($data, $from);
                break;

            case 'mark_as_read':
                $this->handleMarkAsRead($data);
                break;

            case 'get_unread_count':
                if (isset($data['user_id'])) {
                    $user_id = (int) $data['user_id'];
                    $this->sendUnreadCountToUser($user_id, $from);
                }
                break;

            default:
                echo "Unknown message type received: {$data['type']}\n";
        }
    }

    private function handleChatMessage($data, ConnectionInterface $from)
    {
        if (
            isset($data['chat_id'], $data['sender_id'], $data['message_content'])
        ) {
            $chat_id = (int) $data['chat_id'];
            $sender_id = (int) $data['sender_id'];
            $sender_avatar = $data['sender_avatar'];
            $message_content = $data['message_content'];

            // Optional media field
            $media = isset($data['media']) && is_array($data['media']) ? $data['media'] : [];

            // Prepare the message
            $message = [
                'type' => 'chat_message',
                'chat_id' => $chat_id,
                'sender_id' => $sender_id,
                'sender_avatar' => $sender_avatar,
                'message_content' => $message_content,
                'media' => $media,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            // Broadcast the message
            $this->broadcastToChat($chat_id, json_encode($message), $from);

            // $this->updateUnreadMessageCounts($chat_id);

        } else {
            echo "Invalid chat message format\n";
        }
    }

    private function handleMarkAsRead($data)
    {
        if (isset($data['chat_id'], $data['user_id'])) {
            $chat_id = (int) $data['chat_id'];
            $user_id = (int) $data['user_id'];
            $timestamp = date('Y-m-d H:i:s');

            // Update the database to mark all messages in this chat as read by the user
            global $wpdb;
            $wpdb->query($wpdb->prepare(
                "INSERT INTO {$wpdb->prefix}message_reads (message_id, user_id, read_at)
                 SELECT m.message_id, %d, %s
                 FROM {$wpdb->prefix}messages m
                 LEFT JOIN {$wpdb->prefix}message_reads mr
                 ON m.message_id = mr.message_id AND mr.user_id = %d
                 WHERE m.chat_id = %d AND m.sender_id != %d AND mr.message_id IS NULL",
                $user_id,
                $timestamp,
                $user_id,
                $chat_id,
                $user_id
            ));

            // Prepare a read receipt message
            $readReceipt = [
                'type' => 'read_receipt',
                'chat_id' => $chat_id,
                'user_id' => $user_id,
                'timestamp' => $timestamp,
            ];

            // Notify all participants in the chat
            $this->broadcastToChat($chat_id, json_encode($readReceipt));
            echo "Read receipt sent for chat $chat_id by user $user_id\n";
        } else {
            echo "Invalid mark as read format\n";
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);

        foreach ($this->chatConnections as $chat_id => $connections) {
            $user_id = array_search($conn, $connections, true);
            if ($user_id !== false) {
                unset($this->chatConnections[$chat_id][$user_id]);
                $this->notifyUserStatusChange($user_id, false);


                if (empty($this->chatConnections[$chat_id])) {

                    unset($this->chatConnections[$chat_id]);
                }
                echo "User $user_id disconnected from chat $chat_id\n";
            }
        }

        $user_id = array_search($conn, $this->userConnections, true);
        if ($user_id !== false) {
            unset($this->userConnections[$user_id]);
            echo "User $user_id disconnected\n";
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }

    private function broadcastToChat($chat_id, $message, ConnectionInterface $from = null)
    {
        if (isset($this->chatConnections[$chat_id])) {
            foreach ($this->chatConnections[$chat_id] as $user_id => $connection) {
                if ($connection !== $from) {
                    $connection->send($message);
                }
            }
            echo "Message broadcasted to chat $chat_id\n";
        } else {
            echo "Chat $chat_id has no participants connected\n";
        }
    }


    function notifyUserStatusChange($user_id, $isOnline)
    {
        global $wpdb;
        $table_user_chat_status = "{$wpdb->prefix}user_chat_activities";

        $timestamp = date('Y-m-d H:i:s');

        // Check if a record for the user exists
        $existingRecord = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_user_chat_status WHERE user_id = %d ORDER BY last_activity DESC LIMIT 1", $user_id));

        if ($existingRecord) {
            // Update existing record
            $wpdb->update(
                $table_user_chat_status,
                [
                    'is_online' => $isOnline,
                    'last_seen' => !$isOnline ? $timestamp : null,
                    'last_activity' => $timestamp
                ],
                ['user_id' => $user_id],
                ['%d', '%s', '%s'],
                ['%d']
            );
        } else {
            // No record exists, insert a new one
            $wpdb->insert(
                $table_user_chat_status,
                [
                    'user_id' => $user_id,
                    'is_online' => $isOnline,
                    'last_seen' => !$isOnline ? $timestamp : null,
                    'last_activity' => $timestamp
                ],
                ['%d', '%d', '%s', '%s']
            );
        }

        // Prepare and broadcast status update
        $statusMessage = json_encode([
            'type' => 'user_status',
            'user_id' => $user_id,
            'is_online' => $isOnline,
            'timestamp' => $timestamp,
        ]);

        foreach ($this->clients as $client) {
            $client->send($statusMessage);
        }

        echo "User status persisted and broadcasted: User $user_id is " . ($isOnline ? "online" : "offline") . "\n";
    }
    private function updateUnreadMessageCounts($chat_id)
    {
        global $wpdb;

        // Query unread message counts for all users in the chat
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT m.sender_id, COUNT(m.message_id) AS unread_count
             FROM {$wpdb->prefix}messages m
             LEFT JOIN {$wpdb->prefix}message_reads mr 
             ON m.message_id = mr.message_id
             WHERE m.chat_id = %d AND mr.message_id IS NULL
             GROUP BY m.sender_id",
                $chat_id
            )
        );

        $unreadCounts = [];
        foreach ($results as $row) {
            $unreadCounts[$row->sender_id] = (int) $row->unread_count;
        }

        // Notify clients about the updated unread counts
        $unreadCountMessage = json_encode([
            'type' => 'unread_counts',
            'chat_id' => $chat_id,
            'unread_counts' => $unreadCounts,
        ]);

        $this->broadcastToChat($chat_id, $unreadCountMessage);
    }
    private function sendUnreadCountToUser($user_id, ConnectionInterface $client)
    {
        global $wpdb;
    
        // Fetch unread message count for the current user
        $unread_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}messages m
             LEFT JOIN {$wpdb->prefix}message_reads mr
             ON m.message_id = mr.message_id AND mr.user_id = %d
             WHERE mr.message_id IS NULL AND m.sender_id != %d 
             AND m.chat_id IN (
                 SELECT chat_id FROM {$wpdb->prefix}chat_participants WHERE user_id = %d
             )",
            $user_id, $user_id, $user_id
        ));
    
        $unreadCountMessage = json_encode([
            'type' => 'unread_count',
            'user_id' => $user_id,
            'unread_count' => $unread_count,
        ]);
    
        $client->send($unreadCountMessage);
    }
    


}

// Create the WebSocket server
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatServer()
        )
    ),
    5558 // WebSocket port
);

$server->run();
