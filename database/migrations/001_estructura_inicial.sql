-- BiblioWeb - estructura inicial compartida por pruebas y producción.
-- Este archivo crea tablas; no crea la base de datos ni usuarios de MySQL.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `correo` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('administrador','bibliotecario','alumno','profesor','administrativo') NOT NULL DEFAULT 'alumno',
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuarios_correo` (`correo`),
  UNIQUE KEY `uk_usuarios_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `libros` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `titulo` varchar(200) NOT NULL,
  `autor` varchar(150) NOT NULL,
  `isbn` varchar(30) DEFAULT NULL,
  `categoria` varchar(100) NOT NULL,
  `anio_publicacion` year(4) DEFAULT NULL,
  `cantidad_total` int(10) unsigned NOT NULL DEFAULT 1,
  `disponible` int(10) unsigned NOT NULL DEFAULT 1,
  `estado` enum('Disponible','No_disponible') NOT NULL DEFAULT 'Disponible',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_libros_isbn` (`isbn`),
  KEY `idx_libros_titulo` (`titulo`),
  KEY `idx_libros_autor` (`autor`),
  KEY `idx_libros_categoria` (`categoria`),
  KEY `idx_libros_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `prestamos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `libro_id` int(10) unsigned NOT NULL,
  `cantidad` int(10) unsigned NOT NULL DEFAULT 1,
  `fecha_prestamo` date NOT NULL,
  `fecha_devolucion` date NOT NULL,
  `fecha_devolucion_real` date DEFAULT NULL,
  `estado` enum('prestado','devuelto') NOT NULL DEFAULT 'prestado',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_prestamos_usuario` (`usuario_id`),
  KEY `idx_prestamos_libro` (`libro_id`),
  KEY `idx_prestamos_estado` (`estado`),
  KEY `idx_prestamos_fecha` (`fecha_prestamo`),
  CONSTRAINT `fk_prestamos_libro` FOREIGN KEY (`libro_id`) REFERENCES `libros` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_prestamos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
