<?php
require_once __DIR__ . '/../includes/header.php';

if (!is_logged_in()) redirect('views/login.php');

$ruta_id = $_GET['id'] ?? null;
if (!$ruta_id) redirect('views/learning_routes.php');

// Verificar propiedad
$stmt = $pdo->prepare("SELECT * FROM rutas WHERE id = ? AND creador_id = ?");
$stmt->execute([$ruta_id, $_SESSION['user_id']]);
$ruta = $stmt->fetch();

if (!$ruta) die("No tienes permiso para editar esta ruta.");

$success = '';
$error = '';

// ─── ACCIÓN: Actualizar datos generales de la ruta ───
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_route'])) {
    $titulo = sanitize($_POST['titulo']);
    $descripcion = sanitize($_POST['descripcion']);
    $privacidad = $_POST['privacidad'];

    if (empty($titulo)) {
        $error = 'El título es obligatorio.';
    } else {
        $stmt = $pdo->prepare("UPDATE rutas SET titulo = ?, descripcion = ?, privacidad = ? WHERE id = ? AND creador_id = ?");
        $stmt->execute([$titulo, $descripcion, $privacidad, $ruta_id, $_SESSION['user_id']]);
        $success = 'Ruta actualizada correctamente.';
        // Refrescar datos
        $stmt = $pdo->prepare("SELECT * FROM rutas WHERE id = ?");
        $stmt->execute([$ruta_id]);
        $ruta = $stmt->fetch();
    }
}

// ─── ACCIÓN: Añadir nuevo paso ───
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_step'])) {
    $titulo_paso = sanitize($_POST['step_titulo']);
    $contenido = sanitize($_POST['step_contenido']);
    $codigo = $_POST['step_codigo'];

    if (empty($titulo_paso)) {
        $error = 'El título del paso es obligatorio.';
    } else {
        $stmt = $pdo->prepare("SELECT MAX(orden) as max_o FROM pasos_ruta WHERE ruta_id = ?");
        $stmt->execute([$ruta_id]);
        $res = $stmt->fetch();
        $orden = ($res->max_o ?? 0) + 1;

        $stmt = $pdo->prepare("INSERT INTO pasos_ruta (ruta_id, titulo, contenido, codigo_snippet, orden) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$ruta_id, $titulo_paso, $contenido, $codigo, $orden]);
        $success = 'Paso añadido correctamente.';
    }
}

// ─── ACCIÓN: Editar paso existente ───
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_step'])) {
    $step_id = $_POST['step_id'];
    $titulo_paso = sanitize($_POST['step_titulo']);
    $contenido = sanitize($_POST['step_contenido']);
    $codigo = $_POST['step_codigo'];

    // Verificar que el paso pertenece a esta ruta
    $stmt = $pdo->prepare("SELECT id FROM pasos_ruta WHERE id = ? AND ruta_id = ?");
    $stmt->execute([$step_id, $ruta_id]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("UPDATE pasos_ruta SET titulo = ?, contenido = ?, codigo_snippet = ? WHERE id = ?");
        $stmt->execute([$titulo_paso, $contenido, $codigo, $step_id]);
        $success = 'Paso actualizado correctamente.';
    } else {
        $error = 'No se pudo actualizar el paso.';
    }
}

// ─── ACCIÓN: Eliminar paso ───
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_step'])) {
    $step_id = $_POST['step_id'];
    $stmt = $pdo->prepare("DELETE FROM pasos_ruta WHERE id = ? AND ruta_id = ?");
    $stmt->execute([$step_id, $ruta_id]);

    // Reordenar pasos restantes
    $stmt = $pdo->prepare("SELECT id FROM pasos_ruta WHERE ruta_id = ? ORDER BY orden ASC");
    $stmt->execute([$ruta_id]);
    $remaining = $stmt->fetchAll();
    foreach ($remaining as $i => $row) {
        $pdo->prepare("UPDATE pasos_ruta SET orden = ? WHERE id = ?")->execute([$i + 1, $row->id]);
    }
    $success = 'Paso eliminado y orden actualizado.';
}

