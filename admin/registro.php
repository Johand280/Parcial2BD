<?php
// ============================================================
//  admin/registro.php
//  Registro de entrada/salida usando el procedimiento almacenado
// ============================================================
require_once '../includes/auth.php';
requireLogin();
require_once '../config/database.php';
$db = getDB();

$mensaje   = '';
$tipoAlerta = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idUsuario   = (int)($_POST['id_usuario']      ?? 0);
    $idActividad = (int)($_POST['id_actividad']    ?? 0);
    $movimiento  = $_POST['tipo_movimiento']        ?? '';
    $obs         = trim($_POST['observaciones']     ?? '');
    $equipo      = trim($_POST['equipo_asignado']   ?? '');

    if ($idUsuario && $idActividad && in_array($movimiento, ['entrada','salida'])) {
        // Llamada al PROCEDIMIENTO ALMACENADO
        $stmt = $db->prepare("CALL sp_registrar_acceso(?, ?, ?, ?, ?, @p_resultado)");
        $stmt->execute([$idUsuario, $idActividad, $movimiento, $obs ?: null, $equipo ?: null]);
        $resultado = $db->query("SELECT @p_resultado AS res")->fetch()['res'];

        if (str_starts_with($resultado, 'OK')) {
            $mensaje    = '✅ ' . $resultado;
            $tipoAlerta = 'success';
        } elseif (str_starts_with($resultado, 'ADVERTENCIA')) {
            $mensaje    = '⚠️ ' . $resultado;
            $tipoAlerta = 'warning';
        } else {
            $mensaje    = '❌ ' . $resultado;
            $tipoAlerta = 'danger';
        }
    } else {
        $mensaje    = '❌ Por favor complete todos los campos obligatorios.';
        $tipoAlerta = 'danger';
    }
}

$usuarios    = $db->query("SELECT id_usuario, numero_identificacion, nombre, apellido, tipo_usuario FROM usuarios WHERE estado='activo' ORDER BY apellido")->fetchAll();
$actividades = $db->query("SELECT * FROM actividades_catalogo ORDER BY nombre_actividad")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registrar Acceso — Sala de Sistemas</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="wrapper">
  <?php include '../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div>
        <div class="breadcrumb">admin / registro</div>
        <h1>📝 Registrar Entrada / Salida</h1>
      </div>
    </div>
    <div class="page-body">

      <?php if ($mensaje): ?>
        <div class="alert alert-<?= $tipoAlerta ?>"><?= htmlspecialchars($mensaje) ?></div>
      <?php endif; ?>

      <div class="card">
        <div class="card-title">🚪 Nuevo Registro de Acceso</div>
        <p class="text-muted" style="font-size:12px;margin-bottom:20px;">
          Este formulario utiliza el <strong style="color:var(--accent)">procedimiento almacenado sp_registrar_acceso</strong>
          para validar y registrar el movimiento.
        </p>

        <form method="POST" action="">
          <div class="form-grid cols-2" style="margin-bottom:16px;">
            <div class="form-group">
              <label>Persona *</label>
              <select name="id_usuario" required>
                <option value="">— Seleccionar usuario —</option>
                <?php foreach ($usuarios as $u): ?>
                <option value="<?= $u['id_usuario'] ?>"
                  <?= (($_POST['id_usuario'] ?? '') == $u['id_usuario']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($u['apellido'] . ', ' . $u['nombre'] . ' (' . $u['numero_identificacion'] . ')') ?>
                  [<?= ucfirst($u['tipo_usuario']) ?>]
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Tipo de Movimiento *</label>
              <select name="tipo_movimiento" required>
                <option value="">— Seleccionar —</option>
                <option value="entrada" <?= (($_POST['tipo_movimiento'] ?? '') === 'entrada') ? 'selected' : '' ?>>🟢 Entrada</option>
                <option value="salida"  <?= (($_POST['tipo_movimiento'] ?? '') === 'salida')  ? 'selected' : '' ?>>🔴 Salida</option>
              </select>
            </div>
            <div class="form-group">
              <label>Actividad a Realizar *</label>
              <select name="id_actividad" required>
                <option value="">— Seleccionar actividad —</option>
                <?php foreach ($actividades as $a): ?>
                <option value="<?= $a['id_actividad'] ?>"
                  <?= (($_POST['id_actividad'] ?? '') == $a['id_actividad']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($a['nombre_actividad']) ?>
                  (<?= $a['aplica_para'] === 'ambos' ? 'Todos' : ucfirst($a['aplica_para']) ?>)
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Equipo Asignado</label>
              <input type="text" name="equipo_asignado" placeholder="Ej: PC-01, PC-DOCENTE…"
                     value="<?= htmlspecialchars($_POST['equipo_asignado'] ?? '') ?>">
            </div>
          </div>
          <div class="form-group" style="margin-bottom:20px;">
            <label>Observaciones</label>
            <textarea name="observaciones" rows="3" placeholder="Notas adicionales sobre el acceso…"><?= htmlspecialchars($_POST['observaciones'] ?? '') ?></textarea>
          </div>
          <div class="flex gap-10">
            <button type="submit" class="btn btn-primary">🚀 Registrar Movimiento</button>
            <a href="accesos.php" class="btn btn-secondary">Ver historial</a>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>
</body>
</html>
