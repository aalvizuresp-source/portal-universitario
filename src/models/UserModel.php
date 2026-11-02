<?php
/**
 * UserModel — acceso a la tabla users.
 */

declare(strict_types=1);

final class UserModel
{
    public static function porUsername(string $username): ?array
    {
        $stmt = Database::conn()->prepare(
            "SELECT u.*, r.nombre AS rol
               FROM users u
               JOIN roles r ON r.id = u.role_id
              WHERE u.username = ?"
        );
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function porId(int $id): ?array
    {
        $stmt = Database::conn()->prepare(
            "SELECT u.*, r.nombre AS rol
               FROM users u
               JOIN roles r ON r.id = u.role_id
              WHERE u.id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function todos(): array
    {
        return Database::conn()->query(
            "SELECT u.id, u.username, u.nombre_completo, u.email, u.activo,
                    r.nombre AS rol, u.creado_en
               FROM users u
               JOIN roles r ON r.id = u.role_id
              ORDER BY u.id"
        )->fetchAll();
    }

    public static function roles(): array
    {
        return Database::conn()->query(
            "SELECT id, nombre, descripcion FROM roles ORDER BY id"
        )->fetchAll();
    }

    /** Crea un usuario. $passwordHash ya viene hasheado por el controlador. */
    public static function crear(array $d): int
    {
        $stmt = Database::conn()->prepare(
            "INSERT INTO users (username, nombre_completo, email, password_hash, role_id, activo, student_id)
             VALUES (?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $d['username'], $d['nombre_completo'], $d['email'],
            $d['password_hash'], $d['role_id'], $d['activo'] ?? 1,
            $d['student_id'] ?? null,
        ]);
        return (int)Database::conn()->lastInsertId();
    }

    public static function actualizar(int $id, array $d): void
    {
        $stmt = Database::conn()->prepare(
            "UPDATE users
                SET nombre_completo = ?, email = ?, role_id = ?, activo = ?
              WHERE id = ?"
        );
        $stmt->execute([
            $d['nombre_completo'], $d['email'], $d['role_id'], $d['activo'], $id,
        ]);
    }

    public static function cambiarPassword(int $id, string $passwordHash): void
    {
        $stmt = Database::conn()->prepare(
            "UPDATE users SET password_hash = ? WHERE id = ?"
        );
        $stmt->execute([$passwordHash, $id]);
    }

    public static function fijarActivo(int $id, int $activo): void
    {
        $stmt = Database::conn()->prepare("UPDATE users SET activo = ? WHERE id = ?");
        $stmt->execute([$activo, $id]);
    }

    public static function existeUsername(string $username): bool
    {
        $stmt = Database::conn()->prepare("SELECT 1 FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return (bool)$stmt->fetchColumn();
    }
}
