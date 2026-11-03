<?php
/**
 * DocumentModel — acceso a la tabla documents.
 */

declare(strict_types=1);

final class DocumentModel
{
    public static function todos(): array
    {
        return Database::conn()->query(
            "SELECT d.id, d.original_name, d.stored_name, d.mime_type, d.size_bytes,
                    d.subido_en, s.carne, s.nombres || ' ' || s.apellidos AS estudiante,
                    u.nombre_completo AS subido_por_nombre
               FROM documents d
               JOIN students s ON s.id = d.student_id
               LEFT JOIN users u ON u.id = d.subido_por
              ORDER BY d.id DESC"
        )->fetchAll();
    }

    private const SELECT_LISTA =
        "SELECT d.id, d.student_id, d.original_name, d.stored_name, d.mime_type, d.size_bytes,
                d.subido_en, s.carne, s.nombres || ' ' || s.apellidos AS estudiante,
                u.nombre_completo AS subido_por_nombre
           FROM documents d
           JOIN students s ON s.id = d.student_id
           LEFT JOIN users u ON u.id = d.subido_por";

    /** Documentos de un estudiante, con los mismos campos del listado general. */
    public static function listaDeEstudiante(int $studentId): array
    {
        $stmt = Database::conn()->prepare(self::SELECT_LISTA . " WHERE d.student_id = ? ORDER BY d.id DESC");
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }

    /** Documentos de los estudiantes matriculados en los cursos de un docente. */
    public static function listaDeDocente(int $teacherId): array
    {
        $stmt = Database::conn()->prepare(self::SELECT_LISTA . "
              WHERE d.student_id IN (SELECT e.student_id FROM enrollments e
                                       JOIN teacher_courses tc ON tc.course_id = e.course_id
                                      WHERE tc.teacher_id = ?)
              ORDER BY d.id DESC");
        $stmt->execute([$teacherId]);
        return $stmt->fetchAll();
    }

    public static function porId(int $id): ?array
    {
        $stmt = Database::conn()->prepare(
            "SELECT d.*, s.carne
               FROM documents d
               JOIN students s ON s.id = d.student_id
              WHERE d.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function deEstudiante(int $studentId): array
    {
        $stmt = Database::conn()->prepare(
            "SELECT id, original_name, stored_name, mime_type, size_bytes, subido_en
               FROM documents WHERE student_id = ? ORDER BY id DESC"
        );
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }

    public static function crear(array $d): int
    {
        $stmt = Database::conn()->prepare(
            "INSERT INTO documents (student_id, original_name, stored_name, mime_type, size_bytes, subido_por)
             VALUES (?,?,?,?,?,?)"
        );
        $stmt->execute([
            $d['student_id'], $d['original_name'], $d['stored_name'],
            $d['mime_type'], $d['size_bytes'], $d['subido_por'],
        ]);
        return (int)Database::conn()->lastInsertId();
    }

    public static function eliminar(int $id): void
    {
        $stmt = Database::conn()->prepare("DELETE FROM documents WHERE id = ?");
        $stmt->execute([$id]);
    }
}
