<?php
/**
 * AcademicController — expedientes, cursos y registro de calificaciones.
 *
 * Permisos:
 *   - admin:      todos los expedientes y cursos.
 *   - docente:    expedientes de estudiantes de sus cursos; califica sólo sus cursos.
 *   - estudiante: únicamente su propio expediente.
 */

declare(strict_types=1);

final class AcademicController
{
    public function index(): void
    {
        requerir_login();
        $rol = rol_actual();

        if ($rol === 'estudiante') {
            $sid = (int)(usuario_actual()['student_id'] ?? 0);
            if ($sid <= 0) {
                render_vista('error', [
                    'titulo'  => 'Expediente no disponible',
                    'mensaje' => 'Su cuenta aún no está vinculada a una ficha de estudiante.',
                ]);
                return;
            }
            redirigir(url('records/view') . '&id=' . $sid);
        }

        $estudiantes = $rol === 'docente'
            ? AcademicModel::estudiantesDeDocente((int)id_usuario_actual())
            : StudentModel::pagina(0, 500);

        render_vista('records/index', [
            'titulo_pagina' => 'Expedientes académicos',
            'estudiantes'   => $estudiantes,
        ]);
    }

    public function ver(): void
    {
        requerir_login();
        $v = new Validador();
        $id = $v->id((string)get('id', ''));
        if ($id === null) {
            flash('error', 'Expediente no encontrado.');
            redirigir(url('dashboard'));
        }

        if (!puede_acceder_estudiante($id)) {
            denegar_acceso('RECORD_VIEW_DENIED',
                "Intento de consultar expediente no autorizado id=$id",
                rol_actual() === 'estudiante'
                    ? 'Sólo puede consultar su propio expediente.'
                    : 'Sólo puede consultar expedientes de estudiantes de sus cursos.');
        }

        $estudiante = StudentModel::porId($id);
        if (!$estudiante) {
            flash('error', 'Expediente no encontrado.');
            redirigir(url('dashboard'));
        }

        registrar_evento('RECORD_VIEW', "Consulta de expediente id=$id", 'OK');

        render_vista('records/ver', [
            'titulo_pagina' => 'Expediente académico',
            'estudiante'    => $estudiante,
            'expediente'    => AcademicModel::expedienteDe($id),
        ]);
    }

    public function cursos(): void
    {
        requerir_rol(['admin', 'docente']);

        if (rol_actual() === 'docente') {
            $cursos = AcademicModel::cursosDeDocente((int)id_usuario_actual());
        } else {
            $cursos = array_map(fn($c) => [
                'course_id' => $c['id'],
                'codigo'    => $c['codigo'],
                'nombre'    => $c['nombre'],
                'periodo'   => '—',
            ], AcademicModel::cursos());
        }

        render_vista('records/cursos', [
            'titulo_pagina' => 'Cursos y notas',
            'cursos'        => $cursos,
        ]);
    }

    public function notasCurso(): void
    {
        requerir_rol(['admin', 'docente']);
        $courseId = (int)get('id', 0);

        if (rol_actual() === 'docente'
            && !AcademicModel::docenteTieneCurso((int)id_usuario_actual(), $courseId)) {
            denegar_acceso('COURSE_ACCESS_DENIED',
                "Intento de abrir curso no asignado id=$courseId",
                'No tiene asignado este curso.');
        }

        render_vista('records/notas_curso', [
            'titulo_pagina' => 'Registro de notas',
            'courseId'      => $courseId,
            'matriculados'  => AcademicModel::matriculadosEnCurso($courseId),
        ]);
    }

    public function guardarNota(): void
    {
        requerir_rol(['admin', 'docente']);
        $enrollmentId = (int)post('enrollment_id', 0);

        $enrollment = AcademicModel::enrollmentPorId($enrollmentId);
        if (!$enrollment) {
            flash('error', 'Matrícula no encontrada.');
            redirigir(url('courses'));
        }

        if (rol_actual() === 'docente'
            && !AcademicModel::docenteTieneCurso((int)id_usuario_actual(), (int)$enrollment['course_id'])) {
            denegar_acceso('GRADE_UPDATE_DENIED',
                'Intento de calificar curso no asignado id=' . (int)$enrollment['course_id'],
                'No puede registrar notas en un curso que no tiene asignado.');
        }

        $v = new Validador();
        $nota = $v->nota((string)post('nota', ''));
        if ($nota === null) {
            flash('error', $v->primerError());
            redirigir(url('courses/grades') . '&id=' . (int)$enrollment['course_id']);
        }

        AcademicModel::guardarNota($enrollmentId, $nota, $enrollment['periodo'], (int)id_usuario_actual());

        registrar_evento('GRADE_UPDATE', "Nota registrada enrollment=$enrollmentId", 'OK');
        flash('ok', 'Nota registrada.');
        redirigir(url('courses/grades') . '&id=' . (int)$enrollment['course_id']);
    }
}
