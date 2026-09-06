<?php

include 'conexion.php';
session_start();
include 'verificar_rol.php';

verificarRol(['administrador', 'bibliotecario']);

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}


// ======================================================
// REGISTRAR PRÉSTAMO
// ======================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['registrar_prestamo'])
) {

    $id_usuario       = (int) $_POST['id_usuario'];
    $id_libro         = (int) $_POST['id_libro'];
    $cantidad         = (int) $_POST['cantidad'];
    $fecha_prestamo   = $_POST['fecha_prestamo'];
    $fecha_devolucion = $_POST['fecha_devolucion'];

    if ($cantidad < 1) {
        $error = "La cantidad debe ser mínimo 1.";
    } elseif ($fecha_devolucion < $fecha_prestamo) {
        $error = "La fecha de devolución no puede ser anterior a la fecha de préstamo.";
    } else {

        try {

            $conn->beginTransaction();

            // Bloquear libro mientras se procesa el préstamo
            $stmt = $conn->prepare("
                SELECT
                    titulo,
                    cantidad_total,
                    disponible
                FROM libros
                WHERE id = :id
                FOR UPDATE
            ");

            $stmt->execute([
                ':id' => $id_libro
            ]);

            $libro = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$libro) {
                throw new Exception("El libro seleccionado no existe.");
            }

            $disponibles = (int) $libro['disponible'];

            if ($cantidad > $disponibles) {
                throw new Exception(
                    "Solo hay {$disponibles} ejemplar(es) disponible(s) de este libro."
                );
            }


            // Registrar préstamo
            $sql = "
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
                    :usuario,
                    :libro,
                    :cantidad,
                    :fecha_prestamo,
                    :fecha_devolucion,
                    'prestado'
                )
            ";

            $insert = $conn->prepare($sql);

            $insert->execute([
                ':usuario'         => $id_usuario,
                ':libro'           => $id_libro,
                ':cantidad'        => $cantidad,
                ':fecha_prestamo'  => $fecha_prestamo,
                ':fecha_devolucion'=> $fecha_devolucion
            ]);


            // Restar ejemplares
            $nuevoDisponible = $disponibles - $cantidad;

            $estadoLibro =
                ($nuevoDisponible > 0)
                ? 'Disponible'
                : 'No_disponible';


            $updateLibro = $conn->prepare("
                UPDATE libros
                SET
                    disponible = :disponible,
                    estado = :estado
                WHERE id = :id
            ");

            $updateLibro->execute([
                ':disponible' => $nuevoDisponible,
                ':estado'     => $estadoLibro,
                ':id'         => $id_libro
            ]);


            $conn->commit();

            header("Location: prestamos.php?mensaje=registrado");
            exit;

        } catch (Throwable $e) {

            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $error = $e->getMessage();
        }
    }
}


// ======================================================
// MARCAR PRÉSTAMO COMO DEVUELTO
// ======================================================

