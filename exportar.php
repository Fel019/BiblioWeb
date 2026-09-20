<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Exportar Reportes | BiblioSys</title>
  <link rel="stylesheet" href="estilos.css?v=20260916">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body>
  <header class="navbar">
    <div class="navbar-left">
      <i class="fa-solid fa-book-open navbar-logo"></i>
      <span class="navbar-title">BiblioSys</span>
    </div>
    <div class="navbar-center">
      <nav class="nav-tabs">
        <a href="libros.php">Gestión de Libros</a>
        <a href="usuarios.php">Usuarios</a>
        <a href="prestamos.php">Préstamos</a>
        <a class="active" href="exportar.php">Reportes</a>
      </nav>
    </div>
    <div class="navbar-right">
      <span>Bibliotecario: <?php echo $_SESSION['usuario']; ?></span>
      <a href="logout.php" class="btn btn-danger btn-small">
        <i class="fa-solid fa-right-from-bracket"></i> Salir
      </a>
    </div>
  </header>

  <main class="main-content">
    <div class="card">
      <h3>Generar Reporte</h3>
      <form action="exportar_reporte.php" method="GET">
        <label>Tipo de Reporte</label>
        <select name="tipo" required>
          <option value="">Seleccionar...</option>
          <option value="libros">Libros Registrados</option>
          <option value="usuarios">Usuarios Registrados</option>
          <option value="prestamos">Historial de Préstamos</option>
        </select>

        <label>Fecha Inicial</label>
        <input type="date" name="fecha_inicio">

        <label>Fecha Final</label>
        <input type="date" name="fecha_fin">

        <button type="submit" class="btn btn-primary">
          <i class="fa-solid fa-file-excel"></i> Exportar a Excel
        </button>
      </form>
    </div>
  </main>
</body>
</html>
