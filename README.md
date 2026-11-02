# LabSeguridad — Portal Universitario (laboratorio de seguridad)

Aplicación web universitaria desarrollada en **PHP 8 puro + SQLite** para el
curso **Seguridad en Aplicaciones (MASI 2026)**. El proyecto tiene dos etapas:

- **ETAPA 1 — versión vulnerable** (la de este estado del repositorio): sistema
  completamente funcional con **vulnerabilidades intencionales** para poder
  demostrarlas.
- **ETAPA 2 — versión segura**: corrige cada vulnerabilidad y añade cifrado,
  hashing fuerte, gestión de secretos, HTTPS y registro seguro.

> ⚠️ **Entorno de laboratorio.** La versión vulnerable se ejecuta **solo en
> `127.0.0.1`**, con **datos ficticios**. No la exponga a la red ni a Internet.

---

## Requisitos

| Herramienta | Uso | Obligatoria |
|-------------|-----|-------------|
| PHP 8.x (build **NTS** x64) con `pdo_sqlite` y `sodium` | ejecutar la app | Sí |
| Git | control de versiones y demo de Gitleaks | Sí |
| Gitleaks | detección de secretos | Sí (ETAPA 2) |
| Caddy | HTTPS local | Sí (ETAPA 2) |
| OWASP ZAP (+ Java) | escaneo web complementario | Opcional |

La guía de instalación paso a paso para **Windows 11** está en
[`docs/INSTALACION_WINDOWS.md`](docs/INSTALACION_WINDOWS.md).

No se usa Node.js, Composer, Docker, Apache ni MySQL. No se usan CDN: todo el
CSS/JS es local.

---

## Puesta en marcha rápida (Windows PowerShell)

Desde la carpeta raíz del proyecto (`portal-universitario`):

```powershell
# 1. Inicializar la base de datos y datos ficticios
php scripts\init_db.php
php scripts\seed.php

# 2. Arrancar el servidor (solo localhost en la versión vulnerable)
php -S 127.0.0.1:8000 -t public

# 3. Abrir en el navegador
start http://127.0.0.1:8000/
```

Para dejar el laboratorio en estado limpio en cualquier momento:

```powershell
php scripts\reset_lab.php
```

