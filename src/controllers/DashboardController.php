<?php
/**
 * DashboardController — panel principal.
 *
 * Cada rol ve únicamente la información que le corresponde:
 *   - admin:      indicadores globales y bitácora de actividad del sistema.
 *   - docente:    indicadores de sus cursos y estudiantes.
 *   - estudiante: resumen de su propio expediente.
 */

declare(strict_types=1);

final class DashboardController
{
    public function index(): void
    {
        requerir_login();
        $rol = rol_actual();

        if ($rol === 'admin') {
            render_vista('dashboard/index', [
                'titulo_pagina' => 'Panel principal',
                'stats'         => DashboardModel::estadisticas(),
                'actividades'   => DashboardModel::ultimasActividades(8),
            ]);
            return;
        }

        if ($rol === 'docente') {
            $uid = (int)id_usuario_actual();
            render_vista('dashboard/docente', [
                'titulo_pagina' => 'Panel principal',
                'stats'         => DashboardModel::estadisticasDocente($uid),
                'cursos'        => AcademicModel::cursosDeDocente($uid),
            ]);
            return;
        }

        $sid = (int)(usuario_actual()['student_id'] ?? 0);
        render_vista('dashboard/estudiante', [
            'titulo_pagina' => 'Panel principal',
            'stats'         => DashboardModel::estadisticasEstudiante($sid),
            'tieneFicha'    => $sid > 0,
        ]);
    }

    /**
     * Verificación del manejador central de errores (uso administrativo).
     * Provoca un error controlado para comprobar que el usuario sólo recibe un
     * mensaje genérico con una referencia, mientras el detalle técnico queda en
     * el registro del servidor.
     */
    public function diagnosticoErrores(): void
    {
        requerir_rol(['admin']);
        throw new RuntimeException('Error controlado de verificación del manejador de errores');
    }
}
