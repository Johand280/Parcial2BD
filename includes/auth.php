<?php
// ============================================================
//  includes/auth.php
//  Funciones de autenticación y sesión
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit;
    }
}

function login(string $username, string $password): bool {
    require_once __DIR__ . '/../config/database.php';
    $db = getDB();
    $stmt = $db->prepare("SELECT id_admin, password_hash, nombre_completo FROM administradores WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id']   = $admin['id_admin'];
        $_SESSION['admin_name'] = $admin['nombre_completo'];
        // Actualizar último acceso
        $db->prepare("UPDATE administradores SET ultimo_acceso = NOW() WHERE id_admin = ?")->execute([$admin['id_admin']]);
        return true;
    }
    return false;
}

function logout(): void {
    session_destroy();
    header('Location: ../login.php');
    exit;
}
