<?php
include 'conexion.php';
session_start();
include 'verificar_rol.php';

// Portal disponible para alumnos, profesores y personal administrativo.
verificarRol(['alumno', 'profesor', 'administrativo']);

// Si no hay sesión, redirige
if (!isset($_SESSION['usuario']) || !isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$id_usuario = $_SESSION['id'];

// Consultar préstamos del usuario actual
$sql = "SELECT l.titulo, l.autor, l.categoria, p.cantidad,
               p.fecha_prestamo, p.fecha_devolucion, p.estado
        FROM prestamos p
        INNER JOIN libros l ON p.libro_id = l.id
        WHERE p.usuario_id = :id_usuario
        ORDER BY p.fecha_prestamo DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([':id_usuario' => $id_usuario]);
$prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Mis Préstamos | BiblioWeb</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="estilos.css">
  <style>
    body {
      background-color: #f5f7fa;
      font-family: 'Segoe UI', sans-serif;
    }
    .contenedor {
      max-width: 1000px;
      margin: 60px auto;
      background: white;
      border-radius: 12px;
      padding: 30px;
      box-shadow: 0 6px 18px rgba(0,0,0,0.1);
    }
    .encabezado {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
    }
    h2 {
      color: #333;
      margin: 0;
      font-size: 1.8em;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .btn-agregar {
      background-color: #28a745;
      color: white;
      padding: 10px 25px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 4px 10px rgba(40, 167, 69, 0.25);
    }
    .btn-agregar:hover {
      background-color: #218838;
      transform: translateY(-2px);
      box-shadow: 0 6px 12px rgba(40, 167, 69, 0.3);
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }
    th {
      background: #007bff;
      color: white;
      text-transform: uppercase;
      padding: 10px;
      letter-spacing: 0.5px;
      text-align: left;
    }
    td {
      padding: 10px;
      border-bottom: 1px solid #ddd;
      vertical-align: middle;
    }
    tr:hover {
      background-color: #f1f7ff;
    }
    .badge {
      padding: 5px 12px;
      border-radius: 20px;
      color: white;
      font-weight: bold;
      font-size: 0.9em;
    }
    .badge-success { background-color: #28a745; }
    .badge-warning { background-color: #ffc107; color: #333; }
    .btn-volver {
      display: inline-block;
      margin-top: 25px;
      background-color: #007bff;
      color: white;
      padding: 10px 25px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 4px 10px rgba(0, 123, 255, 0.25);
    }
    .btn-volver:hover {
      background-color: #0056b3;
      transform: translateY(-2px);
      box-shadow: 0 6px 12px rgba(0, 123, 255, 0.35);
    }
  </style>
</head>
<body class="portal-user portal-loans">
  <header class="portal-topbar">
    <a class="portal-brand" href="index.php"><img src="assets/Llanoverde.png" alt=""><span>Institución Educativa<br>Llano Verde Sede Calimio</span></a>
    <nav class="portal-links" aria-label="Navegación principal">
      <a href="index.php" title="Inicio"><i class="fa-solid fa-house"></i><span> Inicio</span></a>
      <a href="libros_disponibles.php" title="Catálogo"><i class="fa-solid fa-book-open"></i><span> Catálogo</span></a>
      <a class="active" href="prestamos_usuario.php" title="Mis préstamos"><i class="fa-solid fa-calendar-check"></i><span> Préstamos</span></a>
      <a class="portal-exit" href="logout.php" title="Cerrar sesión"><i class="fa-solid fa-arrow-right-from-bracket"></i><span> Salir</span></a>
    </nav>
  </header>
  <div class="contenedor">
    <div class="encabezado">
      <h2><i class="fa-solid fa-book"></i> Mis Préstamos</h2>
      <a href="realizar_prestamo.php" class="btn-agregar">
        <i class="fa-solid fa-plus"></i> Realizar Préstamo
      </a>
    </div>
    <div class="loan-summary"><i class="fa-solid fa-book-open-reader"></i> Aquí puedes consultar el estado y las fechas de devolución de todos tus préstamos.</div>
    
    <table>
      <thead>
        <tr>
          <th>Título</th>
          <th>Autor</th>
          <th>Categoría</th>
          <th>Ejemplares</th>
          <th>Fecha Préstamo</th>
          <th>Fecha Devolución</th>
          <th>Estado</th>
        </tr>
      </thead>
      <tbody>
        <?php
        if ($prestamos && count($prestamos) > 0) {
          foreach ($prestamos as $p) {
            $badgeClass = $p['estado'] === 'pendiente' ? 'badge-warning' : 'badge-success';
            echo "<tr>
                    <td>{$p['titulo']}</td>
                    <td>{$p['autor']}</td>
                    <td>{$p['categoria']}</td>
                    <td>{$p['cantidad']}</td>
                    <td>{$p['fecha_prestamo']}</td>
                    <td>{$p['fecha_devolucion']}</td>
                    <td><span class='badge $badgeClass'>" . ucfirst($p['estado']) . "</span></td>
                  </tr>";
          }
        } else {
          echo "<tr><td colspan='6' style='text-align:center; color:#555; padding:20px;'>❌ No tienes préstamos registrados.</td></tr>";
        }
        ?>
      </tbody>
    </table>

    <a href="index.php" class="btn-volver">
      <i class="fa-solid fa-arrow-left"></i> Volver al Inicio
    </a>
  </div>
</body>
</html>