// Obtener pasos actuales
$stmt = $pdo->prepare("SELECT * FROM pasos_ruta WHERE ruta_id = ? ORDER BY orden ASC");
$stmt->execute([$ruta_id]);
$pasos = $stmt->fetchAll();
?>

<style>
    .edit-tabs {
        display: flex;
        gap: 0;
        margin-bottom: 30px;
        border-bottom: 2px solid var(--glass-border);
    }
    .edit-tab {
        padding: 14px 28px;
        cursor: pointer;
        font-weight: 600;
        color: var(--text-secondary);
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        transition: all 0.3s;
        background: none;
        border-top: none;
        border-left: none;
        border-right: none;
        font-size: 0.95rem;
    }
    .edit-tab:hover {
        color: var(--primary-color);
    }
    .edit-tab.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
    }
    .tab-panel {
        display: none;
        animation: fadeIn 0.3s ease-out;
    }
    .tab-panel.active {
        display: block;
    }
    .step-card {
        background: #fff;
        border: 1px solid var(--glass-border);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 16px;
        transition: all 0.3s;
        position: relative;
    }
    .step-card:hover {
        border-color: var(--primary-color);
        box-shadow: 0 4px 16px rgba(59, 130, 246, 0.08);
    }
    .step-number {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: #fff;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        font-weight: 700;
        flex-shrink: 0;
    }
    .step-actions {
        display: flex;
        gap: 8px;
    }
    .step-actions button {
        padding: 6px 14px;
        border-radius: 6px;
        border: 1px solid var(--glass-border);
        background: #fff;
        cursor: pointer;
        font-size: 0.8rem;
        font-weight: 500;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .step-actions .btn-edit-step {
        color: var(--primary-color);
        border-color: rgba(59, 130, 246, 0.3);
    }
    .step-actions .btn-edit-step:hover {
        background: rgba(59, 130, 246, 0.08);
    }
    .step-actions .btn-delete-step {
        color: #ef4444;
        border-color: rgba(239, 68, 68, 0.3);
    }
    .step-actions .btn-delete-step:hover {
        background: rgba(239, 68, 68, 0.08);
    }
    .code-preview {
        background: #1e293b;
        color: #e2e8f0;
        padding: 12px 16px;
        border-radius: 8px;
        font-family: 'Fira Code', monospace;
        font-size: 0.8rem;
        margin-top: 10px;
        max-height: 120px;
        overflow: hidden;
        position: relative;
    }
    .code-preview::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 30px;
        background: linear-gradient(transparent, #1e293b);
    }
    /* Modal para editar paso */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(4px);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }
    .modal-overlay.show {
        display: flex;
    }
    .modal-content {
        background: #fff;
        border-radius: 16px;
        padding: 32px;
        max-width: 650px;
        width: 90%;
        max-height: 85vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        animation: fadeIn 0.3s ease-out;
    }
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }
    .modal-close {
        background: none;
        border: none;
        font-size: 1.4rem;
        cursor: pointer;
        color: var(--text-secondary);
        padding: 4px;
        transition: color 0.2s;
    }
    .modal-close:hover {
        color: var(--text-primary);
    }
    .alert-success {
        background: rgba(16, 185, 129, 0.1);
        color: #059669;
        padding: 14px 20px;
        border-radius: 10px;
        margin-bottom: 24px;
        border: 1px solid rgba(16, 185, 129, 0.2);
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
    }
    .alert-error {
        background: rgba(239, 68, 68, 0.1);
        color: #dc2626;
        padding: 14px 20px;
        border-radius: 10px;
        margin-bottom: 24px;
        border: 1px solid rgba(239, 68, 68, 0.2);
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
    }
    .section-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: var(--text-secondary);
    }
    .empty-state i {
        font-size: 2.5rem;
        margin-bottom: 12px;
        display: block;
        opacity: 0.4;
    }
    .confirm-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(4px);
        z-index: 1001;
        align-items: center;
        justify-content: center;
    }
    .confirm-overlay.show {
        display: flex;
    }
    .confirm-box {
        background: #fff;
        border-radius: 16px;
        padding: 32px;
        max-width: 420px;
        width: 90%;
        text-align: center;
        box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        animation: fadeIn 0.3s ease-out;
    }
    .confirm-box .confirm-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: rgba(239, 68, 68, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
    }
    .confirm-box .confirm-icon i {
        color: #ef4444;
        font-size: 1.4rem;
    }
    .confirm-buttons {
        display: flex;
        gap: 12px;
        margin-top: 24px;
    }
    .confirm-buttons button {
        flex: 1;
        padding: 12px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        font-size: 0.9rem;
        transition: all 0.2s;
    }
    .confirm-cancel {
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        color: var(--text-primary);
    }
    .confirm-cancel:hover {
        background: #e2e8f0;
    }
    .confirm-delete {
        background: #ef4444;
        border: none;
        color: #fff;
    }
    .confirm-delete:hover {
        background: #dc2626;
    }
