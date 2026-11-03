<?php /** Pantalla de login (sin layout) */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <div class="marca">
            <span class="marca-logo">LS</span>
            <div><strong><?= e(APP_NAME) ?></strong><small>Portal Universitario</small></div>
        </div>
        <h2>Iniciar sesión</h2>
        <p class="login-sub">Ingrese con su usuario institucional</p>

        <?php foreach (obtener_flash() as $f): ?>
            <div class="alerta alerta-<?= e($f['tipo']) ?>"><?= e($f['mensaje']) ?></div>
        <?php endforeach; ?>

        <form method="post" action="<?= url('login/post') ?>">
            <?= csrf_field() ?>
            <div class="campo">
                <label for="username">Usuario</label>
                <input type="text" id="username" name="username" autofocus required>
            </div>
            <div class="campo">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button class="btn" type="submit" style="width:100%">Ingresar</button>
        </form>

        <p style="text-align:center;margin-top:14px;font-size:13px">
            ¿No tiene cuenta? <a href="<?= url('register') ?>">Regístrese</a>
        </p>
    </div>
</div>
</body>
</html>
