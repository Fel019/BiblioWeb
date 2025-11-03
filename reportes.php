<?php
include 'conexion.php';
session_start();
include 'verificar_rol.php';
verificarRol(['administrador', 'bibliotecario']);

// Verificamos que el usuario haya iniciado sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

$resultados = [];
$mensaje = "";

// Procesar reporte
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['tipo'])) {
    $tipo = $_GET['tipo'];
    $inicio = $_GET['inicio'] ?? null;
    $fin = $_GET['fin'] ?? null;

    if ($tipo && $inicio && $fin) {
        try {
            // ====== REPORTES ======
            if ($tipo === "libros") {
                $sql = "SELECT id AS ID, titulo AS TÍTULO, autor AS AUTOR, categoria AS CATEGORÍA,
                               anio_publicacion AS AÑO, estado AS ESTADO
                        FROM libros
                        WHERE anio_publicacion BETWEEN :inicio AND :fin
                        ORDER BY anio_publicacion ASC";

            } elseif ($tipo === "usuarios") {
                $sql = "SELECT id AS ID, nombre AS NOMBRE, username AS USUARIO, correo AS CORREO,
                               rol AS ROL, estado AS ESTADO, fecha_registro AS REGISTRO
                        FROM usuarios
                        WHERE DATE(fecha_registro) BETWEEN :inicio AND :fin
                        ORDER BY fecha_registro ASC";

            } elseif ($tipo === "prestamos") {
                $sql = "SELECT p.id AS ID,
                               u.nombre AS USUARIO,
                               l.titulo AS LIBRO,
                               p.fecha_prestamo AS FECHA_PRÉSTAMO,
                               p.fecha_devolucion AS FECHA_DEVOLUCIÓN,
                               p.estado AS ESTADO
                        FROM prestamos p
                        INNER JOIN usuarios u ON p.usuario_id = u.id
                        INNER JOIN libros l ON p.libro_id = l.id
                        WHERE p.fecha_prestamo BETWEEN :inicio AND :fin
                        ORDER BY p.fecha_prestamo ASC";

            } else {
                $mensaje = "⚠️ Tipo de reporte no válido.";
            }

            if (!empty($sql)) {
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':inicio' => $inicio,
                    ':fin' => $fin
                ]);
                $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $mensaje = "❌ Error al generar reporte: " . $e->getMessage();
        }
    } else {
        $mensaje = "⚠️ Parámetros incompletos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reportes | BiblioSys</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="estilos.css">
</head>
<body>

<header class="navbar">
  <div class="navbar-left">
    <i class="fa-solid fa-book-open navbar-logo"></i>
    <span class="navbar-title">BiblioSys</span>
  </div>

  <div class="navbar-center">
    <nav class="nav-tabs">
      <a class="<?php echo basename($_SERVER['PHP_SELF']) == 'libros.php' ? 'active' : ''; ?>" href="libros.php">Gestión de Libros</a>
      <a class="<?php echo basename($_SERVER['PHP_SELF']) == 'prestamos.php' ? 'active' : ''; ?>" href="prestamos.php">Préstamos</a>
      <?php if ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'bibliotecario'): ?>
        <a class="<?php echo basename($_SERVER['PHP_SELF']) == 'usuarios.php' ? 'active' : ''; ?>" href="usuarios.php">Usuarios</a>
        <a class="<?php echo basename($_SERVER['PHP_SELF']) == 'reportes.php' ? 'active' : ''; ?>" href="reportes.php">Reportes</a>
      <?php endif; ?>
    </nav>
  </div>

  <div class="navbar-right">
    <span><?php echo ucfirst($_SESSION['rol']); ?>: <?php echo $_SESSION['usuario']; ?></span>
    <a href="logout.php" class="btn btn-small btn-primary">
      <i class="fa-solid fa-right-from-bracket"></i> Salir
    </a>
  </div>
</header>

<main class="main-content">
  <div class="usuarios-layout">

    <!-- Generar reporte -->
    <div class="usuarios-sidebar">
      <div class="card">
        <h3>📊 Generar Reporte</h3>
        <form method="GET" action="">
          <label>Tipo de Reporte</label>
          <select name="tipo" required>
            <option value="">Seleccionar...</option>
            <option value="libros">Libros Registrados</option>
            <option value="usuarios">Usuarios Registrados</option>
            <option value="prestamos">Historial de Préstamos</option>
          </select>

          <label>Fecha Inicial</label>
          <input type="date" name="inicio" required>

          <label>Fecha Final</label>
          <input type="date" name="fin" required>

          <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-chart-column"></i> Generar Reporte
          </button>

          <button type="submit" formaction="exportar_reporte.php" class="btn btn-success">
            <i class="fa-solid fa-download"></i> Descargar
          </button>
        </form>
      </div>
    </div>

    <!-- Resultados -->
    <div class="usuarios-main card">
      <h3>Resultados del Reporte</h3>
      <?php if ($mensaje): ?>
        <p><?php echo $mensaje; ?></p>
      <?php endif; ?>

      <?php if (!empty($resultados)): ?>
        <table>
          <thead>
            <tr>
              <?php foreach (array_keys($resultados[0]) as $columna): ?>
                <th><?php echo htmlspecialchars(strtoupper($columna)); ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($resultados as $fila): ?>
              <tr>
                <?php foreach ($fila as $valor): ?>
                  <td><?php echo htmlspecialchars($valor); ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p>⚠️ No se encontraron resultados.</p>
      <?php endif; ?>
    </div>
  </div>
</main>

</body>
</html>
