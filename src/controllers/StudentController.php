<?php
/**
 * StudentController — administración y búsqueda de estudiantes.
 */

declare(strict_types=1);

final class StudentController
{
    /* ---------------- Listado + paginación ---------------- */
    public function index(): void
    {
        requerir_rol(['admin', 'docente']);

        $pagina = max(1, (int)get('page', 1));
        $total  = StudentModel::contar();
        $offset = ($pagina - 1) * ITEMS_POR_PAGINA;
        $estudiantes = StudentModel::pagina($offset, ITEMS_POR_PAGINA);
        $totalPaginas = max(1, (int)ceil($total / ITEMS_POR_PAGINA));

        render_vista('students/index', [
            'titulo_pagina' => 'Estudiantes',
            'estudiantes'   => $estudiantes,
            'pagina'        => $pagina,
            'totalPaginas'  => $totalPaginas,
            'total'         => $total,
            'termino'       => '',
        ]);
    }

    /* ---------------- Búsqueda ---------------- */
    public function buscar(): void
    {
        requerir_rol(['admin', 'docente']);

        $termino = (string)get('q', '');

        $estudiantes = StudentModel::buscar($termino);

        registrar_evento('STUDENT_SEARCH', "Búsqueda de estudiantes: '{$termino}'", 'OK');

        render_vista('students/index', [
            'titulo_pagina' => 'Estudiantes · Resultados de búsqueda',
            'estudiantes'   => $estudiantes,
            'pagina'        => 1,
            'totalPaginas'  => 1,
            'total'         => count($estudiantes),
            'termino'       => $termino,
        ]);
    }

    /* ---------------- Alta ---------------- */
    public function nuevo(): void
    {
        requerir_rol(['admin']);
        render_vista('students/form', [
            'titulo_pagina' => 'Nuevo estudiante',
            'estudiante'    => null,
            'carreras'      => StudentModel::carreras(),
            'accion'        => url('students/create'),
        ]);
    }

    public function crear(): void
    {
        requerir_rol(['admin']);

        // Se toman los datos tal cual, sin validar formato ni rangos.
        $d = [
            'carne'               => (string)post('carne', ''),
            'nombres'             => (string)post('nombres', ''),
            'apellidos'           => (string)post('apellidos', ''),
            'email'               => (string)post('email', ''),
            'fecha_nacimiento'    => (string)post('fecha_nacimiento', ''),
            'career_id'           => post('career_id') ?: null,
            'estado'              => (string)post('estado', 'activo'),
            'telefono_emergencia' => (string)post('telefono_emergencia', ''),
        ];

        if ($d['carne'] === '' || $d['nombres'] === '' || $d['apellidos'] === '') {
            flash('error', 'Carné, nombres y apellidos son obligatorios.');
            redirigir(url('students/new'));
        }
        if (StudentModel::porCarne($d['carne'])) {
            flash('error', 'Ya existe un estudiante con ese carné.');
            redirigir(url('students/new'));
        }

        $id = StudentModel::crear($d);
        registrar_evento('STUDENT_CREATE', "Alta de estudiante {$d['carne']} (id=$id)", 'OK');
        flash('ok', 'Estudiante registrado correctamente.');
        redirigir(url('students'));
    }

    /* ---------------- Ver ---------------- */
    public function ver(): void
    {
        requerir_rol(['admin', 'docente']);
        $id = (int)get('id', 0);
        $estudiante = StudentModel::porId($id);
        if (!$estudiante) {
            flash('error', 'Estudiante no encontrado.');
            redirigir(url('students'));
        }
        $expediente = AcademicModel::expedienteDe($id);
        render_vista('students/ver', [
            'titulo_pagina' => 'Ficha de estudiante',
            'estudiante'    => $estudiante,
            'expediente'    => $expediente,
        ]);
    }

    /* ---------------- Editar ---------------- */
    public function editar(): void
    {
        requerir_rol(['admin']);
        $id = (int)get('id', 0);
        $estudiante = StudentModel::porId($id);
        if (!$estudiante) {
            flash('error', 'Estudiante no encontrado.');
            redirigir(url('students'));
        }
        render_vista('students/form', [
            'titulo_pagina' => 'Editar estudiante',
            'estudiante'    => $estudiante,
            'carreras'      => StudentModel::carreras(),
            'accion'        => url('students/update') . '&id=' . $id,
        ]);
    }

    public function actualizar(): void
    {
        requerir_rol(['admin']);
        $id = (int)get('id', 0);
        if (!StudentModel::porId($id)) {
            flash('error', 'Estudiante no encontrado.');
            redirigir(url('students'));
        }
        $d = [
            'carne'               => (string)post('carne', ''),
            'nombres'             => (string)post('nombres', ''),
            'apellidos'           => (string)post('apellidos', ''),
            'email'               => (string)post('email', ''),
            'fecha_nacimiento'    => (string)post('fecha_nacimiento', ''),
            'career_id'           => post('career_id') ?: null,
            'estado'              => (string)post('estado', 'activo'),
            'telefono_emergencia' => (string)post('telefono_emergencia', ''),
        ];
        StudentModel::actualizar($id, $d);
        registrar_evento('STUDENT_UPDATE', "Edición de estudiante id=$id", 'OK');
        flash('ok', 'Estudiante actualizado.');
        redirigir(url('students'));
    }

    public function eliminar(): void
    {
        requerir_rol(['admin']);
        $id = (int)get('id', 0);
        StudentModel::eliminar($id);
        registrar_evento('STUDENT_DELETE', "Eliminación de estudiante id=$id", 'OK');
        flash('ok', 'Estudiante eliminado.');
        redirigir(url('students'));
    }
}
