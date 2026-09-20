# Bases por ambiente

Los ambientes de pruebas y producción usan exactamente la misma migración, pero
bases de datos y usuarios MySQL distintos. Nunca se trasladan datos de pruebas a
producción automáticamente.

## Nombres locales sugeridos

| Ambiente | Base de datos | Usuario de aplicación |
| --- | --- | --- |
| Pruebas | `biblioweb_pruebas` | `biblioweb_pruebas_app` |
| Producción simulada | `biblioweb_produccion` | `biblioweb_produccion_app` |

## Crear estructura

Ejecutar la migración en cada base de datos:

```powershell
C:\xampp\mysql\bin\mysql.exe -u USUARIO -p biblioweb_pruebas < database\migrations\001_estructura_inicial.sql
C:\xampp\mysql\bin\mysql.exe -u USUARIO -p biblioweb_produccion < database\migrations\001_estructura_inicial.sql
```

Las cuentas de demostración y los libros de prueba se cargan solo en
`biblioweb_pruebas`. Para la simulación de producción se utiliza un conjunto de
datos ficticios autorizado, nunca datos personales reales.
