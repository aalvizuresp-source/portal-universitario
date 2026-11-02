<?php
/**
 * seed.php — Inserta datos ficticios reproducibles en la base de datos.
 *
 * Uso (desde la raíz del proyecto):
 *     php scripts/seed.php
 *
 * Crea: roles, usuarios (admin/docente/estudiante), carreras, cursos,
 * asignaciones docente-curso, matrículas, calificaciones, comentarios y
 * documentos de muestra.  TODO es ficticio y exclusivo del laboratorio.
 *
 * Contraseñas de demostración (ver docs/README.md):
 *     admin    / Admin123
 *     docente1 / Docente123
 *     docente2 / Docente123
 *     estu1    / Estudiante123   (carné 2021-0001)
 *     estu2    / Estudiante123   (carné 2021-0002)
 *
 */

declare(strict_types=1);

require __DIR__ . '/../src/config/config.php';
require __DIR__ . '/../src/config/Database.php';
require __DIR__ . '/../src/helpers/security.php';
require __DIR__ . '/pdf_simple.php';

$pdo = Database::conn();

echo "[seed] Limpiando datos previos..." . PHP_EOL;
// Orden inverso a las dependencias para respetar las FK.
foreach (['audit_logs','documents','comments','grades','enrollments',
          'teacher_courses','courses','students','careers','users','roles'] as $t) {
    $pdo->exec("DELETE FROM $t");
    // Reinicia los AUTOINCREMENT si existe la tabla de secuencias.
    $pdo->exec("DELETE FROM sqlite_sequence WHERE name='$t'");
}

$pdo->beginTransaction();

