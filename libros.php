<?php
include 'conexion.php';
session_start();

include 'verificar_rol.php';
verificarRol(['administrador', 'bibliotecario']);

// Validar sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}


// ======================================================
// AGREGAR NUEVO LIBRO
// ======================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_libro'])) {

    $titulo         = trim($_POST['titulo']);
    $autor          = trim($_POST['autor']);
    $isbn           = trim($_POST['isbn']);
    $categoria      = trim($_POST['categoria']);
    $cantidad_total = (int) $_POST['cantidad_total'];

    // Por ahora conservamos el comportamiento del proyecto original.
    $anio = date("Y");

    if ($cantidad_total < 1) {
        $cantidad_total = 1;
    }

    // Al crear un libro, todos sus ejemplares están disponibles.
    $disponible = $cantidad_total;

    $estado = ($disponible > 0)
        ? 'Disponible'
        : 'No_disponible';

    try {

        $sql = "INSERT INTO libros
                (
                    titulo,
                    autor,
                    isbn,
                    categoria,
                    anio_publicacion,
                    cantidad_total,
                    disponible,
                    estado
                )
                VALUES
                (
                    :titulo,
                    :autor,
                    :isbn,
                    :categoria,
                    :anio,
                    :cantidad_total,
                    :disponible,
                    :estado
                )";

        $stmt = $conn->prepare($sql);

        $stmt->execute([
            ':titulo'         => $titulo,
            ':autor'          => $autor,
            ':isbn'           => $isbn,
            ':categoria'      => $categoria,
            ':anio'           => $anio,
            ':cantidad_total' => $cantidad_total,
            ':disponible'     => $disponible,
            ':estado'         => $estado
        ]);

        header("Location: libros.php?mensaje=agregado");
        exit;

    } catch (PDOException $e) {

        if ($e->getCode() == 23000) {
            $error = "Ya existe un libro registrado con ese ISBN.";
        } else {
            $error = "No fue posible registrar el libro.";
        }
    }
}


// ======================================================
// ELIMINAR LIBRO
// ======================================================

if (isset($_GET['eliminar'])) {

    $id = (int) $_GET['eliminar'];

    try {

        $del = $conn->prepare(
            "DELETE FROM libros
             WHERE id = :id"
        );

        $del->execute([
            ':id' => $id
        ]);

        header("Location: libros.php?mensaje=eliminado");
        exit;

    } catch (PDOException $e) {

        $error = "No se puede eliminar el libro porque tiene préstamos asociados.";
    }
}


// ======================================================
// BUSCAR LIBROS
// ======================================================

$condiciones = [];
$params = [];

$stmtCategorias = $conn->query("SELECT categoria, COUNT(*) AS total FROM libros GROUP BY categoria ORDER BY categoria ASC");
$categoriasFiltro = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);
$categoriaFiltro = trim($_GET['categoria'] ?? '');

if (!empty($_GET['q'])) {

    $busqueda = trim($_GET['q']);

    $condiciones[] = "(titulo LIKE :q OR autor LIKE :q OR isbn LIKE :q)";

    $params[':q'] = "%" . $busqueda . "%";
}

if ($categoriaFiltro !== '') {
    $condiciones[] = "categoria = :categoria";
    $params[':categoria'] = $categoriaFiltro;
}

$where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';
$porPagina = 10;
$paginaActual = max(1, (int) ($_GET['pagina'] ?? 1));
$stmtTotal = $conn->prepare("SELECT COUNT(*) FROM libros $where");
$stmtTotal->execute($params);
$totalLibros = (int) $stmtTotal->fetchColumn();
$totalPaginas = max(1, (int) ceil($totalLibros / $porPagina));
$paginaActual = min($paginaActual, $totalPaginas);
$offset = ($paginaActual - 1) * $porPagina;


$sql = "
    SELECT
        id,
        titulo,
        autor,
        isbn,
        anio_publicacion,
        categoria,
        cantidad_total,
        disponible,
        estado
    FROM libros
    $where
    ORDER BY id ASC
    LIMIT :limite OFFSET :offset
";

$stmt = $conn->prepare($sql);
foreach ($params as $clave => $valor) {
    $stmt->bindValue($clave, $valor, PDO::PARAM_STR);
}
$stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$libros = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Gestión de Libros | BiblioSys</title>

    <link rel="stylesheet"
          href="estilos.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>


