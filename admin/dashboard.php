<?php
// ============================================================
//  admin/dashboard.php
// ============================================================
require_once '../includes/auth.php';
requireLogin();
require_once '../config/database.php';
$db = getDB();

// Estadísticas
$totalUsuarios   = $db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$totalEstudiantes= $db->query("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario='estudiante'")->fetchColumn();
$totalProfesores = $db->query("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario='profesor'")->fetchColumn();
$totalAccesos    = $db->query("SELECT COUNT(*) FROM registros_acceso")->fetchColumn();
$enSala          = $db->query("
    SELECT COUNT(DISTINCT u.id_usuario)
    FROM usuarios u
    WHERE u.id_usuario IN (
        SELECT r1.id_usuario FROM registros_acceso r1
        WHERE r1.id_registro = (
            SELECT id_registro FROM registros_acceso r2
            WHERE r2.id_usuario = r1.id_usuario
            ORDER BY fecha_hora DESC, id_registro DESC LIMIT 1
        ) AND r1.tipo_movimiento = 'entrada'
    )
")->fetchColumn();

// Últimos registros
$ultimosRegistros = $db->query("
    SELECT r.id_registro, u.nombre, u.apellido, u.tipo_usuario,
           a.nombre_actividad, r.tipo_movimiento, r.fecha_hora, r.equipo_asignado
    FROM registros_acceso r
    JOIN usuarios u ON r.id_usuario = u.id_usuario
    JOIN actividades_catalogo a ON r.id_actividad = a.id_actividad
    ORDER BY r.fecha_hora DESC, r.id_registro DESC
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — Sala de Sistemas</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="wrapper">
  <?php include '../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div>
        <div class="breadcrumb">admin / dashboard</div>
        <h1>Dashboard</h1>
      </div>
      <div style="font-size:12px;color:var(--text-muted);"><?= date('d/m/Y H:i') ?></div>
    </div>
    <div class="page-body">

      <div class="stat-grid">
        <div class="stat-card">
          <div class="label">Usuarios Totales</div>
          <div class="value"><?= $totalUsuarios ?></div>
          <div class="sub">Registrados en el sistema</div>
        </div>
        <div class="stat-card">
          <div class="label">Estudiantes</div>
          <div class="value" style="color:var(--accent2)"><?= $totalEstudiantes ?></div>
          <div class="sub">Activos en el sistema</div>
        </div>
        <div class="stat-card">
          <div class="label">Profesores</div>
          <div class="value"><?= $totalProfesores ?></div>
          <div class="sub">Activos en el sistema</div>
        </div>
        <div class="stat-card">
          <div class="label">En Sala Ahora</div>
          <div class="value" style="color:var(--success)"><?= $enSala ?></div>
          <div class="sub">Personas actualmente dentro</div>
        </div>
      </div>

      <div class="card">
        <div class="card-title">🚪 Últimos Movimientos</div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Usuario</th>
                <th>Tipo</th>
                <th>Actividad</th>
                <th>Movimiento</th>
                <th>Equipo</th>
                <th>Fecha/Hora</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($ultimosRegistros as $r): ?>
              <tr>
                <td class="mono text-muted"><?= $r['id_registro'] ?></td>
                <td><?= htmlspecialchars($r['nombre'] . ' ' . $r['apellido']) ?></td>
                <td><span class="badge badge-<?= $r['tipo_usuario'] ?>"><?= ucfirst($r['tipo_usuario']) ?></span></td>
                <td><?= htmlspecialchars($r['nombre_actividad']) ?></td>
                <td><span class="badge badge-<?= $r['tipo_movimiento'] ?>"><?= ucfirst($r['tipo_movimiento']) ?></span></td>
                <td class="mono"><?= htmlspecialchars($r['equipo_asignado'] ?? '—') ?></td>
                <td class="mono text-muted"><?= date('d/m/Y H:i', strtotime($r['fecha_hora'])) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($ultimosRegistros)): ?>
              <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:24px;">Sin registros aún.</td></tr>
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
