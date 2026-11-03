<?php
/**
 * CommentController — comentarios sobre estudiantes o generales.
 *
 * Visibilidad:
 *   - admin:      todos los comentarios.
 *   - docente:    generales, de sus cursos y sobre estudiantes de sus cursos.
 *   - estudiante: generales, sobre sí mismo y de los cursos en que está matriculado.
 * Eliminación: el administrador o el autor del comentario.
 */

declare(strict_types=1);

final class CommentController
{
    public function index(): void
    {
        requerir_login();
        $rol = rol_actual();
        $uid = (int)id_usuario_actual();

        if ($rol === 'admin') {
            $comentarios = CommentModel::todos();
            $estudiantes = CommentModel::estudiantesParaSelect();
        } elseif ($rol === 'docente') {
            $comentarios = CommentModel::visiblesParaDocente($uid);
            $estudiantes = array_map(fn($s) => [
                'id'     => $s['id'],
                'carne'  => $s['carne'],
                'nombre' => $s['nombres'] . ' ' . $s['apellidos'],
            ], AcademicModel::estudiantesDeDocente($uid));
        } else {
            $sid = (int)(usuario_actual()['student_id'] ?? 0);
            $comentarios = CommentModel::visiblesParaEstudiante($sid, $uid);
            $estudiantes = []; // el estudiante sólo publica comentarios generales o sobre sí mismo
        }

        render_vista('comments/index', [
            'titulo_pagina' => 'Comentarios',
            'comentarios'   => $comentarios,
            'estudiantes'   => $estudiantes,
            'rol'           => $rol,
            'uid'           => $uid,
        ]);
    }

    public function crear(): void
    {
        requerir_login();
        $v = new Validador();
        $contenido = $v->texto((string)post('contenido', ''), 1000, 'comentario');
        if ($contenido === null) {
            flash('error', $v->primerError());
            redirigir(url('comments'));
        }

        // Destino del comentario.
        $studentId = null;
        $destino = (string)post('student_id', '');
        if (rol_actual() === 'estudiante') {
            // "yo" = comentario sobre su propia ficha; cualquier otro valor = general.
            if ($destino === 'yo') {
                $studentId = (int)(usuario_actual()['student_id'] ?? 0) ?: null;
            }
        } elseif ($destino !== '') {
            $id = $v->id($destino);
            if ($id === null || !puede_acceder_estudiante($id)) {
                denegar_acceso('COMMENT_CREATE_DENIED',
                    'Intento de comentar sobre un estudiante no autorizado',
                    'No puede publicar comentarios sobre ese estudiante.');
            }
            $studentId = $id;
        }

        // El contenido se guarda tal cual; se codifica al mostrarlo (vista).
        CommentModel::crear((int)id_usuario_actual(), $studentId, null, $contenido);

        registrar_evento('COMMENT_CREATE', 'Nuevo comentario publicado', 'OK');
        flash('ok', 'Comentario publicado.');
        redirigir(url('comments'));
    }

    public function eliminar(): void
    {
        requerir_login();
        $id = (int)get('id', 0);
        $c = CommentModel::porId($id);
        if (!$c) {
            flash('error', 'Comentario no encontrado.');
            redirigir(url('comments'));
        }

        if (rol_actual() !== 'admin' && (int)$c['autor_id'] !== id_usuario_actual()) {
            denegar_acceso('COMMENT_DELETE_DENIED',
                "Intento de eliminar comentario ajeno id=$id",
                'Sólo puede eliminar sus propios comentarios.');
        }

        CommentModel::eliminar($id);
        registrar_evento('COMMENT_DELETE', "Comentario id=$id eliminado", 'OK');
        flash('ok', 'Comentario eliminado.');
        redirigir(url('comments'));
    }
}
