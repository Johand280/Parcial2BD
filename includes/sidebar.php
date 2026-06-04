<?php
// ============================================================
//  includes/sidebar.php
// ============================================================
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">🖥️</div>
    <h2>Sala Sistemas</h2>
    <p>Control de Acceso</p>
  </div>

  <div class="nav-section">Principal</div>
  <a href="dashboard.php"   class="nav-item <?= $currentPage==='dashboard.php'  ?'active':'' ?>"><span class="icon">📊</span><span>Dashboard</span></a>
  <a href="registro.php"    class="nav-item <?= $currentPage==='registro.php'   ?'active':'' ?>"><span class="icon">📝</span><span>Registrar Acceso</span></a>
  <a href="accesos.php"     class="nav-item <?= $currentPage==='accesos.php'    ?'active':'' ?>"><span class="icon">🚪</span><span>Historial Accesos</span></a>

  <div class="nav-section">Gestión</div>
  <a href="usuarios.php"    class="nav-item <?= $currentPage==='usuarios.php'   ?'active':'' ?>"><span class="icon">👥</span><span>Usuarios</span></a>
  <a href="actividades.php" class="nav-item <?= $currentPage==='actividades.php'?'active':'' ?>"><span class="icon">📋</span><span>Actividades</span></a>

  <div class="nav-section">Reportes</div>
  <a href="reportes.php"    class="nav-item <?= $currentPage==='reportes.php'   ?'active':'' ?>"><span class="icon">📈</span><span>Reportes SQL</span></a>

  <div class="sidebar-footer">
    <div style="font-size:11px;margin-bottom:8px;">👤 <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></div>
    <a href="../logout.php" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center;">⬅ Salir</a>
  </div>
</nav>
