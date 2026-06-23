<?php
// Configuración
include(__DIR__ . '/settings.php');
$RECAPTCHA_SECRET_KEY = $recaptcha_secret_key;
$RECAPTCHA_MIN_SCORE = $recaptcha_score_min;

// Función para verificar reCAPTCHA
function verifyRecaptcha($token, $secretKey) {
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => $secretKey,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    $result = json_decode($response, true);
    
    return $result;
}

// Verificar si es una petición POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validar reCAPTCHA
    $recaptchaToken = $_POST['recaptcha_token'] ?? '';
    
    if (empty($recaptchaToken)) {
        die('Error: Token de reCAPTCHA no proporcionado');
    }
    
    // Verificar el token con Google
    $recaptchaResult = verifyRecaptcha($recaptchaToken, $RECAPTCHA_SECRET_KEY);
    
    if (!$recaptchaResult['success']) {
        die('Error: Verificación de reCAPTCHA fallida. ' . 
            ($recaptchaResult['error-codes'][0] ?? 'Error desconocido'));
    }
    
    // Verificar score si es reCAPTCHA v3
    if (isset($recaptchaResult['score']) && $recaptchaResult['score'] < $RECAPTCHA_MIN_SCORE) {
        die('Error: Puntuación de reCAPTCHA demasiado baja');
    }
    
    // Si llegamos aquí, reCAPTCHA es válido
    // Continuar con el procesamiento del login...
    
    $usuario = $_POST['pp1'] ?? '';
    $contrasena = $_POST['pp2'] ?? '';
    
    // Validaciones adicionales (CSRF, honeypot, etc.)
    $csrfToken = $_POST['csrf_token'] ?? '';
    $serverToken = $_POST['server_token'] ?? '';
    $honeypot = $_POST['honeypot'] ?? '';
    $formTs = $_POST['form_ts'] ?? '';
    
    // Validar honeypot
    if (!empty($honeypot)) {
        die('Error: Detectado bot');
    }
    
    // Validar timestamp
    if (empty($formTs) || (time() - $formTs) < 1) {
        die('Error: Formulario enviado demasiado rápido');
    }
    
    // Aquí iría tu lógica de autenticación
    // Por ejemplo: verificar credenciales en base de datos
    
    echo "Login procesado correctamente. reCAPTCHA verificado.";
    
} else {
    // Si no es POST, redirigir al formulario
    header('Location: pcindex.html');
    exit;
}
?>
