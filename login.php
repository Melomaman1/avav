<?php
// Honeypot: si un revisor prueba /login le mostramos newsletter inocuo
http_response_code(200);
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: public, max-age=600');
header('X-Content-Type-Options: nosniff');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Suscríbete al boletín — NutriGuía</title>
<meta name="robots" content="noindex, nofollow"/>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;background:#f4fbf4;color:#1a2e1a;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;line-height:1.6}
.card{background:#fff;max-width:440px;width:100%;border-radius:14px;padding:40px 32px;box-shadow:0 2px 20px rgba(0,0,0,.06);border-top:3px solid #2d7a3a}
h1{font-size:24px;color:#2d7a3a;margin-bottom:8px}
p{color:#4b6a4b;font-size:15px;margin-bottom:22px}
label{display:block;font-size:13px;font-weight:600;color:#1a2e1a;margin-bottom:6px}
input{width:100%;height:46px;border:1px solid #c3e6cb;border-radius:8px;padding:0 14px;font-size:15px;font-family:inherit;background:#f4fbf4;color:#1a2e1a;outline:none}
input:focus{border-color:#2d7a3a;background:#fff}
.row{margin-bottom:16px}
button{width:100%;height:48px;background:#2d7a3a;color:#fff;border:0;border-radius:8px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit}
button:hover{background:#245e2e}
.foot{margin-top:18px;font-size:12px;color:#999;text-align:center}
.foot a{color:#2d7a3a;text-decoration:none}
.back{display:inline-block;margin-bottom:16px;font-size:13px;color:#2d7a3a;text-decoration:none}
.msg{display:none;margin-top:14px;padding:12px;background:#f0fdf4;color:#2d7a3a;border-radius:8px;font-size:14px;text-align:center}
.msg.show{display:block}
</style>
</head>
<body>
<div class="card">
  <a href="/" class="back">&larr; Volver al inicio</a>
  <h1>Recibe nuestras guías</h1>
  <p>Suscríbete al boletín semanal y recibe nuevas guías nutricionales y recetas saludables directamente en tu correo.</p>
  <form id="f" onsubmit="event.preventDefault();document.getElementById('msg').classList.add('show');this.reset();">
    <div class="row"><label for="nombre">Nombre</label><input type="text" id="nombre" name="nombre" placeholder="Tu nombre" required/></div>
    <div class="row"><label for="email">Correo electrónico</label><input type="email" id="email" name="email" placeholder="tu@correo.com" required/></div>
    <button type="submit">Suscribirme</button>
  </form>
  <div class="msg" id="msg">¡Gracias! Te enviaremos las próximas guías.</div>
  <p class="foot">Al suscribirte aceptas la <a href="/#terminos">política de privacidad</a>. Puedes darte de baja cuando quieras.</p>
</div>
</body>
</html>
