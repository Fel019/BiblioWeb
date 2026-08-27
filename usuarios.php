<?php
include 'conexion.php';
session_start();

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// Solo administrador y bibliotecario
if ($_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== 'bibliotecario') {
    header("Location: sin_permiso.php");
    exit;
}


// ======================================================
// REGISTRAR NUEVO USUARIO
// ======================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_usuario'])) {

    $nombre   = trim($_POST['nombre']);
    $correo   = trim($_POST['correo']);
    $password = md5($_POST['password']);
    $rol      = $_POST['rol'] ?? 'alumno';
    $estado   = 'activo';

    $rolesPermitidos = [
        'alumno',
        'profesor',
        'administrativo',
        'bibliotecario',
        'administrador'
    ];

    if (!in_array($rol, $rolesPermitidos, true)) {
        $rol = 'alumno';
    }

    // Verificar correo duplicado
    $check = $conn->prepare("
        SELECT COUNT(*)
        FROM usuarios
        WHERE correo = :correo
    ");

    $check->execute([
        ':correo' => $correo
    ]);

    if ($check->fetchColumn() > 0) {

        $error = "El correo ya está registrado.";

    } else {

        try {

            $sql = "
                INSERT INTO usuarios
                (
                    nombre,
                    correo,
                    password,
                    rol,
                    estado
                )
                VALUES
                (
                    :nombre,
                    :correo,
                    :password,
                    :rol,
                    :estado
                )
            ";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ':nombre'   => $nombre,
                ':correo'   => $correo,
                ':password' => $password,
                ':rol'      => $rol,
                ':estado'   => $estado
            ]);

            header("Location: usuarios.php?mensaje=registrado");
            exit;

        } catch (PDOException $e) {

            $error = "No fue posible registrar el usuario.";
        }
    }
}


// ======================================================
// ELIMINAR USUARIO
// ======================================================

