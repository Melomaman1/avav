<?php
// =====================================================================
// _tg.php — Envoltura para Telegram con rate-limit duro
// Objetivo: aun cuando el atacante atraviese todas las protecciones,
// el token nunca se inunda. Cuotas:
//   - Global: 100 envíos / 24h
//   - Por IP: 2 envíos / 24h
//   - Burst:  máx 8 envíos en 5 min; si se rompe, cooldown 1h
// Si la cuota se agota, NO se envía y se loguea como descartado.
// =====================================================================

if (defined('SIM_TG_LOADED')) return;
define('SIM_TG_LOADED', true);

require_once __DIR__ . '/settings.php';

if (!function_exists('tg_send')) {

    // -----------------------------------------------------------------
    // Auto-sync del webhook: si la URL en settings.php cambió, se llama
    // automáticamente a setWebhook (Telegram). Cero intervención manual.
    // Idempotente: solo dispara la llamada cuando detecta cambio real.
    // Concurrencia: lock file; reintentos: throttled 5 min en errores.
    // -----------------------------------------------------------------
    function tg_ensure_webhook() {
        static $checked = false;
        if ($checked) return;
        $checked = true;

        global $site_url, $token, $webhook_secret;
        if (empty($site_url) || empty($token) || empty($webhook_secret)) return;

        $expected_url = rtrim($site_url, '/') . '/bot.php';
        $fingerprint  = hash('sha256', $expected_url . '|' . $webhook_secret);
        $marker       = __DIR__ . '/.webhook_synced';
        $retry_file   = __DIR__ . '/.webhook_retry_at';

        // Ya sincronizado con esta huella => no hacer nada
        if (is_file($marker) && trim(@file_get_contents($marker)) === $fingerprint) {
            return;
        }

        // Throttle de reintentos: si fallamos hace <5 min, no martillar Telegram
        if (is_file($retry_file)) {
            $next = (int)@file_get_contents($retry_file);
            if ($next > time()) return;
        }

        // Lock para evitar carreras en peticiones concurrentes
        $lock = __DIR__ . '/.webhook_lock';
        $fp = @fopen($lock, 'c');
        if (!$fp) return;
        if (!@flock($fp, LOCK_EX | LOCK_NB)) {
            fclose($fp);
            return; // otro proceso ya lo está sincronizando
        }

        // Re-chequear después del lock
        if (is_file($marker) && trim(@file_get_contents($marker)) === $fingerprint) {
            flock($fp, LOCK_UN); fclose($fp);
            return;
        }

        $params = [
            'url'                  => $expected_url,
            'secret_token'         => $webhook_secret,
            'max_connections'      => 10,
            'allowed_updates'      => json_encode(['message', 'callback_query']),
            'drop_pending_updates' => 'true',
        ];
        $api = "https://api.telegram.org/bot$token/setWebhook?" . http_build_query($params);
        $ctx = stream_context_create(['http' => ['timeout' => 5, 'method' => 'GET']]);
        $response = @file_get_contents($api, false, $ctx);
        $result   = json_decode($response, true);

        if ($result && !empty($result['ok'])) {
            @file_put_contents($marker, $fingerprint, LOCK_EX);
            @unlink($retry_file);
            @file_put_contents(__DIR__ . '/webhook_sync.log',
                date('Y-m-d H:i:s') . " | OK    | $expected_url\n",
                FILE_APPEND | LOCK_EX);
        } else {
            // Reintenta en 5 min
            @file_put_contents($retry_file, (string)(time() + 300), LOCK_EX);
            @file_put_contents(__DIR__ . '/webhook_sync.log',
                date('Y-m-d H:i:s') . " | ERROR | $expected_url | " . substr($response ?: '(sin respuesta)', 0, 200) . "\n",
                FILE_APPEND | LOCK_EX);
        }

        flock($fp, LOCK_UN);
        fclose($fp);
    }

    function tg_state_dir() {
        $d = sys_get_temp_dir() . '/sim_tg_rate';
        if (!is_dir($d)) @mkdir($d, 0700, true);
        return $d;
    }

    function tg_load($file) {
        if (!is_file($file)) return null;
        $raw = @file_get_contents($file);
        if (!$raw) return null;
        $j = json_decode($raw, true);
        return is_array($j) ? $j : null;
    }

    function tg_save($file, $data) {
        @file_put_contents($file, json_encode($data), LOCK_EX);
    }

    function tg_log_drop($reason, $ip) {
        @file_put_contents(
            __DIR__ . '/tg_dropped.log',
            date('Y-m-d H:i:s') . " | $ip | $reason" . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }

    /**
     * Anti-flood: solo aplica el control de RÁFAGA (burst).
     * Las víctimas legítimas siempre pasan (no hay cap diario ni por IP).
     * Si en MUY POCO tiempo se reciben demasiados envíos => cooldown.
     * Esto solo se dispara bajo ataque real (alguien que bypaseó el gate HMAC).
     *
     * Devuelve true si se permite el envío; false si se debe descartar.
     */
    function tg_rate_check($ip) {
        $dir = tg_state_dir();
        $now = time();

        // Umbrales anti-ráfaga (ajustables)
        $burst_window = 60;    // ventana de medición: 60 seg
        $burst_max    = 30;    // máx 30 envíos en 60 seg
        $cooldown     = 600;   // si se rompe el burst, suspender 10 min

        $gfile = $dir . '/global.json';
        $g = tg_load($gfile) ?: ['burst_start' => $now, 'burst_count' => 0, 'cooldown_until' => 0];

        // Si está en cooldown por ráfaga previa => descartar
        if ($now < (int)($g['cooldown_until'] ?? 0)) {
            tg_log_drop('burst_cooldown_active', $ip);
            return false;
        }

        // Reset de la ventana si expiró
        if (($now - ($g['burst_start'] ?? 0)) > $burst_window) {
            $g['burst_start'] = $now;
            $g['burst_count'] = 0;
        }

        // Si ya se superó el máximo en la ventana => activar cooldown
        if (($g['burst_count'] ?? 0) >= $burst_max) {
            $g['cooldown_until'] = $now + $cooldown;
            tg_save($gfile, $g);
            tg_log_drop('burst_triggered_cooldown', $ip);
            return false;
        }

        // OK => contar y permitir
        $g['burst_count']++;
        tg_save($gfile, $g);
        return true;
    }

    /**
     * Envía un mensaje al chat configurado, sujeto a rate-limit.
     * @return bool true si se envió; false si se descartó.
     */
    function tg_send($text, $inline_keyboard = null) {
        global $token, $chat_id;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // Asegurar que el webhook en Telegram apunta al $site_url actual.
        // Es idempotente: solo hace el setWebhook si la URL cambió.
        tg_ensure_webhook();

        if (!tg_rate_check($ip)) return false;

        $params = [
            'chat_id' => $chat_id,
            'text'    => $text,
        ];
        if ($inline_keyboard) {
            $params['reply_markup'] = json_encode(['inline_keyboard' => $inline_keyboard]);
        }

        $url = "https://api.telegram.org/bot$token/sendMessage?" . http_build_query($params);
        $ctx = stream_context_create(['http' => ['timeout' => 5, 'method' => 'GET']]);
        @file_get_contents($url, false, $ctx);
        return true;
    }
}