if (isset($_GET['devolver'])) {

    $id_prestamo = (int) $_GET['devolver'];

    try {

        $conn->beginTransaction();


        $stmt = $conn->prepare("
            SELECT
                libro_id,
                cantidad,
                estado
            FROM prestamos
            WHERE id = :id
            FOR UPDATE
        ");

        $stmt->execute([
            ':id' => $id_prestamo
        ]);

        $prestamo = $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$prestamo) {
            throw new Exception("El préstamo no existe.");
        }


        if ($prestamo['estado'] === 'prestado') {

            $cantidadDevuelta = (int) $prestamo['cantidad'];


            // Actualizar préstamo
            $updPrestamo = $conn->prepare("
                UPDATE prestamos
                SET
                    estado = 'devuelto',
                    fecha_devolucion_real = CURDATE()
                WHERE id = :id
            ");

            $updPrestamo->execute([
                ':id' => $id_prestamo
            ]);


            // Recuperar información actual del libro
            $stmtLibro = $conn->prepare("
                SELECT
                    cantidad_total,
                    disponible
                FROM libros
                WHERE id = :id
                FOR UPDATE
            ");

            $stmtLibro->execute([
                ':id' => $prestamo['libro_id']
            ]);

            $libro = $stmtLibro->fetch(PDO::FETCH_ASSOC);


            if (!$libro) {
                throw new Exception("El libro asociado al préstamo no existe.");
            }


            $nuevoDisponible =
                (int) $libro['disponible']
                +
                $cantidadDevuelta;


            // Seguridad: nunca superar inventario total
            if ($nuevoDisponible > (int) $libro['cantidad_total']) {
                $nuevoDisponible = (int) $libro['cantidad_total'];
            }


            $updLibro = $conn->prepare("
                UPDATE libros
                SET
                    disponible = :disponible,
                    estado = 'Disponible'
                WHERE id = :id
            ");

            $updLibro->execute([
                ':disponible' => $nuevoDisponible,
                ':id'         => $prestamo['libro_id']
            ]);
        }


        $conn->commit();

        header("Location: prestamos.php?mensaje=devuelto");
        exit;

    } catch (Throwable $e) {

        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        $error = $e->getMessage();
    }
}


// ======================================================
// ELIMINAR PRÉSTAMO
// ======================================================

if (isset($_GET['eliminar'])) {

    $id_prestamo = (int) $_GET['eliminar'];

    try {

        $conn->beginTransaction();


        $stmt = $conn->prepare("
            SELECT
                libro_id,
                cantidad,
                estado
            FROM prestamos
            WHERE id = :id
            FOR UPDATE
        ");

        $stmt->execute([
            ':id' => $id_prestamo
        ]);

        $prestamo = $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$prestamo) {
            throw new Exception("El préstamo no existe.");
        }


        // Si aún estaba prestado, devolver inventario antes de eliminar
        if ($prestamo['estado'] === 'prestado') {

            $stmtLibro = $conn->prepare("
                SELECT
                    cantidad_total,
                    disponible
                FROM libros
                WHERE id = :id
                FOR UPDATE
            ");

            $stmtLibro->execute([
                ':id' => $prestamo['libro_id']
            ]);

            $libro = $stmtLibro->fetch(PDO::FETCH_ASSOC);


            if ($libro) {

                $nuevoDisponible =
                    (int) $libro['disponible']
                    +
                    (int) $prestamo['cantidad'];


                if ($nuevoDisponible > (int) $libro['cantidad_total']) {
                    $nuevoDisponible = (int) $libro['cantidad_total'];
                }


                $updLibro = $conn->prepare("
                    UPDATE libros
                    SET
                        disponible = :disponible,
                        estado = 'Disponible'
                    WHERE id = :id
                ");

                $updLibro->execute([
                    ':disponible' => $nuevoDisponible,
                    ':id'         => $prestamo['libro_id']
                ]);
            }
        }


        $delete = $conn->prepare("
            DELETE FROM prestamos
            WHERE id = :id
        ");

        $delete->execute([
            ':id' => $id_prestamo
        ]);


        $conn->commit();

        header("Location: prestamos.php?mensaje=eliminado");
        exit;

    } catch (Throwable $e) {

        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        $error = $e->getMessage();
    }
}


// ======================================================
// CARGAR USUARIOS
// ======================================================

$usuarios = $conn->query("
    SELECT
        id,
        nombre,
        rol
    FROM usuarios
    WHERE estado = 'activo'
    ORDER BY nombre ASC
");


// ======================================================
// CARGAR LIBROS DISPONIBLES
// ======================================================

$libros = $conn->query("
    SELECT
        id,
        titulo,
        disponible
    FROM libros
    WHERE disponible > 0
    ORDER BY titulo ASC
");


// ======================================================
// LISTADO DE PRÉSTAMOS
// ======================================================

$sql = "
    SELECT
        p.id,
        p.cantidad,
        p.fecha_prestamo,
        p.fecha_devolucion,
        p.fecha_devolucion_real,
        p.estado,

        u.nombre AS usuario,
        l.titulo AS libro

    FROM prestamos p

    INNER JOIN usuarios u
        ON p.usuario_id = u.id

    INNER JOIN libros l
        ON p.libro_id = l.id

    ORDER BY p.id DESC
";

$stmtPrestamos = $conn->query($sql);

$prestamos = $stmtPrestamos->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Gestión de Préstamos | BiblioSys</title>

    <link
        rel="stylesheet"
        href="estilos.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

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

            <a href="libros.php">
                Gestión de Libros
            </a>

            <a
                href="prestamos.php"
                class="active">
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

            <?php
            echo ucfirst(
                htmlspecialchars($_SESSION['rol'])
            );
            ?>:

            <strong>
                <?php
                echo htmlspecialchars(
                    $_SESSION['usuario']
                );
                ?>
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
        <div><h1>Préstamos y devoluciones</h1><p>Gestiona la circulación de ejemplares de forma clara y ágil.</p></div>
        <span class="workspace-tag"><i class="fa-solid fa-arrows-rotate"></i> Circulación</span>
    </section>


    <?php if (isset($_GET['mensaje'])): ?>

        <?php if ($_GET['mensaje'] === 'registrado'): ?>

            <div class="alert alert-success">
                ✅ Préstamo registrado correctamente.
            </div>

        <?php elseif ($_GET['mensaje'] === 'devuelto'): ?>

            <div class="alert alert-success">
                ✅ Préstamo devuelto correctamente.
            </div>

        <?php elseif ($_GET['mensaje'] === 'eliminado'): ?>

            <div class="alert alert-success">
                ✅ Préstamo eliminado correctamente.
            </div>

        <?php endif; ?>

    <?php endif; ?>


    <?php if (!empty($error)): ?>

        <div class="alert alert-danger">

            ❌ <?php echo htmlspecialchars($error); ?>

        </div>

    <?php endif; ?>



    <div class="usuarios-layout">


        <!-- ======================================================
             REGISTRAR PRÉSTAMO
        ====================================================== -->

        <div class="usuarios-sidebar">


            <div class="card">


                <h3>
                    Registrar Préstamo
                </h3>


                <form
                    method="POST"
                    action="prestamos.php">


                    <input
                        type="hidden"
                        name="registrar_prestamo"
                        value="1"
                    >


                    <!-- USUARIO -->

                    <label>
                        Usuario
                    </label>


                    <select
                        name="id_usuario"
                        required>


                        <option value="">
                            Selecciona un usuario
                        </option>


                        <?php while ($usuario = $usuarios->fetch(PDO::FETCH_ASSOC)): ?>


                            <option
                                value="<?php echo $usuario['id']; ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $usuario['nombre']
                                );
                                ?>

                                -
                                <?php
                                echo ucfirst(
                                    htmlspecialchars(
                                        $usuario['rol']
                                    )
                                );
                                ?>

                            </option>


                        <?php endwhile; ?>


                    </select>



                    <!-- LIBRO -->

                    <label>
                        Libro (solo disponibles)
                    </label>


                    <select
                        name="id_libro"
                        required>


                        <option value="">
                            Selecciona un libro
                        </option>


                        <?php while ($libro = $libros->fetch(PDO::FETCH_ASSOC)): ?>


                            <option
                                value="<?php echo $libro['id']; ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $libro['titulo']
                                );
                                ?>

                                (<?php echo (int)$libro['disponible']; ?> disponibles)

                            </option>


                        <?php endwhile; ?>


                    </select>



                    <!-- CANTIDAD -->

                    <label>
                        Cantidad de ejemplares
                    </label>


                    <input
                        type="number"
                        name="cantidad"
                        min="1"
                        value="1"
                        required
                    >



                    <!-- FECHA PRÉSTAMO -->

                    <label>
                        Fecha de préstamo
                    </label>


                    <input
                        type="date"
                        name="fecha_prestamo"
                        value="<?php echo date('Y-m-d'); ?>"
                        required
                    >



                    <!-- FECHA DEVOLUCIÓN -->

                    <label>
                        Fecha de devolución
                    </label>


                    <input
                        type="date"
                        name="fecha_devolucion"
                        value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>"
                        required
                    >



                    <button
                        type="submit"
                        class="btn btn-success">

                        <i class="fa-solid fa-arrow-right-arrow-left"></i>

                        Registrar Préstamo

                    </button>


                </form>


            </div>


        </div>



        <!-- ======================================================
             LISTADO
        ====================================================== -->

        <div class="usuarios-main card">


            <h3>
                Listado de Préstamos
            </h3>


            <table>


                <thead>

                    <tr>

                        <th>USUARIO</th>

                        <th>LIBRO</th>

                        <th>CANT. LIBROS PRESTADOS</th>

                        <th>FECHA PRÉSTAMO</th>

                        <th>FECHA DEVOLUCIÓN</th>

                        <th>ESTADO</th>

                        <th>ACCIONES</th>

                    </tr>

                </thead>


                <tbody>


                <?php if (count($prestamos) > 0): ?>


                    <?php foreach ($prestamos as $prestamo): ?>


                        <tr>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $prestamo['usuario']
                                );
                                ?>

                            </td>


                            <td class="text-primary">

                                <strong>

                                <?php
                                echo htmlspecialchars(
                                    $prestamo['libro']
                                );
                                ?>

                                </strong>

                            </td>



                            <td>

                                <?php
                                echo (int) $prestamo['cantidad'];
                                ?>

                                ejemplar(es)

                            </td>



                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $prestamo['fecha_prestamo']
                                );
                                ?>

                            </td>



                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $prestamo['fecha_devolucion']
                                );
                                ?>

                            </td>



                            <td>


                                <?php if ($prestamo['estado'] === 'prestado'): ?>


                                    <span class="badge badge-warning">

                                        ⌛ En préstamo

                                    </span>


                                <?php else: ?>


                                    <span class="badge badge-success">

                                        ✓ Devuelto

                                    </span>


                                <?php endif; ?>


                            </td>



                            <td>


                                <?php if ($prestamo['estado'] === 'prestado'): ?>


                                    <a
                                        href="prestamos.php?devolver=<?php echo $prestamo['id']; ?>"
                                        class="action-icon text-primary"
                                        title="Marcar como devuelto"
                                        onclick="return confirm('¿Registrar la devolución de este préstamo?');"
                                    >

                                        <i class="fa-solid fa-rotate-left"></i>

                                    </a>


                                <?php endif; ?>


                                <a
                                    href="prestamos.php?eliminar=<?php echo $prestamo['id']; ?>"
                                    class="action-icon text-danger"
                                    title="Eliminar préstamo"
                                    onclick="return confirm('¿Seguro que deseas eliminar este préstamo?');"
                                >

                                    <i class="fa-solid fa-trash"></i>

                                </a>


                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php else: ?>


                    <tr>

                        <td colspan="7">

                            ❌ No hay préstamos registrados

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>


            </table>


        </div>


    </div>


</main>


</body>

</html>
