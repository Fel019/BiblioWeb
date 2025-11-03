<?php
include 'conexion.php';

$stmt = $conn->query("SELECT * FROM usuarios");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['id_usuario'] . " - " . $row['nombre'] . " (" . $row['rol'] . ")<br>";
}
?>
