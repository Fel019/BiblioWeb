<?php
require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

function param(array $names, $default = null) {
    foreach ($names as $n) {
        if (isset($_POST[$n]) && $_POST[$n] !== '') return $_POST[$n];
        if (isset($_GET[$n])  && $_GET[$n]  !== '') return $_GET[$n];
    }
    return $default;
}

try {
    // --- Parámetros ---
    $tipo  = strtolower(param(['tipo','reporte'], 'prestamos'));
    $fi    = param(['fecha_inicio','inicio','desde']);
    $ff    = param(['fecha_fin','fin','hasta']);

    $fi = $fi ? date('Y-m-d', strtotime($fi)) : '1900-01-01';
    $ff = $ff ? date('Y-m-d', strtotime($ff)) : '2100-12-31';

    // --- Conexión BD ---
    $pdo = new PDO('mysql:host=localhost;dbname=biblioteca;charset=utf8','root','');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- Consulta según tipo ---
    switch ($tipo) {
        case 'prestamos':
            $sql = "SELECT p.id_prestamo   AS ID,
                           u.nombre        AS Usuario,
                           l.titulo        AS Libro,
                           p.fecha_prestamo AS 'Fecha préstamo',
                           p.fecha_devolucion AS 'Fecha devolución',
                           p.estado        AS Estado
                    FROM prestamos p
                    INNER JOIN usuarios u ON u.id_usuario = p.id_usuario
                    INNER JOIN libros   l ON l.id_libro   = p.id_libro
                    WHERE p.fecha_prestamo BETWEEN :fi AND :ff
                    ORDER BY p.fecha_prestamo DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':fi'=>$fi, ':ff'=>$ff]);
            break;

        case 'usuarios':
            $sql = "SELECT id_usuario AS ID,
                           nombre     AS Nombre,
                           username   AS Usuario,
                           correo     AS Correo,
                           rol        AS Rol,
                           estado     AS Estado
                    FROM usuarios
                    ORDER BY id_usuario ASC";
            $stmt = $pdo->query($sql);
            break;

        case 'libros':
            $sql = "SELECT id_libro AS ID,
                           titulo   AS Título,
                           autor    AS Autor,
                           anio_publicacion AS Año,
                           categoria AS Categoría,
                           estado   AS Estado
                    FROM libros
                    ORDER BY id_libro ASC";
            $stmt = $pdo->query($sql);
            break;

        default:
            throw new Exception("Tipo de reporte no válido.");
    }

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$data) {
        throw new Exception("No se encontraron resultados para el criterio seleccionado.");
    }

    // --- Crear Excel ---
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Reporte');

    // Encabezados
    $col = 'A';
    foreach (array_keys($data[0]) as $header) {
        $sheet->setCellValue($col.'1', $header);
        $col++;
    }

    // Datos
    $rowNum = 2;
    foreach ($data as $row) {
        $col = 'A';
        foreach ($row as $value) {
            $sheet->setCellValue($col.$rowNum, $value);
            $col++;
        }
        $rowNum++;
    }

    // --- Estilos ---
    $lastCol = $sheet->getHighestColumn();
    $lastRow = $sheet->getHighestRow();

    // Encabezados con color y centrados
    $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical'   => Alignment::VERTICAL_CENTER,
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'color' => ['rgb' => '4F81BD'] // azul pro
        ]
    ]);

    // Bordes para toda la tabla
    $sheet->getStyle("A1:{$lastCol}{$lastRow}")->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ]);

    // Autosize columnas
    foreach (range('A', $lastCol) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // --- Enviar al navegador ---
    $filename = 'reporte_'.$tipo.'_'.date('Ymd_His').'.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    echo "Error al generar informe: ".$e->getMessage();
}
