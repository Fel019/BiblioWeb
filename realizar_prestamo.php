<?php
include 'conexion.php';
session_start();

// Solo alumnos y profesores
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['rol'], ['alumno', 'profesor'])) {
    header("Location: sin_permiso.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $libro_id = $_POST['libro_id'];
    $usuario_id = $_SESSION['id'];
    $fecha_prestamo = date('Y-m-d');
    $fecha_devolucion = date('Y-m-d', strtotime('+7 days'));

    // Verificar disponibilidad
    $verificar = $conn->prepare("SELECT disponible, titulo FROM libros WHERE id = :id");
    $verificar->execute([':id' => $libro_id]);
    $libro = $verificar->fetch(PDO::FETCH_ASSOC);

    if ($libro && $libro['disponible'] > 0) {
        $stmt = $conn->prepare("INSERT INTO prestamos (usuario_id, libro_id, fecha_prestamo, fecha_devolucion, estado)
                                VALUES (:usuario_id, :libro_id, :f_prestamo, :f_devolucion, 'pendiente')");
        $stmt->execute([
            ':usuario_id' => $usuario_id,
            ':libro_id' => $libro_id,
            ':f_prestamo' => $fecha_prestamo,
            ':f_devolucion' => $fecha_devolucion
        ]);

        // Restar copia disponible
        $update = $conn->prepare("UPDATE libros SET disponible = disponible - 1 WHERE id = :id");
        $update->execute([':id' => $libro_id]);

        echo "<script>alert('✅ Préstamo del libro \"{$libro['titulo']}\" registrado correctamente'); window.location='prestamos_usuario.php';</script>";
    } else {
        echo "<script>alert('❌ No hay copias disponibles para este libro');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Generar Préstamo | BiblioWeb</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      background-color: #f4f6fb;
      font-family: 'Segoe UI', sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    .contenedor {
      background: white;
      border-radius: 12px;
      box-shadow: 0 6px 20px rgba(0,0,0,0.1);
      padding: 40px;
      width: 500px;
      text-align: center;
    }
    h2 {
      color: #333;
      margin-bottom: 25px;
      font-weight: 700;
    }
    label {
      display: block;
      text-align: left;
      font-weight: 600;
      margin-bottom: 8px;
      color: #444;
    }
    select {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 6px;
      margin-bottom: 20px;
      font-size: 15px;
    }
    .btn {
      display: block;
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 6px;
      color: white;
      font-weight: bold;
      font-size: 15px;
      cursor: pointer;
      transition: 0.3s;
    }
    .btn-success {
      background-color: #28a745;
      margin-bottom: 12px;
    }
    .btn-success:hover { background-color: #218838; }
    .btn-primary {
      background-color: #007bff;
    }
    .btn-primary:hover { background-color: #0056b3; }
  </style>
</head>
<body>
  <div class="contenedor">
    <h2><i class="fa-solid fa-hand-holding-book"></i> Generar un Nuevo Préstamo</h2>

    <form method="POST" action="">
      <label for="libro_id">Selecciona un libro disponible:</label>
      <select name="libro_id" required>
        <option value="">-- Elige un libro --</option>
        <?php
        $libros = $conn->query("SELECT id, titulo, disponible FROM libros WHERE disponible > 0 ORDER BY titulo ASC");
        while ($row = $libros->fetch(PDO::FETCH_ASSOC)) {
            echo "<option value='{$row['id']}'>{$row['titulo']} ({$row['disponible']} copias disponibles)</option>";
        }
        ?>
      </select>

      <button type="submit" class="btn btn-success">
        <i class="fa-solid fa-plus"></i> Confirmar Préstamo
      </button>

      <a href="prestamos_usuario.php" class="btn btn-primary">
        <i class="fa-solid fa-arrow-left"></i> Volver a Mis Préstamos
      </a>
    </form>
  </div>
</body>
</html>
