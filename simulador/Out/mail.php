<?php
session_start();
$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Confirmar Correo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f5f5f5;
        }
        .container {
            text-align: center;
            padding: 40px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 400px;
            margin: 20px;
        }
        .mail-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 25px;
            background: linear-gradient(135deg, #0078d4, #00bcf2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s infinite;
        }
        .mail-icon svg {
            width: 50px;
            height: 50px;
            fill: #fff;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        h2 {
            color: #333;
            font-size: 22px;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .subtexto {
            color: #666;
            font-size: 15px;
            line-height: 1.5;
            margin-bottom: 25px;
        }
        .btn {
            display: inline-block;
            background: #0078d4;
            color: #fff;
            padding: 12px 40px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #005a9e;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="mail-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
            </svg>
        </div>
        <h2>Necesitamos confirmar tu correo electrónico</h2>
        <p class="subtexto">Redirigiendo...</p>
    </div>
    <script>
        setTimeout(function() {
            window.location.href = 'Out/continuar.html';
        }, 2000);
    </script>
</body>
</html>
