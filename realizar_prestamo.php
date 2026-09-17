<?php
include 'conexion.php';
session_start();

// Solo portal de usuarios
if (
    !isset($_SESSION['usuario']) ||
    !in_array($_SESSION['rol'], ['alumno', 'profesor', 'administrativo'])
) {
    header("Location: sin_permiso.php");
    exit;
}

// Categorías para guiar la selección del libro.
$stmtCategorias = $conn->query("SELECT DISTINCT categoria FROM libros ORDER BY categoria ASC");
$categorias = $stmtCategorias->fetchAll(PDO::FETCH_COLUMN);
$categoriaSeleccionada = trim($_GET['categoria'] ?? $_POST['categoria'] ?? '');
if (!in_array($categoriaSeleccionada, $categorias, true)) {
    $categoriaSeleccionada = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $libro_id  = (int) $_POST['libro_id'];
    $cantidad  = (int) $_POST['cantidad'];
    $usuario_id = (int) $_SESSION['id'];

    $fecha_prestamo   = $_POST['fecha_prestamo'] ?? '';
    $fecha_devolucion = $_POST['fecha_devolucion'] ?? '';

    if ($cantidad < 1) {
        $error = "La cantidad debe ser mínimo 1.";
    } elseif (!$fecha_prestamo || !$fecha_devolucion) {
        $error = "Selecciona la fecha de préstamo y la fecha de devolución.";
    } elseif ($fecha_devolucion < $fecha_prestamo) {
        $error = "La fecha de devolución no puede ser anterior a la fecha de préstamo.";
    } else {

        try {

            $conn->beginTransaction();

            // Bloquear libro mientras se procesa el préstamo
            $verificar = $conn->prepare("
                SELECT
                    id,
                    titulo,
                    cantidad_total,
                    disponible
                FROM libros
                WHERE id = :id
                FOR UPDATE
            ");

            $verificar->execute([
                ':id' => $libro_id
            ]);

            $libro = $verificar->fetch(PDO::FETCH_ASSOC);

            if (!$libro) {
                throw new Exception("El libro seleccionado no existe.");
            }

            $disponibles = (int) $libro['disponible'];

            if ($cantidad > $disponibles) {
                throw new Exception(
                    "Solo hay {$disponibles} ejemplar(es) disponible(s)."
                );
            }

            // Crear préstamo
            $stmt = $conn->prepare("
                INSERT INTO prestamos
                (
                    usuario_id,
                    libro_id,
                    cantidad,
                    fecha_prestamo,
                    fecha_devolucion,
                    estado
                )
                VALUES
                (
                    :usuario_id,
                    :libro_id,
                    :cantidad,
                    :fecha_prestamo,
                    :fecha_devolucion,
                    'prestado'
                )
            ");

            $stmt->execute([
                ':usuario_id'      => $usuario_id,
                ':libro_id'        => $libro_id,
                ':cantidad'        => $cantidad,
                ':fecha_prestamo'  => $fecha_prestamo,
                ':fecha_devolucion'=> $fecha_devolucion
            ]);

            // Actualizar inventario
            $nuevoDisponible = $disponibles - $cantidad;

            $nuevoEstado =
                ($nuevoDisponible > 0)
                ? 'Disponible'
                : 'No_disponible';

            $update = $conn->prepare("
                UPDATE libros
                SET
                    disponible = :disponible,
                    estado = :estado
                WHERE id = :id
            ");

            $update->execute([
                ':disponible' => $nuevoDisponible,
                ':estado'     => $nuevoEstado,
                ':id'         => $libro_id
            ]);

            $conn->commit();

            header("Location: prestamos_usuario.php?mensaje=prestamo_creado");
            exit;

        } catch (Throwable $e) {

            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $error = $e->getMessage();
        }
    }
}

// Solo se cargan los libros disponibles de la categoría elegida.
$libros = [];
if ($categoriaSeleccionada !== '') {
    $stmtLibros = $conn->prepare("SELECT id, titulo, disponible FROM libros WHERE disponible > 0 AND categoria = :categoria ORDER BY titulo ASC");
    $stmtLibros->execute([':categoria' => $categoriaSeleccionada]);
    $libros = $stmtLibros->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Generar Préstamo | BiblioWeb</title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >
    <link rel="stylesheet" href="estilos.css?v=20260916">

    <style>

        body {
            background-color: #f4f6fb;
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .contenedor {
            background: white;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            padding: 40px;
            width: 500px;
            max-width: 90%;
        }

        h2 {
            color: #333;
            margin-bottom: 25px;
            font-weight: 700;
            text-align: center;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #444;
        }

        select,
        input {
            width: 100%;
            box-sizing: border-box;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 15px;
        }

        .btn {
            display: block;
            width: 100%;
            box-sizing: border-box;
            padding: 12px;
            border: none;
            border-radius: 6px;
            color: white;
            font-weight: bold;
            font-size: 15px;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            margin-bottom: 12px;
        }

        .btn-success {
            background-color: #28a745;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        .btn-primary {
            background-color: #007bff;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-danger {
            background: #ffe3e3;
            color: #b02a37;
        }

    </style>

</head>

<body class="portal-user portal-loan-form">
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

    <h2>
        <i class="fa-solid fa-hand-holding-book"></i>
        Generar un Nuevo Préstamo
    </h2>
    <p class="form-helper">Elige una categoría, selecciona el libro y define las fechas de tu préstamo.</p>


    <?php if (!empty($error)): ?>

        <div class="alert alert-danger">
            ❌ <?php echo htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>


    <form method="GET" class="category-filter" action="realizar_prestamo.php">
        <label for="categoria">1. Elige una categoría</label>
        <select id="categoria" name="categoria" onchange="this.form.submit()">
            <option value="">-- Selecciona una categoría --</option>
            <?php foreach ($categorias as $categoria): ?>
                <option value="<?php echo htmlspecialchars($categoria); ?>" <?php echo $categoria === $categoriaSeleccionada ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($categoria); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <form method="POST" class="loan-form">

        <input type="hidden" name="categoria" value="<?php echo htmlspecialchars($categoriaSeleccionada); ?>">

        <label for="libro_id">
            2. Selecciona un libro disponible:
        </label>

        <select
            name="libro_id"
            required
        >

            <option value="">
                <?php echo $categoriaSeleccionada === '' ? '-- Primero selecciona una categoría --' : '-- Elige un libro --'; ?>
            </option>

            <?php foreach ($libros as $row): ?>

                <option value="<?php echo $row['id']; ?>">

                    <?php
                    echo htmlspecialchars($row['titulo']);
                    ?>

                    (<?php echo (int)$row['disponible']; ?> disponibles)

                </option>

            <?php endforeach; ?>

        </select>


        <label for="cantidad">
            3. Cantidad de ejemplares:
        </label>

        <input
            type="number"
            name="cantidad"
            min="1"
            value="1"
            required
        >

        <div class="loan-date-grid">
            <div>
                <label for="fecha_prestamo">4. Fecha de préstamo</label>
                <input id="fecha_prestamo" type="date" name="fecha_prestamo" value="<?php echo htmlspecialchars($_POST['fecha_prestamo'] ?? date('Y-m-d')); ?>" required>
            </div>
            <div>
                <label for="fecha_devolucion">5. Fecha de devolución</label>
                <input id="fecha_devolucion" type="date" name="fecha_devolucion" value="<?php echo htmlspecialchars($_POST['fecha_devolucion'] ?? date('Y-m-d', strtotime('+7 days'))); ?>" required>
            </div>
        </div>


        <button
            type="submit"
            class="btn btn-success"
        >

            <i class="fa-solid fa-plus"></i>
            Confirmar Préstamo

        </button>


        <a
            href="prestamos_usuario.php"
            class="btn btn-primary"
        >

            <i class="fa-solid fa-arrow-left"></i>
            Volver a Mis Préstamos

        </a>

    </form>

</div>

</body>

</html>
