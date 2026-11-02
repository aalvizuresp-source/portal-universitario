<?php /** Reportes CSV y resumen de reportes */ ?>
<div class="panel">
    <h2>Reportes</h2>
    <p class="pista">Los reportes se descargan en formato CSV.</p>
    <div class="acciones">
        <a class="btn" href="<?= url('reports/students') ?>">Estudiantes</a>
        <a class="btn" href="<?= url('reports/grades') ?>">Calificaciones</a>
        <a class="btn" href="<?= url('reports/by-career') ?>">Estudiantes por carrera</a>
        <?php if ($rol === 'admin'): ?>
            <a class="btn" href="<?= url('reports/activity') ?>">Actividad del sistema</a>
        <?php endif; ?>
    </div>
</div>

<div class="panel">
    <h2>Resumen de reportes</h2>
    <p class="pista">Genera un resumen de reportes identificado con una etiqueta.</p>
    <form method="post" action="<?= url('reports/package') ?>" class="acciones">
        <input type="text" name="etiqueta" placeholder="Etiqueta del resumen"
               value="informe" style="flex:1;min-width:260px;padding:9px 11px;border:1px solid var(--gris-borde);border-radius:6px">
        <button class="btn btn-verde" type="submit">Generar resumen</button>
        <?php if ($resumenListo): ?>
            <a class="btn btn-borde" href="<?= url('reports/summary') ?>">Descargar último resumen</a>
        <?php endif; ?>
    </form>
</div>
