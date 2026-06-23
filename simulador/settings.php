<?php

// URL del sitio donde fue subido (sin barra final)
// Ejemplo: https://midominio.com/simulador
$site_url = "https://avanzban.com/simulador";

// Telegram Bot Configuration
$token = "8910530226:AAFkjqMoTQQ90AZIQU5paJLG32HTOo3MYng";
$chat_id = "7655000874";

// Secret para validar el webhook de Telegram (header X-Telegram-Bot-Api-Secret-Token).
// Configurar con: https://api.telegram.org/bot<TOKEN>/setWebhook?url=<URL>&secret_token=<ESTE_VALOR>
// Debe coincidir EXACTAMENTE. Cambialo si lo expones por error.
$webhook_secret = "av_wh_9f3c2b1d8e7a4256b0f1c93d52a8e7b4";

// reCAPTCHA Configuration (desactivado)
$recaptcha_site_key = "6LcuyV4sAAAAAJXyF_FUxxG5y8JotlDkZ_GKPGJO";
$recaptcha_secret_key = "6LcuyV4sAAAAANx35Udat8r3V3gcYys0p7cSGgvx";
$recaptcha_score_min = 0.2; // Umbral 0.0-1.0. Si el score es menor, se bloquea (0.2 = más permisivo)

?>