</style>

<div class="animate-in" style="margin-top: 40px; max-width: 900px; margin-left: auto; margin-right: auto;">

    <!-- Breadcrumb -->
    <div style="margin-bottom: 24px; display: flex; align-items: center; gap: 8px; font-size: 0.9rem;">
        <a href="<?= BASE_URL ?>views/learning_routes.php" style="color: var(--text-secondary); text-decoration: none;">Rutas</a>
        <i class="fas fa-chevron-right" style="font-size: 0.7rem; color: var(--text-secondary);"></i>
        <a href="<?= BASE_URL ?>views/route_detail.php?id=<?= $ruta_id ?>" style="color: var(--text-secondary); text-decoration: none;"><?= $ruta->titulo ?></a>
        <i class="fas fa-chevron-right" style="font-size: 0.7rem; color: var(--text-secondary);"></i>
        <span style="color: var(--primary-color); font-weight: 600;">Editar</span>
    </div>

    <!-- Título principal -->
    <h1 style="font-size: 1.8rem; margin-bottom: 8px;">
        <i class="fas fa-pen-to-square" style="color: var(--primary-color);"></i> Editar Ruta
    </h1>
    <p style="color: var(--text-secondary); margin-bottom: 30px;">Modifica la información, edita los pasos existentes o añade nuevos contenidos.</p>

    <!-- Mensajes -->
    <?php if ($success): ?>
        <div class="alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="edit-tabs">
        <button class="edit-tab active" onclick="switchTab('info')">
            <i class="fas fa-info-circle"></i> Información
        </button>
        <button class="edit-tab" onclick="switchTab('steps')">
            <i class="fas fa-list-ol"></i> Pasos (<?= count($pasos) ?>)
        </button>
        <button class="edit-tab" onclick="switchTab('add')">
            <i class="fas fa-plus"></i> Añadir Paso
        </button>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- TAB 1: Información General de la Ruta      -->
    <!-- ═══════════════════════════════════════════ -->
    <div id="tab-info" class="tab-panel active">
        <div class="glass-card">
            <div class="section-title">
                <i class="fas fa-edit"></i> Datos de la Ruta
            </div>
            <form action="" method="POST">
                <div class="form-group">
                    <label>Título de la Ruta</label>
                    <input type="text" name="titulo" class="form-control" value="<?= htmlspecialchars($ruta->titulo) ?>" required>
                </div>
                <div class="form-group">
                    <label>Descripción General</label>
                    <textarea name="descripcion" class="form-control" rows="4"><?= htmlspecialchars($ruta->descripcion) ?></textarea>
                </div>
                <div class="form-group">
                    <label>Visibilidad</label>
                    <select name="privacidad" class="form-control">
                        <option value="publico" <?= $ruta->privacidad == 'publico' ? 'selected' : '' ?>>Público (Todos pueden verla)</option>
                        <option value="privado" <?= $ruta->privacidad == 'privado' ? 'selected' : '' ?>>Privado (Sólo tú)</option>
                    </select>
                </div>
                <button type="submit" name="update_route" class="btn btn-primary" style="width: 100%; padding: 14px;">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </form>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- TAB 2: Lista de Pasos Existentes           -->
    <!-- ═══════════════════════════════════════════ -->
    <div id="tab-steps" class="tab-panel">
        <div class="section-title">
            <i class="fas fa-layer-group"></i> Pasos de la Ruta
        </div>

        <?php if (empty($pasos)): ?>
            <div class="glass-card empty-state">
                <i class="fas fa-inbox"></i>
                <p>Esta ruta aún no tiene pasos.</p>
                <button onclick="switchTab('add')" class="btn btn-primary" style="margin-top: 16px;">
                    <i class="fas fa-plus"></i> Añadir el primero
                </button>
            </div>
        <?php else: ?>
            <?php foreach ($pasos as $p): ?>
                <div class="step-card" id="step-card-<?= $p->id ?>">
                    <div style="display: flex; align-items: start; gap: 16px;">
                        <div class="step-number"><?= $p->orden ?></div>
                        <div style="flex: 1; min-width: 0;">
                            <h4 style="margin-bottom: 6px; font-size: 1.05rem;"><?= htmlspecialchars($p->titulo) ?></h4>
                            <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5;">
                                <?= mb_strlen($p->contenido) > 180 ? htmlspecialchars(mb_substr($p->contenido, 0, 180)) . '...' : htmlspecialchars($p->contenido) ?>
                            </p>
                            <?php if ($p->codigo_snippet): ?>
                                <div class="code-preview">
                                    <pre style="margin: 0;"><code><?= htmlspecialchars($p->codigo_snippet) ?></code></pre>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="step-actions">
                            <button type="button" class="btn-edit-step" onclick="openEditModal(<?= $p->id ?>, <?= htmlspecialchars(json_encode($p->titulo)) ?>, <?= htmlspecialchars(json_encode($p->contenido)) ?>, <?= htmlspecialchars(json_encode($p->codigo_snippet ?? '')) ?>)">
                                <i class="fas fa-pen"></i> Editar
                            </button>
                            <button type="button" class="btn-delete-step" onclick="confirmDelete(<?= $p->id ?>, <?= htmlspecialchars(json_encode($p->titulo)) ?>)">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <a href="<?= BASE_URL ?>views/route_detail.php?id=<?= $ruta_id ?>" class="btn btn-outline" style="display: block; text-align: center; margin-top: 24px; padding: 14px;">
            <i class="fas fa-eye"></i> Ver Ruta Completa
        </a>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- TAB 3: Añadir Nuevo Paso                   -->
    <!-- ═══════════════════════════════════════════ -->
    <div id="tab-add" class="tab-panel">
        <div class="glass-card">
            <div class="section-title">
                <i class="fas fa-magic"></i> Nuevo Paso
            </div>
            <p style="color: var(--text-secondary); margin-bottom: 24px; font-size: 0.9rem;">
                Explica un concepto y sube el fragmento de código. Este paso se añadirá al final de la ruta.
            </p>
            <form action="" method="POST">
                <div class="form-group">
                    <label>Título del Paso</label>
                    <input type="text" name="step_titulo" class="form-control" placeholder="Ej. El Ciclo For" required>
                </div>
                <div class="form-group">
                    <label>Explicación / Contenido</label>
                    <textarea name="step_contenido" class="form-control" rows="6" placeholder="Explica cómo funciona..."></textarea>
                </div>
                <div class="form-group">
                    <label>Snippet de Código (Java / Otros)</label>
                    <textarea name="step_codigo" class="form-control" rows="8" style="font-family: 'Fira Code', monospace; background: #0f172a; color: #e2e8f0;" placeholder="for(int i=0; i<10; i++) { ... }"></textarea>
                </div>
                <button type="submit" name="add_step" class="btn btn-primary" style="width: 100%; padding: 14px;">
                    <i class="fas fa-plus-circle"></i> Guardar Paso
                </button>
            </form>
        </div>
    </div>

