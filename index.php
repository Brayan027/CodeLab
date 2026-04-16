<?php require_once __DIR__ . '/includes/header.php'; ?>

<section style="padding: 80px 0; text-align: center;">
    <h1 class="animate-in" style="font-size: 3.5rem; font-weight: 800; margin-bottom: 20px;">
        Aprende, Colabora y <span style="color: var(--primary-color);">Domina el Código</span>
    </h1>
    <p class="animate-in" style="color: var(--text-secondary); font-size: 1.2rem; max-width: 700px; margin: 0 auto 40px;">
        La plataforma definitiva para estudiantes universitarios. Crea rutas de aprendizaje, comparte código de Java y resuelve problemas con el apoyo de Inteligencia Artificial.
    </p>
    <div class="animate-in" style="display: flex; gap: 20px; justify-content: center;">
        <?php if (!is_logged_in()): ?>
            <a href="<?= BASE_URL ?>views/register.php" class="btn btn-primary" style="padding: 15px 40px; font-size: 1.1rem;">Empezar Ahora</a>
            <a href="<?= BASE_URL ?>views/forum.php" class="btn btn-outline" style="padding: 15px 40px; font-size: 1.1rem;">Ver Foros</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>views/learning_routes.php" class="btn btn-primary" style="padding: 15px 40px; font-size: 1.1rem;">Mis Rutas</a>
            <a href="<?= BASE_URL ?>views/create_route.php" class="btn btn-outline" style="padding: 15px 40px; font-size: 1.1rem;">Crear Contenido</a>
        <?php endif; ?>
    </div>
</section>

<section class="container" style="padding: 40px 0;">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
        <div class="glass-card animate-in">
            <i class="fas fa-route" style="font-size: 2rem; color: var(--primary-color); margin-bottom: 15px;"></i>
            <h3>Rutas de Aprendizaje</h3>
            <p style="color: var(--text-secondary);">Crea guías paso a paso como "Ciclos en Java" y ayúdanos a mapear el conocimiento.</p>
        </div>
        <div class="glass-card animate-in">
            <i class="fas fa-robot" style="font-size: 2rem; color: var(--secondary-color); margin-bottom: 15px;"></i>
            <h3>Asistencia con IA</h3>
            <p style="color: var(--text-secondary);">Obtén explicaciones automáticas y sugerencias sobre tu código usando Gemini AI.</p>
        </div>
        <div class="glass-card animate-in">
            <i class="fas fa-users" style="font-size: 2rem; color: var(--accent-color); margin-bottom: 15px;"></i>
            <h3>Colaboración Real</h3>
            <p style="color: var(--text-secondary);">Sigue a otros desarrolladores, comenta sus soluciones y chatea en tiempo real.</p>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
