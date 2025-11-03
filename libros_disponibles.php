<?php
include 'conexion.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// Traer los libros con su disponibilidad
$sql = "SELECT id, titulo, autor, categoria, disponible FROM libros ORDER BY titulo ASC";
$stmt = $conn->query($sql);
$libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>📚 Libros Disponibles | BiblioWeb</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
<body>
  <div class="container">
    <h2><i class="fa-solid fa-book"></i> Libros Disponibles</h2>

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

    <a href="index.php" class="btn"><i class="fa-solid fa-arrow-left"></i> Volver al Inicio</a>
  </div>
</body>
</html>
