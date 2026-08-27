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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $libro_id  = (int) $_POST['libro_id'];
    $cantidad  = (int) $_POST['cantidad'];
    $usuario_id = (int) $_SESSION['id'];

    $fecha_prestamo   = date('Y-m-d');
    $fecha_devolucion = date('Y-m-d', strtotime('+7 days'));

    if ($cantidad < 1) {
        $error = "La cantidad debe ser mínimo 1.";
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

// Libros disponibles
$libros = $conn->query("
    SELECT
        id,
        titulo,
        disponible
    FROM libros
    WHERE disponible > 0
    ORDER BY titulo ASC
");
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

<body>

<div class="contenedor">

    <h2>
        <i class="fa-solid fa-hand-holding-book"></i>
        Generar un Nuevo Préstamo
    </h2>


    <?php if (!empty($error)): ?>

        <div class="alert alert-danger">
            ❌ <?php echo htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>


    <form method="POST">

        <label for="libro_id">
            Selecciona un libro disponible:
        </label>

        <select
            name="libro_id"
            required
        >

            <option value="">
                -- Elige un libro --
            </option>

            <?php while ($row = $libros->fetch(PDO::FETCH_ASSOC)): ?>

                <option value="<?php echo $row['id']; ?>">

                    <?php
                    echo htmlspecialchars($row['titulo']);
                    ?>

                    (<?php echo (int)$row['disponible']; ?> disponibles)

                </option>

            <?php endwhile; ?>

        </select>


        <label for="cantidad">
            Cantidad de ejemplares:
        </label>

        <input
            type="number"
            name="cantidad"
            min="1"
            value="1"
            required
        >


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