<?php
// ============================================================
//  admin/usuarios.php
// ============================================================
require_once '../includes/auth.php';
requireLogin();
require_once '../config/database.php';
$db = getDB();

$msg = '';
$msgType = '';

// Eliminar
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $db->prepare("DELETE FROM usuarios WHERE id_usuario = ?")->execute([(int)$_GET['delete']]);
        $msg = 'Usuario eliminado.'; $msgType = 'danger';
    } catch (PDOException $e) {
        $msg = 'No se puede eliminar: el usuario tiene registros de acceso asociados.'; $msgType = 'warning';
    }
}

// Guardar (crear o editar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = (int)($_POST['id_usuario'] ?? 0);
    $data = [
        $_POST['numero_identificacion'],
        $_POST['nombre'],
        $_POST['apellido'],
        $_POST['tipo_usuario'],
        $_POST['programa_facultad'],
        $_POST['email'],
        $_POST['telefono'],
        $_POST['estado'],
    ];
    if ($id) {
        $stmt = $db->prepare("UPDATE usuarios SET numero_identificacion=?,nombre=?,apellido=?,tipo_usuario=?,programa_facultad=?,email=?,telefono=?,estado=? WHERE id_usuario=?");
        $data[] = $id;
        $stmt->execute($data);
        $msg = 'Usuario actualizado.'; $msgType = 'success';
    } else {
        $stmt = $db->prepare("INSERT INTO usuarios (numero_identificacion,nombre,apellido,tipo_usuario,programa_facultad,email,telefono,estado) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute($data);
        $msg = 'Usuario creado exitosamente.'; $msgType = 'success';
    }
}

$usuarios = $db->query("SELECT *, (SELECT COUNT(*) FROM registros_acceso WHERE id_usuario=u.id_usuario) AS total_accesos FROM usuarios u ORDER BY apellido")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Usuarios — Sala de Sistemas</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="wrapper">
  <?php include '../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div>
        <div class="breadcrumb">admin / usuarios</div>
        <h1>👥 Gestión de Usuarios</h1>
      </div>
      <button class="btn btn-primary" onclick="openModal()">+ Nuevo Usuario</button>
    </div>
    <div class="page-body">

      <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>

      <div class="card">
        <div class="card-title">Lista de Usuarios (<?= count($usuarios) ?>)</div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>ID</th><th>Identificación</th><th>Nombre</th><th>Tipo</th>
                <th>Programa / Facultad</th><th>Email</th><th>Accesos</th><th>Estado</th><th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($usuarios as $u): ?>
              <tr>
                <td class="mono text-muted"><?= $u['id_usuario'] ?></td>
                <td class="mono"><?= htmlspecialchars($u['numero_identificacion']) ?></td>
                <td><?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido']) ?></td>
                <td><span class="badge badge-<?= $u['tipo_usuario'] ?>"><?= ucfirst($u['tipo_usuario']) ?></span></td>
                <td><?= htmlspecialchars($u['programa_facultad']) ?></td>
                <td style="font-size:12px;"><?= htmlspecialchars($u['email'] ?? '—') ?></td>
                <td class="mono text-accent"><?= $u['total_accesos'] ?></td>
                <td><span class="badge badge-<?= $u['estado'] ?>"><?= ucfirst($u['estado']) ?></span></td>
                <td>
                  <button class="btn btn-warning btn-sm" onclick="openEdit(<?= htmlspecialchars(json_encode($u)) ?>)">✏️</button>
                  <a href="?delete=<?= $u['id_usuario'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar usuario?')">🗑</a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL -->
<div class="modal-overlay" id="modal">
  <div class="modal">
    <div class="modal-header">
      <h3 id="modalTitle">➕ Nuevo Usuario</h3>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="id_usuario" id="f_id">
      <div class="form-grid cols-2" style="margin-bottom:14px;">
        <div class="form-group">
          <label>N° Identificación *</label>
          <input type="text" name="numero_identificacion" id="f_nid" required>
        </div>
        <div class="form-group">
          <label>Tipo de Usuario *</label>
          <select name="tipo_usuario" id="f_tipo" required>
            <option value="estudiante">Estudiante</option>
            <option value="profesor">Profesor</option>
          </select>
        </div>
        <div class="form-group">
          <label>Nombre *</label>
          <input type="text" name="nombre" id="f_nombre" required>
        </div>
        <div class="form-group">
          <label>Apellido *</label>
          <input type="text" name="apellido" id="f_apellido" required>
        </div>
        <div class="form-group">
          <label>Programa / Facultad</label>
          <input type="text" name="programa_facultad" id="f_prog">
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" id="f_email">
        </div>
        <div class="form-group">
          <label>Teléfono</label>
          <input type="text" name="telefono" id="f_tel">
        </div>
        <div class="form-group">
          <label>Estado</label>
          <select name="estado" id="f_estado">
            <option value="activo">Activo</option>
            <option value="inactivo">Inactivo</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
        <button type="submit" class="btn btn-primary" id="btnSubmit">💾 Guardar</button>
      </div>
    </form>
  </div>
</div>
<script>
function openModal() {
  document.getElementById('modalTitle').textContent = '➕ Nuevo Usuario';
  document.getElementById('btnSubmit').textContent  = '💾 Crear Usuario';
  document.querySelectorAll('#modal input, #modal select, #modal textarea').forEach(e => e.value = '');
  document.getElementById('f_estado').value = 'activo';
  document.getElementById('f_tipo').value   = 'estudiante';
  document.getElementById('modal').classList.add('open');
}
function openEdit(u) {
  document.getElementById('modalTitle').textContent = '✏️ Editar Usuario';
  document.getElementById('btnSubmit').textContent  = '💾 Actualizar';
  document.getElementById('f_id').value      = u.id_usuario;
  document.getElementById('f_nid').value     = u.numero_identificacion;
  document.getElementById('f_tipo').value    = u.tipo_usuario;
  document.getElementById('f_nombre').value  = u.nombre;
  document.getElementById('f_apellido').value= u.apellido;
  document.getElementById('f_prog').value    = u.programa_facultad || '';
  document.getElementById('f_email').value   = u.email || '';
  document.getElementById('f_tel').value     = u.telefono || '';
  document.getElementById('f_estado').value  = u.estado;
  document.getElementById('modal').classList.add('open');
}
function closeModal() { document.getElementById('modal').classList.remove('open'); }
document.getElementById('modal').addEventListener('click', e => { if(e.target===document.getElementById('modal')) closeModal(); });
</script>
</body>
</html>
