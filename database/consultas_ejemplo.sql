-- ============================================================================
--  consultas_ejemplo.sql — Consultas de ejemplo sobre el modelo relacional
--  Ejecútelas con:  php scripts/consulta.php  "SELECT ..."   (ver abajo)
--  o desde cualquier herramienta que abra database/university.db.
-- ============================================================================

-- 1) Todos los estudiantes con su carrera
SELECT s.carne, s.nombres, s.apellidos, c.nombre AS carrera, s.estado
FROM students s
LEFT JOIN careers c ON c.id = s.career_id
ORDER BY s.apellidos;

-- 2) Expediente (calificaciones) de un estudiante por carné
SELECT cu.codigo, cu.nombre AS curso, e.periodo, g.nota,
       CASE WHEN g.aprobado = 1 THEN 'Aprobado' ELSE 'Reprobado' END AS estado
FROM students s
JOIN enrollments e ON e.student_id = s.id
JOIN courses cu    ON cu.id = e.course_id
LEFT JOIN grades g ON g.enrollment_id = e.id
WHERE s.carne = '2021-0001';

-- 3) Cursos asignados a cada docente
SELECT u.username AS docente, cu.codigo, cu.nombre, tc.periodo
FROM teacher_courses tc
JOIN users u    ON u.id = tc.teacher_id
JOIN courses cu ON cu.id = tc.course_id
ORDER BY u.username, cu.codigo;

-- 4) Promedio de notas por curso
SELECT cu.codigo, cu.nombre, ROUND(AVG(g.nota), 2) AS promedio, COUNT(g.id) AS evaluados
FROM grades g
JOIN enrollments e ON e.id = g.enrollment_id
JOIN courses cu    ON cu.id = e.course_id
GROUP BY cu.id
ORDER BY promedio DESC;

-- 5) Cantidad de estudiantes por carrera (activos vs total)
SELECT c.nombre AS carrera,
       COUNT(s.id) AS total,
       SUM(CASE WHEN s.estado = 'activo' THEN 1 ELSE 0 END) AS activos
FROM careers c
LEFT JOIN students s ON s.career_id = c.id
GROUP BY c.id
ORDER BY c.nombre;

-- 6) Últimos eventos de auditoría
SELECT ts, tipo, resultado, descripcion
FROM audit_logs
ORDER BY id DESC
LIMIT 20;

-- 7) Estudiantes reprobados en al menos un curso
SELECT DISTINCT s.carne, s.nombres, s.apellidos, cu.nombre AS curso, g.nota
FROM grades g
JOIN enrollments e ON e.id = g.enrollment_id
JOIN students s    ON s.id = e.student_id
JOIN courses cu    ON cu.id = e.course_id
WHERE g.aprobado = 0
ORDER BY s.apellidos;

-- 8) Documentos por estudiante
SELECT s.carne, s.nombres || ' ' || s.apellidos AS estudiante,
       d.original_name, d.mime_type, d.size_bytes, d.subido_en
FROM documents d
JOIN students s ON s.id = d.student_id
ORDER BY d.subido_en DESC;
