<?php
/**
 * functions.php — utilidades compartidas de la aplicación.
 *
 * Sesión, autenticación, autorización, flash messages, escape de salida,
 * registro de auditoría y ayudas de vista.

 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Sesión
// ---------------------------------------------------------------------------
function iniciar_sesion_segura(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_start();
}

function usuario_actual(): ?array
{
    return $_SESSION['user'] ?? null;
}

function esta_autenticado(): bool
{
    return isset($_SESSION['user']);
}

function rol_actual(): ?string
{
    return $_SESSION['user']['rol'] ?? null;
}

function id_usuario_actual(): ?int
{
    return isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
}

/** Fija el usuario autenticado en la sesión. */
function establecer_usuario(array $user): void
{
    $_SESSION['user'] = [
        'id'         => (int)$user['id'],
        'username'   => $user['username'],
        'nombre'     => $user['nombre_completo'],
        'rol'        => $user['rol'],
        'student_id' => isset($user['student_id']) ? (int)$user['student_id'] : null,
    ];
}

function cerrar_sesion(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// ---------------------------------------------------------------------------
// Autorización
// ---------------------------------------------------------------------------
/** Exige que haya un usuario autenticado; si no, redirige al login. */
function requerir_login(): void
{
    if (!esta_autenticado()) {
        redirigir('?r=login');
    }
}

/** Exige que el rol actual esté dentro de la lista permitida. */
function requerir_rol(array $rolesPermitidos): void
{
    requerir_login();
    if (!in_array(rol_actual(), $rolesPermitidos, true)) {
        http_response_code(403);
        render_vista('error', [
            'titulo'  => 'Acceso denegado',
            'mensaje' => 'No tiene permisos para acceder a esta sección.',
        ]);
        exit;
    }
}

// ---------------------------------------------------------------------------
// Flash messages
// ---------------------------------------------------------------------------
function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function obtener_flash(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

// ---------------------------------------------------------------------------
// Salida
// ---------------------------------------------------------------------------
/**
 * e() — codificación HTML para contextos de texto y de atributo.
 */
function e(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirigir(string $url): void
{
    header('Location: ' . $url);
    exit;
}

// ---------------------------------------------------------------------------
// Vistas
// ---------------------------------------------------------------------------
/** Renderiza una vista dentro del layout principal. */
function render_vista(string $vista, array $datos = []): void
{
    extract($datos, EXTR_SKIP);
    $__vista = SRC_PATH . '/views/' . $vista . '.php';
    if (!is_file($__vista)) {
        http_response_code(500);
        echo "Vista no encontrada: " . htmlspecialchars($vista);
        return;
    }
    ob_start();
    include $__vista;
    $contenido = ob_get_clean();
    include SRC_PATH . '/views/layouts/main.php';
}

/** Renderiza una vista "desnuda" (sin layout), p. ej. la pantalla de login. */
function render_sola(string $vista, array $datos = []): void
{
    extract($datos, EXTR_SKIP);
    $__vista = SRC_PATH . '/views/' . $vista . '.php';
    include $__vista;
}

// ---------------------------------------------------------------------------
// Auditoría
// ---------------------------------------------------------------------------
/**
 * registrar_evento() — escribe en audit_logs y en storage/logs/app.log.

 */
function registrar_evento(string $tipo, string $descripcion, string $resultado = 'OK', ?int $userId = null): void
{
    $userId = $userId ?? id_usuario_actual();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';

    try {
        $stmt = Database::conn()->prepare(
            "INSERT INTO audit_logs (tipo, user_id, descripcion, ip, resultado)
             VALUES (?,?,?,?,?)"
        );
        $stmt->execute([$tipo, $userId, $descripcion, $ip, $resultado]);
    } catch (Throwable $e) {
        // no interrumpir la operación principal por un fallo de log
    }

    // Log en archivo de texto.
    $linea = sprintf("[%s] %s user=%s ip=%s %s :: %s%s",
        date('Y-m-d H:i:s'), $tipo, $userId ?? '-', $ip, $resultado, $descripcion, PHP_EOL);
    @file_put_contents(LOGS_PATH . '/app.log', $linea, FILE_APPEND);
}

// ---------------------------------------------------------------------------
// Ayudas varias
// ---------------------------------------------------------------------------
function post(string $clave, $default = null)
{
    return $_POST[$clave] ?? $default;
}

function get(string $clave, $default = null)
{
    return $_GET[$clave] ?? $default;
}

/** URL a una ruta del front-controller. */
function url(string $ruta): string
{
    return '?r=' . $ruta;
}

/** Marca activa en el menú lateral. */
function activo(string $ruta, string $actual): string
{
    return str_starts_with($actual, $ruta) ? 'activo' : '';
}
