<?php
namespace MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class ChatSocket implements MessageComponentInterface {
    protected $clients;
    protected $user_connections; // Mapeo de user_id a connection

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->user_connections = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "Nueva conexión! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg);
        
        if ($data->type == 'auth') {
            $this->user_connections[$data->user_id] = $from;
            echo "Usuario {$data->user_id} autenticado en socket.\n";
            return;
        }

        if ($data->type == 'message') {
            $destinatario_id = $data->destinatario_id;
            $mensaje = $data->mensaje;
            $remitente_id = $data->remitente_id;

            // Enviar al destinatario si está conectado
            if (isset($this->user_connections[$destinatario_id])) {
                $this->user_connections[$destinatario_id]->send(json_encode([
                    'type' => 'new_message',
                    'remitente_id' => $remitente_id,
                    'mensaje' => $mensaje,
                    'fecha' => date('Y-m-d H:i:s')
                ]));
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        // Limpiar mapeo de usuarios
        foreach ($this->user_connections as $user_id => $socket) {
            if ($socket === $conn) {
                unset($this->user_connections[$user_id]);
                break;
            }
        }
        echo "Conexión {$conn->resourceId} cerrada\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }
}
?>
