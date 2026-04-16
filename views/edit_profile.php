<?php
require_once __DIR__ . '/../includes/header.php';

if (!is_logged_in()) redirect('views/login.php');

$user_id = $_SESSION['user_id'];
$user = get_user_data($pdo, $user_id);

$success = '';
$error = '';

// ─── Actualizar datos del perfil ───
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $nombre = sanitize($_POST['nombre_completo']);
    $usuario = sanitize($_POST['usuario']);
    $email = sanitize($_POST['email']);
    $bio = sanitize($_POST['bio']);

    if (empty($nombre) || empty($usuario) || empty($email)) {
        $error = 'Nombre, usuario y email son obligatorios.';
    } else {
        // Verificar que el usuario/email no estén en uso por otro
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE (usuario = ? OR email = ?) AND id != ?");
        $stmt->execute([$usuario, $email, $user_id]);
        if ($stmt->fetch()) {
            $error = 'Ese nombre de usuario o email ya está en uso.';
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre_completo = ?, usuario = ?, email = ?, bio = ? WHERE id = ?");
            $stmt->execute([$nombre, $usuario, $email, $bio, $user_id]);
            $success = 'Perfil actualizado correctamente.';
            $user = get_user_data($pdo, $user_id);
        }
    }
}

// ─── Cambiar contraseña ───
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (empty($current) || empty($new) || empty($confirm)) {
        $error = 'Todos los campos de contraseña son obligatorios.';
    } elseif ($new !== $confirm) {
        $error = 'Las contraseñas nuevas no coinciden.';
    } elseif (strlen($new) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif (!password_verify($current, $user->password)) {
        $error = 'La contraseña actual es incorrecta.';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $stmt->execute([$hash, $user_id]);
        $success = 'Contraseña actualizada correctamente.';
    }
}
?>

<style>
    .settings-container {
        max-width: 700px;
        margin: 40px auto;
    }
    .settings-tabs {
        display: flex;
        gap: 0;
        margin-bottom: 30px;
        border-bottom: 2px solid var(--glass-border);
    }
    .settings-tab {
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
    .settings-tab:hover {
        color: var(--primary-color);
    }
    .settings-tab.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
    }
    .settings-panel {
        display: none;
        animation: fadeIn 0.3s ease-out;
    }
    .settings-panel.active {
        display: block;
    }
    .profile-avatar-section {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 28px;
        padding-bottom: 24px;
        border-bottom: 1px solid var(--glass-border);
    }
    .profile-avatar-section img {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        border: 3px solid var(--primary-color);
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
    .password-hint {
        font-size: 0.8rem;
        color: var(--text-secondary);
        margin-top: 4px;
    }
</style>

<div class="settings-container animate-in">
    
    <!-- Breadcrumb -->
    <div style="margin-bottom: 24px; display: flex; align-items: center; gap: 8px; font-size: 0.9rem;">
        <a href="<?= BASE_URL ?>views/profile.php" style="color: var(--text-secondary); text-decoration: none;">Mi Perfil</a>
        <i class="fas fa-chevron-right" style="font-size: 0.7rem; color: var(--text-secondary);"></i>
        <span style="color: var(--primary-color); font-weight: 600;">Ajustes</span>
    </div>

    <h1 style="font-size: 1.8rem; margin-bottom: 8px;">
        <i class="fas fa-cog" style="color: var(--primary-color);"></i> Ajustes del Perfil
    </h1>
    <p style="color: var(--text-secondary); margin-bottom: 30px;">Actualiza tu información personal y contraseña.</p>

    <!-- Mensajes -->
    <?php if ($success): ?>
        <div class="alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="settings-tabs">
        <button class="settings-tab active" onclick="switchSettingsTab('profile')">
            <i class="fas fa-user"></i> Perfil
        </button>
        <button class="settings-tab" onclick="switchSettingsTab('password')">
            <i class="fas fa-lock"></i> Contraseña
        </button>
    </div>

    <!-- ═══ TAB 1: Perfil ═══ -->
    <div id="tab-profile" class="settings-panel active">
        <div class="glass-card">
            <div class="profile-avatar-section">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($user->nombre_completo) ?>&size=80&background=random" alt="Avatar">
                <div>
                    <h3 style="margin: 0;"><?= htmlspecialchars($user->nombre_completo) ?></h3>
                    <p style="color: var(--text-secondary); font-size: 0.9rem;">@<?= htmlspecialchars($user->usuario) ?></p>
                </div>
            </div>

            <form action="" method="POST">
                <div class="form-group">
                    <label>Nombre Completo</label>
                    <input type="text" name="nombre_completo" class="form-control" value="<?= htmlspecialchars($user->nombre_completo) ?>" required>
                </div>
                <div style="display: flex; gap: 16px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Nombre de Usuario</label>
                        <input type="text" name="usuario" class="form-control" value="<?= htmlspecialchars($user->usuario) ?>" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user->email) ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Biografía</label>
                    <textarea name="bio" class="form-control" rows="4" placeholder="Cuéntanos sobre ti..."><?= htmlspecialchars($user->bio ?? '') ?></textarea>
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary" style="width: 100%; padding: 14px;">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </form>
        </div>
    </div>

    <!-- ═══ TAB 2: Contraseña ═══ -->
    <div id="tab-password" class="settings-panel">
        <div class="glass-card">
            <div style="margin-bottom: 24px;">
                <h3 style="margin: 0 0 8px;"><i class="fas fa-shield-alt" style="color: var(--secondary-color);"></i> Cambiar Contraseña</h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">Asegúrate de usar una contraseña segura que no uses en otros sitios.</p>
            </div>

            <form action="" method="POST">
                <div class="form-group">
                    <label>Contraseña Actual</label>
                    <input type="password" name="current_password" class="form-control" placeholder="Tu contraseña actual" required>
                </div>
                <div class="form-group">
                    <label>Nueva Contraseña</label>
                    <input type="password" name="new_password" class="form-control" placeholder="Mínimo 6 caracteres" required>
                    <p class="password-hint">Usa al menos 6 caracteres con letras y números.</p>
                </div>
                <div class="form-group">
                    <label>Confirmar Nueva Contraseña</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Repite la nueva contraseña" required>
                </div>
                <button type="submit" name="change_password" class="btn btn-primary" style="width: 100%; padding: 14px;">
                    <i class="fas fa-key"></i> Cambiar Contraseña
                </button>
            </form>
        </div>
    </div>

</div>

<script>
function switchSettingsTab(tab) {
    document.querySelectorAll('.settings-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    const tabs = document.querySelectorAll('.settings-tab');
    const map = { profile: 0, password: 1 };
    if (tabs[map[tab]]) tabs[map[tab]].classList.add('active');
}

// Si hubo error de contraseña, abrir esa pestaña
<?php if ($error && isset($_POST['change_password'])): ?>
    switchSettingsTab('password');
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
