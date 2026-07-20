<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/kpis.php';
require_once __DIR__ . '/lib/auth.php';

// Sin sesion iniciada no se muestra ningun indicador: se redirige al acceso.
exigir_sesion();

$error = null;
$rutas = $periodos = [];
$datos = null;

try {
    $pdo      = conectar_sqlite();
    $rutas    = listar_rutas($pdo);
    $periodos = listar_periodos($pdo);

    $ruta    = (string) ($_GET['ruta'] ?? 'TODAS');
    $periodo = (string) ($_GET['periodo'] ?? 'TODOS');

    // Validacion en servidor tambien aqui: los parametros llegan por URL.
    $codigos = array_column($rutas, 'codigo_ruta');
    if ($ruta !== 'TODAS' && !in_array($ruta, $codigos, true)) {
        $ruta = 'TODAS';
    }
    if ($periodo !== 'TODOS' && !in_array($periodo, $periodos, true)) {
        $periodo = 'TODOS';
    }

    $datos = obtener_kpis($pdo, $ruta, $periodo);
} catch (Throwable $e) {
    $error = $e->getMessage();
}

/** Escape para HTML. */
function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Analisis Inteligente de Demanda - Inteligencia de Negocios UTP</title>
<link rel="stylesheet" href="assets/estilo.css">
</head>
<body>

<header class="barra">
  <div class="contenedor barra-interna">
    <div>
      <h1>Analisis Inteligente de Demanda</h1>
      <p class="subtitulo"><?= h(NOMBRE_ORGANIZACION) ?> - Data Mart de validaciones</p>
    </div>
    <div class="barra-derecha">
      <span class="etiqueta-ia">IA: Google Gemini</span>
      <span class="sesion">
        <?= h(usuario_actual()) ?>
        <a href="logout.php" class="salir">Salir</a>
      </span>
    </div>
  </div>
</header>

<main class="contenedor">

<?php if ($error): ?>
  <div class="aviso aviso-error">
    <strong>No se pudo cargar el modulo.</strong><br><?= h($error) ?>
  </div>
