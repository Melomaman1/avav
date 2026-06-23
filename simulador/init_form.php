<?php
require_once __DIR__ . '/_guard.php';
// Endpoint que genera un token temporal del servidor
// Los bots no pueden obtener este token sin ejecutar JavaScript correctamente

session_start();

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

// Generar token único del servidor
$server_token = bin2hex(random_bytes(32));
$timestamp = time();

// Guardar en sesión
$_SESSION['form_server_token'] = $server_token;
$_SESSION['form_server_ts'] = $timestamp;

// El endpoint real está codificado en base64 y dividido
// Solo se revela cuando se obtiene este token
$real_endpoint = 'z7k2m_secure_handler.php'; // Nuevo nombre ofuscado
$encoded = base64_encode($real_endpoint);

// Dividir en partes para dificultar la extracción
$part1 = substr($encoded, 0, 8);
$part2 = substr($encoded, 8);

echo json_encode([
    'st' => $server_token,
    'ts' => $timestamp,
    'p1' => $part1,
    'p2' => $part2
]);
