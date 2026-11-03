<?php /** Listado y búsqueda de estudiantes */ ?>
<div class="panel">
    <h2>Buscar estudiantes</h2>
    <form method="get" action="index.php" class="acciones">
        <input type="hidden" name="r" value="students/search">
        <input type="text" name="q" placeholder="Carné, nombre, apellido o carrera" maxlength="60"
               value="<?= e($termino) ?>" style="flex:1;min-width:220px;padding:9px 11px;border:1px solid var(--gris-borde);border-radius:6px">
        <button class="btn" type="submit">Buscar</button>
        <a class="btn btn-borde" href="<?= url('students') ?>">Ver todos</a>
    </form>

    <?php if ($termino !== ''): ?>
        <p class="pista" style="margin-top:12px">Resultados para: <?= e($termino) ?></p>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="acciones" style="justify-content:space-between;margin-bottom:12px">
        <h2 style="margin:0">Estudiantes (<?= (int)$total ?>)</h2>
        <a class="btn btn-verde" href="<?= url('students/new') ?>">+ Nuevo estudiante</a>
    </div>

    <div class="tabla-wrap">
        <table class="tabla">
            <thead>
                <tr><th>Carné</th><th>Nombres</th><th>Apellidos</th><th>Correo</th><th>Carrera</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
            <?php if (empty($estudiantes)): ?>
                <tr><td colspan="7">No se encontraron estudiantes.</td></tr>
            <?php else: foreach ($estudiantes as $s): ?>
                <tr>
                    <td><?= e($s['carne']) ?></td>
                    <td><?= e($s['nombres']) ?></td>
                    <td><?= e($s['apellidos']) ?></td>
                    <td><?= e($s['email']) ?></td>
                    <td><?= e($s['carrera'] ?? '—') ?></td>
                    <td>
                        <?php if (($s['estado'] ?? '') === 'activo'): ?>
                            <span class="badge badge-ok">activo</span>
                        <?php else: ?>
                            <span class="badge badge-gris"><?= e($s['estado'] ?? '') ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="acciones">
                        <a class="btn btn-sm btn-borde" href="<?= url('students/view') ?>&id=<?= (int)$s['id'] ?>">Ver</a>
                        <a class="btn btn-sm" href="<?= url('students/edit') ?>&id=<?= (int)$s['id'] ?>">Editar</a>
                        <form method="post" action="<?= url('students/delete') ?>&id=<?= (int)$s['id'] ?>"
                              data-confirmar="¿Eliminar a este estudiante?" style="display:inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-rojo" type="submit">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPaginas > 1): ?>
        <div class="acciones" style="margin-top:14px">
            <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
                <a class="btn btn-sm <?= $p === $pagina ? '' : 'btn-borde' ?>"
                   href="<?= url('students') ?>&page=<?= $p ?>"><?= $p ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>
