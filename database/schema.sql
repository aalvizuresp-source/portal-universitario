-- ============================================================================
-- LabSeguridad — Portal Universitario
-- Esquema relacional SQLite 3
-- ----------------------------------------------------------------------------
-- Este esquema es IDÉNTICO para la versión vulnerable y la versión segura.
-- Lo único que cambia entre etapas es CÓMO el código PHP usa estas tablas
-- (p. ej. el formato del hash de contraseña guardado en users.password_hash).
--
-- Notas de diseño:
--   * Todas las claves foráneas usan ON DELETE según la semántica de cada
--     relación (CASCADE para datos dependientes, RESTRICT/SET NULL si no).
--   * Se activan las FK en tiempo de conexión con  PRAGMA foreign_keys = ON
--     (SQLite las desactiva por defecto). Eso se hace en Database.php.
--   * Las fechas se guardan como TEXT en formato ISO-8601 (YYYY-MM-DD HH:MM:SS),
--     que es la convención recomendada por SQLite.
-- ============================================================================

PRAGMA foreign_keys = ON;

-- ----------------------------------------------------------------------------
-- 1. roles
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS documents;
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS grades;
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS teacher_courses;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS careers;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

CREATE TABLE roles (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre      TEXT    NOT NULL UNIQUE,          -- admin | docente | estudiante
    descripcion TEXT    NOT NULL DEFAULT ''
);

-- ----------------------------------------------------------------------------
-- 2. users
-- ----------------------------------------------------------------------------
-- password_hash:
--    * ETAPA 1 (vulnerable): guarda un SHA-256 hexadecimal simple, SIN sal.
--    * ETAPA 2 (segura): guarda el resultado de password_hash() (Argon2id/bcrypt).
--    El esquema no cambia; el formato del valor sí.
CREATE TABLE users (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    username       TEXT    NOT NULL UNIQUE,
    nombre_completo TEXT   NOT NULL,
    email          TEXT    NOT NULL UNIQUE,
    password_hash  TEXT    NOT NULL,
    role_id        INTEGER NOT NULL,
    activo         INTEGER NOT NULL DEFAULT 1,     -- 1 = activo, 0 = inactivo
    -- student_id enlaza una cuenta de rol "estudiante" con su ficha en students.
    -- NULL para admin y docentes.
    student_id     INTEGER,
    failed_attempts INTEGER NOT NULL DEFAULT 0,    -- usado por el rate-limit (ETAPA 2)
    locked_until   TEXT,                           -- usado por el rate-limit (ETAPA 2)
    creado_en      TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
    FOREIGN KEY (role_id)    REFERENCES roles(id)    ON DELETE RESTRICT,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL
);

-- ----------------------------------------------------------------------------
-- 3. careers (carreras)
-- ----------------------------------------------------------------------------
CREATE TABLE careers (
    id       INTEGER PRIMARY KEY AUTOINCREMENT,
    codigo   TEXT NOT NULL UNIQUE,                 -- p. ej. ISIS, IIND
    nombre   TEXT NOT NULL,
    facultad TEXT NOT NULL DEFAULT ''
);

-- ----------------------------------------------------------------------------
-- 4. students (estudiantes)
-- ----------------------------------------------------------------------------
CREATE TABLE students (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    carne           TEXT    NOT NULL UNIQUE,        -- código universitario único
    nombres         TEXT    NOT NULL,
    apellidos       TEXT    NOT NULL,
    email           TEXT    NOT NULL,
    fecha_nacimiento TEXT,                          -- YYYY-MM-DD
    career_id       INTEGER,
    estado          TEXT    NOT NULL DEFAULT 'activo',  -- activo | inactivo | egresado
    -- telefono_emergencia: dato sensible ficticio que en la ETAPA 2 se cifra
    -- con Sodium (crypto_secretbox). En la ETAPA 1 se guarda en claro.
    telefono_emergencia TEXT,
    fecha_registro  TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
    FOREIGN KEY (career_id) REFERENCES careers(id) ON DELETE SET NULL
);

-- ----------------------------------------------------------------------------
-- 5. courses (cursos)
-- ----------------------------------------------------------------------------
CREATE TABLE courses (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    codigo     TEXT    NOT NULL UNIQUE,            -- p. ej. MAT101
    nombre     TEXT    NOT NULL,
    creditos   INTEGER NOT NULL DEFAULT 3,
    career_id  INTEGER,
    FOREIGN KEY (career_id) REFERENCES careers(id) ON DELETE SET NULL
);

