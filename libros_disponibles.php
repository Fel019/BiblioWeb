<?php
include 'conexion.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// Categorías disponibles para orientar la elección del lector.
$stmtCategorias = $conn->query("SELECT categoria, COUNT(*) AS total FROM libros GROUP BY categoria ORDER BY categoria ASC");
$categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);

$categoriaSeleccionada = trim($_GET['categoria'] ?? '');
$categoriasValidas = array_column($categorias, 'categoria');

// La primera categoría sirve como punto de inicio si no se ha elegido una aún.
if ($categoriaSeleccionada === '' && !empty($categoriasValidas)) {
  $categoriaSeleccionada = $categoriasValidas[0];
}

if (!in_array($categoriaSeleccionada, $categoriasValidas, true)) {
  $categoriaSeleccionada = $categoriasValidas[0] ?? '';
}

// Se muestran máximo 20 libros dentro de una categoría por página.
$porPagina = 20;
$paginaActual = max(1, (int) ($_GET['pagina'] ?? 1));

$contador = $conn->prepare("SELECT COUNT(*) FROM libros WHERE categoria = :categoria");
$contador->execute([':categoria' => $categoriaSeleccionada]);
$totalLibros = (int) $contador->fetchColumn();
$totalPaginas = max(1, (int) ceil($totalLibros / $porPagina));
$paginaActual = min($paginaActual, $totalPaginas);
$offset = ($paginaActual - 1) * $porPagina;

$stmt = $conn->prepare("SELECT id, titulo, autor, categoria, disponible
                        FROM libros
                        WHERE categoria = :categoria
                        ORDER BY titulo ASC
                        LIMIT :limite OFFSET :offset");
$stmt->bindValue(':categoria', $categoriaSeleccionada, PDO::PARAM_STR);
$stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>📚 Libros Disponibles | BiblioWeb</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="estilos.css">
  <style>
    body {
      background: #f4f6fb;
      font-family: "Poppins", sans-serif;
      padding: 50px;
      display: flex;
      justify-content: center;
    }
    .container {
      background: #fff;
      border-radius: 15px;
      padding: 30px 40px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
      width: 90%;
      max-width: 900px;
    }
    h2 {
      text-align: center;
      color: #333;
      margin-bottom: 25px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }
    th, td {
      border-bottom: 1px solid #ddd;
      padding: 10px;
      text-align: left;
    }
    th {
      background: #007bff;
      color: white;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    tr:hover { background: #f9f9f9; }
    .badge {
      padding: 5px 12px;
      border-radius: 12px;
      font-weight: bold;
      font-size: 0.9em;
    }
    .disponible { background: #28a745; color: white; }
    .agotado { background: #dc3545; color: white; }
    .btn {
      display: inline-block;
      background: #007bff;
      color: white;
      padding: 10px 20px;
      border-radius: 8px;
      text-decoration: none;
      margin-top: 20px;
      font-weight: 600;
      transition: 0.3s;
    }
    .btn:hover { background: #0056b3; }
    .cantidad {
      font-weight: 600;
      color: #555;
    }
  </style>
</head>
<body class="portal-user portal-catalog">
  <header class="portal-topbar">
    <a class="portal-brand" href="index.php"><img src="assets/escudo-institucional.png" alt=""><span>Colegio Parroquial<br>Nuestra Señora de los Andes</span></a>
    <nav class="portal-links" aria-label="Navegación principal">
      <a href="index.php" title="Inicio"><i class="fa-solid fa-house"></i><span> Inicio</span></a>
      <a class="active" href="libros_disponibles.php" title="Catálogo"><i class="fa-solid fa-book-open"></i><span> Catálogo</span></a>
      <a href="prestamos_usuario.php" title="Mis préstamos"><i class="fa-solid fa-calendar-check"></i><span> Préstamos</span></a>
      <a class="portal-exit" href="logout.php" title="Cerrar sesión"><i class="fa-solid fa-arrow-right-from-bracket"></i><span> Salir</span></a>
    </nav>
  </header>
  <div class="container">
    <section class="catalog-user-heading">
      <img src="assets/escudo-institucional.png" alt="Escudo institucional">
      <div>
        <h2><i class="fa-solid fa-book-open-reader"></i> Libros disponibles</h2>
        <p>Explora con calma y elige una categoría para encontrar una lectura que te guste.</p>
      </div>
    </section>

    <div class="catalog-selector">
      <form method="GET" action="libros_disponibles.php">
        <label for="categoria"><i class="fa-solid fa-compass"></i> ¿Qué te gustaría leer hoy?</label>
        <select id="categoria" name="categoria" onchange="this.form.submit()">
          <?php foreach ($categorias as $categoria): ?>
            <option value="<?php echo htmlspecialchars($categoria['categoria']); ?>" <?php echo $categoria['categoria'] === $categoriaSeleccionada ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($categoria['categoria']); ?> (<?php echo (int) $categoria['total']; ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </form>
      <span><i class="fa-solid fa-layer-group"></i> <?php echo array_sum(array_column($categorias, 'total')); ?> títulos en el catálogo</span>
    </div>

    <section class="catalog-results">
        <div class="category-heading">
          <div>
            <span class="eyebrow">Explorando categoría</span>
            <h3><?php echo htmlspecialchars($categoriaSeleccionada); ?></h3>
          </div>
          <span class="results-count"><?php echo $totalLibros; ?> libro<?php echo $totalLibros === 1 ? '' : 's'; ?></span>
        </div>

        <table>
      <thead>
        <tr>
          <th>Título</th>
          <th>Autor</th>
          <th>Categoría</th>
          <th>Copias Disponibles</th>
          <th>Estado</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($libros) > 0): ?>
          <?php foreach ($libros as $libro): 
            $copias = (int)$libro['disponible'];
            $estado = $copias > 0 ? 'Disponible' : 'Agotado';
            $clase = $copias > 0 ? 'disponible' : 'agotado';
          ?>
            <tr>
              <td><?php echo htmlspecialchars($libro['titulo']); ?></td>
              <td><?php echo htmlspecialchars($libro['autor']); ?></td>
              <td><?php echo htmlspecialchars($libro['categoria']); ?></td>
              <td class="cantidad"><?php echo $copias; ?></td>
              <td><span class="badge <?php echo $clase; ?>"><?php echo $estado; ?></span></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="5">❌ No hay libros registrados</td></tr>
        <?php endif; ?>
      </tbody>
        </table>

        <?php if ($totalPaginas > 1): ?>
          <nav class="catalog-pagination" aria-label="Paginación de libros">
            <?php if ($paginaActual > 1): ?>
              <a href="libros_disponibles.php?categoria=<?php echo urlencode($categoriaSeleccionada); ?>&pagina=<?php echo $paginaActual - 1; ?>"><i class="fa-solid fa-arrow-left"></i> Anterior</a>
            <?php endif; ?>
            <span>Página <?php echo $paginaActual; ?> de <?php echo $totalPaginas; ?> · 20 libros por página</span>
            <?php if ($paginaActual < $totalPaginas): ?>
              <a href="libros_disponibles.php?categoria=<?php echo urlencode($categoriaSeleccionada); ?>&pagina=<?php echo $paginaActual + 1; ?>">Siguiente <i class="fa-solid fa-arrow-right"></i></a>
            <?php endif; ?>
          </nav>
        <?php endif; ?>

        <a href="index.php" class="btn"><i class="fa-solid fa-arrow-left"></i> Volver al Inicio</a>
    </section>
  </div>
</body>
</html>
