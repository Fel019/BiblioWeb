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

// Validar ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: libros.php");
    exit;
}

$id = (int) $_GET['id'];


// ======================================================
// OBTENER DATOS DEL LIBRO
// ======================================================

$stmt = $conn->prepare("
    SELECT
        id,
        titulo,
        autor,
        isbn,
        categoria,
        anio_publicacion,
        cantidad_total,
        disponible,
        estado
    FROM libros
    WHERE id = :id
");

$stmt->execute([
    ':id' => $id
]);

$libro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$libro) {
    header("Location: libros.php");
    exit;
}


// ======================================================
// CALCULAR EJEMPLARES ACTUALMENTE PRESTADOS
// ======================================================

$prestados =
    (int)$libro['cantidad_total']
    -
    (int)$libro['disponible'];

if ($prestados < 0) {
    $prestados = 0;
}


// ======================================================
// ACTUALIZAR LIBRO
// ======================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $titulo         = trim($_POST['titulo']);
    $autor          = trim($_POST['autor']);
    $isbn           = trim($_POST['isbn']);
    $categoria      = trim($_POST['categoria']);
    $anio           = (int) $_POST['anio_publicacion'];
    $cantidad_total = (int) $_POST['cantidad_total'];

    // No se puede tener menos copias totales que las actualmente prestadas
    if ($cantidad_total < $prestados) {

        $error =
            "No puedes reducir la cantidad total a "
            . $cantidad_total
            . " porque actualmente hay "
            . $prestados
            . " ejemplar(es) prestado(s).";

    } elseif ($cantidad_total < 1) {

        $error = "La cantidad total debe ser mínimo 1.";

    } else {

        // Mantener intactos los préstamos actuales
        $nuevo_disponible =
            $cantidad_total - $prestados;

        // El estado depende de las copias disponibles
        $estado =
            ($nuevo_disponible > 0)
            ? 'Disponible'
            : 'No_disponible';

        try {

            $update = $conn->prepare("
                UPDATE libros
                SET
                    titulo = :titulo,
                    autor = :autor,
                    isbn = :isbn,
                    categoria = :categoria,
                    anio_publicacion = :anio,
                    cantidad_total = :cantidad_total,
                    disponible = :disponible,
                    estado = :estado
                WHERE id = :id
            ");

            $update->execute([
                ':titulo'         => $titulo,
                ':autor'          => $autor,
                ':isbn'           => $isbn,
                ':categoria'      => $categoria,
                ':anio'           => $anio,
                ':cantidad_total' => $cantidad_total,
                ':disponible'     => $nuevo_disponible,
                ':estado'         => $estado,
                ':id'             => $id
            ]);

            header("Location: libros.php?mensaje=actualizado");
            exit;

        } catch (PDOException $e) {

            if ($e->getCode() == 23000) {
                $error = "Ya existe otro libro registrado con ese ISBN.";
            } else {
                $error = "No fue posible actualizar el libro.";
            }
        }
    }
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

    <title>Editar Libro | BiblioSys</title>

    <link
        rel="stylesheet"
        href="estilos.css?v=20260916"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

    <style>

        body {
            font-family: 'Inter', sans-serif;
            background: #f4f6f9;
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .edit-container {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
        }

        .edit-container h2 {
            text-align: center;
            margin-bottom: 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 15px;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 6px;
        }

        input,
        select {
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
        }

        .info-box {
            background: #eef7ff;
            border-left: 4px solid #0d6efd;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 6px;
        }

        .alert-danger {
            background: #ffe3e3;
            color: #b02a37;
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 15px;
        }

        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-group .btn {
            flex: 1;
            text-align: center;
            text-decoration: none;
        }

        .btn-success {
            background: #28a745;
            color: #fff;
        }

        .btn-secondary {
            background: #6c757d;
            color: #fff;
        }

    </style>

</head>


<body>

<div class="edit-container">

    <h2>
        <i class="fa-solid fa-pen-to-square"></i>
        Editar Libro
    </h2>


    <?php if (!empty($error)): ?>

        <div class="alert-danger">
            ❌ <?php echo htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>


    <div class="info-box">

        <strong>Inventario actual</strong>

        <br>

        Total:
        <?php echo (int)$libro['cantidad_total']; ?>

        <br>

        Disponibles:
        <?php echo (int)$libro['disponible']; ?>

        <br>

        Prestados:
        <?php echo $prestados; ?>

    </div>


    <form method="POST">


        <div class="form-group">

            <label>Título</label>

            <input
                type="text"
                name="titulo"
                value="<?php echo htmlspecialchars($libro['titulo']); ?>"
                required
            >

        </div>


        <div class="form-group">

            <label>Autor</label>

            <input
                type="text"
                name="autor"
                value="<?php echo htmlspecialchars($libro['autor']); ?>"
                required
            >

        </div>


        <div class="form-group">

            <label>ISBN</label>

            <input
                type="text"
                name="isbn"
                value="<?php echo htmlspecialchars($libro['isbn']); ?>"
                required
            >

        </div>


        <div class="form-group">

            <label>Año de publicación</label>

            <input
                type="number"
                name="anio_publicacion"
                min="1000"
                max="<?php echo date('Y'); ?>"
                value="<?php echo htmlspecialchars($libro['anio_publicacion']); ?>"
                required
            >

        </div>


        <div class="form-group">

            <label>Categoría</label>

            <select name="categoria" required>

                <?php

                $categorias = [
                    'Novela',
                    'Clásico',
                    'Ficción',
                    'Infantil',
                    'Romance',
                    'Fantasía',
                    'Suspenso',
                    'Historia',
                    'Biografía',
                    'Tecnología',
                    'Inspiracional',
                    'Otros'
                ];

                foreach ($categorias as $categoria):

                ?>

                    <option
                        value="<?php echo $categoria; ?>"
                        <?php
                        echo
                            $libro['categoria'] === $categoria
                            ? 'selected'
                            : '';
                        ?>
                    >
                        <?php echo $categoria; ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <div class="form-group">

            <label>Cantidad total de ejemplares</label>

            <input
                type="number"
                name="cantidad_total"
                min="<?php echo max(1, $prestados); ?>"
                value="<?php echo (int)$libro['cantidad_total']; ?>"
                required
            >

        </div>


        <div class="btn-group">

            <button
                type="submit"
                class="btn btn-success">

                <i class="fa-solid fa-floppy-disk"></i>
                Guardar Cambios

            </button>


            <a
                href="libros.php"
                class="btn btn-secondary">

                <i class="fa-solid fa-arrow-left"></i>
                Volver

            </a>

        </div>

    </form>

</div>

</body>

</html>
