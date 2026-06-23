<?php
require_once("settings.php");

// Esta herramienta SOLO debe ejecutarse manualmente desde el navegador
// una vez (al cambiar de dominio). El .htaccess la deniega externamente;
// para usarla, comentá temporalmente la regla deny y revertí después.

$webhook_url = rtrim($site_url, '/') . "/bot.php";

$params = [
    'url'          => $webhook_url,
    'secret_token' => $webhook_secret,  // debe coincidir con bot.php
    'max_connections' => 10,
    'allowed_updates' => json_encode(['message', 'callback_query']),
];

$api = "https://api.telegram.org/bot$token/setWebhook?" . http_build_query($params);
$response = @file_get_contents($api);
$result = json_decode($response, true);

if ($result && !empty($result['ok'])) {
    echo "<b>✅ Webhook configurado correctamente</b><br>";
    echo "URL registrada: <code>" . htmlspecialchars($webhook_url) . "</code><br>";
    echo "Secret token: <code>" . htmlspecialchars(substr($webhook_secret, 0, 8)) . "…</code> (oculto)";
} else {
    echo "<b>❌ Error al configurar el webhook</b><br>";
    echo "<pre>" . htmlspecialchars($response ?: 'sin respuesta') . "</pre>";
}
?>
