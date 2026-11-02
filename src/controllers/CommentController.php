<?php
/**
 * CommentController — comentarios sobre estudiantes o generales.
 */

declare(strict_types=1);

final class CommentController
{
    public function index(): void
    {
        requerir_login();
        $comentarios = CommentModel::todos();
        $estudiantes = CommentModel::estudiantesParaSelect();
        render_vista('comments/index', [
            'titulo_pagina' => 'Comentarios',
            'comentarios'   => $comentarios,
            'estudiantes'   => $estudiantes,
        ]);
    }

    public function crear(): void
    {
        requerir_login();
        $contenido = (string)post('contenido', '');
        $studentId = post('student_id') ?: null;

        if (trim($contenido) === '') {
            flash('error', 'El comentario no puede estar vacío.');
            redirigir(url('comments'));
        }

        CommentModel::crear(
            id_usuario_actual(),
            $studentId ? (int)$studentId : null,
            null,
            $contenido
        );

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

        $rol = rol_actual();
        if ($rol !== 'admin' && (int)$c['autor_id'] !== id_usuario_actual()) {
            flash('error', 'No puede eliminar este comentario.');
            redirigir(url('comments'));
        }

        CommentModel::eliminar($id);
        registrar_evento('COMMENT_DELETE', "Comentario id=$id eliminado", 'OK');
        flash('ok', 'Comentario eliminado.');
        redirigir(url('comments'));
    }
}