</div>

<!-- ═══════════════════ MODAL: Editar Paso ═══════════════════ -->
<div class="modal-overlay" id="editModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-pen" style="color: var(--primary-color);"></i> Editar Paso</h3>
            <button class="modal-close" onclick="closeEditModal()"><i class="fas fa-times"></i></button>
        </div>
        <form action="" method="POST">
            <input type="hidden" name="step_id" id="edit_step_id">
            <div class="form-group">
                <label>Título del Paso</label>
                <input type="text" name="step_titulo" id="edit_step_titulo" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Explicación / Contenido</label>
                <textarea name="step_contenido" id="edit_step_contenido" class="form-control" rows="6"></textarea>
            </div>
            <div class="form-group">
                <label>Snippet de Código</label>
                <textarea name="step_codigo" id="edit_step_codigo" class="form-control" rows="8" style="font-family: 'Fira Code', monospace; background: #0f172a; color: #e2e8f0;"></textarea>
            </div>
            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="closeEditModal()" class="btn btn-outline" style="flex: 1; padding: 14px;">Cancelar</button>
                <button type="submit" name="edit_step" class="btn btn-primary" style="flex: 2; padding: 14px;">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════════ MODAL: Confirmar Eliminación ═══════════════════ -->
<div class="confirm-overlay" id="confirmModal">
    <div class="confirm-box">
        <div class="confirm-icon">
            <i class="fas fa-trash-alt"></i>
        </div>
        <h3 style="margin-bottom: 8px;">¿Eliminar este paso?</h3>
        <p style="color: var(--text-secondary); font-size: 0.9rem;" id="confirm-step-name"></p>
        <form action="" method="POST">
            <input type="hidden" name="step_id" id="delete_step_id">
            <div class="confirm-buttons">
                <button type="button" class="confirm-cancel" onclick="closeConfirm()">Cancelar</button>
                <button type="submit" name="delete_step" class="confirm-delete">
                    <i class="fas fa-trash-alt"></i> Sí, Eliminar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// ─── Tabs ───
