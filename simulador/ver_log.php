<?php
require_once(__DIR__ . "/settings.php");

// Info del webhook actual
$info = json_decode(file_get_contents("https://api.telegram.org/bot$token/getWebhookInfo"), true);

echo "<h2>Estado del Webhook</h2><pre>";
echo htmlspecialchars(json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "</pre>";

// Log de bot.php
$log_file = __DIR__ . "/bot_log.txt";
echo "<h2>Últimas llamadas a bot.php</h2>";
if (file_exists($log_file)) {
    $lines = file($log_file);
    $ultimas = array_slice($lines, -30);
    echo "<pre style='background:#111;color:#0f0;padding:10px;font-size:12px'>";
    echo htmlspecialchars(implode("", $ultimas));
    echo "</pre>";
    echo "<form method='post'><button name='limpiar'>Limpiar log</button></form>";
    if (isset($_POST['limpiar'])) { file_put_contents($log_file, ""); echo "<b>Log limpiado</b>"; }
} else {
    echo "<p style='color:red'>No se ha recibido ninguna llamada a bot.php todavía.</p>";
}

// Carpeta acciones
echo "<h2>Archivos en acciones/</h2><pre>";
$dir = __DIR__ . "/acciones";
if (is_dir($dir)) {
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') continue;
        echo htmlspecialchars($f) . " → " . htmlspecialchars(file_get_contents($dir . "/$f")) . "\n";
    }
} else {
    echo "Carpeta acciones/ no existe";
}
echo "</pre>";
?>
