<?php /** Reportes CSV y resumen de reportes */ ?>
<div class="panel">
    <h2>Reportes</h2>
    <p class="pista">Los reportes se descargan en formato CSV.</p>
    <div class="acciones">
        <?php if ($rol === 'admin'): ?>
            <a class="btn" href="<?= url('reports/students') ?>">Estudiantes</a>
        <?php endif; ?>
        <a class="btn" href="<?= url('reports/grades') ?>"><?= $rol === 'docente' ? 'Calificaciones de mis cursos' : 'Calificaciones' ?></a>
        <a class="btn" href="<?= url('reports/by-career') ?>">Estudiantes por carrera</a>
        <?php if ($rol === 'admin'): ?>
            <a class="btn" href="<?= url('reports/activity') ?>">Actividad del sistema</a>
        <?php endif; ?>
    </div>
</div>

<div class="panel">
    <h2>Resumen de reportes</h2>
    <p class="pista">Genera un resumen con los totales actuales del sistema, identificado con una etiqueta.</p>
    <form method="post" action="<?= url('reports/package') ?>" class="acciones">
        <?= csrf_field() ?>
        <input type="text" name="etiqueta" placeholder="Etiqueta del resumen" maxlength="40"
               value="informe" style="flex:1;min-width:260px;padding:9px 11px;border:1px solid var(--gris-borde);border-radius:6px">
        <button class="btn btn-verde" type="submit">Generar resumen</button>
        <?php if ($resumenListo): ?>
            <a class="btn btn-borde" href="<?= url('reports/summary') ?>">Descargar último resumen</a>
        <?php endif; ?>
    </form>
</div>
