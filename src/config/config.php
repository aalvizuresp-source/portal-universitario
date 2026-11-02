<?php
/**
 * config.php — configuración central de LabSeguridad.
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

// ---------------------------------------------------------------------------
// Identidad de la aplicación
// ---------------------------------------------------------------------------
define('APP_NAME',    'LabSeguridad');
define('APP_VERSION', '1.0');

// ---------------------------------------------------------------------------
// Credenciales de la aplicación
// ---------------------------------------------------------------------------
define('APP_SECRET_KEY',   'sk_lab_7f3d9a1c8e5b4206bdfa91c3e7d20f45');
define('DB_PASSWORD',      'Portal#Univ2026!db');
define('API_TOKEN',        'ghp_FAKE0lab000TOKEN000demo000no000real00');

// ---------------------------------------------------------------------------
// Parámetros de la aplicación
// ---------------------------------------------------------------------------
define('NOTA_APROBACION', 61);
define('ITEMS_POR_PAGINA', 8);
define('MAX_UPLOAD_BYTES', 5 * 1024 * 1024);

date_default_timezone_set('America/Guatemala');

error_reporting(E_ALL);
ini_set('display_errors', '1');
