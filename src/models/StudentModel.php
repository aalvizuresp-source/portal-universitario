<?php
/**
 * StudentModel — acceso a la tabla students.
 */

declare(strict_types=1);

final class StudentModel
{
    /** Busca estudiantes por carné, nombres, apellidos o carrera. */
    public static function buscar(string $termino): array
    {
        $sql = "SELECT s.id, s.carne, s.nombres, s.apellidos, s.email,
                       s.estado, c.nombre AS carrera
                  FROM students s
                  LEFT JOIN careers c ON c.id = s.career_id
                 WHERE s.nombres   LIKE '%$termino%'
                    OR s.apellidos LIKE '%$termino%'
                    OR s.carne     LIKE '%$termino%'
                    OR c.nombre    LIKE '%$termino%'
                 ORDER BY s.apellidos, s.nombres";
        return Database::conn()->query($sql)->fetchAll();
    }

    public static function contar(): int
    {
        return (int)Database::conn()->query("SELECT COUNT(*) FROM students")->fetchColumn();
    }

    public static function pagina(int $offset, int $limit): array
    {
        $stmt = Database::conn()->prepare(
            "SELECT s.id, s.carne, s.nombres, s.apellidos, s.email, s.estado,
                    c.nombre AS carrera
               FROM students s
               LEFT JOIN careers c ON c.id = s.career_id
              ORDER BY s.id
              LIMIT ? OFFSET ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function porId(int $id): ?array
    {
        $stmt = Database::conn()->prepare(
            "SELECT s.*, c.nombre AS carrera
               FROM students s
               LEFT JOIN careers c ON c.id = s.career_id
              WHERE s.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function porCarne(string $carne): ?array
    {
        $stmt = Database::conn()->prepare("SELECT * FROM students WHERE carne = ?");
        $stmt->execute([$carne]);
        return $stmt->fetch() ?: null;
    }

    public static function crear(array $d): int
    {
        $stmt = Database::conn()->prepare(
            "INSERT INTO students (carne, nombres, apellidos, email, fecha_nacimiento,
                                   career_id, estado, telefono_emergencia)
             VALUES (?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $d['carne'], $d['nombres'], $d['apellidos'], $d['email'],
            $d['fecha_nacimiento'] ?: null, $d['career_id'] ?: null,
            $d['estado'] ?? 'activo', $d['telefono_emergencia'] ?? null,
        ]);
        return (int)Database::conn()->lastInsertId();
    }

    public static function actualizar(int $id, array $d): void
    {
        $stmt = Database::conn()->prepare(
            "UPDATE students
                SET carne = ?, nombres = ?, apellidos = ?, email = ?,
                    fecha_nacimiento = ?, career_id = ?, estado = ?, telefono_emergencia = ?
              WHERE id = ?"
        );
        $stmt->execute([
            $d['carne'], $d['nombres'], $d['apellidos'], $d['email'],
            $d['fecha_nacimiento'] ?: null, $d['career_id'] ?: null,
            $d['estado'], $d['telefono_emergencia'] ?? null, $id,
        ]);
    }

    public static function eliminar(int $id): void
    {
        $stmt = Database::conn()->prepare("DELETE FROM students WHERE id = ?");
        $stmt->execute([$id]);
    }

    public static function carreras(): array
    {
        return Database::conn()->query(
            "SELECT id, codigo, nombre FROM careers ORDER BY nombre"
        )->fetchAll();
    }
}
