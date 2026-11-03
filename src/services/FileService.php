<?php
/**
 * FileService — almacenamiento de documentos subidos.
 *
 * Valida tamaño, extensión y tipo real del contenido (finfo), y guarda el
 * archivo con un nombre aleatorio en storage/uploads, fuera de public/.
 */

declare(strict_types=1);

final class FileService
{
    /**
     * Guarda un archivo subido ($_FILES[...]) tras validarlo.
     * Devuelve [nombre_en_disco, tipo_mime, tamaño] o lanza RuntimeException.
     */
    public static function guardar(array $archivo): array
    {
        if (!is_dir(UPLOADS_PATH)) { mkdir(UPLOADS_PATH, 0775, true); }

        $permitidos = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
        ];

        // Tamaño
        $size = (int)($archivo['size'] ?? 0);
        if ($size <= 0 || $size > MAX_UPLOAD_BYTES) {
            throw new RuntimeException('El archivo excede el tamaño permitido (5 MB).');
        }

        // Extensión
        $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if (!isset($permitidos[$ext])) {
            throw new RuntimeException('Extensión no permitida.');
        }

        // Tipo real (contenido) con fileinfo
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeReal = $finfo->file($archivo['tmp_name']);
        if ($mimeReal !== $permitidos[$ext]) {
            throw new RuntimeException('El contenido del archivo no coincide con su extensión.');
        }

        // Nombre aleatorio
        $stored = bin2hex(random_bytes(16)) . '.' . $ext;
        $destino = UPLOADS_PATH . DIRECTORY_SEPARATOR . $stored;

        // Sólo se aceptan archivos que realmente llegaron por una subida HTTP.
        if (!is_uploaded_file($archivo['tmp_name'])
            || !move_uploaded_file($archivo['tmp_name'], $destino)) {
            throw new RuntimeException('No se pudo almacenar el archivo.');
        }

        return [$stored, $mimeReal, $size];
    }

    /** Ruta absoluta en disco de un documento almacenado. */
    public static function rutaDe(string $storedName): string
    {
        return UPLOADS_PATH . DIRECTORY_SEPARATOR . $storedName;
    }
}
