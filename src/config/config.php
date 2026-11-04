<?php
/**
 * config.php — configuración central de LabSeguridad.
 *
 * Los secretos no se escriben aquí: se leen de variables de entorno mediante
 * lab_secreto(). Los errores no se muestran al usuario; se registran en
 * storage/logs/.
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Rutas base del proyecto
// ---------------------------------------------------------------------------
define('APP_ROOT',     dirname(__DIR__, 2));
define('SRC_PATH',     APP_ROOT . DIRECTORY_SEPARATOR . 'src');
define('PUBLIC_PATH',  APP_ROOT . DIRECTORY_SEPARATOR . 'public');
define('STORAGE_PATH', APP_ROOT . DIRECTORY_SEPARATOR . 'storage');
define('DB_PATH',      APP_ROOT . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'university.db');
define('UPLOADS_PATH', STORAGE_PATH . DIRECTORY_SEPARATOR . 'uploads');
define('REPORTS_PATH', STORAGE_PATH . DIRECTORY_SEPARATOR . 'reports');
define('LOGS_PATH',    STORAGE_PATH . DIRECTORY_SEPARATOR . 'logs');
define('KEYS_PATH',    STORAGE_PATH . DIRECTORY_SEPARATOR . 'keys');

// ---------------------------------------------------------------------------
// Identidad de la aplicación
// ---------------------------------------------------------------------------
define('APP_NAME',    'LabSeguridad');
define('APP_VERSION', '2.0');

// ---------------------------------------------------------------------------
// Cargar el gestor de secretos (provee lab_secreto()).
// ---------------------------------------------------------------------------
require_once __DIR__ . '/secrets.php';

// ---------------------------------------------------------------------------
// Secretos: se leen de variables de entorno con lab_secreto().
// El valor por defecto sólo sirve para desarrollo local; en cualquier otro
// entorno APP_SECRET_KEY debe definirse como variable de entorno.
// ---------------------------------------------------------------------------
// En producción (APP_ENV=production) la aplicación NO arranca si el secreto no
// está definido: falla de forma segura en lugar de usar un valor conocido.
$__app_secret = lab_secreto('APP_SECRET_KEY');
if ($__app_secret === null || $__app_secret === '') {
    if ((getenv('APP_ENV') ?: 'development') === 'production') {
        http_response_code(500);
        error_log('APP_SECRET_KEY no está definido en el entorno de producción.');
        exit('Configuración incompleta: falta APP_SECRET_KEY.');
    }
    // Sólo desarrollo local: valor por defecto marcado, nunca para producción.
    $__app_secret = 'dev-only-no-usar-en-produccion';
}
define('APP_SECRET_KEY', $__app_secret);
unset($__app_secret);

// ---------------------------------------------------------------------------
// Parámetros de la aplicación
// ---------------------------------------------------------------------------
define('NOTA_APROBACION', 61);
define('ITEMS_POR_PAGINA', 8);
define('MAX_UPLOAD_BYTES', 5 * 1024 * 1024); // 5 MB
define('MAX_INTENTOS_LOGIN', 5);             // intentos antes de bloqueo temporal
define('BLOQUEO_LOGIN_SEGUNDOS', 300);       // 5 minutos de bloqueo

// ---------------------------------------------------------------------------
// Zona horaria
// ---------------------------------------------------------------------------
date_default_timezone_set('America/Guatemala');

// ---------------------------------------------------------------------------
// Errores: NO se muestran al usuario (se registran). El manejador central del
// bootstrap muestra un mensaje genérico.
// ---------------------------------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', LOGS_PATH . DIRECTORY_SEPARATOR . 'php_error.log');
