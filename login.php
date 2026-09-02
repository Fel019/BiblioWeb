<?php
include 'conexion.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $correo = trim($_POST['correo']);
    $password = trim($_POST['password']);

    // 🚨 Descomenta esta línea si tus contraseñas están cifradas en MD5
    // $password = md5($password);

    $sql = "SELECT * FROM usuarios WHERE correo = :correo AND estado = 'activo'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':correo' => $correo]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && ($usuario['password'] === $password || $usuario['password'] === md5($password))) {
        // ✅ Guardar sesión
        $_SESSION['id'] = $usuario['id'];
        $_SESSION['usuario'] = $usuario['nombre'];
        $_SESSION['rol'] = $usuario['rol'];

        // 🚀 Redirigir según el rol
        if ($usuario['rol'] === 'administrador' || $usuario['rol'] === 'bibliotecario') {
            header("Location: usuarios.php");
        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        $error = "❌ Credenciales incorrectas o usuario inactivo.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Iniciar Sesión | BiblioWeb</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="estilos.css">
  <style>
    body {
      background: #f4f6fb;
      font-family: "Poppins", sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
    }

    .login-card {
      background: #fff;
      border-radius: 15px;
      padding: 40px 50px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
      width: 400px;
      text-align: center;
    }

    h2 {
      color: #333;
      margin-bottom: 25px;
    }

    input {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 1em;
    }

    input:focus {
      border-color: #007bff;
      outline: none;
      box-shadow: 0 0 3px rgba(0,123,255,0.3);
    }

    .btn {
      width: 100%;
      background: #007bff;
      border: none;
      color: white;
      padding: 12px;
      font-weight: 600;
      border-radius: 8px;
      cursor: pointer;
      transition: 0.3s ease;
      margin-top: 10px;
    }

    .btn:hover {
      background: #0056b3;
    }

    .error {
      background: #ffe3e3;
      color: #c00;
      padding: 8px;
      border-radius: 6px;
      margin-bottom: 10px;
      font-size: 0.9em;
    }
  </style>
</head>
<body class="login-body">
  <div class="login-card">
    <img class="institution-mark" src="assets/escudo-institucional.png" alt="Escudo del Colegio Parroquial Nuestra Señora de los Andes">
    <h2 class="login-title">Biblioteca Colegio Parroquial<br>Nuestra Señora de los Andes</h2>
    <form method="POST">
      <?php if (!empty($error)): ?>
        <div class="error"><?php echo $error; ?></div>
      <?php endif; ?>
      <input type="email" name="correo" placeholder="Correo electrónico" required>
      <input type="password" name="password" placeholder="Contraseña" required>
      <button type="submit" class="btn">Iniciar Sesión</button>
    </form>
  </div>
</body>
</html>