<?php else: ?>

  <!-- ------------------------------------------------------------------ -->
  <!-- Filtros: definen el corte que se muestra y el que se manda a la IA  -->
  <!-- ------------------------------------------------------------------ -->
  <form class="panel filtros" method="get" id="form-filtros">
    <div class="campo">
      <label for="ruta">Ruta</label>
      <select name="ruta" id="ruta">
        <option value="TODAS"<?= $datos['filtro']['ruta'] === 'TODAS' ? ' selected' : '' ?>>Todas las rutas</option>
        <?php foreach ($rutas as $r): ?>
          <option value="<?= h($r['codigo_ruta']) ?>"<?= $datos['filtro']['ruta'] === $r['codigo_ruta'] ? ' selected' : '' ?>>
            <?= h($r['codigo_ruta']) ?> (<?= h($r['tipo_ruta']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="campo">
      <label for="periodo">Periodo</label>
      <select name="periodo" id="periodo">
        <option value="TODOS"<?= $datos['filtro']['periodo'] === 'TODOS' ? ' selected' : '' ?>>Todo el periodo</option>
        <?php foreach ($periodos as $p): ?>
          <option value="<?= h($p) ?>"<?= $datos['filtro']['periodo'] === $p ? ' selected' : '' ?>><?= h($p) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="campo campo-boton">
      <button type="submit" class="btn btn-secundario">Aplicar filtro</button>
    </div>
  </form>

  <!-- ------------------------------------------------------------------ -->
  <!-- Los 4 KPIs obligatorios, calculados en el servidor                  -->
  <!-- ------------------------------------------------------------------ -->
  <section class="tarjetas">
    <div class="tarjeta">
      <span class="tarjeta-titulo">Total de validaciones</span>
      <span class="tarjeta-valor"><?= number_format($datos['kpis']['total_validaciones']) ?></span>
      <span class="tarjeta-pie">abordajes registrados</span>
    </div>
    <div class="tarjeta">
      <span class="tarjeta-titulo">Ingreso total</span>
      <span class="tarjeta-valor">S/ <?= number_format($datos['kpis']['ingreso_total'], 2) ?></span>
      <span class="tarjeta-pie">ticket promedio S/ <?= number_format($datos['kpis']['ticket_promedio'], 2) ?></span>
    </div>
    <div class="tarjeta">
      <span class="tarjeta-titulo">Pasajeros por viaje</span>
      <span class="tarjeta-valor"><?= number_format($datos['kpis']['promedio_pasajeros_viaje'], 2) ?></span>
      <span class="tarjeta-pie"><?= number_format($datos['kpis']['num_carreras']) ?> carreras</span>
    </div>
    <div class="tarjeta">
      <span class="tarjeta-titulo">Demanda en hora punta</span>
      <span class="tarjeta-valor"><?= number_format($datos['kpis']['pct_hora_punta'], 2) ?>%</span>
      <span class="tarjeta-pie">franjas 6-8h y 17-19h</span>
    </div>
  </section>

  <!-- ------------------------------------------------------------------ -->
  <!-- Distribucion por tipo de pasaje (KPI 4)                             -->
  <!-- ------------------------------------------------------------------ -->
  <section class="panel">
    <h2>Distribucion por tipo de pasaje</h2>
    <table class="tabla">
      <thead>
        <tr><th>Tipo de pasaje</th><th class="num">Validaciones</th><th class="num">Participacion</th><th class="num">Ingreso</th></tr>
      </thead>
      <tbody>
      <?php
      $total_val = max(1, $datos['kpis']['total_validaciones']);
      foreach ($datos['tipo_pasaje'] as $tp):
          $pct = 100 * (int) $tp['validaciones'] / $total_val;
      ?>
        <tr>
          <td><?= h($tp['tipo']) ?></td>
          <td class="num"><?= number_format((int) $tp['validaciones']) ?></td>
          <td class="num">
            <div class="barra-pct"><span style="width: <?= round($pct, 2) ?>%"></span></div>
            <?= number_format($pct, 2) ?>%
          </td>
          <td class="num">S/ <?= number_format((float) $tp['ingreso'], 2) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>

  <!-- ------------------------------------------------------------------ -->
  <!-- Analisis generado por IA                                            -->
  <!-- ------------------------------------------------------------------ -->
  <section class="panel">
    <h2>Analisis ejecutivo generado por IA</h2>
    <p class="ayuda">
      El modelo recibe los indicadores ya calculados de este corte y devuelve una
      interpretacion en lenguaje natural con hallazgos, recomendaciones y alertas.
    </p>

    <button type="button" id="btn-analizar" class="btn btn-primario">
      Generar analisis con IA
    </button>

    <div id="resultado" class="resultado" hidden></div>
  </section>

  <!-- ------------------------------------------------------------------ -->
  <!-- Consulta en lenguaje natural (texto a SQL)                          -->
  <!-- ------------------------------------------------------------------ -->
  <section class="panel">
    <h2>Pregunta a los datos</h2>
    <p class="ayuda">
      Escribe una pregunta en lenguaje corriente. El modelo la traduce a una
      consulta sobre el Data Mart, se ejecuta en modo de solo lectura y la
      respuesta se redacta con las cifras obtenidas. La consulta ejecutada se
      muestra junto a la respuesta, para poder verificar de donde sale cada dato.
    </p>

    <form class="pregunta-form" id="form-pregunta">
      <input type="text" id="pregunta" name="pregunta" maxlength="400"
             placeholder="Ejemplo: cual es el paradero con mas demanda en la ruta R-01"
             autocomplete="off">
      <button type="submit" class="btn btn-primario" id="btn-preguntar">
        Preguntar
      </button>
    </form>

    <div class="sugerencias">
      <span>Prueba con:</span>
      <button type="button" class="chip">Cuales son las 5 rutas con mayor ingreso</button>
      <button type="button" class="chip">En que hora se concentra mas la demanda</button>
      <button type="button" class="chip">Cuanto representa el pasaje gratuito</button>
      <button type="button" class="chip">Que paraderos tienen mas demanda en la Zona Norte</button>
      <button type="button" class="chip">Que deberia hacer para mejorar los ingresos</button>
    </div>

    <div id="respuesta" class="resultado" hidden></div>
  </section>

  <p class="nota-etica">
    Conjunto de datos anonimizado: los codigos de ruta, paradero y zona fueron
    sustituidos por identificadores genericos y las medidas de volumen fueron
    perturbadas por un factor constante que preserva todos los ratios. Ver la
    seccion de Consideraciones Eticas del informe.
  </p>

<?php endif; ?>
</main>

<footer class="pie">
  <div class="contenedor">
    Proyecto Final - Inteligencia de Negocios (100000I62N) - UTP, Ciclo 1 2026
  </div>
</footer>

<script>
(function () {
  const boton     = document.getElementById('btn-analizar');
  const resultado = document.getElementById('resultado');
  if (!boton) return;

  /**
   * Render minimo del Markdown que devuelve el modelo (encabezados, vinetas,
   * negritas). Se escapa el HTML primero para no inyectar marcado ajeno.
   */
  function render(md) {
    const esc = md.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    const lineas = esc.split('\n');
    let html = '', enLista = false;

    for (let linea of lineas) {
      linea = linea.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');

      if (/^#{2,3}\s+/.test(linea)) {
        if (enLista) { html += '</ul>'; enLista = false; }
        html += '<h3>' + linea.replace(/^#{2,3}\s+/, '') + '</h3>';
      } else if (/^\s*[-*]\s+/.test(linea)) {
        if (!enLista) { html += '<ul>'; enLista = true; }
        html += '<li>' + linea.replace(/^\s*[-*]\s+/, '') + '</li>';
      } else if (linea.trim() === '') {
        if (enLista) { html += '</ul>'; enLista = false; }
      } else {
        if (enLista) { html += '</ul>'; enLista = false; }
        html += '<p>' + linea + '</p>';
      }
    }
    if (enLista) html += '</ul>';
    return html;
  }

  boton.addEventListener('click', async function () {
    // Candado del boton: evita que un doble clic dispare dos llamadas.
    boton.disabled = true;
    const textoOriginal = boton.textContent;
    boton.textContent = 'Analizando...';

    resultado.hidden = false;
    resultado.className = 'resultado cargando';

    // Contador visible: sin senal de avance la pagina parece colgada.
    let segundos = 0;
    resultado.innerHTML = '<p>Consultando el modelo... <strong>0 s</strong></p>';
    const reloj = setInterval(function () {
      segundos++;
      const aviso = segundos > 30
        ? '<br><span class="tenue">El servicio esta respondiendo lento. Se reintentara con otro modelo si hace falta.</span>'
        : '';
      resultado.innerHTML = '<p>Consultando el modelo... <strong>' + segundos + ' s</strong>' + aviso + '</p>';
    }, 1000);

    const datos = new FormData();
    datos.append('ruta', document.getElementById('ruta').value);
    datos.append('periodo', document.getElementById('periodo').value);

    try {
      const r = await fetch('api/analizar.php', { method: 'POST', body: datos });
      const j = await r.json();

      if (j.ok) {
        resultado.className = 'resultado';
        resultado.innerHTML = render(j.analisis)
          + '<p class="firma">Generado con ' + j.modelo + '</p>';
      } else {
        resultado.className = 'resultado resultado-error';
        resultado.innerHTML = '<p><strong>No se pudo generar el analisis.</strong></p><p>'
          + j.error.replace(/</g, '&lt;') + '</p>';
      }
    } catch (e) {
      resultado.className = 'resultado resultado-error';
      resultado.innerHTML = '<p><strong>Error de conexion.</strong></p><p>'
        + String(e).replace(/</g, '&lt;') + '</p>';
    } finally {
      clearInterval(reloj);
      // Se re-habilita para poder pedir otro corte sin recargar la pagina.
      boton.disabled = false;
      boton.textContent = textoOriginal;
    }
  });

  // ---------------------------------------------------------------------
  // Consulta en lenguaje natural
  // ---------------------------------------------------------------------
  const formP = document.getElementById('form-pregunta');
  const campoP = document.getElementById('pregunta');
  const btnP = document.getElementById('btn-preguntar');
  const cajaR = document.getElementById('respuesta');

  // Los ejemplos rellenan el campo y lanzan la consulta directamente.
  document.querySelectorAll('.chip').forEach(function (chip) {
    chip.addEventListener('click', function () {
      campoP.value = chip.textContent.trim();
      formP.requestSubmit();
    });
  });

  function tablaHtml(filas) {
    if (!filas || !filas.length) return '';
    const cols = Object.keys(filas[0]);
    let h = '<div class="tabla-scroll"><table class="tabla tabla-mini"><thead><tr>';
    cols.forEach(c => h += '<th>' + c + '</th>');
    h += '</tr></thead><tbody>';
    filas.forEach(function (f) {
      h += '<tr>';
      cols.forEach(c => h += '<td>' + (f[c] === null ? '' : f[c]) + '</td>');
      h += '</tr>';
    });
    return h + '</tbody></table></div>';
  }

  formP.addEventListener('submit', async function (ev) {
    ev.preventDefault();
    const pregunta = campoP.value.trim();
    if (!pregunta) return;

    // Cada pregunta implica dos llamadas al modelo: se bloquea el boton.
    btnP.disabled = true;
    const textoBtn = btnP.textContent;
    btnP.textContent = 'Consultando...';

    cajaR.hidden = false;
    cajaR.className = 'resultado cargando';
    let seg = 0;
    cajaR.innerHTML = '<p>Interpretando la pregunta... <strong>0 s</strong></p>';
    const reloj = setInterval(function () {
      seg++;
      cajaR.innerHTML = '<p>Interpretando la pregunta... <strong>' + seg + ' s</strong></p>';
    }, 1000);

    const datos = new FormData();
    datos.append('pregunta', pregunta);
    // El corte activo acota las preguntas de recomendacion.
    datos.append('ruta', document.getElementById('ruta').value);
    datos.append('periodo', document.getElementById('periodo').value);

    try {
      const r = await fetch('api/preguntar.php', { method: 'POST', body: datos });
      const j = await r.json();

      if (j.ok) {
        cajaR.className = 'resultado';

        // Las preguntas de recomendacion no ejecutan SQL: en su lugar se indica
        // sobre que corte se elaboro la respuesta.
        const detalle = (j.modo === 'consultiva')
          ? '<p class="nota-modo">Recomendacion elaborada sobre los indicadores ' +
            'del corte seleccionado. No ejecuta consulta: interpreta los datos ya calculados.</p>'
          : '<details class="detalle-sql"><summary>Ver la consulta ejecutada (' +
            j.n_filas + ' fila' + (j.n_filas === 1 ? '' : 's') + ')</summary>' +
            '<pre>' + j.sql.replace(/</g, '&lt;') + '</pre>' +
            tablaHtml(j.filas) + '</details>';

        cajaR.innerHTML =
          '<p class="pregunta-eco">' + pregunta.replace(/</g, '&lt;') + '</p>' +
          render(j.respuesta) + detalle +
          '<p class="firma">Generado con ' + j.modelo + '</p>';
        campoP.value = '';
      } else {
        cajaR.className = 'resultado resultado-error';
        cajaR.innerHTML = '<p><strong>No se pudo responder.</strong></p><p>' +
          j.error.replace(/</g, '&lt;') + '</p>';
      }
    } catch (e) {
      cajaR.className = 'resultado resultado-error';
      cajaR.innerHTML = '<p><strong>Error de conexion.</strong></p><p>' +
        String(e).replace(/</g, '&lt;') + '</p>';
    } finally {
      clearInterval(reloj);
      btnP.disabled = false;
      btnP.textContent = textoBtn;
    }
  });
})();
</script>

</body>
</html>
