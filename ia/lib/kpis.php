<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function conectar_sqlite(): PDO
{
    if (!is_readable(RUTA_SQLITE)) {
        throw new RuntimeException(
            'No se encuentra el extracto de datos en ' . RUTA_SQLITE .
            '. Genera el archivo con etl/exportar_kpis_sqlite.py'
        );
    }
    $pdo = new PDO('sqlite:' . RUTA_SQLITE);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}

/** Rutas disponibles, ordenadas por volumen de demanda. */
function listar_rutas(PDO $pdo): array
{
    return $pdo->query(
        'SELECT codigo_ruta, tipo_ruta, SUM(validaciones) AS validaciones
           FROM kpi_mensual_ruta
          GROUP BY codigo_ruta, tipo_ruta
          ORDER BY validaciones DESC'
    )->fetchAll();
}

/** Periodos disponibles en el extracto. */
function listar_periodos(PDO $pdo): array
{
    return $pdo->query(
        'SELECT DISTINCT anio_mes FROM kpi_mensual_ruta ORDER BY anio_mes'
    )->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Construye el corte analitico para una ruta y un periodo.
 *
 * @param string $ruta    codigo de ruta, o 'TODAS'
 * @param string $periodo anio_mes (YYYY-MM), o 'TODOS'
 */
function obtener_kpis(PDO $pdo, string $ruta, string $periodo): array
{
    $cond = [];
    $par  = [];
    if ($ruta !== 'TODAS') {
        $cond[] = 'codigo_ruta = :ruta';
        $par[':ruta'] = $ruta;
    }
    if ($periodo !== 'TODOS') {
        $cond[] = 'anio_mes = :periodo';
        $par[':periodo'] = $periodo;
    }
    $where = $cond ? ' WHERE ' . implode(' AND ', $cond) : '';

    $consultar = function (string $sql) use ($pdo, $par): array {
        $st = $pdo->prepare($sql);
        $st->execute($par);
        return $st->fetchAll();
    };

    // Volumen e ingreso. Los dias con dato se toman como MAX por mes y luego
    // se suman: dentro de un mes las rutas comparten los mismos dias, y
    // sumarlos por ruta contaria el mismo dia varias veces.
    $totales = $consultar(
        "SELECT SUM(validaciones) AS validaciones, SUM(ingreso) AS ingreso,
                (SELECT SUM(d) FROM (
                    SELECT MAX(dias_con_dato) AS d
                      FROM kpi_mensual_ruta $where
                     GROUP BY anio_mes)) AS dias_con_dato
           FROM kpi_mensual_ruta $where"
    )[0];

    // Promedio de pasajeros por viaje: se calcula sobre el hecho de carreras
    // para evitar el doble conteo del hecho agregado.
    $carreras = $consultar(
        "SELECT SUM(carreras) AS carreras, SUM(pasajeros) AS pasajeros
           FROM kpi_mensual_carrera $where"
    )[0];

    $tipos = $consultar(
        "SELECT tipo, SUM(validaciones) AS validaciones, SUM(ingreso) AS ingreso
           FROM kpi_mensual_tipo_pasaje $where
          GROUP BY tipo ORDER BY validaciones DESC"
    );

    $horas = $consultar(
        "SELECT hora, hora_texto, franja, es_hora_punta,
                SUM(validaciones) AS validaciones
           FROM kpi_mensual_hora $where
          GROUP BY hora, hora_texto, franja, es_hora_punta
          ORDER BY hora"
    );

    $paraderos = $consultar(
        "SELECT paradero, zona, SUM(validaciones) AS validaciones
           FROM kpi_mensual_paradero $where
          GROUP BY paradero, zona
          ORDER BY validaciones DESC LIMIT 10"
    );

    $dias = $consultar(
        "SELECT dia_nombre, dia_semana, es_fin_semana,
                SUM(validaciones) AS validaciones
           FROM kpi_mensual_dia_semana $where
          GROUP BY dia_nombre, dia_semana, es_fin_semana
          ORDER BY dia_semana"
    );

    // El ranking de rutas solo tiene sentido si no se filtro una ruta.
    $ranking = [];
    if ($ruta === 'TODAS') {
        $ranking = $consultar(
            "SELECT codigo_ruta, tipo_ruta, SUM(validaciones) AS validaciones,
                    SUM(ingreso) AS ingreso
               FROM kpi_mensual_ruta $where
              GROUP BY codigo_ruta, tipo_ruta
              ORDER BY ingreso DESC LIMIT 8"
        );
    }

    $evolucion = [];
    if ($periodo === 'TODOS') {
        $evolucion = $consultar(
            "SELECT anio_mes, SUM(validaciones) AS validaciones,
                    SUM(ingreso) AS ingreso, MAX(dias_con_dato) AS dias_con_dato
               FROM kpi_mensual_ruta $where
              GROUP BY anio_mes ORDER BY anio_mes"
        );
        // Sin normalizar, un mes con 4 dias de registro parece de baja demanda.
        foreach ($evolucion as &$e) {
            $d = max(1, (int) $e['dias_con_dato']);
            $e['validaciones_por_dia'] = round((int) $e['validaciones'] / $d);
        }
        unset($e);
    }

    $validaciones  = (int) ($totales['validaciones'] ?? 0);
    $ingreso       = (float) ($totales['ingreso'] ?? 0);
    $num_carreras  = (int) ($carreras['carreras'] ?? 0);
    $pasajeros     = (int) ($carreras['pasajeros'] ?? 0);
    $dias_con_dato = (int) ($totales['dias_con_dato'] ?? 0);

    $val_punta = 0;
    foreach ($horas as $h) {
        if ((int) $h['es_hora_punta'] === 1) {
            $val_punta += (int) $h['validaciones'];
        }
    }

    return [
        'filtro' => ['ruta' => $ruta, 'periodo' => $periodo],
        'kpis' => [
            // Los nombres coinciden con las medidas DAX del dashboard.
            'total_validaciones'       => $validaciones,
            'ingreso_total'            => round($ingreso, 2),
            'promedio_pasajeros_viaje' => $num_carreras ? round($pasajeros / $num_carreras, 2) : 0,
            'num_carreras'             => $num_carreras,
            'ticket_promedio'          => $validaciones ? round($ingreso / $validaciones, 4) : 0,
            'pct_hora_punta'           => $validaciones ? round(100 * $val_punta / $validaciones, 2) : 0,
            'dias_con_dato'            => $dias_con_dato,
            'validaciones_por_dia'     => $dias_con_dato ? round($validaciones / $dias_con_dato) : 0,
        ],
        'tipo_pasaje' => $tipos,
        'por_hora'    => $horas,
        'paraderos'   => $paraderos,
        'dias'        => $dias,
        'ranking'     => $ranking,
        'evolucion'   => $evolucion,
    ];
}
