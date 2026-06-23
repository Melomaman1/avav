<?php
/**
 * og-image.php — Imagen OG (1200x630) para preview en redes sociales.
 * Tema: FinanzasNic — portal de finanzas personales Nicaragua.
 */

header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400, immutable');
header('X-Content-Type-Options: nosniff');

$W = 1200;
$H = 630;

$im = imagecreatetruecolor($W, $H);
imageantialias($im, true);

// --- Fondo: azul grisáceo oscuro (look editorial/finanzas) ---
$c1 = [18, 32, 58];
$c2 = [28, 50, 85];
for ($y = 0; $y < $H; $y++) {
    $t = $y / $H;
    $r = (int)($c1[0] + ($c2[0] - $c1[0]) * $t);
    $g = (int)($c1[1] + ($c2[1] - $c1[1]) * $t);
    $b = (int)($c1[2] + ($c2[2] - $c1[2]) * $t);
    imageline($im, 0, $y, $W, $y, imagecolorallocate($im, $r, $g, $b));
}

// Acento circular suave derecha
for ($i = 0; $i < 50; $i++) {
    $alpha = (int)(115 - $i * 2.2);
    if ($alpha < 0) break;
    $col = imagecolorallocatealpha($im, 60, 120, 200, $alpha);
    $rad  = 320 - $i * 5;
    imagefilledellipse($im, $W - 80, $H - 60, $rad, $rad, $col);
}

// Helper texto escalado
function bigText($im, $text, $x, $y, $size, $color, $bold = false) {
    $font = 5;
    $tw = imagefontwidth($font) * strlen($text);
    $th = imagefontheight($font);
    $tmp = imagecreatetruecolor($tw, $th);
    $bg  = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
    imagealphablending($tmp, false); imagesavealpha($tmp, true);
    imagefill($tmp, 0, 0, $bg);
    imagealphablending($tmp, true);
    $cc = imagecolorallocate($tmp, 255, 255, 255);
    imagestring($tmp, $font, 0, 0, $text, $cc);
    if ($bold) imagestring($tmp, $font, 1, 0, $text, $cc);
    $rgb = imagecolorsforindex($im, $color);
    for ($yy = 0; $yy < $th; $yy++) {
        for ($xx = 0; $xx < $tw; $xx++) {
            $a = (imagecolorat($tmp, $xx, $yy) >> 24) & 0x7F;
            if ($a < 127) imagesetpixel($tmp, $xx, $yy,
                imagecolorallocatealpha($tmp, $rgb['red'], $rgb['green'], $rgb['blue'], $a));
        }
    }
    $nW = (int)($tw * $size); $nH = (int)($th * $size);
    imagecopyresampled($im, $tmp, $x, $y, 0, 0, $nW, $nH, $tw, $th);
    imagedestroy($tmp);
}

$white  = imagecolorallocate($im, 255, 255, 255);
$teal   = imagecolorallocate($im, 80, 180, 160);
$light  = imagecolorallocate($im, 180, 200, 230);

// Barra superior
imagefilledrectangle($im, 0, 0, $W, 6, $teal);

// Nombre del sitio
bigText($im, 'FinanzasNic', 80, 60, 4.2, $white, true);

// Línea separadora
imagefilledrectangle($im, 80, 155, 320, 160, $teal);

// Título principal
bigText($im, 'Guias de Finanzas Personales', 80, 185, 5.8, $white, true);
bigText($im, 'para Nicaragua', 80, 275, 5.8, $white, true);

// Subtítulo
bigText($im, 'Credito, ahorro, inversion y presupuesto.', 80, 370, 2.6, $light, false);
bigText($im, 'Todo lo que necesitas saber para mejorar', 80, 410, 2.6, $light, false);
bigText($im, 'tu situacion financiera.', 80, 450, 2.6, $light, false);

// URL discreta
bigText($im, 'bnproblog.com', 80, 550, 2.0, $teal, false);

imagepng($im, null, 6);
imagedestroy($im);
