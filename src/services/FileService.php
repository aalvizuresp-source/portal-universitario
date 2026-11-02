<?php
/**
 * FileService — almacenamiento de documentos subidos en storage/uploads.
 */

declare(strict_types=1);

final class FileService
{
    /**
     * Guarda un archivo subido ($_FILES[...]).
     * Devuelve [nombre_en_disco, tipo_mime, tamaño].
     */
    public static function guardar(array $archivo): array
    {
        if (!is_dir(UPLOADS_PATH)) { mkdir(UPLOADS_PATH, 0775, true); }

        $stored  = basename($archivo['name']);
        $destino = UPLOADS_PATH . DIRECTORY_SEPARATOR . $stored;
        move_uploaded_file($archivo['tmp_name'], $destino);

        return [
            $stored,
            $archivo['type'] ?? 'application/octet-stream',
            (int)($archivo['size'] ?? filesize($destino)),
        ];
    }

    /** Ruta absoluta en disco de un documento almacenado. */
    public static function rutaDe(string $storedName): string
    {
        return UPLOADS_PATH . DIRECTORY_SEPARATOR . $storedName;
    }
}
