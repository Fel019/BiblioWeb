<?php
include 'conexion.php';
session_start();
include 'verificar_rol.php';

// Solo admin y bibliotecario
verificarRol(['administrador', 'bibliotecario']);

// Si no hay sesión, redirige
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// =======================
// Registrar préstamo
// =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_usuario'], $_POST['id_libro'])) {
    $id_usuario      = (int)$_POST['id_usuario'];
    $id_libro        = (int)$_POST['id_libro'];
    $fecha_prestamo  = $_POST['fecha_prestamo'] ?? date('Y-m-d');
    $fecha_devolucion= $_POST['fecha_devolucion'] ?? date('Y-m-d', strtotime('+7 days'));
    $estado          = "pendiente";

    // Validar copias disponibles
    $stmt = $conn->prepare("SELECT disponible FROM libros WHERE id = :id");
    $stmt->execute([':id' => $id_libro]);
    $disponibles = (int)$stmt->fetchColumn();

    if ($disponibles > 0) {
        // Insertar préstamo
        $sql = "INSERT INTO prestamos (usuario_id, libro_id, fecha_prestamo, fecha_devolucion, estado)
                VALUES (:u, :l, :fp, :fd, :e)";
        $ins = $conn->prepare($sql);
        $ins->execute([
            ':u' => $id_usuario,
            ':l' => $id_libro,
            ':fp'=> $fecha_prestamo,
            ':fd'=> $fecha_devolucion,
            ':e' => $estado
        ]);

        // Actualizar copias y estado del libro
        $upd = $conn->prepare("
            UPDATE libros
            SET disponible = disponible - 1,
                estado = CASE WHEN disponible - 1 <= 0 THEN 'no_disponible' ELSE 'prestado' END
            WHERE id = :id
        ");
        $upd->execute([':id' => $id_libro]);

        echo "<script>alert('✅ Préstamo registrado con éxito y libro marcado como prestado');</script>";
    } else {
        echo "<script>alert('❌ No hay copias disponibles para ese libro');</script>";
    }
}