-- ----------------------------------------------------------------------------
-- 6. teacher_courses  (cursos asignados a cada docente)
-- ----------------------------------------------------------------------------
-- Relación N:M entre users(rol docente) y courses, por período.
CREATE TABLE teacher_courses (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    teacher_id INTEGER NOT NULL,                   -- users.id de un docente
    course_id  INTEGER NOT NULL,
    periodo    TEXT    NOT NULL,                    -- p. ej. 2026-S1
    UNIQUE (teacher_id, course_id, periodo),
    FOREIGN KEY (teacher_id) REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (course_id)  REFERENCES courses(id) ON DELETE CASCADE
);

-- ----------------------------------------------------------------------------
-- 7. enrollments (asignaciones de estudiantes a cursos)
-- ----------------------------------------------------------------------------
CREATE TABLE enrollments (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL,
    course_id  INTEGER NOT NULL,
    periodo    TEXT    NOT NULL,                    -- período académico
    UNIQUE (student_id, course_id, periodo),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id)  REFERENCES courses(id)  ON DELETE CASCADE
);

-- ----------------------------------------------------------------------------
-- 8. grades (calificaciones)
-- ----------------------------------------------------------------------------
CREATE TABLE grades (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    enrollment_id INTEGER NOT NULL,
    nota          REAL    NOT NULL,                 -- 0..100 (validado en la app)
    aprobado      INTEGER NOT NULL DEFAULT 0,       -- 1 si nota >= 61
    periodo       TEXT    NOT NULL,
    registrado_por INTEGER,                         -- users.id del docente/admin
    registrado_en TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
    FOREIGN KEY (enrollment_id)  REFERENCES enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (registrado_por) REFERENCES users(id)       ON DELETE SET NULL
);

-- ----------------------------------------------------------------------------
-- 9. comments (comentarios)
-- ----------------------------------------------------------------------------
-- Un comentario se asocia a un estudiante O a un curso (al menos uno).
-- contenido: en la ETAPA 1 se renderiza SIN escapar (XSS almacenado).
CREATE TABLE comments (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    autor_id   INTEGER NOT NULL,                    -- users.id
    student_id INTEGER,                             -- objetivo (opcional)
    course_id  INTEGER,                             -- objetivo (opcional)
    contenido  TEXT    NOT NULL,
    creado_en  TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
    FOREIGN KEY (autor_id)   REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id)  REFERENCES courses(id)  ON DELETE CASCADE
);

-- ----------------------------------------------------------------------------
-- 10. documents (documentos subidos)
-- ----------------------------------------------------------------------------
-- stored_name: nombre real en disco.
--    * ETAPA 1: conserva el nombre original (inseguro).
--    * ETAPA 2: nombre aleatorio.
CREATE TABLE documents (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id    INTEGER NOT NULL,
    original_name TEXT    NOT NULL,
    stored_name   TEXT    NOT NULL,
    mime_type     TEXT    NOT NULL DEFAULT '',
    size_bytes    INTEGER NOT NULL DEFAULT 0,
    subido_por    INTEGER,                          -- users.id
    subido_en     TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subido_por) REFERENCES users(id)    ON DELETE SET NULL
);

-- ----------------------------------------------------------------------------
-- 11. audit_logs (registro de eventos)
-- ----------------------------------------------------------------------------
-- En la ETAPA 1 este registro guarda, a propósito, datos que NO debería
-- (p. ej. la contraseña en texto plano en intentos de login). En la ETAPA 2
-- se corrige para no registrar secretos.
CREATE TABLE audit_logs (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    ts         TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
    tipo       TEXT    NOT NULL,                    -- LOGIN_OK, LOGIN_FAIL, ...
    user_id    INTEGER,                             -- puede ser NULL
    descripcion TEXT   NOT NULL DEFAULT '',
    ip         TEXT    NOT NULL DEFAULT '',
    resultado  TEXT    NOT NULL DEFAULT ''          -- OK | ERROR | DENEGADO
);

-- ----------------------------------------------------------------------------
-- Índices
-- ----------------------------------------------------------------------------
CREATE INDEX idx_users_role        ON users(role_id);
CREATE INDEX idx_users_student     ON users(student_id);
CREATE INDEX idx_students_career   ON students(career_id);
CREATE INDEX idx_students_carne    ON students(carne);
CREATE INDEX idx_courses_career    ON courses(career_id);
CREATE INDEX idx_tc_teacher        ON teacher_courses(teacher_id);
CREATE INDEX idx_tc_course         ON teacher_courses(course_id);
CREATE INDEX idx_enroll_student    ON enrollments(student_id);
CREATE INDEX idx_enroll_course     ON enrollments(course_id);
CREATE INDEX idx_grades_enroll     ON grades(enrollment_id);
CREATE INDEX idx_comments_student  ON comments(student_id);
CREATE INDEX idx_comments_course   ON comments(course_id);
CREATE INDEX idx_documents_student ON documents(student_id);
CREATE INDEX idx_audit_tipo        ON audit_logs(tipo);
CREATE INDEX idx_audit_user        ON audit_logs(user_id);
