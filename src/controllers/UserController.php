<?php
/**
 * UserController — administración de usuarios (sólo admin).
 */

declare(strict_types=1);

final class UserController
{
    public function index(): void
    {
        requerir_rol(['admin']);
        render_vista('users/index', [
            'titulo_pagina' => 'Usuarios',
            'usuarios'      => UserModel::todos(),
        ]);
    }

    public function nuevo(): void
    {
        requerir_rol(['admin']);
        render_vista('users/form', [
            'titulo_pagina' => 'Nuevo usuario',
            'usuario'       => null,
            'roles'         => UserModel::roles(),
            'accion'        => url('users/create'),
        ]);
    }

    public function crear(): void
    {
        requerir_rol(['admin']);
        $username = (string)post('username', '');
        $nombre   = (string)post('nombre_completo', '');
        $email    = (string)post('email', '');
        $password = (string)post('password', '');
        $roleId   = (int)post('role_id', 0);

        $v = new Validador();
        $email  = $v->email($email);
        $nombre = $v->nombre($nombre, 'nombre completo');
        if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $username)) {
            $v->errores[] = 'Usuario inválido (3–30 caracteres: letras, números, . _ -).';
        }
        if (mb_strlen($password) < 8) {
            $v->errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        }
        if ($roleId === 0) {
            $v->errores[] = 'Seleccione un rol.';
        }
        if (!$v->ok()) {
            flash('error', $v->primerError());
            redirigir(url('users/new'));
        }
        if (UserModel::existeUsername($username)) {
            flash('error', 'El nombre de usuario ya existe.');
            redirigir(url('users/new'));
        }

        UserModel::crear([
            'username'        => $username,
            'nombre_completo' => $nombre,
            'email'           => $email,
            'password_hash'   => lab_hash_password($password),
            'role_id'         => $roleId,
            'activo'          => 1,
            'student_id'      => null,
        ]);
        registrar_evento('USER_CREATE', "Alta de usuario {$username}", 'OK');
        flash('ok', 'Usuario creado.');
        redirigir(url('users'));
    }

    public function editar(): void
    {
        requerir_rol(['admin']);
        $id = (int)get('id', 0);
        $usuario = UserModel::porId($id);
        if (!$usuario) {
            flash('error', 'Usuario no encontrado.');
            redirigir(url('users'));
        }
        render_vista('users/form', [
            'titulo_pagina' => 'Editar usuario',
            'usuario'       => $usuario,
            'roles'         => UserModel::roles(),
            'accion'        => url('users/update') . '&id=' . $id,
        ]);
    }

    public function actualizar(): void
    {
        requerir_rol(['admin']);
        $id = (int)get('id', 0);
        if (!UserModel::porId($id)) {
            flash('error', 'Usuario no encontrado.');
            redirigir(url('users'));
        }
        UserModel::actualizar($id, [
            'nombre_completo' => (string)post('nombre_completo', ''),
            'email'           => (string)post('email', ''),
            'role_id'         => (int)post('role_id', 0),
            'activo'          => (int)post('activo', 1),
        ]);
        registrar_evento('USER_UPDATE', "Edición de usuario id=$id", 'OK');
        flash('ok', 'Usuario actualizado.');
        redirigir(url('users'));
    }

    public function toggle(): void
    {
        requerir_rol(['admin']);
        $id = (int)get('id', 0);
        $u = UserModel::porId($id);
        if ($u) {
            $nuevo = (int)$u['activo'] === 1 ? 0 : 1;
            UserModel::fijarActivo($id, $nuevo);
            registrar_evento('USER_TOGGLE',
                "Usuario id=$id " . ($nuevo ? 'activado' : 'desactivado'), 'OK');
            flash('ok', 'Estado del usuario actualizado.');
        }
        redirigir(url('users'));
    }
}
