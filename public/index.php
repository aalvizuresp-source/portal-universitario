<?php
/**
 * index.php — Front controller del portal LabSeguridad.
 *
 * Todas las peticiones pasan por aquí. La "ruta" se indica con ?r=<nombre>.
 * Ejemplos:
 *   ?r=login            ?r=dashboard       ?r=students
 *   ?r=students/edit&id=3                  ?r=comments
 *
 * Este diseño mantiene el proyecto simple (un solo punto de entrada) y permite
 * que Caddy haga de proxy inverso sin reglas de reescritura complicadas.
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

$r = $_GET['r'] ?? 'dashboard';

/*
 * Tabla de rutas -> [Controlador, método].
 * Las rutas sin login requerido se filtran dentro de cada controlador con
 * requerir_login()/requerir_rol(), salvo las de autenticación.
 */
$rutas = [
    // Autenticación (públicas)
    'login'            => [AuthController::class, 'mostrarLogin'],
    'login/post'       => [AuthController::class, 'procesarLogin'],
    'logout'           => [AuthController::class, 'logout'],
    'register'         => [AuthController::class, 'mostrarRegistro'],
    'register/post'    => [AuthController::class, 'procesarRegistro'],

    // Panel
    'dashboard'        => [DashboardController::class, 'index'],

    // Perfil / contraseña
    'profile'          => [AuthController::class, 'perfil'],
    'profile/password' => [AuthController::class, 'cambiarPassword'],

    // Usuarios (admin)
    'users'            => [UserController::class, 'index'],
    'users/new'        => [UserController::class, 'nuevo'],
    'users/create'     => [UserController::class, 'crear'],
    'users/edit'       => [UserController::class, 'editar'],
    'users/update'     => [UserController::class, 'actualizar'],
    'users/toggle'     => [UserController::class, 'toggle'],

    // Estudiantes
    'students'         => [StudentController::class, 'index'],
    'students/search'  => [StudentController::class, 'buscar'],
    'students/new'     => [StudentController::class, 'nuevo'],
    'students/create'  => [StudentController::class, 'crear'],
    'students/view'    => [StudentController::class, 'ver'],
    'students/edit'    => [StudentController::class, 'editar'],
    'students/update'  => [StudentController::class, 'actualizar'],
    'students/delete'  => [StudentController::class, 'eliminar'],

    // Expedientes / notas
    'records'          => [AcademicController::class, 'index'],
    'records/view'     => [AcademicController::class, 'ver'],
    'courses'          => [AcademicController::class, 'cursos'],
    'courses/grades'   => [AcademicController::class, 'notasCurso'],
    'grades/save'      => [AcademicController::class, 'guardarNota'],

    // Comentarios
    'comments'         => [CommentController::class, 'index'],
    'comments/create'  => [CommentController::class, 'crear'],
    'comments/delete'  => [CommentController::class, 'eliminar'],

    // Documentos
    'documents'        => [DocumentController::class, 'index'],
    'documents/upload' => [DocumentController::class, 'subir'],
    'documents/download' => [DocumentController::class, 'descargar'],
    'documents/delete' => [DocumentController::class, 'eliminar'],

    // Reportes
    'reports'          => [ReportController::class, 'index'],
    'reports/students' => [ReportController::class, 'estudiantes'],
    'reports/grades'   => [ReportController::class, 'calificaciones'],
    'reports/by-career'=> [ReportController::class, 'porCarrera'],
    'reports/activity' => [ReportController::class, 'actividad'],
    'reports/package'  => [ReportController::class, 'empaquetar'],
    'reports/summary'  => [ReportController::class, 'descargarResumen'],
];

try {
    if (!isset($rutas[$r])) {
        http_response_code(404);
        if (esta_autenticado()) {
            render_vista('error', [
                'titulo'  => 'Página no encontrada',
                'mensaje' => "La ruta solicitada no existe: " . e($r),
            ]);
        } else {
            redirigir(url('login'));
        }
        exit;
    }

    [$clase, $metodo] = $rutas[$r];
    (new $clase())->$metodo();
} catch (Throwable $ex) {
    http_response_code(500);
    echo "<pre style='background:#300;color:#fdd;padding:16px'>";
    echo "Error en la aplicación:\n\n";
    echo e($ex->getMessage()) . "\n\n";
    echo e($ex->getFile()) . ':' . $ex->getLine() . "\n\n";
    echo e($ex->getTraceAsString());
    echo "</pre>";
}
