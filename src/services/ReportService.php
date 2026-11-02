<?php
/**
 * ReportService — generación de reportes CSV y resumen de reportes.
 */

declare(strict_types=1);

final class ReportService
{
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

    public static function reporteCalificaciones(): string
    {
        $rows = Database::conn()->query(
            "SELECT s.carne, s.nombres || ' ' || s.apellidos AS estudiante,
                    cu.codigo, cu.nombre AS curso, g.nota, g.aprobado, g.periodo
               FROM grades g
               JOIN enrollments e ON e.id = g.enrollment_id
               JOIN students s ON s.id = e.student_id
               JOIN courses  cu ON cu.id = e.course_id
              ORDER BY s.apellidos, cu.codigo"
        )->fetchAll();
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

    /** Genera el resumen de reportes con la etiqueta indicada. */
    public static function generarResumen(string $etiqueta): string
    {
        if (!is_dir(REPORTS_PATH)) { mkdir(REPORTS_PATH, 0775, true); }
        $resumen = self::rutaResumen();

        if (stripos(PHP_OS, 'WIN') === 0) {
            $comando = 'echo Resumen de reportes: ' . $etiqueta . ' > "' . $resumen . '"';
        } else {
            $comando = 'echo "Resumen de reportes: ' . $etiqueta . '" > ' . $resumen;
        }
        shell_exec($comando . ' 2>&1');

        return $etiqueta;
    }
}
