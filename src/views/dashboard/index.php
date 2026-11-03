<?php /** Panel principal del administrador: indicadores globales y bitácora */ ?>
<div class="stats">
    <div class="stat"><div class="num"><?= (int)$stats['usuarios'] ?></div><div class="lbl">Usuarios registrados</div></div>
    <div class="stat"><div class="num"><?= (int)$stats['estudiantes'] ?></div><div class="lbl">Estudiantes activos</div></div>
    <div class="stat"><div class="num"><?= (int)$stats['docentes'] ?></div><div class="lbl">Docentes</div></div>
    <div class="stat"><div class="num"><?= (int)$stats['cursos'] ?></div><div class="lbl">Cursos disponibles</div></div>
    <div class="stat"><div class="num"><?= (int)$stats['documentos'] ?></div><div class="lbl">Documentos almacenados</div></div>
    <div class="stat"><div class="num"><?= (int)$stats['comentarios'] ?></div><div class="lbl">Comentarios</div></div>
</div>

<div class="panel">
    <h2>Últimas actividades del sistema</h2>
    <div class="tabla-wrap">
        <table class="tabla">
            <thead>
                <tr><th>Fecha</th><th>Tipo</th><th>Usuario</th><th>Resultado</th><th>Descripción</th></tr>
            </thead>
            <tbody>
            <?php if (empty($actividades)): ?>
                <tr><td colspan="5">Sin actividad registrada.</td></tr>
            <?php else: foreach ($actividades as $a): ?>
                <tr>
                    <td><?= e($a['ts']) ?></td>
                    <td><span class="badge badge-gris"><?= e($a['tipo']) ?></span></td>
                    <td><?= e($a['username'] ?? '-') ?></td>
                    <td>
                        <?php if ($a['resultado'] === 'OK'): ?>
                            <span class="badge badge-ok">OK</span>
                        <?php else: ?>
                            <span class="badge badge-no"><?= e($a['resultado']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($a['descripcion']) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