<body>


<!-- ======================================================
     NAVBAR
====================================================== -->

<header class="navbar">

    <div class="navbar-left">

        <img class="navbar-logo" src="assets/Llanoverde.png" alt="Escudo institucional">

        <span class="navbar-title">
            Institución Educativa Llano Verde Sede Calimio
        </span>

    </div>


    <div class="navbar-center">

        <nav class="nav-tabs">

            <a
                class="active"
                href="libros.php">
                Gestión de Libros
            </a>

            <a href="prestamos.php">
                Préstamos
            </a>

            <a href="usuarios.php">
                Usuarios
            </a>

            <a href="reportes.php">
                Reportes
            </a>

        </nav>

    </div>


    <div class="navbar-right">

        <span>

            <?php echo ucfirst(htmlspecialchars($_SESSION['rol'])); ?>:

            <strong>
                <?php echo htmlspecialchars($_SESSION['usuario']); ?>
            </strong>

        </span>


        <a
            href="logout.php"
            class="btn btn-primary btn-small">

            <i class="fa-solid fa-right-from-bracket"></i>

            Salir

        </a>

    </div>

</header>



<!-- ======================================================
     CONTENIDO
====================================================== -->

<main class="main-content">
    <section class="workspace-heading">
        <div><h1>Gestión de libros</h1><p>Organiza el catálogo, la disponibilidad y los ejemplares de la biblioteca.</p></div>
        <span class="workspace-tag"><i class="fa-solid fa-book"></i> Catálogo institucional</span>
    </section>


    <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'agregado'): ?>

        <div class="alert alert-success">
            ✅ Libro agregado correctamente.
        </div>

    <?php endif; ?>

    <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'actualizado'): ?>

    <div class="alert alert-success">
        ✅ Libro actualizado correctamente.
    </div>

