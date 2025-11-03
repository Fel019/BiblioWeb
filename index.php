<?php
include 'conexion.php';
session_start();

// Verificamos si hay sesión activa
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Inicio | BiblioWeb</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      background: #f4f6fb;
      font-family: "Poppins", sans-serif;
      text-align: center;
      padding: 50px;
    }
    .container {
      background: #fff;
      border-radius: 15px;
      padding: 40px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      max-width: 600px;
      margin: auto;
    }
    h1 { color: #333; }
    .rol {
      margin-top: 10px;
      color: #555;
      font-weight: 500;
    }
    .btn {
      display: inline-block;
      background: #007bff;
      color: white;
      padding: 10px 20px;
      margin: 10px 5px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
    }
    .btn:hover { background: #0056b3; }
    .btn-danger { background: #dc3545; }
    .btn-danger:hover { background: #b02a37; }
  </style>
</head>
<body>
  <div class="container">
    <h1>👋 Bienvenido, <?php echo $_SESSION['usuario']; ?>!</h1>
    <p class="rol">Tu rol actual es: <b><?php echo ucfirst($_SESSION['rol']); ?></b></p>

    <?php if ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'bibliotecario'): ?>
  <a href="usuarios.php" class="btn"><i class="fa-solid fa-users"></i> Gestionar Usuarios</a>
  <a href="libros.php" class="btn"><i class="fa-solid fa-book"></i> Gestión de Libros</a>
  <a href="prestamos.php" class="btn"><i class="fa-solid fa-hand-holding-book"></i> Gestión de Préstamos</a>
  <a href="reportes.php" class="btn"><i class="fa-solid fa-chart-line"></i> Reportes</a>
<?php else: ?>
  <a href="libros_disponibles.php" class="btn"><i class="fa-solid fa-book-open-reader"></i> Ver Libros</a>
  <a href="prestamos_usuario.php" class="btn"><i class="fa-solid fa-hand-holding-book"></i> Ver Préstamos</a>
<?php endif; ?>


    <a href="logout.php" class="btn btn-danger"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a>
  </div>
</body>
</html>