function switchTab(tab) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.edit-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    // Activar el tab correcto
    const tabs = document.querySelectorAll('.edit-tab');
    const map = { info: 0, steps: 1, add: 2 };
    if (tabs[map[tab]]) tabs[map[tab]].classList.add('active');
}

// ─── Modal Editar ───
function openEditModal(id, titulo, contenido, codigo) {
    document.getElementById('edit_step_id').value = id;
    document.getElementById('edit_step_titulo').value = titulo;
    document.getElementById('edit_step_contenido').value = contenido;
    document.getElementById('edit_step_codigo').value = codigo;
    document.getElementById('editModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('show');
    document.body.style.overflow = '';
}

// ─── Modal Confirmar Eliminación ───
function confirmDelete(id, titulo) {
    document.getElementById('delete_step_id').value = id;
    document.getElementById('confirm-step-name').textContent = '"' + titulo + '"';
    document.getElementById('confirmModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeConfirm() {
    document.getElementById('confirmModal').classList.remove('show');
    document.body.style.overflow = '';
}

// Cerrar modales con Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeEditModal();
        closeConfirm();
    }
});

// Cerrar modales al clicar fuera
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
document.getElementById('confirmModal').addEventListener('click', function(e) {
    if (e.target === this) closeConfirm();
});

// Si hubo una acción de paso, activar la pestaña de pasos
<?php if ($success && (isset($_POST['edit_step']) || isset($_POST['delete_step']) || isset($_POST['add_step']))): ?>
    switchTab('steps');
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
