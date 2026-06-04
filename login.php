<?php
// ============================================================
//  login.php
// ============================================================
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: admin/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (login($username, $password)) {
        header('Location: admin/dashboard.php');
        exit;
    } else {
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acceso Administrativo — Sala de Sistemas</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <div class="logo">
      <div class="icon">🖥️</div>
      <h2>Sala de Sistemas</h2>
      <p>Control de Acceso — Panel Administrativo</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-grid" style="margin-bottom:16px;">
        <div class="form-group">
          <label>Usuario</label>
          <input type="text" name="username" placeholder="Ingrese su usuario" required autofocus>
        </div>
        <div class="form-group">
          <label>Contraseña</label>
          <input type="password" name="password" placeholder="••••••••" required>
        </div>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
        🔐 Ingresar al Sistema
      </button>
    </form>

    <p class="text-muted" style="text-align:center;margin-top:20px;font-size:11px;">
      Acceso restringido a personal autorizado
    </p>
  </div>
</div>
</body>
</html>
