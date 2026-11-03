<?php
/**
 * AuthController — registro, login, logout, perfil, cambio de contraseña.
 *
 * - Contraseñas con password_hash()/password_verify() y migración automática
 *   de hashes heredados del sistema anterior.
 * - Regeneración del ID de sesión al autenticar y límite de intentos fallidos.
 * - Los registros de autenticación nunca incluyen contraseñas.
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
        $username = trim((string)post('username', ''));
        $password = (string)post('password', '');

        $user = UserModel::porUsername($username);

        // --- Límite de intentos (rate limiting) ---
        if ($user && self::estaBloqueado($user)) {
            registrar_evento('LOGIN_BLOCKED',
                "Cuenta temporalmente bloqueada por intentos fallidos: {$username}",
                'DENEGADO', (int)$user['id']);
            flash('error', 'Demasiados intentos fallidos. Intente de nuevo en unos minutos.');
            redirigir(url('login'));
        }

        $credencialesOk = $user
            && (int)$user['activo'] === 1
            && self::verificarPassword($password, $user);

        if ($credencialesOk) {
            // Nuevo ID de sesión al autenticar (evita la fijación de sesión).
            session_regenerate_id(true);
            establecer_usuario($user);
            UserModel::reiniciarIntentos((int)$user['id']);
            registrar_evento('LOGIN_OK', "Ingreso exitoso de {$username}", 'OK', (int)$user['id']);
            redirigir(url('dashboard'));
        }

        // Se registra el intento sin la contraseña.
        if ($user) {
            UserModel::incrementarIntentos((int)$user['id']);
        }
        registrar_evento(
            'LOGIN_FAIL',
            "Intento de inicio de sesión fallido para el usuario '{$username}'",
            'ERROR',
            $user['id'] ?? null
        );

        flash('error', 'Usuario o contraseña incorrectos.');
        redirigir(url('login'));
    }

    /**
     * Verifica la contraseña. Si el hash almacenado es del formato heredado,
     * lo migra a password_hash() tras una verificación correcta.
     */
    private static function verificarPassword(string $password, array $user): bool
    {
        $hash = $user['password_hash'];

        // Hash heredado: verificar y reemplazar por uno moderno.
        if (lab_es_hash_legado($hash)) {
            if (lab_verificar_hash_legado($password, $hash)) {
                UserModel::cambiarPassword((int)$user['id'], lab_hash_password($password));
                registrar_evento('PASSWORD_MIGRATE',
                    "Hash de contraseña actualizado para {$user['username']}",
                    'OK', (int)$user['id']);
                return true;
            }
            return false;
        }

        // Hash moderno: se vuelve a generar si cambiaron los parámetros de coste.
        if (lab_verificar_password($password, $hash)) {
            if (password_needs_rehash($hash, lab_algoritmo_password())) {
                UserModel::cambiarPassword((int)$user['id'], lab_hash_password($password));
            }
            return true;
        }
        return false;
    }

    private static function estaBloqueado(array $user): bool
    {
        if ((int)$user['failed_attempts'] < MAX_INTENTOS_LOGIN) {
            return false;
        }
        $hasta = $user['locked_until'] ?? null;
        if ($hasta === null) {
            // Marcar el bloqueo a partir de ahora.
            UserModel::bloquearHasta((int)$user['id'],
                date('Y-m-d H:i:s', time() + BLOQUEO_LOGIN_SEGUNDOS));
            return true;
        }
        if (strtotime($hasta) > time()) {
            return true;
        }
        // El bloqueo expiró: reiniciar contador.
        UserModel::reiniciarIntentos((int)$user['id']);
        return false;
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
        $username = trim((string)post('username', ''));
        $nombre   = (string)post('nombre_completo', '');
        $password = (string)post('password', '');

        $v = new Validador();
        $email  = $v->email((string)post('email', ''));
        $nombre = $v->nombre($nombre, 'nombre completo');

        if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $username)) {
            $v->errores[] = 'El usuario debe tener 3–30 caracteres (letras, números, . _ -).';
        }
        if (mb_strlen($password) < 8) {
            $v->errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        }
        if (!$v->ok()) {
            flash('error', $v->primerError());
            redirigir(url('register'));
        }
        if (UserModel::existeUsername($username)) {
            flash('error', 'El nombre de usuario ya existe.');
            redirigir(url('register'));
        }

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

        if (!self::verificarPassword($actual, $user)) {
            flash('error', 'La contraseña actual no es correcta.');
            redirigir(url('profile'));
        }
        if (mb_strlen($nueva) < 8) {
            flash('error', 'La nueva contraseña debe tener al menos 8 caracteres.');
            redirigir(url('profile'));
        }

        UserModel::cambiarPassword((int)$user['id'], lab_hash_password($nueva));
        registrar_evento('PASSWORD_CHANGE', "Cambio de contraseña de {$user['username']}", 'OK');
        flash('ok', 'Contraseña actualizada.');
        redirigir(url('profile'));
    }
}