<?php endif; ?>


    <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'eliminado'): ?>

        <div class="alert alert-success">
            ✅ Libro eliminado correctamente.
        </div>

    <?php endif; ?>


    <?php if (!empty($error)): ?>

        <div class="alert alert-danger">

            ❌ <?php echo htmlspecialchars($error); ?>

        </div>

    <?php endif; ?>



    <div class="usuarios-layout">


        <!-- ======================================================
             COLUMNA IZQUIERDA
        ====================================================== -->

        <div class="usuarios-sidebar">


            <!-- BUSCAR LIBRO -->

            <div class="card">

                <h3>
                    🔍 Buscar Libro
                </h3>


                <form
                    method="GET"
                    action="libros.php">

                    <input
                        type="text"
                        name="q"
                        placeholder="Título, autor o ISBN..."
                        value="<?php
                            echo isset($_GET['q'])
                                ? htmlspecialchars($_GET['q'])
                                : '';
                        ?>"
                    >

                    <select name="categoria">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categoriasFiltro as $categoria): ?>
                            <option value="<?php echo htmlspecialchars($categoria['categoria']); ?>" <?php echo $categoriaFiltro === $categoria['categoria'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($categoria['categoria']); ?> (<?php echo (int) $categoria['total']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>


                    <button
                        type="submit"
                        class="btn btn-buscar">

                        Buscar

                    </button>

                </form>

            </div>



            <!-- AGREGAR LIBRO -->

            <div class="card">

                <h3>
                    📚 Agregar Nuevo Libro
                </h3>


                <form
                    method="POST"
                    action="libros.php">


                    <input
                        type="hidden"
                        name="agregar_libro"
                        value="1"
                    >


                    <input
                        type="text"
                        name="titulo"
                        placeholder="Título"
                        required
                    >


                    <input
                        type="text"
                        name="autor"
                        placeholder="Autor"
                        required
                    >


                    <input
                        type="text"
                        name="isbn"
                        placeholder="ISBN"
                        required
                    >


                    <select
                        name="categoria"
                        required>

                        <option value="">
                            Categoría
                        </option>

                        <option value="Novela">
                            Novela
                        </option>

                        <option value="Clásico">
                            Clásico
                        </option>

                        <option value="Ficción">
                            Ficción
                        </option>

                        <option value="Infantil">
                            Infantil
                        </option>

                        <option value="Romance">
                            Romance
                        </option>

                        <option value="Fantasía">
                            Fantasía
                        </option>

                        <option value="Suspenso">
                            Suspenso
                        </option>

                        <option value="Historia">
                            Historia
                        </option>

                        <option value="Biografía">
                            Biografía
                        </option>

                        <option value="Tecnología">
                            Tecnología
                        </option>

                        <option value="Inspiracional">
                            Inspiracional
                        </option>

                        <option value="Otros">
                            Otros
                        </option>

                    </select>


                    <input
                        type="number"
                        name="cantidad_total"
                        min="1"
                        value="1"
                        placeholder="Cantidad de ejemplares"
                        required
                    >


                    <button
                        type="submit"
                        class="btn btn-agregar">

                        <i class="fa-solid fa-plus"></i>

                        Agregar Libro

                    </button>

                </form>

            </div>

        </div>



        <!-- ======================================================
             CATÁLOGO
        ====================================================== -->

        <div class="usuarios-main card">


            <h3>
                Catálogo de Libros <span class="list-counter"><?php echo $totalLibros; ?> resultado<?php echo $totalLibros === 1 ? '' : 's'; ?></span>
            </h3>


            <table>


                <thead>

                    <tr>

                        <th>TÍTULO</th>

                        <th>AUTOR</th>

                        <th>ISBN</th>

                        <th>AÑO</th>

                        <th>CATEGORÍA</th>

                        <th>DISP.</th>

                        <th>ESTADO</th>

                        <th>ACCIONES</th>

                    </tr>

                </thead>


                <tbody>


                <?php if (count($libros) > 0): ?>


                    <?php foreach ($libros as $libro): ?>


                        <?php

                        // Estado calculado según disponibilidad real.

                        $estadoActual =
                            ((int)$libro['disponible'] > 0)
                            ? 'Disponible'
                            : 'No_disponible';

                        ?>


                        <tr>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $libro['titulo']
                                );
                                ?>

                            </td>


                            <td class="text-primary">

                                <strong>

                                <?php
                                echo htmlspecialchars(
                                    $libro['autor']
                                );
                                ?>

                                </strong>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $libro['isbn']
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $libro['anio_publicacion']
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $libro['categoria']
                                );
                                ?>

                            </td>



                            <!-- DISPONIBILIDAD -->

                            <td>

                                <?php

                                echo
                                    (int)$libro['disponible']
                                    .
                                    "/"
                                    .
                                    (int)$libro['cantidad_total'];

                                ?>

                            </td>



                            <!-- ESTADO -->

                            <td>


                                <?php if ($estadoActual === 'Disponible'): ?>


                                    <span class="badge badge-success">

                                        Disponible

                                    </span>


                                <?php else: ?>


                                    <span class="badge badge-danger">

                                        No_disponible

                                    </span>


                                <?php endif; ?>


                            </td>



                            <!-- ACCIONES -->

                            <td>


                                <a
                                    href="editar_libro.php?id=<?php echo $libro['id']; ?>"
                                    class="action-icon text-success"
                                    title="Editar">

                                    <i class="fa-solid fa-pen-to-square"></i>

                                </a>



                                <a
                                    href="libros.php?eliminar=<?php echo $libro['id']; ?>"
                                    class="action-icon text-danger"
                                    title="Eliminar"
                                    onclick="return confirm('¿Seguro que quieres eliminar este libro?');">

                                    <i class="fa-solid fa-trash"></i>

                                </a>


                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php else: ?>


                    <tr>

                        <td colspan="8">

                            ❌ No se encontraron libros

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>


            </table>

            <?php if ($totalPaginas > 1): ?>
                <nav class="admin-pagination" aria-label="Paginación de libros">
                    <?php if ($paginaActual > 1): ?><a href="libros.php?<?php echo http_build_query(['q' => $_GET['q'] ?? '', 'categoria' => $categoriaFiltro, 'pagina' => $paginaActual - 1]); ?>">Anterior</a><?php endif; ?>
                    <span>Página <?php echo $paginaActual; ?> de <?php echo $totalPaginas; ?> · 10 por página</span>
                    <?php if ($paginaActual < $totalPaginas): ?><a href="libros.php?<?php echo http_build_query(['q' => $_GET['q'] ?? '', 'categoria' => $categoriaFiltro, 'pagina' => $paginaActual + 1]); ?>">Siguiente</a><?php endif; ?>
                </nav>
            <?php endif; ?>


        </div>


    </div>


</main>


</body>

</html>