try {
    // --- roles -------------------------------------------------------------
    $roles = [
        ['admin',      'Administrador del sistema'],
        ['docente',    'Docente / catedrático'],
        ['estudiante', 'Estudiante'],
    ];
    $stmt = $pdo->prepare("INSERT INTO roles (nombre, descripcion) VALUES (?, ?)");
    foreach ($roles as $r) { $stmt->execute($r); }

    $roleId = [];
    foreach ($pdo->query("SELECT id, nombre FROM roles") as $row) {
        $roleId[$row['nombre']] = (int)$row['id'];
    }

    // --- carreras ----------------------------------------------------------
    $careers = [
        ['ISIS', 'Ingeniería en Sistemas',       'Ingeniería'],
        ['IIND', 'Ingeniería Industrial',        'Ingeniería'],
        ['LADM', 'Licenciatura en Administración','Ciencias Económicas'],
    ];
    $stmt = $pdo->prepare("INSERT INTO careers (codigo, nombre, facultad) VALUES (?,?,?)");
    foreach ($careers as $c) { $stmt->execute($c); }

    $careerId = [];
    foreach ($pdo->query("SELECT id, codigo FROM careers") as $row) {
        $careerId[$row['codigo']] = (int)$row['id'];
    }

    // --- estudiantes (fichas) ---------------------------------------------
    // telefono_emergencia es el dato sensible que la ETAPA 2 cifrará con Sodium.
    $students = [
        ['2021-0001','Ana','García López','ana.garcia@lab.edu.gt','2003-04-12',$careerId['ISIS'],'activo','5512-0001'],
        ['2021-0002','Luis','Martínez Pérez','luis.martinez@lab.edu.gt','2002-11-30',$careerId['ISIS'],'activo','5512-0002'],
        ['2021-0003','María','Hernández Ruiz','maria.hernandez@lab.edu.gt','2003-07-08',$careerId['IIND'],'activo','5512-0003'],
        ['2020-0010','Carlos','Díaz Morales','carlos.diaz@lab.edu.gt','2001-01-22',$careerId['IIND'],'activo','5512-0004'],
        ['2020-0011','Sofía','Ramírez Castro','sofia.ramirez@lab.edu.gt','2002-05-17',$careerId['LADM'],'activo','5512-0005'],
        ['2019-0020','Jorge','Flores Mejía','jorge.flores@lab.edu.gt','2000-09-03',$careerId['LADM'],'egresado','5512-0006'],
    ];
    $stmt = $pdo->prepare(
        "INSERT INTO students (carne, nombres, apellidos, email, fecha_nacimiento, career_id, estado, telefono_emergencia)
         VALUES (?,?,?,?,?,?,?,?)"
    );
    foreach ($students as $s) { $stmt->execute($s); }

    $studentIdByCarne = [];
    foreach ($pdo->query("SELECT id, carne FROM students") as $row) {
        $studentIdByCarne[$row['carne']] = (int)$row['id'];
    }

    // --- usuarios ----------------------------------------------------------
    // password_hash = SHA-256 sin sal (V05).
    $users = [
        // username, nombre, email, password, role, student_carne(o null)
        ['admin',    'Administrador General', 'admin@lab.edu.gt',    'Admin123',     'admin',      null],
        ['docente1', 'Pedro Soto',           'pedro.soto@lab.edu.gt','Docente123',  'docente',    null],
        ['docente2', 'Rosa Vega',            'rosa.vega@lab.edu.gt', 'Docente123',  'docente',    null],
        ['estu1',    'Ana García López',      'ana.garcia@lab.edu.gt','Estudiante123','estudiante','2021-0001'],
        ['estu2',    'Luis Martínez Pérez',   'luis.martinez@lab.edu.gt','Estudiante123','estudiante','2021-0002'],
    ];
    $stmt = $pdo->prepare(
        "INSERT INTO users (username, nombre_completo, email, password_hash, role_id, activo, student_id)
         VALUES (?,?,?,?,?,1,?)"
    );
    foreach ($users as $u) {
        [$username,$nombre,$email,$pass,$rol,$carne] = $u;
        $sid = $carne !== null ? $studentIdByCarne[$carne] : null;
        $stmt->execute([
            $username, $nombre, $email,
            lab_hash_password($pass),
            $roleId[$rol], $sid,
        ]);
    }

    $userIdByName = [];
    foreach ($pdo->query("SELECT id, username FROM users") as $row) {
        $userIdByName[$row['username']] = (int)$row['id'];
    }

    // --- cursos ------------------------------------------------------------
    $courses = [
        ['MAT101','Matemática I',          4, $careerId['ISIS']],
        ['PRG101','Programación I',        4, $careerId['ISIS']],
        ['PRG201','Programación II',       4, $careerId['ISIS']],
        ['IND101','Introducción a la Industria',3,$careerId['IIND']],
        ['ADM101','Fundamentos de Administración',3,$careerId['LADM']],
    ];
    $stmt = $pdo->prepare("INSERT INTO courses (codigo, nombre, creditos, career_id) VALUES (?,?,?,?)");
    foreach ($courses as $c) { $stmt->execute($c); }

    $courseIdByCode = [];
    foreach ($pdo->query("SELECT id, codigo FROM courses") as $row) {
        $courseIdByCode[$row['codigo']] = (int)$row['id'];
    }

    // --- teacher_courses ---------------------------------------------------
    $periodo = '2026-S1';
    $tc = [
        ['docente1','MAT101'],
        ['docente1','PRG101'],
        ['docente2','PRG201'],
        ['docente2','IND101'],
    ];
    $stmt = $pdo->prepare(
        "INSERT INTO teacher_courses (teacher_id, course_id, periodo) VALUES (?,?,?)"
    );
    foreach ($tc as $row) {
        $stmt->execute([$userIdByName[$row[0]], $courseIdByCode[$row[1]], $periodo]);
    }

    // --- enrollments -------------------------------------------------------
    $enroll = [
        ['2021-0001','MAT101'],
        ['2021-0001','PRG101'],
        ['2021-0001','PRG201'],
        ['2021-0002','MAT101'],
        ['2021-0002','PRG101'],
        ['2021-0003','IND101'],
        ['2020-0010','IND101'],
        ['2020-0011','ADM101'],
    ];
    $stmt = $pdo->prepare(
        "INSERT INTO enrollments (student_id, course_id, periodo) VALUES (?,?,?)"
    );
    foreach ($enroll as $row) {
        $stmt->execute([$studentIdByCarne[$row[0]], $courseIdByCode[$row[1]], $periodo]);
    }

    $enrollId = [];
    foreach ($pdo->query(
        "SELECT e.id, s.carne, c.codigo
           FROM enrollments e
           JOIN students s ON s.id = e.student_id
           JOIN courses  c ON c.id = e.course_id") as $row) {
        $enrollId[$row['carne'].'|'.$row['codigo']] = (int)$row['id'];
    }

    // --- grades ------------------------------------------------------------
    $grades = [
        ['2021-0001|MAT101', 85, 'docente1'],
        ['2021-0001|PRG101', 92, 'docente1'],
        ['2021-0001|PRG201', 58, 'docente2'],   // reprobado
        ['2021-0002|MAT101', 71, 'docente1'],
        ['2021-0002|PRG101', 64, 'docente1'],
        ['2021-0003|IND101', 88, 'docente2'],
        ['2020-0010|IND101', 45, 'docente2'],   // reprobado
        ['2020-0011|ADM101', 77, 'admin'],
    ];
    $stmt = $pdo->prepare(
        "INSERT INTO grades (enrollment_id, nota, aprobado, periodo, registrado_por)
         VALUES (?,?,?,?,?)"
    );
    foreach ($grades as $g) {
        [$key,$nota,$who] = $g;
        $aprob = $nota >= NOTA_APROBACION ? 1 : 0;
        $stmt->execute([$enrollId[$key], $nota, $aprob, $periodo, $userIdByName[$who]]);
    }

    // --- comments ----------------------------------------------------------
    $comments = [
        [$userIdByName['docente1'], $studentIdByCarne['2021-0001'], null, 'Excelente desempeño en el primer parcial.'],
        [$userIdByName['estu1'],    $studentIdByCarne['2021-0001'], null, 'Gracias por la retroalimentación.'],
        [$userIdByName['docente2'], null, $courseIdByCode['PRG201'], 'Recuerden entregar el proyecto final.'],
    ];
    $stmt = $pdo->prepare(
        "INSERT INTO comments (autor_id, student_id, course_id, contenido) VALUES (?,?,?,?)"
    );
    foreach ($comments as $c) { $stmt->execute($c); }

    // --- documents (registros de muestra + archivos físicos) ---------------
    if (!is_dir(UPLOADS_PATH)) { mkdir(UPLOADS_PATH, 0775, true); }
    $docSamples = [
        ['2021-0001', 'constancia_ana.pdf',   'Constancia de estudios - Ana García López - Carné 2021-0001'],
        ['2021-0002', 'constancia_luis.pdf',  'Constancia de estudios - Luis Martínez Pérez - Carné 2021-0002'],
    ];
    $stmt = $pdo->prepare(
        "INSERT INTO documents (student_id, original_name, stored_name, mime_type, size_bytes, subido_por)
         VALUES (?,?,?,?,?,?)"
    );
    foreach ($docSamples as $d) {
        [$carne,$name,$body] = $d;
        $pdf = pdf_simple($body);
        file_put_contents(UPLOADS_PATH . DIRECTORY_SEPARATOR . $name, $pdf);
        $stmt->execute([
            $studentIdByCarne[$carne], $name, $name,
            'application/pdf', strlen($pdf), $userIdByName['admin'],
        ]);
    }

    // --- audit_logs (una entrada de ejemplo) -------------------------------
    $pdo->prepare(
        "INSERT INTO audit_logs (tipo, user_id, descripcion, ip, resultado)
         VALUES ('SEED', ?, 'Carga inicial de datos', '127.0.0.1', 'OK')"
    )->execute([$userIdByName['admin']]);

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "ERROR durante el seed: " . $e->getMessage() . "\n");
    exit(1);
}

// Resumen
echo "[seed] Datos ficticios insertados:" . PHP_EOL;
foreach (['roles','users','careers','students','courses','teacher_courses',
          'enrollments','grades','comments','documents'] as $t) {
    $n = (int)$pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
    printf("        %-16s %d%s", $t, $n, PHP_EOL);
}
echo "[seed] Listo. Usuarios de demo: admin/Admin123, docente1/Docente123, estu1/Estudiante123" . PHP_EOL;
