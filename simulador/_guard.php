<?php
// =====================================================================
// _guard.php — Validación de cookie HMAC del gate raíz para /simulador/
// Cualquier acceso sin cookie válida => 404 + blacklist progresiva.
// Bypass intencional: bot.php (webhook Telegram) NO incluye este guard,
// el .htaccess permite la ruta solo para método POST con secret header.
// =====================================================================

if (defined('SIM_GUARD_LOADED')) return;
define('SIM_GUARD_LOADED', true);

require_once __DIR__ . '/../_lib.php';

$_guard_ip = $_SERVER['REMOTE_ADDR'] ?? '';

// Kill switch global => fuera todos.
if (gate_kill_switch_active()) {
    http_response_code(503);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html><head><title>503 Service Unavailable</title></head><body><h1>Service Unavailable</h1></body></html>';
    exit;
}

// IP blacklisteada => 404 directo, sin loguear (ya está marcada).
if ($_guard_ip && gate_is_blacklisted($_guard_ip)) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>Not Found</h1></body></html>';
    exit;
}

// Si la cookie HMAC del gate es válida => continuar.
if (gate_has_valid_cookie()) {
    // Auto-sync del webhook de Telegram (idempotente; solo dispara si la URL cambió).
    // Se carga aquí porque _guard se incluye en todas las páginas protegidas.
    @include_once __DIR__ . '/_tg.php';
    if (function_exists('tg_ensure_webhook')) tg_ensure_webhook();
    return;
}

// ---- Acceso sin cookie: registrar IP en blacklist progresiva ----
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$blacklist_file   = __DIR__ . '/blocked_ips.txt';
$blocked_log_file = __DIR__ . '/blocked_log.txt';

if (filter_var($ip, FILTER_VALIDATE_IP)) {
    // Log del intento
    @file_put_contents(
        $blocked_log_file,
        date('Y-m-d H:i:s') . " | $ip | guard_no_cookie | " . ($_SERVER['REQUEST_URI'] ?? '?') . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );

    // Contador in-memory por IP (file-based, ventana de 10 min)
    $counter_dir = sys_get_temp_dir() . '/sim_guard_hits';
    @mkdir($counter_dir, 0700, true);
    $safe = preg_replace('/[^0-9a-fA-F:.]/', '_', $ip);
    $cfile = $counter_dir . "/$safe.json";
    $now = time();
    $state = ['n' => 0, 't' => $now];
    if (is_file($cfile)) {
        $raw = @file_get_contents($cfile);
        $tmp = $raw ? json_decode($raw, true) : null;
        if (is_array($tmp) && isset($tmp['n'], $tmp['t'])) $state = $tmp;
    }
    if (($now - $state['t']) > 600) $state = ['n' => 0, 't' => $now];
    $state['n']++;
    @file_put_contents($cfile, json_encode($state), LOCK_EX);

    // Tras 3 intentos en 10 min => blacklist permanente
    if ($state['n'] >= 3) {
        $bf = @fopen($blacklist_file, 'c+');
        if ($bf) {
            if (flock($bf, LOCK_EX)) {
                $existing = file($blacklist_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
                if (!in_array($ip, $existing, true)) {
                    fseek($bf, 0, SEEK_END);
                    fwrite($bf, $ip . PHP_EOL);
                }
                flock($bf, LOCK_UN);
            }
            fclose($bf);
        }
        @file_put_contents(
            $blocked_log_file,
            date('Y-m-d H:i:s') . " | $ip | auto_blacklisted_guard" . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
}

// Respuesta neutral: 404 sin pista (no revelar que existe el endpoint)
http_response_code(404);
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, private');
echo '<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL was not found on this server.</p></body></html>';
exit;
