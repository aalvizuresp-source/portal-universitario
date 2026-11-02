<?php
/**
 * DocumentController — subida, listado, descarga y eliminación de documentos.
 */

declare(strict_types=1);

final class DocumentController
{
    public function index(): void
    {
        requerir_login();
        $documentos = DocumentModel::todos();
        $estudiantes = CommentModel::estudiantesParaSelect();
        render_vista('documents/index', [
            'titulo_pagina' => 'Documentos',
            'documentos'    => $documentos,
            'estudiantes'   => $estudiantes,
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

        [$stored, $mime, $size] = FileService::guardar($_FILES['archivo']);

        DocumentModel::crear([
            'student_id'    => $studentId,
            'original_name' => $_FILES['archivo']['name'],
            'stored_name'   => $stored,
            'mime_type'     => $mime,
            'size_bytes'    => $size,
            'subido_por'    => id_usuario_actual(),
        ]);

        registrar_evento('DOC_UPLOAD',
            "Documento '{$_FILES['archivo']['name']}' subido para estudiante id=$studentId", 'OK');
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
            echo "Documento no encontrado.";
            return;
        }


        $ruta = FileService::rutaDe($doc['stored_name']);
        if (!is_file($ruta)) {
            http_response_code(404);
            echo "Archivo físico no encontrado.";
            return;
        }

        registrar_evento('DOC_DOWNLOAD', "Descarga de documento id=$id", 'OK');

        header('Content-Type: ' . ($doc['mime_type'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . basename($doc['original_name']) . '"');
        header('Content-Length: ' . filesize($ruta));
        readfile($ruta);
        exit;
    }

    public function eliminar(): void
    {
        requerir_rol(['admin', 'docente']);
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
