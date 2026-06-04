<?php
// ============================================================
//  admin/actividades.php
// ============================================================
require_once '../includes/auth.php';
requireLogin();
require_once '../config/database.php';
$db = getDB();

$msg = ''; $msgType = '';

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $db->prepare("DELETE FROM actividades_catalogo WHERE id_actividad=?")->execute([(int)$_GET['delete']]);
        $msg = 'Actividad eliminada.'; $msgType = 'danger';
    } catch (PDOException $e) {
        $msg = 'No se puede eliminar: tiene registros de acceso asociados.'; $msgType = 'warning';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id_actividad'] ?? 0);
    $d  = [$_POST['nombre_actividad'], $_POST['descripcion'], $_POST['aplica_para']];
    if ($id) {
        $d[] = $id;
        $db->prepare("UPDATE actividades_catalogo SET nombre_actividad=?,descripcion=?,aplica_para=? WHERE id_actividad=?")->execute($d);
        $msg = 'Actividad actualizada.'; $msgType = 'success';
    } else {
        $db->prepare("INSERT INTO actividades_catalogo (nombre_actividad,descripcion,aplica_para) VALUES (?,?,?)")->execute($d);
        $msg = 'Actividad creada.'; $msgType = 'success';
    }
}

$actividades = $db->query("SELECT *, (SELECT COUNT(*) FROM registros_acceso WHERE id_actividad=a.id_actividad) AS usos FROM actividades_catalogo a ORDER BY nombre_actividad")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Actividades — Sala de Sistemas</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="wrapper">
  <?php include '../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div>
        <div class="breadcrumb">admin / actividades</div>
        <h1>📋 Catálogo de Actividades</h1>
      </div>
      <button class="btn btn-primary" onclick="openModal()">+ Nueva Actividad</button>
    </div>
    <div class="page-body">
      <?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
      <div class="card">
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>#</th><th>Nombre</th><th>Descripción</th><th>Aplica para</th><th>Usos</th><th>Acciones</th></tr>
            </thead>
            <tbody>
              <?php foreach ($actividades as $a): ?>
              <tr>
                <td class="mono text-muted"><?= $a['id_actividad'] ?></td>
                <td><?= htmlspecialchars($a['nombre_actividad']) ?></td>
                <td style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($a['descripcion'] ?? '') ?></td>
                <td><span class="badge badge-<?= $a['aplica_para'] === 'ambos' ? 'activo' : $a['aplica_para'] ?>"><?= ucfirst($a['aplica_para']) ?></span></td>
                <td class="mono text-accent"><?= $a['usos'] ?></td>
                <td>
                  <button class="btn btn-warning btn-sm" onclick="openEdit(<?= htmlspecialchars(json_encode($a)) ?>)">✏️</button>
                  <a href="?delete=<?= $a['id_actividad'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar?')">🗑</a>
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

<div class="modal-overlay" id="modal">
  <div class="modal">
    <div class="modal-header">
      <h3 id="mTitle">Nueva Actividad</h3>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="id_actividad" id="f_id">
      <div class="form-grid" style="margin-bottom:14px;">
        <div class="form-group">
          <label>Nombre de la Actividad *</label>
          <input type="text" name="nombre_actividad" id="f_nom" required>
        </div>
        <div class="form-group">
          <label>Descripción</label>
          <textarea name="descripcion" id="f_desc" rows="3"></textarea>
        </div>
        <div class="form-group">
          <label>Aplica para</label>
          <select name="aplica_para" id="f_aplica">
            <option value="ambos">Todos (Estudiantes y Profesores)</option>
            <option value="estudiante">Solo Estudiantes</option>
            <option value="profesor">Solo Profesores</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
        <button type="submit" class="btn btn-primary">💾 Guardar</button>
      </div>
    </form>
  </div>
</div>
<script>
function openModal(){
  document.getElementById('mTitle').textContent='Nueva Actividad';
  ['f_id','f_nom','f_desc'].forEach(id=>document.getElementById(id).value='');
  document.getElementById('f_aplica').value='ambos';
  document.getElementById('modal').classList.add('open');
}
function openEdit(a){
  document.getElementById('mTitle').textContent='Editar Actividad';
  document.getElementById('f_id').value    = a.id_actividad;
  document.getElementById('f_nom').value   = a.nombre_actividad;
  document.getElementById('f_desc').value  = a.descripcion||'';
  document.getElementById('f_aplica').value= a.aplica_para;
  document.getElementById('modal').classList.add('open');
}
function closeModal(){document.getElementById('modal').classList.remove('open');}
document.getElementById('modal').addEventListener('click',e=>{if(e.target===document.getElementById('modal'))closeModal();});
</script>
</body>
</html>
