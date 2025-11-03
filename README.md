# 📚 BiblioWeb — Sistema de Gestión de Biblioteca

> Proyecto académico desarrollado por **Andrés Astudillo**  
> Desarrollado con fines educativos para la administración digital de bibliotecas, control de préstamos, gestión de usuarios y generación de reportes.

---

## 🧠 Descripción del Proyecto

**BiblioSys** es un sistema web que permite gestionar de forma eficiente los libros, usuarios y préstamos de una biblioteca.  
Cuenta con control de roles (Administrador, Bibliotecario y Usuario) y funcionalidades avanzadas para el seguimiento del inventario, historial de préstamos y generación de reportes.

---

## 🚀 Tecnologías Utilizadas

| Tecnología | Descripción |
|-------------|-------------|
| 🐘 **PHP 8+** | Lógica del backend y conexión con base de datos |
| 💾 **MySQL / MariaDB** | Sistema de gestión de base de datos |
| 🎨 **HTML5 / CSS3** | Estructura y estilos del sitio |
| ⚡ **JavaScript (vanilla)** | Interactividad en formularios y tablas |
| 🧩 **FontAwesome** | Iconos y elementos visuales |
| 🧱 **XAMPP / Apache** | Entorno de servidor local |
| 🔐 **PDO (PHP Data Objects)** | Conexión segura a la base de datos |

---

## 🧩 Módulos Principales

### 👤 **Gestión de Usuarios**
- Registro, edición y eliminación de usuarios.  
- Roles definidos:  
  - **Administrador:** Control total del sistema.  
  - **Bibliotecario:** Gestión de libros y préstamos.  
  - **Usuario:** Visualización de sus préstamos activos.

---

### 📚 **Gestión de Libros**
- Registro de nuevos libros con campos:
  - Título, Autor, ISBN, Año de publicación, Categoría y Estado.  
- Edición y eliminación de registros.  
- Búsqueda por título, autor o ISBN.  
- Control automático del estado del libro (disponible / no disponible).

---

### 🔄 **Préstamos**
- Registro de préstamos con fechas de inicio y devolución.  
- Verificación automática de disponibilidad.  
- Actualización automática del estado del libro al prestarlo o devolverlo.  
- Listado histórico de préstamos con su respectivo estado.

---

### 📈 **Reportes**
- Generación de reportes por rango de fechas:
  - Libros registrados.
  - Usuarios registrados.
  - Historial de préstamos.  
- Exportación a formatos descargables.

---

BiblioWeb/
│
├── conexion.php # Conexión con la base de datos
├── verificar_rol.php # Control de roles y permisos
├── login.php / logout.php # Autenticación de usuarios
├── libros.php # Módulo de gestión de libros
├── prestamos.php # Módulo de gestión de préstamos
├── usuarios.php # Módulo de gestión de usuarios
├── reportes.php # Generación de reportes
│
├── estilos.css # Estilos generales
└── assets/ # Iconos, imágenes o recursos estáticos

