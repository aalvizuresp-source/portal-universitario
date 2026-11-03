<?php
/**
 * DashboardModel — estadísticas reales consultadas desde SQLite.
 */

declare(strict_types=1);

final class DashboardModel
{
    public static function estadisticas(): array
    {
        $db = Database::conn();
        return [
            'usuarios'    => (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'estudiantes' => (int)$db->query("SELECT COUNT(*) FROM students WHERE estado='activo'")->fetchColumn(),
            'docentes'    => (int)$db->query(
                "SELECT COUNT(*) FROM users u JOIN roles r ON r.id=u.role_id WHERE r.nombre='docente'"
            )->fetchColumn(),
            'cursos'      => (int)$db->query("SELECT COUNT(*) FROM courses")->fetchColumn(),
            'documentos'  => (int)$db->query("SELECT COUNT(*) FROM documents")->fetchColumn(),
            'comentarios' => (int)$db->query("SELECT COUNT(*) FROM comments")->fetchColumn(),
        ];
    }

    /** Indicadores de un docente: sólo sus cursos y sus estudiantes. */
    public static function estadisticasDocente(int $teacherId): array
    {
        $db = Database::conn();
        $q = function (string $sql) use ($db, $teacherId): int {
            $st = $db->prepare($sql);
            $st->execute([$teacherId]);
            return (int)$st->fetchColumn();
        };
        return [
            'cursos'      => $q("SELECT COUNT(*) FROM teacher_courses WHERE teacher_id = ?"),
            'estudiantes' => $q("SELECT COUNT(DISTINCT e.student_id) FROM enrollments e
                                   JOIN teacher_courses tc ON tc.course_id = e.course_id
                                  WHERE tc.teacher_id = ?"),
            'calificadas' => $q("SELECT COUNT(*) FROM enrollments e
                                   JOIN teacher_courses tc ON tc.course_id = e.course_id
                                   JOIN grades g ON g.enrollment_id = e.id
                                  WHERE tc.teacher_id = ?"),
            'pendientes'  => $q("SELECT COUNT(*) FROM enrollments e
                                   JOIN teacher_courses tc ON tc.course_id = e.course_id
                                   LEFT JOIN grades g ON g.enrollment_id = e.id
                                  WHERE tc.teacher_id = ? AND g.id IS NULL"),
        ];
    }

    /** Indicadores de un estudiante: sólo su propio expediente. */
    public static function estadisticasEstudiante(int $studentId): array
    {
        $stmt = Database::conn()->prepare(
            "SELECT COUNT(e.id)                                   AS cursos,
                    SUM(CASE WHEN g.aprobado = 1 THEN 1 ELSE 0 END) AS aprobados,
                    SUM(CASE WHEN g.id IS NULL THEN 1 ELSE 0 END)   AS pendientes,
                    ROUND(AVG(g.nota), 1)                           AS promedio
               FROM enrollments e
               LEFT JOIN grades g ON g.enrollment_id = e.id
              WHERE e.student_id = ?"
        );
        $stmt->execute([$studentId]);
        $r = $stmt->fetch() ?: [];
        $docs = Database::conn()->prepare("SELECT COUNT(*) FROM documents WHERE student_id = ?");
        $docs->execute([$studentId]);
        return [
            'cursos'     => (int)($r['cursos'] ?? 0),
            'aprobados'  => (int)($r['aprobados'] ?? 0),
            'pendientes' => (int)($r['pendientes'] ?? 0),
            'promedio'   => $r['promedio'] !== null ? (float)$r['promedio'] : null,
            'documentos' => (int)$docs->fetchColumn(),
        ];
    }

    public static function ultimasActividades(int $limite = 8): array
    {
        $stmt = Database::conn()->prepare(
            "SELECT a.ts, a.tipo, a.descripcion, a.resultado, u.username
               FROM audit_logs a
               LEFT JOIN users u ON u.id = a.user_id
              ORDER BY a.id DESC
              LIMIT ?"
        );
        $stmt->bindValue(1, $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
