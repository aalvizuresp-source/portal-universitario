<?php
/**
 * bootstrap.php — punto de arranque común.
 *
 * Carga configuración, base de datos, helpers, modelos, servicios y
 * controladores mediante require explícito (sin dependencias externas).
 */

declare(strict_types=1);

require __DIR__ . '/config/config.php';
require __DIR__ . '/config/Database.php';

require __DIR__ . '/helpers/functions.php';
require __DIR__ . '/helpers/security.php';

// Modelos
require __DIR__ . '/models/UserModel.php';
require __DIR__ . '/models/StudentModel.php';
require __DIR__ . '/models/AcademicModel.php';
require __DIR__ . '/models/CommentModel.php';
require __DIR__ . '/models/DocumentModel.php';
require __DIR__ . '/models/DashboardModel.php';

// Servicios
require __DIR__ . '/services/ReportService.php';
require __DIR__ . '/services/FileService.php';

// Controladores
require __DIR__ . '/controllers/AuthController.php';
require __DIR__ . '/controllers/DashboardController.php';
require __DIR__ . '/controllers/UserController.php';
require __DIR__ . '/controllers/StudentController.php';
require __DIR__ . '/controllers/AcademicController.php';
require __DIR__ . '/controllers/CommentController.php';
require __DIR__ . '/controllers/DocumentController.php';
require __DIR__ . '/controllers/ReportController.php';

// Sin base de datos el servicio no puede atender: mensaje genérico al usuario,
// detalle en el registro del servidor.
if (!is_file(DB_PATH)) {
    error_log('Base de datos no encontrada en ' . DB_PATH);
    http_response_code(503);
    echo "<!doctype html><meta charset='utf-8'><title>Servicio no disponible</title>";
    echo "<p style='font-family:sans-serif;padding:24px'>El servicio no está disponible en este momento.</p>";
    exit;
}

iniciar_sesion_segura();
enviar_cabeceras_seguridad();
