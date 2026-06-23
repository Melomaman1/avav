<?php
require_once __DIR__ . '/_guard.php';
session_start();
$_SESSION['show_error'] = true;

$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$esMovil = preg_match('/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent);

if ($esMovil) {
    header("Location: indexmovil.html?error=1");
} else {
    header("Location: pcindex.html?error=1");
}
exit;
?>
