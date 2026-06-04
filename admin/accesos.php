<?php
// ============================================================
//  admin/accesos.php
//  Historial de accesos con filtros y edición
// ============================================================
require_once '../includes/auth.php';
requireLogin();
require_once '../config/database.php';
$db = getDB();

// --- Eliminar registro ---
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $db->prepare("DELETE FROM registros_acceso WHERE id_registro = ?")->execute([(int)$_GET['delete']]);
    header('Location: accesos.php?msg=deleted');
    exit;
}

// --- Guardar edición ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $stmt = $db->prepare("
        UPDATE registros_acceso
        SET id_usuario=?, id_actividad=?, tipo_movimiento=?, observaciones=?, equipo_asignado=?
        WHERE id_registro=?
    ");
    $stmt->execute([
        (int)$_POST['id_usuario'],
        (int)$_POST['id_actividad'],
        $_POST['tipo_movimiento'],
        $_POST['observaciones'],
        $_POST['equipo_asignado'],
        (int)$_POST['edit_id'],
    ]);
    header('Location: accesos.php?msg=updated');
    exit;
}

// --- Filtros ---
$where = ['1=1'];
$params = [];

if (!empty($_GET['tipo_usuario'])) {
    $where[] = "u.tipo_usuario = ?";
    $params[] = $_GET['tipo_usuario'];
}
if (!empty($_GET['tipo_movimiento'])) {
    $where[] = "r.tipo_movimiento = ?";
    $params[] = $_GET['tipo_movimiento'];
}
if (!empty($_GET['fecha_desde'])) {
    $where[] = "DATE(r.fecha_hora) >= ?";
    $params[] = $_GET['fecha_desde'];
}
if (!empty($_GET['fecha_hasta'])) {
    $where[] = "DATE(r.fecha_hora) <= ?";
    $params[] = $_GET['fecha_hasta'];
}
if (!empty($_GET['buscar'])) {
    $where[] = "(u.nombre LIKE ? OR u.apellido LIKE ? OR u.numero_identificacion LIKE ?)";
    $like = '%' . $_GET['buscar'] . '%';
    $params = array_merge($params, [$like, $like, $like]);
}

