<?php
/**
 * DocumentController — subida, listado, descarga y eliminación de documentos.
 *
 * Permisos:
 *   - admin:      todos los documentos; puede subir y eliminar.
 *   - docente:    documentos de estudiantes de sus cursos; puede subir para ellos.
 *   - estudiante: sólo sus propios documentos (consulta y descarga).
 * Toda descarga pasa por este controlador, que comprueba la propiedad.
 */

declare(strict_types=1);

final class DocumentController
{
    public function index(): void
    {
        requerir_login();
        $rol = rol_actual();
        $uid = (int)id_usuario_actual();

        if ($rol === 'admin') {
            $documentos  = DocumentModel::todos();
            $estudiantes = CommentModel::estudiantesParaSelect();
        } elseif ($rol === 'docente') {
            $documentos  = DocumentModel::listaDeDocente($uid);
            $estudiantes = array_map(fn($s) => [
                'id'     => $s['id'],
                'carne'  => $s['carne'],
                'nombre' => $s['nombres'] . ' ' . $s['apellidos'],
            ], AcademicModel::estudiantesDeDocente($uid));
        } else {
            $sid = (int)(usuario_actual()['student_id'] ?? 0);
            $documentos  = $sid > 0 ? DocumentModel::listaDeEstudiante($sid) : [];
            $estudiantes = [];
        }

        render_vista('documents/index', [
            'titulo_pagina' => 'Documentos',
            'documentos'    => $documentos,
            'estudiantes'   => $estudiantes,
            'rol'           => $rol,
        ]);
    }

    public function subir(): void
    {
        requerir_rol(['admin', 'docente']);
        $studentId = (int)post('student_id', 0);

        if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Debe seleccionar un archivo válido.');
            redirigir(url('documents'));
        }
        if (!StudentModel::porId($studentId)) {
            flash('error', 'Estudiante no válido.');
            redirigir(url('documents'));
        }
        if (!puede_acceder_estudiante($studentId)) {
            denegar_acceso('DOC_UPLOAD_DENIED',
                "Intento de subir documento para estudiante no autorizado id=$studentId",
                'No puede subir documentos para ese estudiante.');
        }

        // Validación de extensión, tamaño y tipo real; nombre aleatorio en disco.
        try {
            [$stored, $mime, $size] = FileService::guardar($_FILES['archivo']);
        } catch (RuntimeException $ex) {
            registrar_evento('DOC_UPLOAD_REJECTED', 'Archivo rechazado: ' . $ex->getMessage(), 'DENEGADO');
            flash('error', 'Archivo no permitido: ' . $ex->getMessage());
            redirigir(url('documents'));
        }

        DocumentModel::crear([
            'student_id'    => $studentId,
            'original_name' => basename((string)$_FILES['archivo']['name']),
            'stored_name'   => $stored,
            'mime_type'     => $mime,
            'size_bytes'    => $size,
            'subido_por'    => id_usuario_actual(),
        ]);

        registrar_evento('DOC_UPLOAD', "Documento subido para estudiante id=$studentId", 'OK');
        flash('ok', 'Documento subido.');
        redirigir(url('documents'));
    }

    public function descargar(): void
    {
        requerir_login();
        $id = (int)get('id', 0);
        $doc = DocumentModel::porId($id);
        if (!$doc) {
            http_response_code(404);
            render_vista('error', ['titulo' => 'No encontrado', 'mensaje' => 'El documento no existe.']);
            return;
        }

        if (!puede_acceder_estudiante((int)$doc['student_id'])) {
            denegar_acceso('DOC_DOWNLOAD_DENIED',
                "Intento de descargar documento no autorizado id=$id",
                'No tiene permiso para descargar este documento.');
        }

        $ruta = FileService::rutaDe($doc['stored_name']);
        if (!is_file($ruta)) {
            http_response_code(404);
            render_vista('error', ['titulo' => 'No encontrado', 'mensaje' => 'El archivo no está disponible.']);
            return;
        }

        registrar_evento('DOC_DOWNLOAD', "Descarga de documento id=$id", 'OK');

        header('Content-Type: ' . ($doc['mime_type'] ?: 'application/octet-stream'));
        $nombre = basename($doc['original_name']);
        $seguro = preg_replace('/[^A-Za-z0-9._-]/', '_', $nombre);
        header('Content-Disposition: attachment; filename="' . $seguro . '"; filename*=UTF-8\'\'' . rawurlencode($nombre));
        header('Content-Length: ' . filesize($ruta));
        readfile($ruta);
        exit;
    }

    public function eliminar(): void
    {
        requerir_rol(['admin']);
        $id = (int)get('id', 0);
        $doc = DocumentModel::porId($id);
        if ($doc) {
            $ruta = FileService::rutaDe($doc['stored_name']);
            if (is_file($ruta)) { @unlink($ruta); }
            DocumentModel::eliminar($id);
            registrar_evento('DOC_DELETE', "Documento id=$id eliminado", 'OK');
            flash('ok', 'Documento eliminado.');
        }
        redirigir(url('documents'));
    }
}
