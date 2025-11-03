<?php
include 'conexion.php';
session_start();

$id = $_GET['id'] ?? null;

if ($id) {
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id_usuario = :id");
    $stmt->execute([':id' => $id]);
}

header("Location: usuarios.php");
exit;
