<?php /** Formulario de alta/edición de usuario */ ?>
<div class="panel">
    <h2><?= $usuario ? 'Editar usuario' : 'Nuevo usuario' ?></h2>
    <form method="post" action="<?= $accion ?>">
        <?= csrf_field() ?>
        <div class="form-grid">
            <?php if (!$usuario): ?>
            <div class="campo">
                <label for="username">Usuario *</label>
                <input type="text" id="username" name="username" required>
            </div>
            <?php endif; ?>
            <div class="campo">
                <label for="nombre_completo">Nombre completo *</label>
                <input type="text" id="nombre_completo" name="nombre_completo" required
                       value="<?= e($usuario['nombre_completo'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="email">Correo electrónico</label>
                <input type="text" id="email" name="email"
                       value="<?= e($usuario['email'] ?? '') ?>">
            </div>
            <?php if (!$usuario): ?>
            <div class="campo">
                <label for="password">Contraseña *</label>
                <input type="password" id="password" name="password" required>
            </div>
            <?php endif; ?>
            <div class="campo">
                <label for="role_id">Rol *</label>
                <select id="role_id" name="role_id" required>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= (int)$r['id'] ?>"
                            <?= (isset($usuario['role_id']) && (int)$usuario['role_id'] === (int)$r['id']) ? 'selected' : '' ?>>
                            <?= e($r['descripcion']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($usuario): ?>
            <div class="campo">
                <label for="activo">Estado</label>
                <select id="activo" name="activo">
                    <option value="1" <?= (int)($usuario['activo'] ?? 1) === 1 ? 'selected' : '' ?>>Activo</option>
                    <option value="0" <?= (int)($usuario['activo'] ?? 1) === 0 ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
            <?php endif; ?>
        </div>
        <div class="acciones">
            <button class="btn btn-verde" type="submit">Guardar</button>
            <a class="btn btn-borde" href="<?= url('users') ?>">Cancelar</a>
        </div>
    </form>
</div>
