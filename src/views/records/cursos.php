<?php /** Cursos del docente / admin */ ?>
<div class="panel">
    <h2>Cursos y notas</h2>
    <div class="tabla-wrap">
        <table class="tabla">
            <thead><tr><th>Código</th><th>Curso</th><th>Período</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($cursos)): ?>
                <tr><td colspan="4">No tiene cursos asignados.</td></tr>
            <?php else: foreach ($cursos as $c): ?>
                <tr>
                    <td><?= e($c['codigo']) ?></td>
                    <td><?= e($c['nombre']) ?></td>
                    <td><?= e($c['periodo']) ?></td>
                    <td><a class="btn btn-sm" href="<?= url('courses/grades') ?>&id=<?= (int)$c['course_id'] ?>">Registrar notas</a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
