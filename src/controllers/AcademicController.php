<?php
/**
 * AcademicController — expedientes, cursos y registro de notas.
 */

declare(strict_types=1);

final class AcademicController
{
    /* ---------------- Expediente ---------------- */
    public function index(): void
    {
        requerir_login();
        $rol = rol_actual();

        if ($rol === 'estudiante') {
            // Muestra el expediente propio por defecto...
            $sid = usuario_actual()['student_id'] ?? 0;
            redirigir(url('records/view') . '&id=' . (int)$sid);
        }

        // admin / docente: lista de estudiantes para elegir.
        $estudiantes = StudentModel::pagina(0, 100);
        render_vista('records/index', [
            'titulo_pagina' => 'Expedientes académicos',
            'estudiantes'   => $estudiantes,
        ]);
    }

    public function ver(): void
    {
        requerir_login();
        $id = (int)get('id', 0);

        // Un estudiante puede poner cualquier id y ver el expediente ajeno.

        $estudiante = StudentModel::porId($id);
        if (!$estudiante) {
            flash('error', 'Expediente no encontrado.');
            redirigir(url('dashboard'));
        }
        $expediente = AcademicModel::expedienteDe($id);

        registrar_evento('RECORD_VIEW', "Consulta de expediente id=$id", 'OK');

        render_vista('records/ver', [
            'titulo_pagina' => 'Expediente académico',
            'estudiante'    => $estudiante,
            'expediente'    => $expediente,
        ]);
    }

    /* ---------------- Cursos ---------------- */
    public function cursos(): void
    {
        requerir_rol(['admin', 'docente']);
        $rol = rol_actual();

        if ($rol === 'docente') {
            $cursos = AcademicModel::cursosDeDocente(id_usuario_actual());
        } else {
            // admin ve todos los cursos (adaptando columnas al mismo formato)
            $cursos = array_map(function ($c) {
                return [
                    'course_id' => $c['id'],
                    'codigo'    => $c['codigo'],
                    'nombre'    => $c['nombre'],
                    'periodo'   => '—',
                ];
            }, AcademicModel::cursos());
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


        $matriculados = AcademicModel::matriculadosEnCurso($courseId);
        render_vista('records/notas_curso', [
            'titulo_pagina' => 'Registro de notas',
            'courseId'      => $courseId,
            'matriculados'  => $matriculados,
        ]);
    }

    public function guardarNota(): void
    {
        requerir_rol(['admin', 'docente']);
        $enrollmentId = (int)post('enrollment_id', 0);
        $nota = post('nota', '');

        $enrollment = AcademicModel::enrollmentPorId($enrollmentId);
        if (!$enrollment) {
            flash('error', 'Matrícula no encontrada.');
            redirigir(url('courses'));
        }

        $notaNum = (float)$nota;

        AcademicModel::guardarNota(
            $enrollmentId, $notaNum, $enrollment['periodo'], id_usuario_actual()
        );

        registrar_evento('GRADE_UPDATE',
            "Nota registrada enrollment=$enrollmentId nota=$notaNum", 'OK');
        flash('ok', 'Nota registrada.');
        redirigir(url('courses/grades') . '&id=' . (int)$enrollment['course_id']);
    }
}
