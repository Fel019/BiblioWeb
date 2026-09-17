<?php
/**
 * Hostinger y otros hostings compartidos no siempre permiten definir
 * variables de entorno por sitio. Si existe, este archivo local (ignorado por
 * Git) carga la configuración del ambiente sin incorporarla al repositorio.
 */
$archivoConfiguracionLocal = __DIR__ . DIRECTORY_SEPARATOR . '.biblio-env.php';

if (is_file($archivoConfiguracionLocal)) {
    $configuracionLocal = require $archivoConfiguracionLocal;

    if (!is_array($configuracionLocal)) {
        throw new RuntimeException('La configuración local del ambiente no es válida.');
    }

    foreach ($configuracionLocal as $nombre => $valor) {
        if (is_string($nombre) && is_scalar($valor)) {
            putenv($nombre . '=' . $valor);
        }
    }
}

/**
 * La configuración sensible se inyecta desde el servidor con variables de
 * entorno. El bloque development mantiene la compatibilidad con XAMPP local.
 * No se deben usar esos valores por defecto fuera del equipo de desarrollo.
 */
function obtenerVariableEntorno(string $nombre, ?string $predeterminado = null): ?string
{
    $valor = getenv($nombre);

    return $valor === false || $valor === '' ? $predeterminado : $valor;
}

$appEnv = strtolower(obtenerVariableEntorno('APP_ENV', 'development'));
$appEnv = $appEnv === 'test' ? 'testing' : $appEnv;

if (!in_array($appEnv, ['development', 'testing', 'production'], true)) {
    throw new RuntimeException('El ambiente de ejecución configurado no es válido.');
}

$esDesarrollo = $appEnv === 'development';
$host = obtenerVariableEntorno('DB_HOST', $esDesarrollo ? 'localhost' : null);
$dbname = obtenerVariableEntorno('DB_NAME', $esDesarrollo ? 'biblioweb' : null);
$username = obtenerVariableEntorno('DB_USER', $esDesarrollo ? 'root' : null);
$password = obtenerVariableEntorno('DB_PASSWORD', $esDesarrollo ? '' : null);

if ($host === null || $dbname === null || $username === null || $password === null) {
    error_log('BiblioWeb: faltan variables de conexión para el ambiente ' . $appEnv . '.');
    http_response_code(500);
    exit('La aplicación no está configurada correctamente.');
}

if (!preg_match('/^[A-Za-z0-9_]+$/', $dbname)) {
    throw new RuntimeException('El nombre de la base de datos no es válido.');
}

try {
    $conn = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log('BiblioWeb: error de conexión a la base de datos (' . $appEnv . '): ' . $e->getMessage());
    http_response_code(500);
    exit('No fue posible conectar con la base de datos.');
}
