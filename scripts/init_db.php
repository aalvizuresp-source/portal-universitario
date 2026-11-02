<?php
/**
 * init_db.php — Inicializa la base de datos SQLite ejecutando schema.sql.
 *
 * Uso (desde la raíz del proyecto):
 *     php scripts/init_db.php
 *
 * Crea/recrea el archivo database/university.db con todas las tablas.
 * NO inserta datos ficticios; eso lo hace seed.php.
 */

declare(strict_types=1);

require __DIR__ . '/../src/config/config.php';
require __DIR__ . '/../src/config/Database.php';

$schemaFile = APP_ROOT . '/database/schema.sql';

if (!is_file($schemaFile)) {
    fwrite(STDERR, "ERROR: no se encontró schema.sql en $schemaFile\n");
    exit(1);
}

echo "[init_db] Usando base de datos: " . DB_PATH . PHP_EOL;

$sql = file_get_contents($schemaFile);
if ($sql === false) {
    fwrite(STDERR, "ERROR: no se pudo leer schema.sql\n");
    exit(1);
}

try {
    $pdo = Database::conn();
    // exec() de PDO-SQLite acepta múltiples sentencias separadas por ';'.
    $pdo->exec($sql);
    echo "[init_db] Esquema aplicado correctamente." . PHP_EOL;

    // Verificación: listar tablas creadas.
    $tablas = $pdo->query(
        "SELECT name FROM sqlite_master WHERE type='table' ORDER BY name"
    )->fetchAll(PDO::FETCH_COLUMN);

    echo "[init_db] Tablas creadas (" . count($tablas) . "): "
        . implode(', ', $tablas) . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, "ERROR al aplicar el esquema: " . $e->getMessage() . "\n");
    exit(1);
}

echo "[init_db] Listo. Ahora ejecute:  php scripts/seed.php" . PHP_EOL;
