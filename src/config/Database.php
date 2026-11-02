<?php
/**
 * Database — envoltura ligera sobre PDO + SQLite.
 *
 * Expone una única conexión compartida (patrón singleton sencillo).
 * Activa PRAGMA foreign_keys = ON en cada conexión (SQLite las desactiva
 * por defecto, así que las FK del esquema sólo se respetan si se activan aquí).
 */

declare(strict_types=1);

final class Database
{
    private static ?PDO $pdo = null;

    /** Devuelve la conexión PDO compartida, creándola la primera vez. */
    public static function conn(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        if (!is_dir(dirname(DB_PATH))) {
            mkdir(dirname(DB_PATH), 0775, true);
        }

        $dsn = 'sqlite:' . DB_PATH;
        $pdo = new PDO($dsn, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        // Integridad referencial + espera si la BD está bloqueada.
        $pdo->exec('PRAGMA foreign_keys = ON;');
        $pdo->exec('PRAGMA busy_timeout = 5000;');

        self::$pdo = $pdo;
        return self::$pdo;
    }

    /** Útil para los tests: fuerza una reconexión. */
    public static function reset(): void
    {
        self::$pdo = null;
    }
}
