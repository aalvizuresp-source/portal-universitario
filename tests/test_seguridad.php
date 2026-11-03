<?php
/**
 * ============================================================================
 *  test_seguridad.php  (versión segura)
 * ============================================================================
 *  Pruebas automatizadas en PHP CLI (sin PHPUnit) que verifican los controles
 *  de seguridad, usando — cuando aplica — las mismas entradas que explotaban la
 *  versión vulnerable.
 *
 *  Uso (desde la raíz del proyecto):
 *      php tests/test_seguridad.php
 *
 *  ATENCIÓN: reinicia la base de datos antes de ejecutarse.
 *  Cada [OK] significa que el control funciona.
 * ============================================================================
 */

declare(strict_types=1);

require __DIR__ . '/../src/config/config.php';
require __DIR__ . '/../src/config/Database.php';
require __DIR__ . '/../src/helpers/functions.php';
require __DIR__ . '/../src/helpers/security.php';
require __DIR__ . '/../src/models/StudentModel.php';
require __DIR__ . '/../src/models/UserModel.php';
require __DIR__ . '/../src/models/AcademicModel.php';
require __DIR__ . '/../src/models/CommentModel.php';
require __DIR__ . '/../src/models/DocumentModel.php';
require __DIR__ . '/../src/services/ReportService.php';

$GLOBALS['__ok'] = 0;
$GLOBALS['__fail'] = 0;

function afirmar(string $n, bool $c, string $d = ''): void {
    if ($c) { echo "  [OK]    $n" . ($d ? "  — $d" : '') . PHP_EOL; $GLOBALS['__ok']++; }
    else    { echo "  [FALLA] $n" . ($d ? "  — $d" : '') . PHP_EOL; $GLOBALS['__fail']++; }
}
function titulo(string $t): void { echo PHP_EOL . "== $t ==" . PHP_EOL; }

/** Simula una sesión iniciada con el usuario indicado (sin HTTP). */
function como(string $username): void {
    $u = UserModel::porUsername($username);
    $_SESSION = [];
    establecer_usuario($u);
}

echo "Reiniciando la base de datos para las pruebas..." . PHP_EOL;
$nulo = stripos(PHP_OS, 'WIN') === 0 ? 'NUL' : '/dev/null';
passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/../scripts/reset_lab.php') . " > $nulo 2>&1", $rc);
if ($rc !== 0) { echo "No se pudo reiniciar la base de datos.\n"; exit(1); }
Database::reset();

$sid = fn(string $carne) => (int)StudentModel::porCarne($carne)['id'];

// ---------------------------------------------------------------------------
titulo('SQL Injection — consultas preparadas');
$normal = StudentModel::buscar('Ana');
afirmar('Búsqueda "Ana" devuelve resultados', count($normal) >= 1, 'filas=' . count($normal));
$iny = StudentModel::buscar("' OR '1'='1");
afirmar("La inyección \"' OR '1'='1\" no devuelve todos los registros",
    count($iny) === 0, 'filas=' . count($iny) . ' (se trata como texto literal)');
afirmar("La comilla de \"a'b\" no rompe la consulta", is_array(StudentModel::buscar("a'b")));
afirmar('La búsqueda incluye la carrera', count(StudentModel::buscar('Industrial')) >= 1);

// ---------------------------------------------------------------------------
titulo('Contraseñas — password_hash con sal + migración');
$h1 = lab_hash_password('Estudiante123');
$h2 = lab_hash_password('Estudiante123');
afirmar('La misma contraseña produce hashes distintos (sal)', $h1 !== $h2);
afirmar('El hash usa una KDF ($argon2id$ o $2y$)',
    str_starts_with($h1, '$argon2id$') || str_starts_with($h1, '$2y$'), substr($h1, 0, 10));
afirmar('Se acepta la contraseña correcta', password_verify('Estudiante123', $h1));
afirmar('Se rechaza una contraseña incorrecta', !password_verify('otra', $h1));

$estu2 = UserModel::porUsername('estu2');
afirmar('estu2 parte de un hash heredado', lab_es_hash_legado($estu2['password_hash']));
if (lab_verificar_hash_legado('Estudiante123', $estu2['password_hash'])) {
    UserModel::cambiarPassword((int)$estu2['id'], lab_hash_password('Estudiante123'));
}
afirmar('Tras verificarlo, estu2 queda con hash moderno',
    !lab_es_hash_legado(UserModel::porUsername('estu2')['password_hash']));

// ---------------------------------------------------------------------------
titulo('Inyección de comandos — sin intérprete de comandos');
$evid = REPORTS_PATH . DIRECTORY_SEPARATOR . 'evidencia_prueba.txt';
if (is_file($evid)) { unlink($evid); }
$payload = stripos(PHP_OS, 'WIN') === 0
    ? 'informe & echo X > "' . $evid . '"'
    : 'x"; echo X > ' . escapeshellarg($evid) . '; echo "y';
$etiqueta = ReportService::generarResumen($payload);
clearstatcache();
afirmar('La etiqueta maliciosa no ejecuta ningún comando', !is_file($evid));
afirmar('La etiqueta queda depurada (sin & ; > " \')', !preg_match('/[&;>"\']/', $etiqueta), "\"$etiqueta\"");

