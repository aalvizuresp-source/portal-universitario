<?php /** Registro de notas de un curso */ ?>
<div class="panel">
    <h2>Registro de notas del curso</h2>
    <p class="pista">Calificación entre 0 y 100. Nota mínima de aprobación: <?= (int)NOTA_APROBACION ?>.</p>
    <div class="tabla-wrap">
        <table class="tabla">
            <thead><tr><th>Carné</th><th>Estudiante</th><th>Nota actual</th><th>Nueva nota</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($matriculados)): ?>
                <tr><td colspan="5">No hay estudiantes matriculados.</td></tr>
            <?php else: foreach ($matriculados as $m): ?>
                <tr>
                    <td><?= e($m['carne']) ?></td>
                    <td><?= e($m['nombres'] . ' ' . $m['apellidos']) ?></td>
                    <td><?= $m['nota'] !== null ? e((string)$m['nota']) : '—' ?></td>
                    <td colspan="2">
                        <form method="post" action="<?= url('grades/save') ?>" class="acciones">
                            <input type="hidden" name="enrollment_id" value="<?= (int)$m['enrollment_id'] ?>">
                            <input type="text" name="nota" placeholder="0-100" style="width:90px;padding:7px;border:1px solid var(--gris-borde);border-radius:6px">
                            <button class="btn btn-sm btn-verde" type="submit">Guardar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div style="margin-top:14px"><a class="btn btn-borde" href="<?= url('courses') ?>">← Volver a cursos</a></div>
</div>
