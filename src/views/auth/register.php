<?php /** Pantalla de registro (sin layout) */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <div class="marca">
            <span class="marca-logo">LS</span>
            <div><strong><?= e(APP_NAME) ?></strong><small>Portal Universitario</small></div>
        </div>
        <h2>Crear cuenta</h2>
        <p class="login-sub">Se creará con rol <strong>estudiante</strong></p>

        <?php foreach (obtener_flash() as $f): ?>
            <div class="alerta alerta-<?= e($f['tipo']) ?>"><?= e($f['mensaje']) ?></div>
        <?php endforeach; ?>

        <form method="post" action="<?= url('register/post') ?>">
            <div class="campo">
                <label for="username">Usuario</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="campo">
                <label for="nombre_completo">Nombre completo</label>
                <input type="text" id="nombre_completo" name="nombre_completo" required>
            </div>
            <div class="campo">
                <label for="email">Correo electrónico</label>
                <input type="text" id="email" name="email" required>
            </div>
            <div class="campo">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button class="btn" type="submit" style="width:100%">Registrarse</button>
        </form>

        <p style="text-align:center;margin-top:14px;font-size:13px">
            ¿Ya tiene cuenta? <a href="<?= url('login') ?>">Inicie sesión</a>
        </p>
    </div>
</div>
</body>
</html>
