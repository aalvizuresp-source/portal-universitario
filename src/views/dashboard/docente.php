<?php /** Panel principal del docente: sus cursos y estudiantes */ ?>
<div class="stats">
    <div class="stat"><div class="num"><?= (int)$stats['cursos'] ?></div><div class="lbl">Cursos asignados</div></div>
    <div class="stat"><div class="num"><?= (int)$stats['estudiantes'] ?></div><div class="lbl">Estudiantes en mis cursos</div></div>
    <div class="stat"><div class="num"><?= (int)$stats['calificadas'] ?></div><div class="lbl">Notas registradas</div></div>
    <div class="stat"><div class="num"><?= (int)$stats['pendientes'] ?></div><div class="lbl">Notas pendientes</div></div>
</div>

<div class="panel">
    <h2>Mis cursos</h2>
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
