<?php /** Expediente académico (reutiliza students/ver estructura) */ ?>
<div class="panel">
    <h2>Expediente de <?= e($estudiante['nombres'] . ' ' . $estudiante['apellidos']) ?></h2>
    <table class="tabla">
        <tr><th>Carné</th><td><?= e($estudiante['carne']) ?></td></tr>
        <tr><th>Carrera</th><td><?= e($estudiante['carrera'] ?? '—') ?></td></tr>
        <tr><th>Estado</th><td><?= e($estudiante['estado']) ?></td></tr>
    </table>
</div>

<div class="panel">
    <h2>Calificaciones</h2>
    <div class="tabla-wrap">
        <table class="tabla">
            <thead><tr><th>Curso</th><th>Período</th><th>Nota</th><th>Estado</th><th>Registrada</th></tr></thead>
            <tbody>
            <?php if (empty($expediente)): ?>
                <tr><td colspan="5">Sin cursos registrados.</td></tr>
            <?php else: foreach ($expediente as $r): ?>
                <tr>
                    <td><?= e($r['curso']) ?></td>
                    <td><?= e($r['periodo']) ?></td>
                    <td><?= $r['nota'] !== null ? e((string)$r['nota']) : '—' ?></td>
                    <td>
                        <?php if ($r['nota'] === null): ?>
                            <span class="badge badge-gris">pendiente</span>
                        <?php elseif ((int)$r['aprobado'] === 1): ?>
                            <span class="badge badge-ok">aprobado</span>
                        <?php else: ?>
                            <span class="badge badge-no">reprobado</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($r['registrado_en'] ?? '—') ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
