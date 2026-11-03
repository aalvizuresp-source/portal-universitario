<?php
/**
 * CommentModel — acceso a la tabla comments.
 *
 * El contenido se guarda tal como lo escribe el usuario; la vista lo codifica
 * con htmlspecialchars() al mostrarlo.
 */

declare(strict_types=1);

final class CommentModel
{
    private const SELECT_BASE =
        "SELECT cm.id, cm.contenido, cm.creado_en, cm.autor_id,
                u.nombre_completo AS autor,
                s.nombres || ' ' || s.apellidos AS estudiante,
                co.nombre AS curso
           FROM comments cm
           JOIN users u ON u.id = cm.autor_id
           LEFT JOIN students s ON s.id = cm.student_id
           LEFT JOIN courses  co ON co.id = cm.course_id";

    /** Todos los comentarios (uso del administrador). */
    public static function todos(): array
    {
        return Database::conn()->query(self::SELECT_BASE . " ORDER BY cm.id DESC")->fetchAll();
    }

    /**
     * Comentarios visibles para un estudiante: generales, los que tratan sobre
     * él, los de los cursos en que está matriculado y los que él escribió.
     */
    public static function visiblesParaEstudiante(int $studentId, int $userId): array
    {
        $stmt = Database::conn()->prepare(self::SELECT_BASE . "
              WHERE (cm.student_id IS NULL AND cm.course_id IS NULL)
                 OR cm.student_id = :sid
                 OR cm.autor_id   = :uid
                 OR cm.course_id IN (SELECT course_id FROM enrollments WHERE student_id = :sid)
              ORDER BY cm.id DESC");
        $stmt->execute([':sid' => $studentId, ':uid' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Comentarios visibles para un docente: generales, los de sus cursos, los
     * que tratan sobre estudiantes de sus cursos y los que él escribió.
     */
    public static function visiblesParaDocente(int $teacherId): array
    {
        $stmt = Database::conn()->prepare(self::SELECT_BASE . "
              WHERE (cm.student_id IS NULL AND cm.course_id IS NULL)
                 OR cm.autor_id = :tid
                 OR cm.course_id IN (SELECT course_id FROM teacher_courses WHERE teacher_id = :tid)
                 OR cm.student_id IN (SELECT e.student_id FROM enrollments e
                                        JOIN teacher_courses tc ON tc.course_id = e.course_id
                                       WHERE tc.teacher_id = :tid)
              ORDER BY cm.id DESC");
        $stmt->execute([':tid' => $teacherId]);
        return $stmt->fetchAll();
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
