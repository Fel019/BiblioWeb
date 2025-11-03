<?php
include 'conexion.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM libros WHERE id_libro = :id");
    $stmt->execute([':id' => $id]);
}

header("Location: libros.php");
exit;
?>
