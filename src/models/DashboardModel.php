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
