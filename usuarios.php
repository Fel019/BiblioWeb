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

$condiciones = [];
$params = [];
$rolesDisponibles = ['alumno', 'profesor', 'administrativo', 'bibliotecario', 'administrador'];
$rolFiltro = trim($_GET['rol'] ?? '');
$stmtConteoRoles = $conn->query("SELECT rol, COUNT(*) AS total FROM usuarios GROUP BY rol");
$conteoRoles = $stmtConteoRoles->fetchAll(PDO::FETCH_KEY_PAIR);

if (!empty($_GET['q'])) {

    $q = "%" . trim($_GET['q']) . "%";

    $condiciones[] = "(nombre LIKE :q OR correo LIKE :q)";

    $params[':q'] = $q;
}

if (in_array($rolFiltro, $rolesDisponibles, true)) {
    $condiciones[] = "rol = :rol";
    $params[':rol'] = $rolFiltro;
} else {
    $rolFiltro = '';
}

$where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';
$porPagina = 10;
$paginaActual = max(1, (int) ($_GET['pagina'] ?? 1));
$stmtTotal = $conn->prepare("SELECT COUNT(*) FROM usuarios $where");
$stmtTotal->execute($params);
$totalUsuarios = (int) $stmtTotal->fetchColumn();
$totalPaginas = max(1, (int) ceil($totalUsuarios / $porPagina));
$paginaActual = min($paginaActual, $totalPaginas);
$offset = ($paginaActual - 1) * $porPagina;


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
    LIMIT :limite OFFSET :offset
";

$stmtUsuarios = $conn->prepare($sql);
foreach ($params as $clave => $valor) {
    $stmtUsuarios->bindValue($clave, $valor, PDO::PARAM_STR);
}
$stmtUsuarios->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmtUsuarios->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmtUsuarios->execute();

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

<main class="main-content usuarios-layout admin-users">
    <section class="workspace-heading">
        <div><h1>Comunidad lectora</h1><p>Registra y acompaña a quienes hacen parte de la biblioteca.</p></div>
        <span class="workspace-tag"><i class="fa-solid fa-users"></i> Usuarios activos</span>
    </section>


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

                <select name="rol">
                    <option value="">Todos los roles</option>
                    <?php foreach ($rolesDisponibles as $rol): ?>
                        <option value="<?php echo $rol; ?>" <?php echo $rolFiltro === $rol ? 'selected' : ''; ?>>
                            <?php echo ucfirst($rol); ?> (<?php echo (int) ($conteoRoles[$rol] ?? 0); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>


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
            Usuarios Registrados <span class="list-counter"><?php echo $totalUsuarios; ?> resultado<?php echo $totalUsuarios === 1 ? '' : 's'; ?></span>
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

        <?php if ($totalPaginas > 1): ?>
            <nav class="admin-pagination" aria-label="Paginación de usuarios">
                <?php if ($paginaActual > 1): ?><a href="usuarios.php?<?php echo http_build_query(['q' => $_GET['q'] ?? '', 'rol' => $rolFiltro, 'pagina' => $paginaActual - 1]); ?>">Anterior</a><?php endif; ?>
                <span>Página <?php echo $paginaActual; ?> de <?php echo $totalPaginas; ?> · 10 por página</span>
                <?php if ($paginaActual < $totalPaginas): ?><a href="usuarios.php?<?php echo http_build_query(['q' => $_GET['q'] ?? '', 'rol' => $rolFiltro, 'pagina' => $paginaActual + 1]); ?>">Siguiente</a><?php endif; ?>
            </nav>
        <?php endif; ?>

    </div>

</main>


</body>

</html>
