<?php
/**
 * StudentModel — acceso a la tabla students.
 *
 * El teléfono de emergencia se guarda cifrado (Sodium) y se descifra al leerlo.
 */

declare(strict_types=1);

final class StudentModel
{
    /**
     * Busca estudiantes por carné, nombres, apellidos o carrera.
     * El término se pasa como parámetro enlazado de una sentencia preparada.
     */
    public static function buscar(string $termino): array
    {
        $like = '%' . $termino . '%';
        $stmt = Database::conn()->prepare(
            "SELECT s.id, s.carne, s.nombres, s.apellidos, s.email,
                    s.estado, c.nombre AS carrera
               FROM students s
               LEFT JOIN careers c ON c.id = s.career_id
              WHERE s.nombres   LIKE ?
                 OR s.apellidos LIKE ?
                 OR s.carne     LIKE ?
                 OR c.nombre    LIKE ?
              ORDER BY s.apellidos, s.nombres"
        );
        $stmt->execute([$like, $like, $like, $like]);
        return $stmt->fetchAll();
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
        $row = $stmt->fetch();
        if (!$row) { return null; }
        if (isset($row['telefono_emergencia'])) {
            $row['telefono_emergencia'] = lab_descifrar($row['telefono_emergencia']);
        }
        return $row;
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
            $d['estado'] ?? 'activo', self::cifrarTelefono($d['telefono_emergencia'] ?? null),
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
            $d['estado'], self::cifrarTelefono($d['telefono_emergencia'] ?? null), $id,
        ]);
    }

    /** Cifra el teléfono de emergencia antes de guardarlo. */
    private static function cifrarTelefono(?string $valor): ?string
    {
        if ($valor === null || $valor === '') { return null; }
        return lab_cifrar($valor);
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
