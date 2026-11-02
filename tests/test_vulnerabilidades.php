<?php
/**
 * ============================================================================
 *  test_vulnerabilidades.php  (versión vulnerable)
 * ============================================================================
 *  Pruebas automatizadas en PHP CLI (sin PHPUnit) que demuestran que cada
 *  vulnerabilidad de la versión 1.0 es reproducible.
 *
 *  Uso (desde la raíz del proyecto):
 *      php tests/test_vulnerabilidades.php
 *
 *  ATENCIÓN: reinicia la base de datos antes de ejecutarse.
 *  Cada [OK] significa que la vulnerabilidad se reproduce.
 * ============================================================================
 */

declare(strict_types=1);

require __DIR__ . '/../src/config/config.php';
require __DIR__ . '/../src/config/Database.php';
require __DIR__ . '/../src/helpers/security.php';
require __DIR__ . '/../src/models/StudentModel.php';
require __DIR__ . '/../src/services/ReportService.php';

$GLOBALS['__ok'] = 0;
$GLOBALS['__fail'] = 0;

function afirmar(string $n, bool $c, string $d = ''): void {
    if ($c) { echo "  [OK]    $n" . ($d ? "  — $d" : '') . PHP_EOL; $GLOBALS['__ok']++; }
    else    { echo "  [FALLA] $n" . ($d ? "  — $d" : '') . PHP_EOL; $GLOBALS['__fail']++; }
}
function titulo(string $t): void { echo PHP_EOL . "== $t ==" . PHP_EOL; }

echo "Reiniciando la base de datos para las pruebas..." . PHP_EOL;
$nulo = stripos(PHP_OS, 'WIN') === 0 ? 'NUL' : '/dev/null';
passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/../scripts/reset_lab.php') . " > $nulo 2>&1", $rc);
if ($rc !== 0) { echo "No se pudo reiniciar la base de datos.\n"; exit(1); }
Database::reset();

// ---------------------------------------------------------------------------
titulo('V02 · SQL Injection en la búsqueda de estudiantes');
$normal = StudentModel::buscar('Ana');
afirmar('Búsqueda "Ana" devuelve un resultado', count($normal) === 1, 'filas=' . count($normal));
$total = StudentModel::contar();
$iny = StudentModel::buscar("' OR '1'='1");
afirmar("La inyección \"' OR '1'='1\" devuelve TODOS los estudiantes",
    count($iny) === $total, 'filas=' . count($iny) . ' de ' . $total);
echo "  Consulta construida: ... WHERE s.nombres LIKE '%' OR '1'='1%' OR ..." . PHP_EOL;
$error = null;
try { StudentModel::buscar("a'b"); } catch (Throwable $e) { $error = $e->getMessage(); }
afirmar("La comilla de \"a'b\" rompe la sintaxis SQL", $error !== null, (string)$error);

// ---------------------------------------------------------------------------
titulo('V05 · Contraseñas con SHA-256 sin sal');
afirmar('La misma contraseña produce siempre el mismo hash',
    lab_hash_password('Estudiante123') === lab_hash_password('Estudiante123'));
$admin = Database::conn()->query("SELECT password_hash FROM users WHERE username='admin'")->fetchColumn();
afirmar('El hash del admin es sha256("Admin123")', hash('sha256', 'Admin123') === $admin);
$hs = Database::conn()->query("SELECT password_hash FROM users WHERE username IN ('estu1','estu2')")
    ->fetchAll(PDO::FETCH_COLUMN);
afirmar('estu1 y estu2 (misma contraseña) tienen el mismo hash', $hs[0] === $hs[1]);

// ---------------------------------------------------------------------------
titulo('V04 · Inyección de comandos en el resumen de reportes');
$evid = REPORTS_PATH . DIRECTORY_SEPARATOR . 'evidencia_prueba.txt';
if (is_file($evid)) { unlink($evid); }
$payload = stripos(PHP_OS, 'WIN') === 0
    ? 'informe & echo DEMO_INJECCION > "' . $evid . '" & echo listo'
    : 'x"; echo DEMO_INJECCION > ' . escapeshellarg($evid) . '; echo "y';
ReportService::generarResumen($payload);
clearstatcache();
afirmar('La etiqueta inyectada ejecutó un comando adicional', is_file($evid),
    is_file($evid) ? 'contenido: ' . trim((string)file_get_contents($evid)) : '');

// ---------------------------------------------------------------------------
titulo('V07 · Registro inseguro');
$clave = 'ClaveSecreta_prueba_123';
Database::conn()->prepare(
    "INSERT INTO audit_logs (tipo, user_id, descripcion, ip, resultado) VALUES ('LOGIN_FAIL', NULL, ?, '127.0.0.1', 'ERROR')"
)->execute(["Intento fallido. usuario='admin' password='{$clave}'"]);
$desc = Database::conn()->query("SELECT descripcion FROM audit_logs WHERE tipo='LOGIN_FAIL' ORDER BY id DESC LIMIT 1")->fetchColumn();
afirmar('El registro de intentos fallidos contiene la contraseña en claro', str_contains($desc, $clave));
$auth = file_get_contents(SRC_PATH . '/controllers/AuthController.php');
afirmar('AuthController escribe la contraseña en el registro', str_contains($auth, "password='{\$password}'"));

// ---------------------------------------------------------------------------
titulo('V06 · Secreto escrito en el código');
$config = file_get_contents(SRC_PATH . '/config/config.php');
afirmar('config.php contiene la clave sk_lab_', str_contains($config, 'sk_lab_7f3d9a1c8e5b4206bdfa91c3e7d20f45'));

// ---------------------------------------------------------------------------
titulo('Datos sensibles en claro');
$tel = Database::conn()->query("SELECT telefono_emergencia FROM students WHERE carne='2021-0001'")->fetchColumn();
afirmar('El teléfono de emergencia se guarda legible', $tel === '5512-0001', (string)$tel);

echo PHP_EOL . str_repeat('=', 60) . PHP_EOL;
printf("RESULTADO: %d comprobaciones OK, %d fallas%s", $GLOBALS['__ok'], $GLOBALS['__fail'], PHP_EOL);
exit($GLOBALS['__fail'] > 0 ? 1 : 0);
