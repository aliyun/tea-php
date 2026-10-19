<?php

require __DIR__ . '/../../vendor/autoload.php';

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class EchoWebSocketServer implements MessageComponentInterface
{
    protected $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $payload = (string) $msg;
        if ($payload === 'please-close') {
            $from->close();

            return;
        }
        if ($payload === 'please-binary') {
            $from->send(new \Ratchet\RFC6455\Messaging\Frame(
                'bin',
                true,
                \Ratchet\RFC6455\Messaging\Frame::OP_BINARY
            ));

            return;
        }
        $from->send($msg);
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $conn->close();
    }
}

$port = isset($argv[1]) ? (int) $argv[1] : 18080;
$server = Ratchet\Server\IoServer::factory(
    new Ratchet\Http\HttpServer(
        new Ratchet\WebSocket\WsServer(new EchoWebSocketServer())
    ),
    $port
);
$server->run();
