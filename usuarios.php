<?php
include 'conexion.php';
session_start();

// 🔹 Verificamos que el usuario haya iniciado sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// 🔹 Verificamos el rol (según tu BD)
if ($_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== 'bibliotecario') {
    header("Location: sin_permiso.php");
    exit;
}

// 🔹 Insertar nuevo usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $password = md5($_POST['password']);
    $rol = !empty($_POST['rol']) ? $_POST['rol'] : 'estudiante';
    $estado = 'activo';

    // Verificar si ya existe el correo
    $check = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE correo = :correo");
    $check->execute([':correo' => $correo]);
    if ($check->fetchColumn() > 0) {
        echo "<script>alert('❌ El correo ya está registrado, elige otro.');</script>";
    } else {
        $sql = "INSERT INTO usuarios (nombre, correo, password, rol, estado)
                VALUES (:nombre, :correo, :password, :rol, :estado)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':nombre' => $nombre,
            ':correo' => $correo,
            ':password' => $password,
            ':rol' => $rol,
            ':estado' => $estado
        ]);
        echo "<script>alert('✅ Usuario registrado correctamente');</script>";
    }
}

// 🔹 Eliminar usuario
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $del = $conn->prepare("DELETE FROM usuarios WHERE id = :id");
    $del->execute([':id' => $id]);
    header("Location: usuarios.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión de Usuarios | BiblioWeb</title>
  <link rel="stylesheet" href="estilos.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <header class="navbar">
    <div class="navbar-left">
      <i class="fa-solid fa-book-open navbar-logo"></i>
      <span class="navbar-title">BiblioWeb</span>
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

  <main class="main-content usuarios-layout">
    <!-- 🔹 Columna izquierda -->
    <div class="usuarios-sidebar">
      <div class="card">
        <h3>🔍 Buscar Usuario</h3>
        <form method="GET" action="usuarios.php">
          <input type="text" name="q" placeholder="Nombre o correo..."
                 value="<?php echo isset($_GET['q']) ? $_GET['q'] : ''; ?>">
          <button type="submit" class="btn btn-buscar">🔍Buscar</button>
        </form>
      </div>

      <div class="card">
        <h3>👤 Registrar Nuevo Usuario</h3>
        <form method="POST" action="">
          <input type="text" name="nombre" placeholder="Nombre completo" required>
          <input type="email" name="correo" placeholder="Correo electrónico" required>
          <select name="rol" required>
            <option value="" disabled selected>Selecciona un rol</option>
            <option value="estudiante">Estudiante</option>
            <option value="profesor">Profesor</option>
            <option value="administrativo">Administrativo</option>
            <option value="bibliotecario">Bibliotecario</option>
            <option value="administrador">Administrador</option>
          </select>
          <input type="password" name="password" placeholder="Contraseña" required>
          <button type="submit" class="btn btn-success">
            <i class="fa-solid fa-user-plus"></i> Registrar
          </button>
        </form>
      </div>
    </div>

    <!-- 🔹 Columna derecha -->
    <div class="usuarios-main card">
      <h3>Usuarios Registrados</h3>
      <table>
        <thead>
          <tr>
            <th>NOMBRE</th>
            <th>CORREO</th>
            <th>ROL</th>
            <th>ESTADO</th>
            <th>ACCIONES</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $where = "";
          $params = [];

          if (!empty($_GET['q'])) {
              $q = "%" . $_GET['q'] . "%";
              $where = "WHERE nombre LIKE :q OR correo LIKE :q";
              $params[':q'] = $q;
          }

          // 🔹 Consulta ajustada a tu base de datos real
          $sql = "SELECT id, nombre, correo, rol, estado FROM usuarios $where ORDER BY id ASC";
          $stmt = $conn->prepare($sql);
          $stmt->execute($params);

          if ($stmt->rowCount() > 0) {
              while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                  echo "<tr>";
                  echo "<td>{$row['nombre']}</td>";
                  echo "<td>{$row['correo']}</td>";
                  echo "<td>" . ucfirst($row['rol']) . "</td>";
                  echo "<td><span class='badge " .
                        ($row['estado'] == 'activo' ? 'badge-success' : 'badge-danger') . "'>"
                        . ucfirst($row['estado']) . "</span></td>";
                  echo "<td>
                          <a href='editar_usuario.php?id={$row['id']}' class='action-icon text-success'>
                            <i class='fa-solid fa-pen-to-square'></i>
                          </a>
                          <a href='usuarios.php?eliminar={$row['id']}'
                             class='action-icon text-danger'
                             onclick=\"return confirm('¿Seguro que quieres eliminar este usuario?');\">
                            <i class='fa-solid fa-trash'></i>
                          </a>
                        </td>";
                  echo "</tr>";
              }
          } else {
              echo "<tr><td colspan='5'>❌ No se encontraron usuarios</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
  </main>
</body>
</html>
