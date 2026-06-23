<?php
// Expone la site key de reCAPTCHA para el frontend (la site key es pública por diseño)
include(__DIR__ . '/settings.php');
header('Content-Type: application/javascript; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
echo 'var RECAPTCHA_SITE_KEY = ' . json_encode($recaptcha_site_key ?? '') . ';';
