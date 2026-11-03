<?php /** Lista de estudiantes para abrir expediente (admin/docente) */ ?>
<div class="panel">
    <h2>Seleccione un expediente</h2>
    <?php if (rol_actual() === 'docente'): ?>
        <p class="pista">Estudiantes matriculados en sus cursos.</p>
    <?php endif; ?>
    <div class="tabla-wrap">
        <table class="tabla">
            <thead><tr><th>Carné</th><th>Estudiante</th><th>Carrera</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($estudiantes)): ?>
                <tr><td colspan="4">No hay estudiantes para mostrar.</td></tr>
            <?php endif; ?>
            <?php foreach ($estudiantes as $s): ?>
                <tr>
                    <td><?= e($s['carne']) ?></td>
                    <td><?= e($s['nombres'] . ' ' . $s['apellidos']) ?></td>
                    <td><?= e($s['carrera'] ?? '—') ?></td>
                    <td><a class="btn btn-sm btn-borde" href="<?= url('records/view') ?>&id=<?= (int)$s['id'] ?>">Ver expediente</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
