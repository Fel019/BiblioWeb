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

// Insertar nuevo libro
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo    = $_POST['titulo'];
    $autor     = $_POST['autor'];
    $isbn      = $_POST['isbn'];
    $categoria = $_POST['categoria'];
    $anio      = date("Y"); 
    $estado    = 'disponible';
    $disponible = 1;

    $sql = "INSERT INTO libros (titulo, autor, isbn, categoria, anio_publicacion, estado, disponible)
            VALUES (:titulo, :autor, :isbn, :categoria, :anio, :estado, :disponible)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':titulo'    => $titulo,
        ':autor'     => $autor,
        ':isbn'      => $isbn,
        ':categoria' => $categoria,
        ':anio'      => $anio,
        ':estado'    => $estado,
        ':disponible'=> $disponible
    ]);
    echo "<script>alert('✅ Libro agregado correctamente');</script>";
}

// Eliminar libro
if (isset($_GET['eliminar'])) {
    $id  = $_GET['eliminar'];
    $del = $conn->prepare("DELETE FROM libros WHERE id = :id");
    $del->execute([':id' => $id]);

    header("Location: libros.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión de Libros | BiblioSys</title>
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
    <a href="logout.php" class="btn btn-primary btn-small">
      <i class="fa-solid fa-right-from-bracket"></i> Salir
    </a>
  </div>
</header>

<main class="main-content">
  <div class="usuarios-layout">
      
    <!-- Columna izquierda -->
    <div class="usuarios-sidebar">
      <!-- Buscar libro -->
      <div class="card">
        <h3>🔍 Buscar Libro</h3>
        <form method="GET" action="libros.php">
          <input type="text" name="q" placeholder="Título, autor o ISBN..." 
                 value="<?php echo isset($_GET['q']) ? $_GET['q'] : ''; ?>">
          <button type="submit" class="btn btn-buscar">Buscar</button>
        </form>
      </div>

      <!-- Agregar libro (solo admin y bibliotecario) -->
      <?php if ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'bibliotecario'): ?>
        <div class="card">
          <h3>📚 Agregar Nuevo Libro</h3>
          <form method="POST" action="">
            <input type="text" name="titulo" placeholder="Título" required>
            <input type="text" name="autor" placeholder="Autor" required>
            <input type="text" name="isbn" placeholder="ISBN" required>
            <select name="categoria" required>
              <option value="">Categoría</option>
              <option value="Novela">Novela</option>
              <option value="Clásico">Clásico</option>
              <option value="Tecnología">Tecnología</option>
              <option value="Infantil">Infantil</option>
              <option value="Otros">Otros</option>
            </select>
            <button type="submit" class="btn btn-agregar">➕ Agregar Libro</button>
          </form>
        </div>
      <?php endif; ?>
    </div>

    <!-- Columna derecha -->
    <div class="usuarios-main card">
      <h3>Catálogo de Libros</h3>
      <table>
        <thead>
          <tr>
            <th>TÍTULO</th>
            <th>AUTOR</th>
            <th>ISBN</th>
            <th>AÑO</th>
            <th>CATEGORÍA</th>
            <th>ESTADO</th>
            <?php if ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'bibliotecario'): ?>
              <th>ACCIONES</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php
          $where  = "";
          $params = [];

          if (!empty($_GET['q'])) {
              $q = "%" . $_GET['q'] . "%";
              $where = "WHERE titulo LIKE :q OR autor LIKE :q OR isbn LIKE :q";
              $params[':q'] = $q;
          }

          $sql = "SELECT id, titulo, autor, isbn, anio_publicacion AS anio, categoria, estado 
                  FROM libros $where ORDER BY id ASC";
          $stmt = $conn->prepare($sql);
          $stmt->execute($params);

          if ($stmt->rowCount() > 0) {
              while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                  echo "<tr>";
                  echo "<td>" . htmlspecialchars($row['titulo']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['autor']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['isbn']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['anio']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['categoria']) . "</td>";
                  echo "<td><span class='badge " . 
                        ($row['estado'] == 'disponible' ? 'badge-success' : 'badge-danger') . "'>" 
                        . ucfirst($row['estado']) . "</span></td>";

                  if ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'bibliotecario') {
                      echo "<td>
                              <a href='editar_libro.php?id=" . $row['id'] . "' class='action-icon text-success'>
                                <i class='fa-solid fa-pen-to-square'></i>
                              </a>
                              <a href='libros.php?eliminar=" . $row['id'] . "' 
                                 class='action-icon text-danger' 
                                 onclick=\"return confirm('¿Seguro que quieres eliminar este libro?');\">
                                <i class='fa-solid fa-trash'></i>
                              </a>
                            </td>";
                  }

                  echo "</tr>";
              }
          } else {
              $colspan = ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'bibliotecario') ? 7 : 6;
              echo "<tr><td colspan='".$colspan."'>❌ No se encontraron libros</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
</body>
</html>
