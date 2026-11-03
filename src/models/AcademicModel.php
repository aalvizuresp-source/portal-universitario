<?php
/**
 * AcademicModel — expedientes: cursos, matrículas, calificaciones.
 */

declare(strict_types=1);

final class AcademicModel
{
    /** Expediente completo (calificaciones) de un estudiante. */
    public static function expedienteDe(int $studentId): array
    {
        $stmt = Database::conn()->prepare(
            "SELECT c.codigo, c.nombre AS curso, e.periodo,
                    g.nota, g.aprobado, g.registrado_en
               FROM enrollments e
               JOIN courses c ON c.id = e.course_id
               LEFT JOIN grades g ON g.enrollment_id = e.id
              WHERE e.student_id = ?
              ORDER BY e.periodo DESC, c.nombre"
        );
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }

    /** Cursos asignados a un docente. */
    public static function cursosDeDocente(int $teacherId): array
    {
        $stmt = Database::conn()->prepare(
            "SELECT tc.id AS tc_id, c.id AS course_id, c.codigo, c.nombre, tc.periodo
               FROM teacher_courses tc
               JOIN courses c ON c.id = tc.course_id
              WHERE tc.teacher_id = ?
              ORDER BY tc.periodo DESC, c.nombre"
        );
        $stmt->execute([$teacherId]);
        return $stmt->fetchAll();
    }

    /** Estudiantes matriculados en un curso, con su nota (si existe). */
    public static function matriculadosEnCurso(int $courseId): array
    {
        $stmt = Database::conn()->prepare(
            "SELECT e.id AS enrollment_id, s.id AS student_id, s.carne,
                    s.nombres, s.apellidos, e.periodo,
                    g.id AS grade_id, g.nota, g.aprobado
               FROM enrollments e
               JOIN students s ON s.id = e.student_id
               LEFT JOIN grades g ON g.enrollment_id = e.id
              WHERE e.course_id = ?
              ORDER BY s.apellidos, s.nombres"
        );
        $stmt->execute([$courseId]);
        return $stmt->fetchAll();
    }

    /** ¿El docente tiene asignado este curso? */
    public static function docenteTieneCurso(int $teacherId, int $courseId): bool
    {
        $stmt = Database::conn()->prepare(
            "SELECT 1 FROM teacher_courses WHERE teacher_id = ? AND course_id = ?"
        );
        $stmt->execute([$teacherId, $courseId]);
        return (bool)$stmt->fetchColumn();
    }

    /** ¿El estudiante está matriculado en algún curso asignado al docente? */
    public static function docenteTieneEstudiante(int $teacherId, int $studentId): bool
    {
        $stmt = Database::conn()->prepare(
            "SELECT 1
               FROM enrollments e
               JOIN teacher_courses tc ON tc.course_id = e.course_id
              WHERE tc.teacher_id = ? AND e.student_id = ?
              LIMIT 1"
        );
        $stmt->execute([$teacherId, $studentId]);
        return (bool)$stmt->fetchColumn();
    }

    /** Estudiantes matriculados en los cursos de un docente. */
    public static function estudiantesDeDocente(int $teacherId): array
    {
        $stmt = Database::conn()->prepare(
            "SELECT DISTINCT s.id, s.carne, s.nombres, s.apellidos, s.email, s.estado,
                    c.nombre AS carrera
               FROM students s
               JOIN enrollments e      ON e.student_id = s.id
               JOIN teacher_courses tc ON tc.course_id = e.course_id
               LEFT JOIN careers c     ON c.id = s.career_id
              WHERE tc.teacher_id = ?
              ORDER BY s.apellidos, s.nombres"
        );
        $stmt->execute([$teacherId]);
        return $stmt->fetchAll();
    }

    public static function enrollmentPorId(int $enrollmentId): ?array
    {
        $stmt = Database::conn()->prepare(
            "SELECT e.*, c.id AS course_id
               FROM enrollments e
               JOIN courses c ON c.id = e.course_id
              WHERE e.id = ?"
        );
        $stmt->execute([$enrollmentId]);
        return $stmt->fetch() ?: null;
    }

    /** Inserta o actualiza la nota de una matrícula. */
    public static function guardarNota(int $enrollmentId, float $nota, string $periodo, int $registradoPor): void
    {
        $aprob = $nota >= NOTA_APROBACION ? 1 : 0;
        $pdo = Database::conn();

        $stmt = $pdo->prepare("SELECT id FROM grades WHERE enrollment_id = ?");
        $stmt->execute([$enrollmentId]);
        $gid = $stmt->fetchColumn();

        if ($gid) {
            $upd = $pdo->prepare(
                "UPDATE grades SET nota = ?, aprobado = ?, periodo = ?, registrado_por = ?,
                        registrado_en = datetime('now','localtime')
                  WHERE id = ?"
            );
            $upd->execute([$nota, $aprob, $periodo, $registradoPor, $gid]);
        } else {
            $ins = $pdo->prepare(
                "INSERT INTO grades (enrollment_id, nota, aprobado, periodo, registrado_por)
                 VALUES (?,?,?,?,?)"
            );
            $ins->execute([$enrollmentId, $nota, $aprob, $periodo, $registradoPor]);
        }
    }

    public static function cursos(): array
    {
        return Database::conn()->query(
            "SELECT c.id, c.codigo, c.nombre, c.creditos, ca.nombre AS carrera
               FROM courses c
               LEFT JOIN careers ca ON ca.id = c.career_id
              ORDER BY c.codigo"
        )->fetchAll();
    }
}
