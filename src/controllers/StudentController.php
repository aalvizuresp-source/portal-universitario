<?php
/**
 * StudentController — administración de estudiantes (sólo administrador).
 *
 * Listado con paginación, búsqueda por carné/nombre/apellido/carrera y CRUD.
 * Los docentes consultan a sus estudiantes desde Expedientes y Cursos.
 */

declare(strict_types=1);

final class StudentController
{
    public function index(): void
    {
        requerir_rol(['admin']);

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

    public function buscar(): void
    {
        requerir_rol(['admin']);

        $v = new Validador();
        $termino = $v->busqueda((string)get('q', ''));

        $estudiantes = StudentModel::buscar($termino);

        registrar_evento('STUDENT_SEARCH', 'Búsqueda de estudiantes', 'OK');

        render_vista('students/index', [
            'titulo_pagina' => 'Estudiantes · Resultados de búsqueda',
            'estudiantes'   => $estudiantes,
            'pagina'        => 1,
            'totalPaginas'  => 1,
            'total'         => count($estudiantes),
            'termino'       => $termino,
        ]);
    }

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

        $d = $this->validarEntradaEstudiante();
        if ($d === null) {
            redirigir(url('students/new'));
        }
        if (StudentModel::porCarne($d['carne'])) {
            flash('error', 'Ya existe un estudiante con ese carné.');
            redirigir(url('students/new'));
        }

        $id = StudentModel::crear($d);
        registrar_evento('STUDENT_CREATE', "Alta de estudiante id=$id", 'OK');
        flash('ok', 'Estudiante registrado correctamente.');
        redirigir(url('students'));
    }

    public function ver(): void
    {
        requerir_rol(['admin']);
        $id = (int)get('id', 0);
        $estudiante = StudentModel::porId($id);
        if (!$estudiante) {
            flash('error', 'Estudiante no encontrado.');
            redirigir(url('students'));
        }
        registrar_evento('STUDENT_VIEW', "Consulta de ficha id=$id", 'OK');
        render_vista('students/ver', [
            'titulo_pagina' => 'Ficha de estudiante',
            'estudiante'    => $estudiante,
            'expediente'    => AcademicModel::expedienteDe($id),
        ]);
    }

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
        $d = $this->validarEntradaEstudiante();
        if ($d === null) {
            redirigir(url('students/edit') . '&id=' . $id);
        }
        $otro = StudentModel::porCarne($d['carne']);
        if ($otro && (int)$otro['id'] !== $id) {
            flash('error', 'Ya existe otro estudiante con ese carné.');
            redirigir(url('students/edit') . '&id=' . $id);
        }
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

    /**
     * Valida y normaliza el formulario de estudiante.
     * Devuelve los datos saneados, o null (con mensaje flash) si algo no es válido.
     */
    private function validarEntradaEstudiante(): ?array
    {
        $v = new Validador();
        $carne     = $v->carne((string)post('carne', ''));
        $nombres   = $v->nombre((string)post('nombres', ''), 'nombres');
        $apellidos = $v->nombre((string)post('apellidos', ''), 'apellidos');
        $email     = $v->email((string)post('email', ''));
        $fecha     = $v->fechaNacimiento((string)post('fecha_nacimiento', ''));
        $telefono  = $v->telefono((string)post('telefono_emergencia', ''));

        $estado = (string)post('estado', 'activo');
        if (!in_array($estado, ['activo', 'inactivo', 'egresado'], true)) {
            $v->errores[] = 'Estado no válido.';
        }

        $careerId = (string)post('career_id', '');
        if ($careerId !== '') {
            $careerId = $v->id($careerId);
        } else {
            $careerId = null;
        }

        if (!$v->ok()) {
            flash('error', $v->primerError());
            return null;
        }

        return [
            'carne'               => $carne,
            'nombres'             => $nombres,
            'apellidos'           => $apellidos,
            'email'               => $email,
            'fecha_nacimiento'    => $fecha,
            'career_id'           => $careerId,
            'estado'              => $estado,
            'telefono_emergencia' => $telefono,
        ];
    }
}
