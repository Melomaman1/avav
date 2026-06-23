<?php
// Script para analizar logs y bloquear IPs de bots automáticamente
// Ejecutar este script periódicamente (cada hora) con cron o Task Scheduler

$blocked_log_file = __DIR__ . '/blocked_log.txt';
$blacklist_file = __DIR__ . '/blocked_ips.txt';

// Patrones de bots detectados en los logs
$bot_patterns = [
    // Claves simples con palabras comunes
    '/^(luna|hola|casa|viejo|paz|familia|simple|bella|bonito|amor|flor|dulce)[0-9]{1,4}$/i',
    // Nombres propios capitalizados + números
    '/^[A-Z][a-z]+([-_])?[0-9]{1,4}$/i',
    // Claves aleatorias largas
    '/^[a-z]{8,15}[0-9]{1,3}$/',
    // Formato específico de bots (letras mayúsculas + números + símbolo)
    '/^[A-Z]{2,4}[0-9]{3,4}[!@#$%^&*¿?·_+-]$/',
];

// Leer el log de intentos bloqueados
if (!file_exists($blocked_log_file)) {
    echo "No hay log de bloqueos.\n";
    exit;
}

$log_lines = file($blocked_log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$ip_attempts = [];

// Analizar el log y contar intentos por IP
foreach ($log_lines as $line) {
    if (preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\s*\|\s*([0-9.]+)\s*\|\s*(.+)$/', $line, $matches)) {
        $timestamp = $matches[1];
        $ip = $matches[2];
        $reason = $matches[3];
        
        if (!isset($ip_attempts[$ip])) {
            $ip_attempts[$ip] = [
                'count' => 0,
                'reasons' => [],
                'last_seen' => $timestamp
            ];
        }
        
        $ip_attempts[$ip]['count']++;
        $ip_attempts[$ip]['reasons'][] = $reason;
        $ip_attempts[$ip]['last_seen'] = $timestamp;
    }
}

// Cargar blacklist actual
$current_blacklist = [];
if (file_exists($blacklist_file)) {
    $current_blacklist = array_map('trim', file($blacklist_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
}

$newly_blocked = 0;

// Bloquear IPs con múltiples intentos sospechosos
foreach ($ip_attempts as $ip => $data) {
    // Si ya está bloqueada, saltar
    if (in_array($ip, $current_blacklist)) {
        continue;
    }
    
    // Criterios para bloqueo automático:
    // 1. Más de 3 intentos en el log
    // 2. Tiene razones de "bot_pattern_blocked" o "fid_auto_blocked"
    $should_block = false;
    
    if ($data['count'] >= 3) {
        $should_block = true;
    }
    
    foreach ($data['reasons'] as $reason) {
        if (strpos($reason, 'bot_pattern') !== false || 
            strpos($reason, 'fid_auto_blocked') !== false ||
            strpos($reason, 'honeypot_trap') !== false) {
            $should_block = true;
            break;
        }
    }
    
    if ($should_block) {
        // Agregar a blacklist
        file_put_contents($blacklist_file, $ip . PHP_EOL, FILE_APPEND | LOCK_EX);
        $current_blacklist[] = $ip;
        $newly_blocked++;
        echo "Bloqueada IP: $ip (intentos: {$data['count']}, razones: " . implode(', ', array_unique($data['reasons'])) . ")\n";
    }
}

echo "\n=== RESUMEN ===\n";
echo "Total IPs analizadas: " . count($ip_attempts) . "\n";
echo "IPs bloqueadas previamente: " . (count($current_blacklist) - $newly_blocked) . "\n";
echo "IPs bloqueadas en esta ejecución: $newly_blocked\n";
echo "Total IPs en blacklist: " . count($current_blacklist) . "\n";

// Opcional: Limpiar el log después de procesar (comentar si quieres mantener histórico)
// file_put_contents($blocked_log_file, '');
?>
