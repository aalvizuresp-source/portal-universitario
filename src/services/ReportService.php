<?php
/**
 * ReportService — generación de reportes CSV con PHP nativo y resumen de reportes.
 *
 * Ninguna operación invoca al intérprete de comandos del sistema: los archivos
 * se generan con funciones nativas de PHP (fputcsv, file_put_contents).
 */

declare(strict_types=1);

final class ReportService
{
    /* -------------------------------------------------------------------- *
     *  Reportes CSV  (PHP nativo, sin dependencias)                        *
     * -------------------------------------------------------------------- */

    /** Escribe filas a un CSV y devuelve la ruta del archivo generado. */
    private static function escribirCsv(string $nombreBase, array $cabeceras, array $filas): string
    {
        if (!is_dir(REPORTS_PATH)) { mkdir(REPORTS_PATH, 0775, true); }
        $ruta = REPORTS_PATH . DIRECTORY_SEPARATOR . $nombreBase . '_' . date('Ymd_His') . '.csv';
        $fh = fopen($ruta, 'w');
        // BOM UTF-8 para que Excel en Windows muestre bien los acentos.
        fwrite($fh, "\xEF\xBB\xBF");
        fputcsv($fh, $cabeceras);
        foreach ($filas as $f) { fputcsv($fh, $f); }
        fclose($fh);
        return $ruta;
    }

    public static function reporteEstudiantes(): string
    {
        $rows = Database::conn()->query(
            "SELECT s.carne, s.nombres, s.apellidos, s.email, s.estado,
                    c.nombre AS carrera, s.fecha_registro
               FROM students s LEFT JOIN careers c ON c.id = s.career_id
              ORDER BY s.apellidos"
        )->fetchAll();
        $filas = array_map(fn($r) => [
            $r['carne'], $r['nombres'], $r['apellidos'], $r['email'],
            $r['estado'], $r['carrera'] ?? '', $r['fecha_registro'],
        ], $rows);
        return self::escribirCsv('estudiantes',
            ['Carné','Nombres','Apellidos','Email','Estado','Carrera','Registro'], $filas);
    }

    /**
     * Calificaciones. Si se indica $teacherId, sólo las de los cursos asignados
     * a ese docente.
     */
    public static function reporteCalificaciones(?int $teacherId = null): string
    {
        $sql = "SELECT s.carne, s.nombres || ' ' || s.apellidos AS estudiante,
                       cu.codigo, cu.nombre AS curso, g.nota, g.aprobado, g.periodo
                  FROM grades g
                  JOIN enrollments e ON e.id = g.enrollment_id
                  JOIN students s ON s.id = e.student_id
                  JOIN courses  cu ON cu.id = e.course_id";
        $params = [];
        if ($teacherId !== null) {
            $sql .= " WHERE cu.id IN (SELECT course_id FROM teacher_courses WHERE teacher_id = ?)";
            $params[] = $teacherId;
        }
        $sql .= " ORDER BY s.apellidos, cu.codigo";
        $stmt = Database::conn()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $filas = array_map(fn($r) => [
            $r['carne'], $r['estudiante'], $r['codigo'], $r['curso'],
            $r['nota'], $r['aprobado'] ? 'Sí' : 'No', $r['periodo'],
        ], $rows);
        return self::escribirCsv('calificaciones',
            ['Carné','Estudiante','Código','Curso','Nota','Aprobado','Período'], $filas);
    }

    public static function reportePorCarrera(): string
    {
        $rows = Database::conn()->query(
            "SELECT c.nombre AS carrera, COUNT(s.id) AS total,
                    SUM(CASE WHEN s.estado='activo' THEN 1 ELSE 0 END) AS activos
               FROM careers c LEFT JOIN students s ON s.career_id = c.id
              GROUP BY c.id ORDER BY c.nombre"
        )->fetchAll();
        $filas = array_map(fn($r) => [
            $r['carrera'], $r['total'], $r['activos'],
        ], $rows);
        return self::escribirCsv('estudiantes_por_carrera',
            ['Carrera','Total','Activos'], $filas);
    }

    public static function reporteActividad(): string
    {
        $rows = Database::conn()->query(
            "SELECT a.ts, a.tipo, a.descripcion, a.resultado,
                    COALESCE(u.username,'-') AS usuario, a.ip
               FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
              ORDER BY a.id DESC LIMIT 500"
        )->fetchAll();
        $filas = array_map(fn($r) => [
            $r['ts'], $r['tipo'], $r['usuario'], $r['resultado'], $r['ip'], $r['descripcion'],
        ], $rows);
        return self::escribirCsv('actividad',
            ['Fecha','Tipo','Usuario','Resultado','IP','Descripción'], $filas);
    }

    /** Ruta del archivo de resumen de reportes. */
    public static function rutaResumen(): string
    {
        return REPORTS_PATH . DIRECTORY_SEPARATOR . 'resumen.txt';
    }

    /**
     * Genera el resumen de reportes con la etiqueta indicada por el usuario.
     *
     * La etiqueta se depura por lista blanca (letras, números, espacio y guion;
     * máximo 40 caracteres) y el archivo se escribe con file_put_contents(): no
     * se construye ni ejecuta ningún comando del sistema operativo.
     * Devuelve la etiqueta finalmente utilizada.
     */
    public static function generarResumen(string $etiqueta): string
    {
        if (!is_dir(REPORTS_PATH)) { mkdir(REPORTS_PATH, 0775, true); }

        $limpia = preg_replace('/[^\p{L}0-9 \-]/u', '', $etiqueta) ?? '';
        $limpia = mb_substr(trim(preg_replace('/\s+/', ' ', $limpia)), 0, 40);
        if ($limpia === '') { $limpia = 'reporte'; }

        $db = Database::conn();
        $contenido = "Resumen de reportes: {$limpia}" . PHP_EOL
            . 'Generado: ' . date('Y-m-d H:i:s') . PHP_EOL
            . 'Estudiantes registrados: ' . (int)$db->query("SELECT COUNT(*) FROM students")->fetchColumn() . PHP_EOL
            . 'Calificaciones registradas: ' . (int)$db->query("SELECT COUNT(*) FROM grades")->fetchColumn() . PHP_EOL
            . 'Cursos: ' . (int)$db->query("SELECT COUNT(*) FROM courses")->fetchColumn() . PHP_EOL;

        file_put_contents(self::rutaResumen(), "\xEF\xBB\xBF" . $contenido);
        return $limpia;
    }
}