$whereStr = implode(' AND ', $where);
$stmt = $db->prepare("
    SELECT r.id_registro, u.nombre, u.apellido, u.tipo_usuario, u.numero_identificacion,
           a.nombre_actividad, r.tipo_movimiento, r.fecha_hora, r.observaciones, r.equipo_asignado,
           r.id_usuario, r.id_actividad
    FROM registros_acceso r
    JOIN usuarios u ON r.id_usuario = u.id_usuario
    JOIN actividades_catalogo a ON r.id_actividad = a.id_actividad
    WHERE $whereStr
    ORDER BY r.fecha_hora DESC, r.id_registro DESC
    LIMIT 200
");
$stmt->execute($params);
$registros = $stmt->fetchAll();

$usuarios    = $db->query("SELECT id_usuario, numero_identificacion, nombre, apellido, tipo_usuario FROM usuarios ORDER BY apellido")->fetchAll();
$actividades = $db->query("SELECT * FROM actividades_catalogo ORDER BY nombre_actividad")->fetchAll();

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Historial de Accesos — Sala de Sistemas</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="wrapper">
  <?php include '../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div>
        <div class="breadcrumb">admin / accesos</div>
        <h1>🚪 Historial de Accesos</h1>
      </div>
      <a href="registro.php" class="btn btn-primary btn-sm">+ Nuevo Registro</a>
    </div>
    <div class="page-body">

      <?php if ($msg === 'updated'): ?>
        <div class="alert alert-success">✅ Registro actualizado correctamente.</div>
      <?php elseif ($msg === 'deleted'): ?>
        <div class="alert alert-danger">🗑 Registro eliminado.</div>
      <?php endif; ?>

      <!-- FILTROS -->
      <div class="card">
        <div class="card-title">🔍 Filtrar Registros</div>
        <form method="GET" action="">
          <div class="filter-bar">
            <div class="form-group">
              <label>Buscar</label>
              <input type="text" name="buscar" placeholder="Nombre, apellido o ID…" value="<?= htmlspecialchars($_GET['buscar'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label>Tipo de usuario</label>
              <select name="tipo_usuario">
                <option value="">Todos</option>
                <option value="estudiante" <?= ($_GET['tipo_usuario'] ?? '') === 'estudiante' ? 'selected' : '' ?>>Estudiante</option>
                <option value="profesor"   <?= ($_GET['tipo_usuario'] ?? '') === 'profesor'   ? 'selected' : '' ?>>Profesor</option>
              </select>
            </div>
            <div class="form-group">
              <label>Movimiento</label>
              <select name="tipo_movimiento">
                <option value="">Todos</option>
                <option value="entrada" <?= ($_GET['tipo_movimiento'] ?? '') === 'entrada' ? 'selected' : '' ?>>Entrada</option>
                <option value="salida"  <?= ($_GET['tipo_movimiento'] ?? '') === 'salida'  ? 'selected' : '' ?>>Salida</option>
              </select>
            </div>
            <div class="form-group">
              <label>Desde</label>
              <input type="date" name="fecha_desde" value="<?= htmlspecialchars($_GET['fecha_desde'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label>Hasta</label>
              <input type="date" name="fecha_hasta" value="<?= htmlspecialchars($_GET['fecha_hasta'] ?? '') ?>">
            </div>
            <div class="form-group" style="justify-content:flex-end;">
              <label>&nbsp;</label>
              <div class="flex gap-10">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="accesos.php" class="btn btn-secondary">Limpiar</a>
              </div>
            </div>
          </div>
        </form>
      </div>

      <!-- TABLA -->
      <div class="card">
        <div class="card-title flex justify-between align-center">
          <span>📋 Registros encontrados: <?= count($registros) ?></span>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Identificación</th>
                <th>Persona</th>
                <th>Tipo</th>
                <th>Actividad</th>
                <th>Movimiento</th>
                <th>Equipo</th>
                <th>Fecha/Hora</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($registros as $r): ?>
              <tr>
                <td class="mono text-muted"><?= $r['id_registro'] ?></td>
                <td class="mono"><?= htmlspecialchars($r['numero_identificacion']) ?></td>
                <td><?= htmlspecialchars($r['nombre'] . ' ' . $r['apellido']) ?></td>
                <td><span class="badge badge-<?= $r['tipo_usuario'] ?>"><?= ucfirst($r['tipo_usuario']) ?></span></td>
                <td><?= htmlspecialchars($r['nombre_actividad']) ?></td>
                <td><span class="badge badge-<?= $r['tipo_movimiento'] ?>"><?= ucfirst($r['tipo_movimiento']) ?></span></td>
                <td class="mono"><?= htmlspecialchars($r['equipo_asignado'] ?? '—') ?></td>
                <td class="mono text-muted" style="white-space:nowrap;"><?= date('d/m/Y H:i', strtotime($r['fecha_hora'])) ?></td>
                <td>
                  <button class="btn btn-warning btn-sm"
                    onclick="openEdit(<?= htmlspecialchars(json_encode($r)) ?>)">✏️</button>
                  <a href="?delete=<?= $r['id_registro'] ?>&<?= http_build_query($_GET) ?>"
                     class="btn btn-danger btn-sm"
                     onclick="return confirm('¿Eliminar este registro?')">🗑</a>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($registros)): ?>
              <tr><td colspan="9" style="text-align:center;color:var(--text-muted);padding:24px;">Sin registros.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- MODAL EDITAR -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <div class="modal-header">
      <h3>✏️ Editar Registro de Acceso</h3>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <form method="POST" action="">
      <input type="hidden" name="edit_id" id="edit_id">
      <div class="form-grid cols-2" style="margin-bottom:14px;">
        <div class="form-group">
          <label>Persona</label>
          <select name="id_usuario" id="edit_usuario" required>
            <?php foreach ($usuarios as $u): ?>
            <option value="<?= $u['id_usuario'] ?>"><?= htmlspecialchars($u['apellido'].', '.$u['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Movimiento</label>
          <select name="tipo_movimiento" id="edit_movimiento" required>
            <option value="entrada">Entrada</option>
            <option value="salida">Salida</option>
          </select>
        </div>
        <div class="form-group">
          <label>Actividad</label>
          <select name="id_actividad" id="edit_actividad" required>
            <?php foreach ($actividades as $a): ?>
            <option value="<?= $a['id_actividad'] ?>"><?= htmlspecialchars($a['nombre_actividad']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Equipo</label>
          <input type="text" name="equipo_asignado" id="edit_equipo">
        </div>
      </div>
      <div class="form-group" style="margin-bottom:4px;">
        <label>Observaciones</label>
        <textarea name="observaciones" id="edit_obs" rows="3"></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
        <button type="submit" class="btn btn-primary">💾 Guardar Cambios</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEdit(r) {
  document.getElementById('edit_id').value       = r.id_registro;
  document.getElementById('edit_usuario').value  = r.id_usuario;
  document.getElementById('edit_movimiento').value = r.tipo_movimiento;
  document.getElementById('edit_actividad').value  = r.id_actividad;
  document.getElementById('edit_equipo').value   = r.equipo_asignado || '';
  document.getElementById('edit_obs').value      = r.observaciones || '';
  document.getElementById('editModal').classList.add('open');
}
function closeModal() {
  document.getElementById('editModal').classList.remove('open');
}
document.getElementById('editModal').addEventListener('click', function(e){
  if (e.target === this) closeModal();
});
</script>
</body>
</html>