En Linux/Mac los comandos son equivalentes usando `/` en vez de `\`.

---

## Usuarios de demostración (ficticios)

| Usuario    | Contraseña      | Rol        |
|------------|-----------------|------------|
| `admin`    | `Admin123`      | Administrador |
| `docente1` | `Docente123`    | Docente    |
| `docente2` | `Docente123`    | Docente    |
| `estu1`    | `Estudiante123` | Estudiante (carné 2021-0001) |
| `estu2`    | `Estudiante123` | Estudiante (carné 2021-0002) |

> Estas credenciales son **ficticias y exclusivas del laboratorio**.

---

## Estructura del proyecto

```
portal-universitario/
├── public/            # raíz web (index.php = front controller) + assets locales
├── src/
│   ├── config/        # config.php (contiene el secreto V06) y Database.php
│   ├── controllers/   # un controlador por módulo
│   ├── models/        # acceso a datos (incluye la búsqueda vulnerable V02)
│   ├── services/      # reportes (V04) y archivos
│   ├── helpers/       # funciones comunes + hashing inseguro (V05)
│   └── views/         # vistas PHP (XSS V03 en comments y students)
├── database/
│   ├── schema.sql     # esquema relacional
│   └── university.db  # base SQLite (generada; ignorada por Git)
├── storage/
│   ├── uploads/       # documentos subidos (fuera de public/)
│   ├── reports/       # CSV y evidencias generadas
│   └── logs/          # app.log
├── scripts/           # init_db.php, seed.php, reset_lab.php
├── tests/             # pruebas CLI (sin PHPUnit)
├── config/            # Caddyfile (ETAPA 2)
├── docs/              # documentación académica
├── .gitignore
└── README.md
```

> La base SQLite, los documentos y los registros viven **fuera de `public/`**,
> de modo que el servidor web no los sirve directamente.

---

## Módulos funcionales

- **Autenticación y usuarios:** registro, login/logout, perfil, cambio de
  contraseña, CRUD de usuarios, roles (admin/docente/estudiante), activar/desactivar.
- **Estudiantes:** CRUD, búsqueda (carné/nombre/apellido/carrera), paginación.
- **Expedientes:** carreras, cursos, matrículas, calificaciones (0–100),
  períodos, estado de aprobación, historial.
- **Comentarios:** publicar/listar/eliminar sobre estudiantes o cursos.
- **Documentos:** subir (PDF/JPG/PNG), listar, descargar (vía controlador),
  eliminar, con registro de quién hizo cada operación.
- **Reportes:** CSV con PHP nativo (estudiantes, calificaciones, por carrera,
  actividad) + empaquetado (punto de la demo de inyección de comandos).
- **Panel principal:** estadísticas reales consultadas desde SQLite.

---

## Documentación

- [`docs/INSTALACION_WINDOWS.md`](docs/INSTALACION_WINDOWS.md) — instalación y
  ejecución en Windows paso a paso.
- [`docs/VULNERABILIDADES.md`](docs/VULNERABILIDADES.md) — cada vulnerabilidad
  (causa raíz, riesgo, prueba, corrección prevista).
- [`docs/PRUEBAS.md`](docs/PRUEBAS.md) — matriz de pruebas reproducibles.
- [`docs/ARQUITECTURA.md`](docs/ARQUITECTURA.md) — arquitectura y modelo de datos.
- [`docs/MATRIZ_SEGURIDAD.md`](docs/MATRIZ_SEGURIDAD.md) — matriz de seguridad.
- [`docs/INFORME_TECNICO.md`](docs/INFORME_TECNICO.md) — informe técnico y
  preguntas de defensa.

---

## Pruebas automatizadas (ETAPA 1)

```powershell
php tests\test_vulnerabilidades.php
```

Reproduce V02, V04, V05, V06 y V07 directamente contra los modelos/BD y confirma
que cada vulnerabilidad es explotable (cada `[OK]` = vulnerabilidad reproducible).

---

## Estado

**ETAPA 1 y ETAPA 2 completadas y verificadas** en el entorno de desarrollo.

Las dos versiones se conservan en Git para poder compararlas:

| Versión | Rama | Etiqueta | Estado del código |
|---------|------|----------|-------------------|
| Vulnerable (ETAPA 1) | `etapa-1-vulnerable` | `v1-vulnerable` | pie de página: Versión 1.0 |
| Segura (ETAPA 2)     | `main`               | `v2-segura`    | pie de página: Versión 2.0 |

```powershell
# Ver la versión vulnerable:
git checkout v1-vulnerable
# Volver a la versión segura:
git checkout main
```

### Correcciones de la ETAPA 2 (resumen)

- **V01** validación de entradas en el servidor (clase `Validador`).
- **V02** consultas preparadas con PDO (SQLi neutralizado).
- **V03** codificación de salida con `htmlspecialchars()` (XSS neutralizado).
- **V04** resumen de reportes sin intérprete de comandos + etiqueta por lista blanca (inyección de comandos neutralizada).
- **V05** `password_hash()` Argon2id/bcrypt con sal + migración transparente de hashes SHA-256.
- **V06** secretos fuera del código, leídos con `lab_secreto()` desde variables de entorno (ver `docs/SECRETOS.md`).
- **V07** registro de eventos sin contraseñas ni secretos.
- **V08** control de propiedad en expedientes, documentos y notas (403 ante acceso ajeno).
- **Cifrado** del dato sensible `telefono_emergencia` con Sodium (ver `docs/CIFRADO.md`).
- **Controles adicionales:** CSRF, regeneración de sesión, cookies `HttpOnly`/`SameSite`,
  cabeceras de seguridad HTTP, límite de intentos de login, manejo central de errores.
- **HTTPS/TLS** con Caddy (ver `docs/HTTPS_TLS.md`).

### Pruebas

```powershell
# En la versión vulnerable (rama/etiqueta v1-vulnerable):
php tests\test_vulnerabilidades.php   # 11/11 OK = vulnerabilidades reproducibles

# En la versión segura (main / v2-segura):
php tests\test_seguridad.php          # 46/46 OK = correcciones verificadas
```

> Pendiente de ejecutar en Windows por el estudiante (requieren herramientas de
> escritorio): la detección con **Gitleaks** (`scripts/gitleaks_demo.ps1`), el
> escaneo con **OWASP ZAP** y el **HTTPS con Caddy**. Los procedimientos están
> documentados en `docs/`.
