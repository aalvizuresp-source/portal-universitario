<?php /** Formulario de alta/edición de estudiante */ ?>
<div class="panel">
    <h2><?= $estudiante ? 'Editar estudiante' : 'Nuevo estudiante' ?></h2>
    <form method="post" action="<?= $accion ?>">
        <div class="form-grid">
            <div class="campo">
                <label for="carne">Carné *</label>
                <input type="text" id="carne" name="carne" required
                       value="<?= e($estudiante['carne'] ?? '') ?>">
                <span class="pista">Formato: 2021-0001</span>
            </div>
            <div class="campo">
                <label for="nombres">Nombres *</label>
                <input type="text" id="nombres" name="nombres" required
                       value="<?= e($estudiante['nombres'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="apellidos">Apellidos *</label>
                <input type="text" id="apellidos" name="apellidos" required
                       value="<?= e($estudiante['apellidos'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="email">Correo electrónico</label>
                <input type="text" id="email" name="email"
                       value="<?= e($estudiante['email'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="fecha_nacimiento">Fecha de nacimiento</label>
                <input type="date" id="fecha_nacimiento" name="fecha_nacimiento"
                       value="<?= e($estudiante['fecha_nacimiento'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="career_id">Carrera</label>
                <select id="career_id" name="career_id">
                    <option value="">— Sin asignar —</option>
                    <?php foreach ($carreras as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"
                            <?= (isset($estudiante['career_id']) && (int)$estudiante['career_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                            <?= e($c['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="estado">Estado</label>
                <select id="estado" name="estado">
                    <?php foreach (['activo','inactivo','egresado'] as $opt): ?>
                        <option value="<?= $opt ?>"
                            <?= (($estudiante['estado'] ?? 'activo') === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="telefono_emergencia">Teléfono de emergencia</label>
                <input type="text" id="telefono_emergencia" name="telefono_emergencia"
                       value="<?= e($estudiante['telefono_emergencia'] ?? '') ?>">
                <span class="pista">Ejemplo: 5512-0001</span>
            </div>
        </div>
        <div class="acciones">
            <button class="btn btn-verde" type="submit">Guardar</button>
            <a class="btn btn-borde" href="<?= url('students') ?>">Cancelar</a>
        </div>
    </form>
</div>
