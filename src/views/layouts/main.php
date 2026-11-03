<?php
/**
 * Layout principal: menú lateral + encabezado + área de contenido.
 * Recibe $contenido (string ya renderizado) y, opcionalmente, $titulo_pagina.
 */
$u = usuario_actual();
$rutaActual = $_GET['r'] ?? 'dashboard';
$flashes = obtener_flash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo_pagina ?? 'Panel') ?> · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
<div class="app">
    <!-- ===== Menú lateral ===== -->
    <aside class="sidebar">
        <div class="marca">
            <span class="marca-logo">LS</span>
            <div>
                <strong><?= e(APP_NAME) ?></strong>
                <small>Portal Universitario</small>
            </div>
        </div>

        <nav class="menu">
            <a class="<?= activo('dashboard', $rutaActual) ?>" href="<?= url('dashboard') ?>">Panel principal</a>

            <?php if (rol_actual() === 'admin'): ?>
                <a class="<?= activo('students', $rutaActual) ?>" href="<?= url('students') ?>">Estudiantes</a>
            <?php endif; ?>

            <a class="<?= activo('records', $rutaActual) ?>" href="<?= url('records') ?>">Expedientes</a>

            <?php if (in_array(rol_actual(), ['admin','docente'], true)): ?>
                <a class="<?= activo('courses', $rutaActual) ?>" href="<?= url('courses') ?>">Cursos y notas</a>
            <?php endif; ?>

            <a class="<?= activo('comments', $rutaActual) ?>" href="<?= url('comments') ?>">Comentarios</a>
            <a class="<?= activo('documents', $rutaActual) ?>" href="<?= url('documents') ?>">Documentos</a>

            <?php if (in_array(rol_actual(), ['admin','docente'], true)): ?>
                <a class="<?= activo('reports', $rutaActual) ?>" href="<?= url('reports') ?>">Reportes</a>
            <?php endif; ?>

            <?php if (rol_actual() === 'admin'): ?>
                <a class="<?= activo('users', $rutaActual) ?>" href="<?= url('users') ?>">Usuarios</a>
            <?php endif; ?>
        </nav>

    </aside>

    <!-- ===== Columna principal ===== -->
    <div class="principal">
        <header class="encabezado">
            <h1><?= e($titulo_pagina ?? 'Panel') ?></h1>
            <div class="usuario-box">
                <div class="usuario-datos">
                    <strong><?= e($u['nombre'] ?? '') ?></strong>
                    <small>Rol: <?= e($u['rol'] ?? '') ?></small>
                </div>
                <a class="btn btn-sm" href="<?= url('profile') ?>">Perfil</a>
                <a class="btn btn-sm btn-rojo" href="<?= url('logout') ?>">Salir</a>
            </div>
        </header>

        <main class="contenido">
            <?php foreach ($flashes as $f): ?>
                <div class="alerta alerta-<?= e($f['tipo']) ?>"><?= e($f['mensaje']) ?></div>
            <?php endforeach; ?>

            <?= $contenido ?>
        </main>

        <footer class="pie">
            &copy; <?= date('Y') ?> <?= e(APP_NAME) ?> · Portal Universitario · Versión <?= e(APP_VERSION) ?>
        </footer>
    </div>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>
