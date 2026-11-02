<?php /** Ficha de estudiante con expediente */ ?>
<div class="panel">
    <h2>Ficha del estudiante</h2>
    <table class="tabla">
        <tr><th>Carné</th><td><?= e($estudiante['carne']) ?></td></tr>
        <tr><th>Nombre</th><td><?= e($estudiante['nombres'] . ' ' . $estudiante['apellidos']) ?></td></tr>
        <tr><th>Correo</th><td><?= e($estudiante['email']) ?></td></tr>
        <tr><th>Fecha de nacimiento</th><td><?= e($estudiante['fecha_nacimiento'] ?? '—') ?></td></tr>
        <tr><th>Carrera</th><td><?= e($estudiante['carrera'] ?? '—') ?></td></tr>
        <tr><th>Estado</th><td><?= e($estudiante['estado']) ?></td></tr>
        <tr><th>Teléfono de emergencia</th><td><?= e($estudiante['telefono_emergencia'] ?? '—') ?></td></tr>
        <tr><th>Fecha de registro</th><td><?= e($estudiante['fecha_registro']) ?></td></tr>
    </table>
</div>

<div class="panel">
    <h2>Expediente académico</h2>
    <div class="tabla-wrap">
        <table class="tabla">
            <thead><tr><th>Curso</th><th>Período</th><th>Nota</th><th>Estado</th></tr></thead>
            <tbody>
            <?php if (empty($expediente)): ?>
                <tr><td colspan="4">Sin cursos registrados.</td></tr>
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
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div style="margin-top:14px"><a class="btn btn-borde" href="<?= url('students') ?>">← Volver</a></div>
</div>
