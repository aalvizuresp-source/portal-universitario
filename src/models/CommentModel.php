<?php
/**
 * CommentModel — acceso a la tabla comments.

 */

declare(strict_types=1);

final class CommentModel
{
    public static function todos(): array
    {
        return Database::conn()->query(
            "SELECT cm.id, cm.contenido, cm.creado_en,
                    u.nombre_completo AS autor,
                    s.nombres || ' ' || s.apellidos AS estudiante,
                    co.nombre AS curso
               FROM comments cm
               JOIN users u ON u.id = cm.autor_id
               LEFT JOIN students s ON s.id = cm.student_id
               LEFT JOIN courses  co ON co.id = cm.course_id
              ORDER BY cm.id DESC"
        )->fetchAll();
    }

    public static function porId(int $id): ?array
    {
        $stmt = Database::conn()->prepare(
            "SELECT * FROM comments WHERE id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function crear(int $autorId, ?int $studentId, ?int $courseId, string $contenido): int
    {
        $stmt = Database::conn()->prepare(
            "INSERT INTO comments (autor_id, student_id, course_id, contenido)
             VALUES (?,?,?,?)"
        );
        $stmt->execute([$autorId, $studentId, $courseId, $contenido]);
        return (int)Database::conn()->lastInsertId();
    }

    public static function eliminar(int $id): void
    {
        $stmt = Database::conn()->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->execute([$id]);
    }

    public static function estudiantesParaSelect(): array
    {
        return Database::conn()->query(
            "SELECT id, carne, nombres || ' ' || apellidos AS nombre
               FROM students ORDER BY apellidos"
        )->fetchAll();
    }
}