// =======================
// Marcar como devuelto
// =======================
if (isset($_GET['devolver'])) {
    $id_prestamo = (int)$_GET['devolver'];

    $sqlInfo = $conn->prepare("SELECT libro_id, estado FROM prestamos WHERE id = :id");
    $sqlInfo->execute([':id' => $id_prestamo]);
    $info = $sqlInfo->fetch(PDO::FETCH_ASSOC);

    if ($info && $info['estado'] === 'pendiente') {
        // Cambiar préstamo a devuelto
        $updP = $conn->prepare("UPDATE prestamos SET estado = 'devuelto' WHERE id = :id");
        $updP->execute([':id' => $id_prestamo]);

        // Devolver copia y actualizar estado del libro
        $updL = $conn->prepare("
            UPDATE libros
            SET disponible = disponible + 1,
                estado = CASE WHEN disponible + 1 > 0 THEN 'disponible' ELSE estado END
            WHERE id = :id_libro
        ");
        $updL->execute([':id_libro' => $info['libro_id']]);
    }

    header("Location: prestamos.php");
    exit;
}

// =======================
// Eliminar préstamo
// =======================
if (isset($_GET['eliminar'])) {
    $id_prestamo = (int)$_GET['eliminar'];

    $sqlInfo = $conn->prepare("SELECT libro_id, estado FROM prestamos WHERE id = :id");
    $sqlInfo->execute([':id' => $id_prestamo]);
    $info = $sqlInfo->fetch(PDO::FETCH_ASSOC);

    if ($info && $info['estado'] === 'pendiente') {
        $updL = $conn->prepare("
            UPDATE libros
            SET disponible = disponible + 1,
                estado = CASE WHEN disponible + 1 > 0 THEN 'disponible' ELSE estado END
            WHERE id = :id_libro
        ");
        $updL->execute([':id_libro' => $info['libro_id']]);
    }

    $del = $conn->prepare("DELETE FROM prestamos WHERE id = :id");
    $del->execute([':id' => $id_prestamo]);

    header("Location: prestamos.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión de Préstamos | BiblioSys</title>
  <link rel="stylesheet" href="estilos.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <header class="navbar">
    <div class="navbar-left">
      <i class="fa-solid fa-book-open navbar-logo"></i>
      <span class="navbar-title">BiblioSys</span>
    </div>
    <div class="navbar-center">
      <nav class="nav-tabs">
        <a class="<?php echo basename($_SERVER['PHP_SELF'])=='libros.php'?'active':''; ?>" href="libros.php">Gestión de Libros</a>
        <a class="<?php echo basename($_SERVER['PHP_SELF'])=='prestamos.php'?'active':''; ?>" href="prestamos.php">Préstamos</a>
        <?php if ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'bibliotecario'): ?>
          <a class="<?php echo basename($_SERVER['PHP_SELF'])=='usuarios.php'?'active':''; ?>" href="usuarios.php">Usuarios</a>
          <a class="<?php echo basename($_SERVER['PHP_SELF'])=='reportes.php'?'active':''; ?>" href="reportes.php">Reportes</a>
        <?php endif; ?>
      </nav>
    </div>
    <div class="navbar-right">
      <span><?php echo ucfirst($_SESSION['rol']); ?>: <?php echo $_SESSION['usuario']; ?></span>
      <a href="logout.php" class="btn btn-primary btn-small"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
    </div>
  </header>

  <main class="main-content">
    <div class="usuarios-layout">
      <!-- Columna izquierda -->
      <div class="usuarios-sidebar">
        <div class="card">
          <h3>Registrar Préstamo</h3>
          <form method="POST" action="">
            <label>Usuario</label>
            <select name="id_usuario" required>
              <option value="">Selecciona un usuario</option>
              <?php
              $usuarios = $conn->query("SELECT id, nombre FROM usuarios ORDER BY nombre ASC");
              while ($u = $usuarios->fetch(PDO::FETCH_ASSOC)) {
                  echo "<option value='{$u['id']}'>".htmlspecialchars($u['nombre'])."</option>";
              }
              ?>
            </select>

            <label>Libro (solo disponibles)</label>
            <select name="id_libro" required>
              <option value="">Selecciona un libro</option>
              <?php
              $libros = $conn->query("SELECT id, titulo FROM libros WHERE disponible > 0 ORDER BY titulo ASC");
              while ($l = $libros->fetch(PDO::FETCH_ASSOC)) {
                  echo "<option value='{$l['id']}'>".htmlspecialchars($l['titulo'])."</option>";
              }
              ?>
            </select>

            <label>Fecha de préstamo</label>
            <input type="date" name="fecha_prestamo" value="<?php echo date('Y-m-d'); ?>" required>

            <label>Fecha de devolución</label>
            <input type="date" name="fecha_devolucion" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required>

            <button type="submit" class="btn btn-success">
              <i class="fa-solid fa-arrow-right-arrow-left"></i> Registrar Préstamo
            </button>
          </form>
        </div>
      </div>

      <!-- Columna derecha -->
      <div class="usuarios-main card">
        <h3>Listado de Préstamos</h3>
        <table>
          <thead>
            <tr>
              <th>USUARIO</th>
              <th>LIBRO</th>
              <th>FECHA PRÉSTAMO</th>
              <th>FECHA DEVOLUCIÓN</th>
              <th>ESTADO</th>
              <th>ACCIONES</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $sql = "SELECT p.id, u.nombre AS usuario, l.titulo AS libro,
                           p.fecha_prestamo, p.fecha_devolucion, p.estado
                    FROM prestamos p
                    INNER JOIN usuarios u ON p.usuario_id = u.id
                    INNER JOIN libros l   ON p.libro_id   = l.id
                    ORDER BY p.id DESC";
            $stmt = $conn->query($sql);

            if ($stmt->rowCount() > 0) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>";
                    echo "<td>".htmlspecialchars($row['usuario'])."</td>";
                    echo "<td>".htmlspecialchars($row['libro'])."</td>";
                    echo "<td>".$row['fecha_prestamo']."</td>";
                    echo "<td>".$row['fecha_devolucion']."</td>";
                    echo "<td><span class='badge ".($row['estado']=='pendiente'?'badge-warning':'badge-success')."'>".ucfirst($row['estado'])."</span></td>";
                    echo "<td>";
                    if ($row['estado'] === 'pendiente') {
                        echo "<a href='prestamos.php?devolver=".$row['id']."' class='action-icon text-success' title='Marcar como devuelto'><i class='fa-solid fa-check'></i></a> ";
                    }
                    echo "<a href='prestamos.php?eliminar=".$row['id']."' class='action-icon text-danger' onclick=\"return confirm('¿Eliminar este préstamo?');\" title='Eliminar préstamo'><i class='fa-solid fa-trash'></i></a>";
                    echo "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6'>❌ No hay préstamos registrados</td></tr>";
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</body>
</html>
