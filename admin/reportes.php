<?php
// ============================================================
//  admin/reportes.php
//  Implementa las consultas UNIÓN, INTERSECCIÓN y DIFERENCIA
// ============================================================
require_once '../includes/auth.php';
requireLogin();
require_once '../config/database.php';
$db = getDB();

// ============================================================
// CONSULTA A: UNIÓN
// Une (sin duplicados) el resultado de dos SELECT:
//   - Estudiantes con algún registro de acceso
//   - Profesores con algún registro de acceso
// Propósito: listar TODOS los usuarios (de cualquier tipo)
// que han tenido actividad en la sala, en un solo conjunto.
// ============================================================
$unionQuery = "
    SELECT u.nombre, u.apellido, 'Estudiante' AS tipo_label,
           u.programa_facultad, COUNT(r.id_registro) AS total_accesos
    FROM usuarios u
    INNER JOIN registros_acceso r ON u.id_usuario = r.id_usuario
    WHERE u.tipo_usuario = 'estudiante'
    GROUP BY u.id_usuario

    UNION

    SELECT u.nombre, u.apellido, 'Profesor' AS tipo_label,
           u.programa_facultad, COUNT(r.id_registro) AS total_accesos
    FROM usuarios u
    INNER JOIN registros_acceso r ON u.id_usuario = r.id_usuario
    WHERE u.tipo_usuario = 'profesor'
    GROUP BY u.id_usuario

    ORDER BY tipo_label, apellido
";
$unionResult = $db->query($unionQuery)->fetchAll();

// ============================================================
// CONSULTA B: INTERSECCIÓN (simulada con IN doble)
// MySQL no tiene INTERSECT nativo; se implementa con subconsultas IN.
// Devuelve usuarios que tienen TANTO registros de tipo 'entrada'
// COMO de tipo 'salida', es decir, quienes completaron ciclos.
// Propósito: identificar usuarios con uso completo de la sala.
// ============================================================
$interseccionQuery = "
    SELECT DISTINCT u.id_usuario, u.nombre, u.apellido,
                    u.tipo_usuario, u.programa_facultad
    FROM usuarios u
    WHERE u.id_usuario IN (
        SELECT id_usuario FROM registros_acceso WHERE tipo_movimiento = 'entrada'
    )
    AND u.id_usuario IN (
        SELECT id_usuario FROM registros_acceso WHERE tipo_movimiento = 'salida'
    )
    ORDER BY u.apellido
";
$interseccionResult = $db->query($interseccionQuery)->fetchAll();

// ============================================================
// CONSULTA C: DIFERENCIA (simulada con NOT IN)
// MySQL no tiene EXCEPT nativo; se implementa con NOT IN.
// Devuelve usuarios registrados en el sistema que NUNCA han
// tenido ningún movimiento de entrada/salida en la sala.
// Propósito: detectar usuarios inactivos / no han usado la sala.
// ============================================================
$diferenciaQuery = "
    SELECT u.id_usuario, u.nombre, u.apellido,
           u.tipo_usuario, u.programa_facultad, u.estado
    FROM usuarios u
    WHERE u.id_usuario NOT IN (
        SELECT DISTINCT id_usuario FROM registros_acceso
    )
    ORDER BY u.tipo_usuario, u.apellido
";
$diferenciaResult = $db->query($diferenciaQuery)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reportes SQL — Sala de Sistemas</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="wrapper">
  <?php include '../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div>
        <div class="breadcrumb">admin / reportes</div>
        <h1>📈 Reportes — Consultas SQL Especiales</h1>
      </div>
    </div>
    <div class="page-body">

      <div class="card" style="border-color:#3b82f6;border-left-width:4px;">
        <div class="card-title" style="color:#3b82f6;">ℹ️ Sobre estas consultas</div>
        <p style="font-size:13px;color:var(--text-muted);line-height:1.7;">
          Esta sección ejecuta directamente las tres operaciones de álgebra relacional solicitadas:
          <strong style="color:var(--text-main)">UNIÓN, INTERSECCIÓN y DIFERENCIA</strong>.
          MySQL no soporta de forma nativa <code>INTERSECT</code> ni <code>EXCEPT</code>,
          por lo que se simulan con subconsultas <code>IN</code> y <code>NOT IN</code>
          respectivamente, logrando el mismo resultado semántico.
        </p>
      </div>

      <!-- UNIÓN -->
      <div class="card" style="border-top: 3px solid var(--success);">
        <div class="card-title">
          <span style="color:var(--success);font-family:monospace;font-size:16px;">∪</span>
          A. UNIÓN — Todos los usuarios con actividad en sala
        </div>
        <div style="background:var(--bg-dark);border-radius:8px;padding:14px;margin-bottom:16px;font-family:'JetBrains Mono',monospace;font-size:11px;color:#94a3b8;line-height:1.8;overflow-x:auto;">
