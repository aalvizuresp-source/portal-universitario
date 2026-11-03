<?php /** Perfil de usuario + cambio de contraseña */ ?>
<div class="panel">
    <h2>Datos de la cuenta</h2>
    <table class="tabla">
        <tr><th>Usuario</th><td><?= e($user['username']) ?></td></tr>
        <tr><th>Nombre</th><td><?= e($user['nombre_completo']) ?></td></tr>
        <tr><th>Correo</th><td><?= e($user['email']) ?></td></tr>
        <tr><th>Rol</th><td><?= e($user['rol']) ?></td></tr>
        <tr><th>Estado</th><td>
            <?php if ((int)$user['activo'] === 1): ?>
                <span class="badge badge-ok">Activo</span>
            <?php else: ?>
                <span class="badge badge-no">Inactivo</span>
            <?php endif; ?>
        </td></tr>
    </table>
</div>

<div class="panel">
    <h2>Cambiar contraseña</h2>
    <form method="post" action="<?= url('profile/password') ?>">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="campo">
                <label for="actual">Contraseña actual</label>
                <input type="password" id="actual" name="actual" required>
            </div>
            <div class="campo">
                <label for="nueva">Nueva contraseña</label>
                <input type="password" id="nueva" name="nueva" required>
            </div>
        </div>
        <button class="btn" type="submit">Actualizar contraseña</button>
    </form>
</div>
