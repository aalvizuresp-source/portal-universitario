<?php /** Vista de error genérica */ ?>
<div class="panel">
    <h2><?= e($titulo ?? 'Error') ?></h2>
    <div class="alerta alerta-error"><?= e($mensaje ?? 'Ha ocurrido un error.') ?></div>
    <a class="btn btn-borde" href="<?= url('dashboard') ?>">← Volver al panel</a>
</div>