if (isset($_GET['eliminar'])) {

    $id = (int) $_GET['eliminar'];

    // Evitar eliminarse a sí mismo
    if (isset($_SESSION['id']) && $id === (int) $_SESSION['id']) {

        $error = "No puedes eliminar tu propio usuario mientras tienes la sesión iniciada.";

    } else {

        try {

            $del = $conn->prepare("
                DELETE FROM usuarios
                WHERE id = :id
            ");

            $del->execute([
                ':id' => $id
            ]);

            header("Location: usuarios.php?mensaje=eliminado");
            exit;

        } catch (PDOException $e) {

            $error = "No se puede eliminar este usuario porque tiene préstamos asociados.";
        }
    }
}


// ======================================================
// BUSCAR USUARIOS
// ======================================================

$where = "";
$params = [];

if (!empty($_GET['q'])) {

    $q = "%" . trim($_GET['q']) . "%";

    $where = "
        WHERE nombre LIKE :q
           OR correo LIKE :q
    ";

    $params[':q'] = $q;
}


// ======================================================
// LISTADO DE USUARIOS
// ======================================================

$sql = "
    SELECT
        id,
        nombre,
        correo,
        rol,
        estado
    FROM usuarios
    $where
    ORDER BY id ASC
";

$stmtUsuarios = $conn->prepare($sql);
$stmtUsuarios->execute($params);

$usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Gestión de Usuarios | BiblioWeb</title>

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

        <i class="fa-solid fa-book-open navbar-logo"></i>

        <span class="navbar-title">
            Colegio Parroquial Nuestra Señora de los Andes
        </span>

    </div>


    <div class="navbar-center">

        <nav class="nav-tabs">

            <a href="libros.php">
                Gestión de Libros
            </a>

            <a href="prestamos.php">
                Préstamos
            </a>

            <a
                href="usuarios.php"
                class="active">
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
            class="btn btn-small btn-primary">

            <i class="fa-solid fa-right-from-bracket"></i>

            Salir

        </a>

    </div>

</header>



<!-- ======================================================
     CONTENIDO
====================================================== -->

<main class="main-content usuarios-layout">


    <!-- ======================================================
         COLUMNA IZQUIERDA
    ====================================================== -->

    <div class="usuarios-sidebar">


        <!-- BUSCAR USUARIO -->

        <div class="card">

            <h3>
                🔍 Buscar Usuario
            </h3>


            <form
                method="GET"
                action="usuarios.php">


                <input
                    type="text"
                    name="q"
                    placeholder="Nombre o correo..."
                    value="<?php
                        echo isset($_GET['q'])
                            ? htmlspecialchars($_GET['q'])
                            : '';
                    ?>"
                >


                <button
                    type="submit"
                    class="btn btn-buscar">

                    🔍 Buscar

                </button>

            </form>

        </div>



        <!-- REGISTRAR USUARIO -->

        <div class="card">

            <h3>
                👤 Registrar Nuevo Usuario
            </h3>


            <?php if (!empty($error)): ?>

                <div class="alert alert-danger">

                    ❌ <?php echo htmlspecialchars($error); ?>

                </div>

            <?php endif; ?>


            <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'registrado'): ?>

                <div class="alert alert-success">

                    ✅ Usuario registrado correctamente.

                </div>

            <?php endif; ?>


            <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'eliminado'): ?>

                <div class="alert alert-success">

                    ✅ Usuario eliminado correctamente.

                </div>

            <?php endif; ?>


            <form
                method="POST"
                action="usuarios.php">


                <input
                    type="hidden"
                    name="registrar_usuario"
                    value="1"
                >


                <input
                    type="text"
                    name="nombre"
                    placeholder="Nombre completo"
                    required
                >


                <input
                    type="email"
                    name="correo"
                    placeholder="Correo electrónico"
                    required
                >


                <select
                    name="rol"
                    required>

                    <option
                        value=""
                        disabled
                        selected>

                        Selecciona un rol

                    </option>


                    <option value="alumno">
                        Alumno
                    </option>


                    <option value="profesor">
                        Profesor
                    </option>


                    <option value="administrativo">
                        Administrativo
                    </option>


                    <option value="bibliotecario">
                        Bibliotecario
                    </option>


                    <option value="administrador">
                        Administrador
                    </option>

                </select>


                <input
                    type="password"
                    name="password"
                    placeholder="Contraseña"
                    required
                >


                <button
                    type="submit"
                    class="btn btn-success">

                    <i class="fa-solid fa-user-plus"></i>

                    Registrar

                </button>

            </form>

        </div>

    </div>



    <!-- ======================================================
         LISTADO DE USUARIOS
    ====================================================== -->

    <div class="usuarios-main card">

        <h3>
            Usuarios Registrados
        </h3>


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


            <?php if (count($usuarios) > 0): ?>


                <?php foreach ($usuarios as $usuario): ?>


                    <tr>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $usuario['nombre']
                            );
                            ?>

                        </td>


                        <td class="text-primary">

                            <strong>

                            <?php
                            echo htmlspecialchars(
                                $usuario['correo']
                            );
                            ?>

                            </strong>

                        </td>


                        <td>

                            <?php
                            echo ucfirst(
                                htmlspecialchars(
                                    $usuario['rol']
                                )
                            );
                            ?>

                        </td>


                        <td>


                            <?php if ($usuario['estado'] === 'activo'): ?>


                                <span class="badge badge-success">

                                    Activo

                                </span>


                            <?php else: ?>


                                <span class="badge badge-danger">

                                    Inactivo

                                </span>


                            <?php endif; ?>


                        </td>


                        <td>


                            <a
                                href="editar_usuario.php?id=<?php echo $usuario['id']; ?>"
                                class="action-icon text-success"
                                title="Editar">

                                <i class="fa-solid fa-pen-to-square"></i>

                            </a>


                            <a
                                href="usuarios.php?eliminar=<?php echo $usuario['id']; ?>"
                                class="action-icon text-danger"
                                title="Eliminar"
                                onclick="return confirm('¿Seguro que quieres eliminar este usuario?');">

                                <i class="fa-solid fa-trash"></i>

                            </a>


                        </td>


                    </tr>


                <?php endforeach; ?>


            <?php else: ?>


                <tr>

                    <td colspan="5">

                        ❌ No se encontraron usuarios

                    </td>

                </tr>


            <?php endif; ?>


            </tbody>

        </table>

    </div>

</main>


</body>

</html>