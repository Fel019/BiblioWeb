<?php
include 'conexion.php';
session_start();

if (!isset($_GET['id'])) {
    header("Location: libros.php");
    exit;
}

$id = $_GET['id'];

// Obtener datos del libro
$stmt = $conn->prepare("SELECT * FROM libros WHERE id_libro = :id");
$stmt->execute([':id' => $id]);
$libro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$libro) {
    echo "<script>alert('❌ Libro no encontrado'); window.location='libros.php';</script>";
    exit;
}

// Actualizar libro
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = $_POST['titulo'];
    $autor = $_POST['autor'];
    $categoria = $_POST['categoria'];
    $estado = $_POST['estado'];

    $update = $conn->prepare("UPDATE libros 
                              SET titulo = :titulo, autor = :autor, categoria = :categoria, estado = :estado 
                              WHERE id_libro = :id");
    $update->execute([
        ':titulo' => $titulo,
        ':autor' => $autor,
        ':categoria' => $categoria,
        ':estado' => $estado,
        ':id' => $id
    ]);

    echo "<script>alert('✅ Libro actualizado correctamente'); window.location='libros.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Libro | BiblioSys</title>
  <link rel="stylesheet" href="estilos.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: #f4f6f9;
      margin: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    .edit-container {
      background: #fff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 450px;
      text-align: center;
    }
    h3 {
      margin-bottom: 20px;
    }
    form {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }
    input, select {
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 14px;
    }
    .btn {
      padding: 12px;
      border: none;
      border-radius: 8px;
      font-size: 15px;
      font-weight: bold;
      cursor: pointer;
      transition: 0.3s;
    }
    .btn-success {
      background: #28a745;
      color: #fff;
    }
    .btn-success:hover {
      background: #218838;
    }
    .btn-secondary {
      background: #6c757d;
      color: #fff;
    }
    .btn-secondary:hover {
      background: #5a6268;
    }
    .btn-group {
      display: flex;
      justify-content: space-between;
      gap: 10px;
    }
  </style>
</head>
<body>
  <div class="edit-container">
    <h3>✏️ Editar Libro</h3>
    <form method="POST" action="">
      <input type="text" name="titulo" value="<?php echo $libro['titulo']; ?>" required>
      <input type="text" name="autor" value="<?php echo $libro['autor']; ?>" required>

      <select name="categoria" required>
        <option value="Novela" <?php echo $libro['categoria']=='Novela'?'selected':''; ?>>Novela</option>
        <option value="Clásico" <?php echo $libro['categoria']=='Clásico'?'selected':''; ?>>Clásico</option>
        <option value="Tecnología" <?php echo $libro['categoria']=='Tecnología'?'selected':''; ?>>Tecnología</option>
        <option value="Otros" <?php echo $libro['categoria']=='Otros'?'selected':''; ?>>Otros</option>
      </select>

      <select name="estado" required>
        <option value="disponible" <?php echo $libro['estado']=='disponible'?'selected':''; ?>>Disponible</option>
        <option value="prestado" <?php echo $libro['estado']=='prestado'?'selected':''; ?>>Prestado</option>
      </select>

      <div class="btn-group">
        <button type="submit" class="btn btn-success">
          <i class="fa-solid fa-save"></i> Guardar
        </button>
        <a href="libros.php" class="btn btn-secondary">
          <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
      </div>
    </form>
  </div>
</body>
</html>
