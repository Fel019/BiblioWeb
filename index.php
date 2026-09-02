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
  <link rel="stylesheet" href="estilos.css">
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
<body class="portal-user portal-home">
  <header class="portal-topbar">
    <a class="portal-brand" href="index.php"><img src="assets/escudo-institucional.png" alt=""><span>Colegio Parroquial<br>Nuestra Señora de los Andes</span></a>
    <nav class="portal-links" aria-label="Navegación principal">
      <a class="active" href="index.php" title="Inicio"><i class="fa-solid fa-house"></i><span> Inicio</span></a>
      <a href="libros_disponibles.php" title="Catálogo"><i class="fa-solid fa-book-open"></i><span> Catálogo</span></a>
      <a href="prestamos_usuario.php" title="Mis préstamos"><i class="fa-solid fa-calendar-check"></i><span> Préstamos</span></a>
      <a class="portal-exit" href="logout.php" title="Cerrar sesión"><i class="fa-solid fa-arrow-right-from-bracket"></i><span> Salir</span></a>
    </nav>
  </header>
  <div class="container">
    <img class="institution-mark" src="assets/escudo-institucional.png" alt="Escudo del Colegio Parroquial Nuestra Señora de los Andes">
    <p class="institution-name">Biblioteca Colegio Parroquial<br>Nuestra Señora de los Andes</p>
    <h1>👋 Bienvenido, <?php echo $_SESSION['usuario']; ?>!</h1>
    <p class="rol">Tu rol actual es: <b><?php echo ucfirst($_SESSION['rol']); ?></b></p>

    <p class="welcome-copy">Aquí puedes explorar el catálogo y llevar un seguimiento sencillo de tus préstamos. Todo lo que necesitas para disfrutar de la biblioteca, en un mismo lugar.</p>

    <?php if ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'bibliotecario'): ?>
  <a href="usuarios.php" class="btn"><i class="fa-solid fa-users"></i> Gestionar Usuarios</a>
  <a href="libros.php" class="btn"><i class="fa-solid fa-book"></i> Gestión de Libros</a>
  <a href="prestamos.php" class="btn"><i class="fa-solid fa-hand-holding-book"></i> Gestión de Préstamos</a>
  <a href="reportes.php" class="btn"><i class="fa-solid fa-chart-line"></i> Reportes</a>
<?php else: ?>
  <div class="quick-actions">
    <a href="libros_disponibles.php" class="quick-action"><i class="fa-solid fa-book-open-reader"></i><strong>Explorar el catálogo</strong><span>Encuentra títulos y revisa las copias disponibles.</span></a>
    <a href="prestamos_usuario.php" class="quick-action"><i class="fa-solid fa-calendar-check"></i><strong>Mis préstamos</strong><span>Consulta fechas y realiza un nuevo préstamo.</span></a>
  </div>
<?php endif; ?>
    <p class="portal-note"><i class="fa-solid fa-circle-info"></i> Si necesitas ayuda, acércate a la biblioteca.</p>
  </div>
</body>
</html>
