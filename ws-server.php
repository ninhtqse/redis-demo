<?php
require __DIR__ . '/vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop\Factory;
use Clue\React\Redis\Factory as RedisFactory;

class RedisPubSub implements MessageComponentInterface {
    protected $clients = [];
    protected $redisSubscriber;
    protected $loop;

    public function __construct($loop, $redisSubscriber) {
        $this->loop = $loop;
        $this->redisSubscriber = $redisSubscriber;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients[$conn->resourceId] = $conn;
        $conn->send(json_encode(['info' => 'WebSocket connected']));
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        if (isset($data['cmd']) && $data['cmd'] === 'SUBSCRIBE' && !empty($data['channel'])) {
            $channel = $data['channel'];
            $from->channel = $channel;
            $from->send(json_encode(['info' => "Subscribed to $channel"]));
            // Subscribe Redis channel
            $this->redisSubscriber->subscribe($channel);
        }
    }

    public function onClose(ConnectionInterface $conn) {
        unset($this->clients[$conn->resourceId]);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $conn->close();
    }

    public function broadcast($channel, $message) {
        foreach ($this->clients as $client) {
            if (isset($client->channel) && $client->channel === $channel) {
                $client->send(json_encode([
                    'channel' => $channel,
                    'message' => $message
                ]));
            }
        }
    }
}

$loop = Factory::create();
$redisFactory = new RedisFactory($loop);

$redisClientPromise = $redisFactory->createClient('redis://redis-node1:7000');

$redisClientPromise->then(function ($redis) use (&$pubsubApp, $loop) {
    $pubsubApp = new RedisPubSub($loop, $redis);

    $redis->on('message', function ($channel, $message) use ($pubsubApp) {
        $pubsubApp->broadcast($channel, $message);
    });

    $webSock = new React\Socket\Server('0.0.0.0:8089', $loop);
    $webServer = new Ratchet\Server\IoServer(
        new Ratchet\Http\HttpServer(
            new Ratchet\WebSocket\WsServer(
                $pubsubApp
            )
        ),
        $webSock,
        $loop
    );
});

$loop->run(); 