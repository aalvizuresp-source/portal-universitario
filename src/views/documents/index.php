<?php /** Documentos: subir + listar */ ?>
<?php $rol = rol_actual(); ?>
<?php if (in_array($rol, ['admin','docente'], true)): ?>
<div class="panel">
    <h2>Subir documento</h2>
    <form method="post" action="<?= url('documents/upload') ?>" enctype="multipart/form-data">
        <div class="form-grid">
            <div class="campo">
                <label for="student_id">Estudiante *</label>
                <select id="student_id" name="student_id" required>
                    <option value="">— Seleccione —</option>
                    <?php foreach ($estudiantes as $s): ?>
                        <option value="<?= (int)$s['id'] ?>"><?= e($s['carne'] . ' · ' . $s['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="archivo">Archivo *</label>
                <input type="file" id="archivo" name="archivo" required>
                <span class="pista">PDF, JPG o PNG. Tamaño máximo: 5 MB.</span>
            </div>
        </div>
        <button class="btn btn-verde" type="submit">Subir</button>
    </form>
</div>
<?php endif; ?>

<div class="panel">
    <h2>Documentos almacenados</h2>
    <div class="tabla-wrap">
        <table class="tabla">
            <thead><tr><th>Archivo</th><th>Estudiante</th><th>Tipo</th><th>Tamaño</th><th>Subido</th><th>Por</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($documentos)): ?>
                <tr><td colspan="7">No hay documentos.</td></tr>
            <?php else: foreach ($documentos as $d): ?>
                <tr>
                    <td><?= e($d['original_name']) ?></td>
                    <td><?= e($d['carne'] . ' · ' . $d['estudiante']) ?></td>
                    <td><?= e($d['mime_type']) ?></td>
                    <td><?= number_format((int)$d['size_bytes'] / 1024, 1) ?> KB</td>
                    <td><?= e($d['subido_en']) ?></td>
                    <td><?= e($d['subido_por_nombre'] ?? '—') ?></td>
                    <td class="acciones">
                        <a class="btn btn-sm btn-borde" href="<?= url('documents/download') ?>&id=<?= (int)$d['id'] ?>">Descargar</a>
                        <?php if (in_array($rol, ['admin','docente'], true)): ?>
                            <form method="post" action="<?= url('documents/delete') ?>&id=<?= (int)$d['id'] ?>"
                                  data-confirmar="¿Eliminar documento?" style="display:inline">
                                <button class="btn btn-sm btn-rojo" type="submit">Eliminar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