SELECT nombre, apellido, 'Estudiante' AS tipo ... FROM usuarios JOIN registros_acceso WHERE tipo='estudiante' GROUP BY id_usuario<br>
<span style="color:var(--success);font-weight:700;">UNION</span><br>
SELECT nombre, apellido, 'Profesor' AS tipo ... FROM usuarios JOIN registros_acceso WHERE tipo='profesor' GROUP BY id_usuario
        </div>
        <p style="font-size:12px;color:var(--text-muted);margin-bottom:14px;">
          🔍 Une sin duplicados los estudiantes y profesores que han registrado al menos un acceso. Resultado: <?= count($unionResult) ?> usuarios únicos con actividad.
        </p>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Nombre</th><th>Apellido</th><th>Tipo</th><th>Programa/Facultad</th><th>Total Accesos</th></tr></thead>
            <tbody>
              <?php foreach ($unionResult as $r): ?>
              <tr>
                <td><?= htmlspecialchars($r['nombre']) ?></td>
                <td><?= htmlspecialchars($r['apellido']) ?></td>
                <td><span class="badge badge-<?= strtolower($r['tipo_label']) ?>"><?= $r['tipo_label'] ?></span></td>
                <td><?= htmlspecialchars($r['programa_facultad']) ?></td>
                <td class="mono text-accent"><?= $r['total_accesos'] ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($unionResult)): ?>
              <tr><td colspan="5" style="text-align:center;color:var(--text-muted);">Sin datos.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- INTERSECCIÓN -->
      <div class="card" style="border-top: 3px solid var(--accent);">
        <div class="card-title">
          <span style="color:var(--accent);font-family:monospace;font-size:16px;">∩</span>
          B. INTERSECCIÓN — Usuarios con ciclo completo (entrada Y salida)
        </div>
        <div style="background:var(--bg-dark);border-radius:8px;padding:14px;margin-bottom:16px;font-family:'JetBrains Mono',monospace;font-size:11px;color:#94a3b8;line-height:1.8;overflow-x:auto;">
SELECT DISTINCT u.* FROM usuarios u<br>
WHERE u.id_usuario <span style="color:var(--accent);font-weight:700;">IN</span> (SELECT id_usuario FROM registros_acceso WHERE tipo_movimiento='entrada')<br>
<span style="color:var(--accent);font-weight:700;">AND</span> u.id_usuario IN (SELECT id_usuario FROM registros_acceso WHERE tipo_movimiento='salida')
        </div>
        <p style="font-size:12px;color:var(--text-muted);margin-bottom:14px;">
          🔍 Usuarios que tienen registros de ENTRADA y también de SALIDA — han completado al menos un ciclo. Resultado: <?= count($interseccionResult) ?> usuarios.
        </p>
        <div class="table-wrap">
          <table>
            <thead><tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Tipo</th><th>Programa/Facultad</th></tr></thead>
            <tbody>
              <?php foreach ($interseccionResult as $r): ?>
              <tr>
                <td class="mono text-muted"><?= $r['id_usuario'] ?></td>
                <td><?= htmlspecialchars($r['nombre']) ?></td>
                <td><?= htmlspecialchars($r['apellido']) ?></td>
                <td><span class="badge badge-<?= $r['tipo_usuario'] ?>"><?= ucfirst($r['tipo_usuario']) ?></span></td>
                <td><?= htmlspecialchars($r['programa_facultad']) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($interseccionResult)): ?>
              <tr><td colspan="5" style="text-align:center;color:var(--text-muted);">Sin usuarios con ciclo completo.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- DIFERENCIA -->
      <div class="card" style="border-top: 3px solid var(--warning);">
        <div class="card-title">
          <span style="color:var(--warning);font-family:monospace;font-size:16px;">−</span>
          C. DIFERENCIA — Usuarios sin ningún acceso registrado
        </div>
        <div style="background:var(--bg-dark);border-radius:8px;padding:14px;margin-bottom:16px;font-family:'JetBrains Mono',monospace;font-size:11px;color:#94a3b8;line-height:1.8;overflow-x:auto;">
SELECT u.* FROM usuarios u<br>
WHERE u.id_usuario <span style="color:var(--warning);font-weight:700;">NOT IN</span> (<br>
&nbsp;&nbsp;SELECT DISTINCT id_usuario FROM registros_acceso<br>
)
        </div>
        <p style="font-size:12px;color:var(--text-muted);margin-bottom:14px;">
          🔍 Usuarios registrados en el sistema que NUNCA han aparecido en un registro de acceso (ni entrada ni salida). Resultado: <?= count($diferenciaResult) ?> usuarios sin actividad.
        </p>
        <div class="table-wrap">
          <table>
            <thead><tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Tipo</th><th>Programa/Facultad</th><th>Estado</th></tr></thead>
            <tbody>
              <?php foreach ($diferenciaResult as $r): ?>
              <tr>
                <td class="mono text-muted"><?= $r['id_usuario'] ?></td>
                <td><?= htmlspecialchars($r['nombre']) ?></td>
                <td><?= htmlspecialchars($r['apellido']) ?></td>
                <td><span class="badge badge-<?= $r['tipo_usuario'] ?>"><?= ucfirst($r['tipo_usuario']) ?></span></td>
                <td><?= htmlspecialchars($r['programa_facultad']) ?></td>
                <td><span class="badge badge-<?= $r['estado'] ?>"><?= ucfirst($r['estado']) ?></span></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($diferenciaResult)): ?>
              <tr><td colspan="6" style="text-align:center;color:var(--text-muted);">Todos los usuarios tienen al menos un acceso registrado.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>
</body>
</html>
