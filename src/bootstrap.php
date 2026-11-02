<?php
/**
 * bootstrap.php — punto de arranque común.
 *
 * Carga configuración, base de datos, helpers y todos los modelos/controladores.
 * Carga mediante require explícito (sin dependencias externas).
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

// La base de datos debe existir.
if (!is_file(DB_PATH)) {
    http_response_code(500);
    echo "<h1>Base de datos no inicializada</h1>";
    echo "<p>Ejecute desde la raíz del proyecto:</p>";
    echo "<pre>php scripts/init_db.php\nphp scripts/seed.php</pre>";
    exit;
}

iniciar_sesion_segura();
