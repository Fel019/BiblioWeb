<?php
include 'conexion.php';
session_start();

/* Cambia esto si tu login se llama distinto */
$LOGIN_PAGE = 'login.php';

$mensaje = "";
$tipo = "";  // success | error
$temporal = ""; // para mostrar la nueva clave

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $correo = trim($_POST['correo']);

    try {
        // 1) Buscar usuario por correo
        $stmt = $conn->prepare("SELECT id_usuario, username FROM usuarios WHERE correo = :correo LIMIT 1");
        $stmt->execute([':correo' => $correo]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // 2) Generar contraseña temporal (10 carac.)
            $chars = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#%&";
            $temporal = '';
            for ($i=0; $i<10; $i++) {
                $temporal .= $chars[random_int(0, strlen($chars)-1)];
            }

            // 3) Guardar con bcrypt (asegúrate de tener VARCHAR(255) en la columna password)
            // ALTER TABLE usuarios MODIFY password VARCHAR(255) NOT NULL;
            $nuevoHash = password_hash($temporal, PASSWORD_BCRYPT);
            $upd = $conn->prepare("UPDATE usuarios SET password = :nuevo WHERE id_usuario = :id");
            $upd->execute([':nuevo' => $nuevoHash, ':id' => $user['id_usuario']]);

            // 4) Mensaje + auto-redirect
            $mensaje = "Listo <b>{$user['username']}</b>. Tu nueva contraseña temporal es:<br><code style='font-weight:bold;font-size:1.1em;'>{$temporal}</code><br>Úsala para iniciar sesión y cámbiala luego.";
            $tipo = "success";

            // Meta refresh para redirigir en 7s (da tiempo de copiar)
            echo "<meta http-equiv='refresh' content='7;url={$LOGIN_PAGE}'>";
        } else {
            $mensaje = "No existe un usuario registrado con ese correo.";
            $tipo = "error";
        }
    } catch (Throwable $e) {
        $mensaje = "Error al procesar la solicitud: " . htmlspecialchars($e->getMessage());
        $tipo = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recuperar Contraseña | BiblioSys</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container d-flex justify-content-center align-items-center" style="min-height:100vh;">
    <div class="card p-4 shadow-lg" style="max-width: 430px; width:100%;">
      <h3 class="text-center mb-2">Recuperar contraseña</h3>
      <p class="text-muted text-center small mb-4">
        Ingresa el correo con el que estás registrado y te daremos una clave temporal.
      </p>

      <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?php echo $tipo==='success' ? 'success' : 'danger'; ?>" role="alert">
          <?php echo $mensaje; ?>
          <?php if ($tipo==='success'): ?>
            <hr>
            <div class="d-flex gap-2">
              <button class="btn btn-outline-secondary btn-sm" onclick="copyTemp()">Copiar clave</button>
              <a class="btn btn-primary btn-sm" href="<?php echo $LOGIN_PAGE; ?>">Volver al login ahora</a>
              <span class="ms-auto small text-muted">Redirigiendo…</span>
            </div>
            <script>
              function copyTemp(){
                navigator.clipboard.writeText("<?php echo $temporal; ?>").then(()=>alert("Contraseña copiada"));
              }
            </script>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="recuperar.php" <?php echo $tipo==='success' ? 'style="display:none;"' : ''; ?>>
        <div class="mb-3">
          <label for="correo" class="form-label">Correo registrado</label>
          <input type="email" class="form-control" id="correo" name="correo" placeholder="ejemplo@correo.com" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Generar nueva contraseña</button>
      </form>

      <p class="text-center mt-3 mb-0">
        <a href="<?php echo $LOGIN_PAGE; ?>">Volver al inicio de sesión</a>
      </p>
    </div>
  </div>
</body>
</html>
