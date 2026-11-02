<?php
/**
 * reset_lab.php — Reinicia por completo el laboratorio.
 *
 * Uso (desde la raíz del proyecto):
 *     php scripts/reset_lab.php
 *
 * 1. Borra el archivo university.db (si existe).
 * 2. Vuelve a aplicar el esquema  (init_db.php).
 * 3. Vuelve a poblar datos ficticios (seed.php).
 * 4. Limpia uploads/reports/logs generados durante las pruebas,
 *    conservando el archivo .gitkeep de cada carpeta.
 *
 * Es seguro ejecutarlo tantas veces como se quiera: deja el laboratorio
 * en un estado limpio y conocido.
 */

declare(strict_types=1);

require __DIR__ . '/../src/config/config.php';

echo "[reset] Reiniciando laboratorio LabSeguridad..." . PHP_EOL;

// 1. Eliminar la base de datos.
if (is_file(DB_PATH)) {
    unlink(DB_PATH);
    echo "[reset] Base de datos eliminada." . PHP_EOL;
}

// 4. Limpiar carpetas de almacenamiento (menos .gitkeep).
foreach ([UPLOADS_PATH, REPORTS_PATH, LOGS_PATH] as $dir) {
    if (!is_dir($dir)) { continue; }
    foreach (glob($dir . DIRECTORY_SEPARATOR . '*') as $f) {
        if (is_file($f) && basename($f) !== '.gitkeep') {
            unlink($f);
        }
    }
    echo "[reset] Carpeta limpiada: " . basename($dir) . PHP_EOL;
}

// 2. Reaplicar esquema.
echo "[reset] Aplicando esquema..." . PHP_EOL;
passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/init_db.php'), $rc1);
if ($rc1 !== 0) { fwrite(STDERR, "ERROR en init_db.php\n"); exit(1); }

// 3. Reponer datos.
echo "[reset] Reponiendo datos ficticios..." . PHP_EOL;
passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/seed.php'), $rc2);
if ($rc2 !== 0) { fwrite(STDERR, "ERROR en seed.php\n"); exit(1); }

echo "[reset] Laboratorio reiniciado correctamente." . PHP_EOL;
