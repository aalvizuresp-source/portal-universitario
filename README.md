# Portal Universitario — LabSeguridad

Aplicación web universitaria desarrollada en **PHP 8 + SQLite** para la
administración de estudiantes, expedientes académicos, calificaciones,
comentarios, documentos y reportes, con autenticación y control de acceso por
roles (administrador, docente y estudiante).

El proyecto se conserva en **dos versiones** para comparar el mismo sistema
antes y después de aplicar controles de seguridad:

| Versión | Rama | Etiqueta | Descripción |
|---------|------|----------|-------------|
| 1.0 | `etapa-1-vulnerable` | `v1-vulnerable` | Versión con vulnerabilidades (SQL Injection, XSS, inyección de comandos, contraseñas sin sal, secretos en el código, registro inseguro, control de acceso incompleto). |
| 2.0 | `main` | `v2-segura` | Versión corregida: consultas preparadas, codificación de salida, validación en servidor, `password_hash`, cifrado con Sodium, gestión de secretos por variables de entorno, registro seguro, autorización por rol, CSRF, cabeceras de seguridad y HTTPS. |

```bash
git checkout v1-vulnerable   # versión vulnerable
git checkout main            # versión segura
```

---

## Requisitos

- **PHP 8.x** (build NTS en Windows) con las extensiones `pdo_sqlite`, `sodium`,
  `mbstring`, `fileinfo` y `openssl`.
- **Git**.
- Opcionales para las pruebas: **Caddy** (HTTPS), **Gitleaks** (detección de
  secretos) y **OWASP ZAP** (escaneo web).

Comprobar extensiones:
```bash
php -m
```

---

## Puesta en marcha

Desde la carpeta del proyecto:

```powershell
# 1. Inicializar la base de datos con datos de ejemplo
php scripts\init_db.php
php scripts\seed.php

# 2. Arrancar el servidor
php -S 127.0.0.1:8000 -t public

# 3. Abrir en el navegador
start http://127.0.0.1:8000/
```

Para dejar la base de datos en un estado limpio en cualquier momento:

```powershell
php scripts\reset_lab.php
```

En Linux/Mac los comandos son equivalentes usando `/` en vez de `\`.

### Usuarios de ejemplo

| Usuario    | Contraseña      | Rol        |
|------------|-----------------|------------|
| `admin`    | `Admin123`      | Administrador |
| `docente1` | `Docente123`    | Docente    |
| `docente2` | `Docente123`    | Docente    |
| `estu1`    | `Estudiante123` | Estudiante (carné 2021-0001) |
| `estu2`    | `Estudiante123` | Estudiante (carné 2021-0002) |

> Todos los datos y credenciales son ficticios.

---

## Estructura

```
portal-universitario/
├── public/              # raíz web (index.php + assets)
│   ├── index.php        # front controller
│   └── assets/          # CSS y JS
├── src/
│   ├── config/          # configuración, conexión PDO y gestión de secretos
│   ├── controllers/     # un controlador por módulo
│   ├── models/          # acceso a datos
│   ├── services/        # reportes y archivos
│   ├── helpers/         # funciones comunes y de seguridad
│   └── views/           # plantillas
├── database/
│   ├── schema.sql       # esquema relacional
│   └── consultas_ejemplo.sql
├── storage/             # base SQLite, uploads, reportes, logs, claves (fuera de public/)
├── scripts/             # init_db, seed, reset_lab
├── tests/               # pruebas en PHP CLI
├── config/              # Caddyfile (HTTPS) y regla de Gitleaks
├── .env.example         # plantilla de variables de entorno (versión 2.0)
└── .gitignore
```

La base de datos SQLite, los documentos subidos, los registros y las claves
viven **fuera de `public/`**, de modo que el servidor web no los sirve
directamente.

---

## Pruebas

```powershell
# Versión 1.0 (rama etapa-1-vulnerable)
php tests\test_vulnerabilidades.php

# Versión 2.0 (rama main)
php tests\test_seguridad.php
```

---

## HTTPS (versión 2.0)

Con el servidor PHP corriendo en `127.0.0.1:8000`, Caddy actúa como proxy
inverso para ofrecer HTTPS local:

```powershell
cd config
caddy run --config .\Caddyfile
```

El backend PHP escucha únicamente en `127.0.0.1`; Caddy aporta el cifrado en
tránsito con un certificado de su autoridad certificadora local.
