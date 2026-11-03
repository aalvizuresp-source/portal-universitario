<?php /** Panel principal del estudiante: resumen de su expediente */ ?>
<?php if (!$tieneFicha): ?>
    <div class="panel">
        <h2>Bienvenido</h2>
        <p>Su cuenta aún no está vinculada a una ficha de estudiante. Comuníquese con la administración.</p>
    </div>
<?php else: ?>
<div class="stats">
    <div class="stat"><div class="num"><?= (int)$stats['cursos'] ?></div><div class="lbl">Cursos inscritos</div></div>
    <div class="stat"><div class="num"><?= (int)$stats['aprobados'] ?></div><div class="lbl">Cursos aprobados</div></div>
    <div class="stat"><div class="num"><?= (int)$stats['pendientes'] ?></div><div class="lbl">Notas pendientes</div></div>
    <div class="stat"><div class="num"><?= $stats['promedio'] !== null ? e(number_format($stats['promedio'], 1)) : '—' ?></div><div class="lbl">Promedio</div></div>
    <div class="stat"><div class="num"><?= (int)$stats['documentos'] ?></div><div class="lbl">Mis documentos</div></div>
</div>

<div class="panel">
    <h2>Accesos rápidos</h2>
    <div class="acciones">
        <a class="btn" href="<?= url('records') ?>">Ver mi expediente</a>
        <a class="btn btn-borde" href="<?= url('documents') ?>">Mis documentos</a>
        <a class="btn btn-borde" href="<?= url('comments') ?>">Comentarios</a>
    </div>
</div>
<?php endif; ?>