// ---------------------------------------------------------------------------
titulo('XSS — codificación de salida');
$esc = e("<script>alert('XSS')</script>");
afirmar('e() neutraliza <script>', !str_contains($esc, '<script>') && str_contains($esc, '&lt;script&gt;'));

// ---------------------------------------------------------------------------
titulo('Validación de entradas');
$casos = [
    ['email',           'esto-no-es-correo', false], ['email', 'juangarcia.com', false], ['email', 'ana@uni.edu.gt', true],
    ['carne',           '2561', false],              ['carne', '2021-0001', true],
    ['nota',            '150', false], ['nota', '-5', false], ['nota', 'abc', false], ['nota', '85', true],
    ['fechaNacimiento', '0011-05-20', false], ['fechaNacimiento', '2003-02-30', false],
    ['fechaNacimiento', '2099-01-01', false], ['fechaNacimiento', '2003-04-12', true],
    ['telefono',        'hola', false], ['telefono', '12', false], ['telefono', '5512-0001', true],
    ['nombre',          '<b>Ana</b>', false], ['nombre', 'María José', true],
];
foreach ($casos as [$metodo, $valor, $valido]) {
    $v = new Validador();
    $r = $v->$metodo($valor);
    afirmar(sprintf('%s("%s") %s', $metodo, $valor, $valido ? 'se acepta' : 'se rechaza'),
        $valido ? $v->ok() && $r !== null : !$v->ok());
}

// ---------------------------------------------------------------------------
titulo('Control de acceso por rol');
como('estu1');
afirmar('Estudiante: accede a su propio expediente', puede_acceder_estudiante($sid('2021-0001')));
afirmar('Estudiante: NO accede a un expediente ajeno', !puede_acceder_estudiante($sid('2021-0003')));
$docs = DocumentModel::listaDeEstudiante($sid('2021-0001'));
afirmar('Estudiante: sólo lista sus documentos',
    count($docs) >= 1 && count(array_filter($docs, fn($d) => (int)$d['student_id'] !== $sid('2021-0001'))) === 0);
CommentModel::crear((int)UserModel::porUsername('docente1')['id'], $sid('2021-0002'), null,
    'Comentario privado sobre Luis (prueba)');
$com = CommentModel::visiblesParaEstudiante($sid('2021-0001'), (int)UserModel::porUsername('estu1')['id']);
$ajenos = array_filter($com, fn($c) => $c['estudiante'] !== null && $c['estudiante'] !== 'Ana García López');
afirmar('Estudiante: ve los comentarios que le corresponden', count($com) >= 2, 'visibles=' . count($com));
afirmar('Estudiante: no ve comentarios sobre otros estudiantes', count($ajenos) === 0);

como('docente1');   // MAT101 y PRG101: estudiantes 2021-0001 y 2021-0002
afirmar('Docente: accede a estudiantes de sus cursos', puede_acceder_estudiante($sid('2021-0001')));
afirmar('Docente: NO accede a estudiantes de otros cursos', !puede_acceder_estudiante($sid('2021-0003')));
afirmar('Docente: no tiene asignado IND101',
    !AcademicModel::docenteTieneCurso((int)id_usuario_actual(), (int)Database::conn()
        ->query("SELECT id FROM courses WHERE codigo='IND101'")->fetchColumn()));

como('admin');
afirmar('Administrador: accede a cualquier estudiante', puede_acceder_estudiante($sid('2021-0003')));

// ---------------------------------------------------------------------------
titulo('Cifrado del dato sensible (Sodium)');
$c = lab_cifrar('5512-9999');
afirmar('El texto cifrado no contiene el valor original', is_string($c) && !str_contains($c, '5512-9999'));
afirmar('El descifrado recupera el valor', lab_descifrar($c) === '5512-9999');
$raw = Database::conn()->query("SELECT telefono_emergencia FROM students WHERE carne='2021-0001'")->fetchColumn();
afirmar('En la base de datos el teléfono no es legible', !preg_match('/^\d{4}-\d{4}$/', (string)$raw));

// ---------------------------------------------------------------------------
titulo('Secretos fuera del código');
$config = file_get_contents(SRC_PATH . '/config/config.php');
afirmar('config.php no contiene la clave sk_lab_', !str_contains($config, 'sk_lab_'));
afirmar('config.php obtiene el secreto con lab_secreto()', str_contains($config, "lab_secreto('APP_SECRET_KEY'"));

// ---------------------------------------------------------------------------
titulo('Registro seguro');
$_SESSION = [];
registrar_evento('PRUEBA', "linea1\nFALSO_EVENTO user=admin", 'OK');
$ultima = Database::conn()->query("SELECT descripcion FROM audit_logs ORDER BY id DESC LIMIT 1")->fetchColumn();
afirmar('Los saltos de línea se eliminan (no se pueden falsificar entradas)', !str_contains($ultima, "\n"));

echo PHP_EOL . str_repeat('=', 60) . PHP_EOL;
printf("RESULTADO: %d comprobaciones OK, %d fallas%s", $GLOBALS['__ok'], $GLOBALS['__fail'], PHP_EOL);
exit($GLOBALS['__fail'] > 0 ? 1 : 0);
