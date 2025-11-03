<?php
function verificarRol($rolesPermitidos = []) {
    // Iniciar sesión si no está activa
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Verificar si hay sesión válida
    if (!isset($_SESSION['id']) || !isset($_SESSION['rol'])) {
        header("Location: login.php");
        exit;
    }

    // Normalizar valores (evita errores de mayúsculas/minúsculas o espacios)
    $rolUsuario = strtolower(trim($_SESSION['rol']));
    $rolesPermitidos = array_map('strtolower', array_map('trim', $rolesPermitidos));

    // Si el rol no está dentro de los permitidos, se redirige
    if (!in_array($rolUsuario, $rolesPermitidos)) {
        header("Location: sin_permiso.php");
        exit;
    }
}
?>
