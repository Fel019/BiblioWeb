<?php
include 'conexion.php';
session_start();

// 🔹 Verificamos sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// 🔹 Validamos rol permitido
if ($_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== 'bibliotecario') {
    header("Location: sin_permiso.php");
    exit;
}

// 🔹 Obtenemos el ID por GET
if (!isset($_GET['id'])) {
    echo "<script>alert('ID de usuario no especificado'); window.location='usuarios.php';</script>";
    exit;
}

$id = $_GET['id'];

// 🔹 Consultamos los datos del usuario
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = :id");
$stmt->execute([':id' => $id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    echo "<script>alert('Usuario no encontrado'); window.location='usuarios.php';</script>";
    exit;
}

// 🔹 Guardamos cambios al enviar el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $rol = $_POST['rol'];
    $estado = $_POST['estado'];

    $sql = "UPDATE usuarios 
            SET nombre = :nombre, correo = :correo, rol = :rol, estado = :estado 
            WHERE id = :id";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':nombre' => $nombre,
        ':correo' => $correo,
        ':rol' => $rol,
        ':estado' => $estado,
        ':id' => $id
    ]);

    echo "<script>alert('✅ Usuario actualizado correctamente'); window.location='usuarios.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Usuario | BiblioWeb</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      background: #f4f6fb;
      font-family: "Poppins", sans-serif;
      margin: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }

    .card {
      background: #fff;
      border-radius: 15px;
      padding: 40px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      width: 500px;
      max-width: 90%;
    }

    h2 {
      text-align: center;
      color: #333;
      margin-bottom: 25px;
      font-size: 1.6em;
    }

    h2 i {
      color: #007bff;
      margin-right: 10px;
    }

    label {
      display: block;
      font-weight: 600;
      margin-top: 15px;
      color: #444;
    }

    input, select {
      width: 100%;
      padding: 10px 12px;
      margin-top: 8px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 1em;
      transition: 0.2s ease;
    }

    input:focus, select:focus {
      border-color: #007bff;
      outline: none;
      box-shadow: 0 0 3px rgba(0, 123, 255, 0.3);
    }

    .btn {
      display: inline-block;
      width: 100%;
      padding: 12px;
      font-size: 1em;
      font-weight: 600;
      text-align: center;
      border: none;
      border-radius: 10px;
      color: white;
      cursor: pointer;
      transition: 0.3s ease;
      margin-top: 20px;
    }

    .btn-success {
      background-color: #28a745;
    }

    .btn-success:hover {
      background-color: #218838;
    }

    .btn-primary {
      background-color: #007bff;
      text-decoration: none;
      display: block;
      margin-top: 10px;
      line-height: 42px;
    }

    .btn-primary:hover {
      background-color: #0056b3;
    }

    .back-link {
      text-align: center;
      margin-top: 15px;
    }

    .back-link a {
      color: #007bff;
      text-decoration: none;
      font-weight: 500;
    }

    .back-link a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="card">
    <h2><i class="fa-solid fa-pen-to-square"></i>Editar Usuario</h2>
    <form method="POST">
      <label>Nombre:</label>
      <input type="text" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>

      <label>Correo:</label>
      <input type="email" name="correo" value="<?php echo htmlspecialchars($usuario['correo']); ?>" required>

      <label>Rol:</label>
      <select name="rol" required>
        <option value="estudiante" <?php if($usuario['rol']=='estudiante') echo 'selected'; ?>>Estudiante</option>
        <option value="profesor" <?php if($usuario['rol']=='profesor') echo 'selected'; ?>>Profesor</option>
        <option value="administrativo" <?php if($usuario['rol']=='administrativo') echo 'selected'; ?>>Administrativo</option>
        <option value="bibliotecario" <?php if($usuario['rol']=='bibliotecario') echo 'selected'; ?>>Bibliotecario</option>
        <option value="administrador" <?php if($usuario['rol']=='administrador') echo 'selected'; ?>>Administrador</option>
      </select>

      <label>Estado:</label>
      <select name="estado" required>
        <option value="activo" <?php if($usuario['estado']=='activo') echo 'selected'; ?>>Activo</option>
        <option value="inactivo" <?php if($usuario['estado']=='inactivo') echo 'selected'; ?>>Inactivo</option>
      </select>

      <button type="submit" class="btn btn-success"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
      <a href="usuarios.php" class="btn btn-primary"><i class="fa-solid fa-arrow-left"></i> Volver</a>
    </form>
  </div>
</body>
</html>
