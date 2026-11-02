<?php /** Listado de usuarios (admin) */ ?>
<div class="panel">
    <div class="acciones" style="justify-content:space-between;margin-bottom:12px">
        <h2 style="margin:0">Usuarios del sistema</h2>
        <a class="btn btn-verde" href="<?= url('users/new') ?>">+ Nuevo usuario</a>
    </div>
    <div class="tabla-wrap">
        <table class="tabla">
            <thead><tr><th>ID</th><th>Usuario</th><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= (int)$u['id'] ?></td>
                    <td><?= e($u['username']) ?></td>
                    <td><?= e($u['nombre_completo']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><span class="badge badge-gris"><?= e($u['rol']) ?></span></td>
                    <td>
                        <?php if ((int)$u['activo'] === 1): ?>
                            <span class="badge badge-ok">activo</span>
                        <?php else: ?>
                            <span class="badge badge-no">inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td class="acciones">
                        <a class="btn btn-sm" href="<?= url('users/edit') ?>&id=<?= (int)$u['id'] ?>">Editar</a>
                        <form method="post" action="<?= url('users/toggle') ?>&id=<?= (int)$u['id'] ?>" style="display:inline">
                            <button class="btn btn-sm btn-gris" type="submit">
                                <?= (int)$u['activo'] === 1 ? 'Desactivar' : 'Activar' ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
