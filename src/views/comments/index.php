<?php /** Comentarios: publicar y listar */ ?>
<div class="panel">
    <h2>Publicar comentario</h2>
    <form method="post" action="<?= url('comments/create') ?>">
        <div class="form-grid">
            <div class="campo">
                <label for="student_id">Dirigido a</label>
                <select id="student_id" name="student_id">
                    <option value="">General</option>
                    <?php foreach ($estudiantes as $s): ?>
                        <option value="<?= (int)$s['id'] ?>"><?= e($s['carne'] . ' · ' . $s['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="campo">
            <label for="contenido">Comentario</label>
            <textarea id="contenido" name="contenido" required
                      placeholder="Escriba su comentario..."></textarea>
        </div>
        <button class="btn btn-verde" type="submit">Publicar</button>
    </form>
</div>

<div class="panel">
    <h2>Comentarios publicados</h2>
    <?php if (empty($comentarios)): ?>
        <p>No hay comentarios.</p>
    <?php else: foreach ($comentarios as $c): ?>
        <div style="border:1px solid var(--gris-borde);border-radius:8px;padding:14px;margin-bottom:12px">
            <div class="acciones" style="justify-content:space-between">
                <div>
                    <strong><?= e($c['autor']) ?></strong>
                    <small class="pista">
                        · <?= e($c['creado_en']) ?>
                        <?php if ($c['estudiante']): ?> · sobre <?= e($c['estudiante']) ?><?php endif; ?>
                        <?php if ($c['curso']): ?> · curso <?= e($c['curso']) ?><?php endif; ?>
                    </small>
                </div>
                <form method="post" action="<?= url('comments/delete') ?>&id=<?= (int)$c['id'] ?>"
                      data-confirmar="¿Eliminar comentario?" style="display:inline">
                    <button class="btn btn-sm btn-rojo" type="submit">Eliminar</button>
                </form>
            </div>
            <div style="margin-top:8px">
                <?= $c['contenido'] ?>
            </div>
        </div>
    <?php endforeach; endif; ?>
</div>
