<?php
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/api/ChatSocket.php';

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new \MyApp\ChatSocket()
        )
    ),
    8080
);

echo "Servidor de Sockets iniciado en el puerto 8080...\n";
$server->run();
?>
