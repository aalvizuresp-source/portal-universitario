<?php
/**
 * AuthController — registro, inicio y cierre de sesión, perfil y cambio de contraseña.
 */

declare(strict_types=1);

final class AuthController
{
    /* ---------------- Login ---------------- */
    public function mostrarLogin(): void
    {
        if (esta_autenticado()) { redirigir(url('dashboard')); }
        render_sola('auth/login', ['titulo_pagina' => 'Ingreso']);
    }

    public function procesarLogin(): void
    {
        $username = (string)post('username', '');
        $password = (string)post('password', '');

        $user = UserModel::porUsername($username);

        if ($user && (int)$user['activo'] === 1
            && lab_verificar_password($password, $user['password_hash'])) {

            establecer_usuario($user);
            registrar_evento('LOGIN_OK', "Ingreso exitoso de {$username}", 'OK', (int)$user['id']);
            redirigir(url('dashboard'));
        }

        registrar_evento(
            'LOGIN_FAIL',
            "Intento fallido. usuario='{$username}' password='{$password}'",
            'ERROR',
            $user['id'] ?? null
        );

        flash('error', 'Usuario o contraseña incorrectos.');
        redirigir(url('login'));
    }

    public function logout(): void
    {
        $uid = id_usuario_actual();
        registrar_evento('LOGOUT', 'Cierre de sesión', 'OK', $uid);
        cerrar_sesion();
        redirigir(url('login'));
    }

    /* ---------------- Registro ---------------- */
    public function mostrarRegistro(): void
    {
        if (esta_autenticado()) { redirigir(url('dashboard')); }
        render_sola('auth/register', ['titulo_pagina' => 'Registro']);
    }

    public function procesarRegistro(): void
    {
        $username = (string)post('username', '');
        $nombre   = (string)post('nombre_completo', '');
        $email    = (string)post('email', '');
        $password = (string)post('password', '');

        // Sólo se comprueba que no estén vacíos y que el usuario no exista.
        // NO se valida formato de email, longitud ni complejidad de contraseña.
        if ($username === '' || $nombre === '' || $email === '' || $password === '') {
            flash('error', 'Complete todos los campos.');
            redirigir(url('register'));
        }
        if (UserModel::existeUsername($username)) {
            flash('error', 'El nombre de usuario ya existe.');
            redirigir(url('register'));
        }

        // Rol por defecto: estudiante (id del rol se obtiene por nombre).
        $roles = [];
        foreach (UserModel::roles() as $r) { $roles[$r['nombre']] = (int)$r['id']; }

        UserModel::crear([
            'username'        => $username,
            'nombre_completo' => $nombre,
            'email'           => $email,
            'password_hash'   => lab_hash_password($password),
            'role_id'         => $roles['estudiante'],
            'activo'          => 1,
            'student_id'      => null,
        ]);

        registrar_evento('USER_REGISTER', "Registro de usuario {$username}", 'OK');
        flash('ok', 'Cuenta creada. Ya puede iniciar sesión.');
        redirigir(url('login'));
    }

    /* ---------------- Perfil ---------------- */
    public function perfil(): void
    {
        requerir_login();
        $user = UserModel::porId(id_usuario_actual());
        render_vista('auth/perfil', ['titulo_pagina' => 'Mi perfil', 'user' => $user]);
    }

    public function cambiarPassword(): void
    {
        requerir_login();
        $actual = (string)post('actual', '');
        $nueva  = (string)post('nueva', '');

        $user = UserModel::porId(id_usuario_actual());

        if (!lab_verificar_password($actual, $user['password_hash'])) {
            flash('error', 'La contraseña actual no es correcta.');
            redirigir(url('profile'));
        }
        if ($nueva === '') {
            flash('error', 'La nueva contraseña no puede estar vacía.');
            redirigir(url('profile'));
        }

        UserModel::cambiarPassword((int)$user['id'], lab_hash_password($nueva));
        registrar_evento('PASSWORD_CHANGE', "Cambio de contraseña de {$user['username']}", 'OK');
        flash('ok', 'Contraseña actualizada.');
        redirigir(url('profile'));
    }
}
