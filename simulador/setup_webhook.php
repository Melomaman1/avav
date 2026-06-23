<?php
require_once("settings.php");

// =====================================================================
// Herramienta de configuración del webhook de Telegram.
// Protegida por clave secreta en la URL: ?key=ADMIN_KEY
// Cambiá esa clave aquí abajo antes de subir.
// =====================================================================

$ADMIN_KEY = "mi_diag_2026_x9k2"; // misma clave que /?diag=... o cambiala

$provided = (string)($_GET['key'] ?? '');
if (!hash_equals($ADMIN_KEY, $provided)) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>Not Found</h1></body></html>';
    exit;
}

header('Content-Type: text/html; charset=UTF-8');
echo "<html><body style='font-family:sans-serif;max-width:700px;margin:30px auto;'>";
echo "<h2>Configuración Webhook Telegram</h2>";

$action = $_GET['action'] ?? 'status';
$api_base = "https://api.telegram.org/bot$token";

// ---- 1) Estado actual ----
echo "<h3>📡 Estado actual del webhook</h3>";
$info_raw = @file_get_contents("$api_base/getWebhookInfo");
$info = json_decode($info_raw, true);
if ($info && !empty($info['ok'])) {
    $w = $info['result'];
    echo "<pre style='background:#f0f0f0;padding:10px;border-radius:5px;'>";
    echo "URL actual:           " . htmlspecialchars($w['url'] ?? '(sin webhook)') . "\n";
    echo "Has custom cert:      " . (!empty($w['has_custom_certificate']) ? 'sí' : 'no') . "\n";
    echo "Pending updates:      " . ($w['pending_update_count'] ?? 0) . "\n";
    echo "Last error date:      " . (!empty($w['last_error_date']) ? date('Y-m-d H:i:s', $w['last_error_date']) : '-') . "\n";
    echo "Last error message:   " . htmlspecialchars($w['last_error_message'] ?? '-') . "\n";
    echo "Max connections:      " . ($w['max_connections'] ?? '-') . "\n";
    echo "Allowed updates:      " . implode(',', $w['allowed_updates'] ?? []) . "\n";
    echo "</pre>";
} else {
    echo "<p style='color:red'>❌ No se pudo consultar Telegram: " . htmlspecialchars($info_raw ?: 'sin respuesta') . "</p>";
}

// ---- 2) Acciones ----
$webhook_url = rtrim($site_url, '/') . "/bot.php";

if ($action === 'register') {
    echo "<h3>⚙️ Registrando webhook nuevo...</h3>";
    $params = [
        'url'             => $webhook_url,
        'secret_token'    => $webhook_secret,
        'max_connections' => 10,
        'allowed_updates' => json_encode(['message', 'callback_query']),
        'drop_pending_updates' => 'true',
    ];
    $response = @file_get_contents("$api_base/setWebhook?" . http_build_query($params));
    $result = json_decode($response, true);
    if ($result && !empty($result['ok'])) {
        echo "<p style='color:green;font-weight:bold;'>✅ Webhook registrado correctamente</p>";
        echo "<p>URL: <code>" . htmlspecialchars($webhook_url) . "</code></p>";
        echo "<p>Secret token: <code>" . htmlspecialchars(substr($webhook_secret, 0, 8)) . "...</code></p>";
        echo "<p><a href='?key=" . urlencode($ADMIN_KEY) . "'>↻ Refrescar estado</a></p>";
    } else {
        echo "<p style='color:red;'>❌ Error al registrar:</p>";
        echo "<pre>" . htmlspecialchars($response ?: 'sin respuesta') . "</pre>";
    }
} elseif ($action === 'delete') {
    echo "<h3>🗑️ Eliminando webhook...</h3>";
    $response = @file_get_contents("$api_base/deleteWebhook?drop_pending_updates=true");
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
} else {
    echo "<h3>🛠️ Acciones</h3>";
    echo "<p>URL configurada en <code>settings.php</code>: <code>" . htmlspecialchars($webhook_url) . "</code></p>";
    echo "<p>";
    echo "<a href='?key=" . urlencode($ADMIN_KEY) . "&action=register' style='background:#007;color:white;padding:8px 16px;text-decoration:none;border-radius:4px;'>📝 Registrar / Re-registrar webhook</a>&nbsp;&nbsp;";
    echo "<a href='?key=" . urlencode($ADMIN_KEY) . "&action=delete' style='background:#700;color:white;padding:8px 16px;text-decoration:none;border-radius:4px;'>🗑️ Eliminar webhook</a>";
    echo "</p>";
}

echo "</body></html>";
?>
