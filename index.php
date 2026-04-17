<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="animate-in">
    <!-- Hero Section con Gradiente Animado -->
    <section style="padding: 100px 0 60px; text-align: center; position: relative; overflow: hidden;">
        <div style="position: absolute; top: -100px; left: 50%; transform: translateX(-50%); width: 600px; height: 600px; background: radial-gradient(circle, rgba(59, 130, 246, 0.15) 0%, transparent 70%); z-index: -1;"></div>
        
        <h1 style="font-size: 4rem; font-weight: 900; line-height: 1.1; margin-bottom: 25px; background: linear-gradient(135deg, #1e293b 0%, #3b82f6 50%, #8b5cf6 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; filter: drop-shadow(0 10px 10px rgba(0,0,0,0.05));">
            Evoluciona tu Código <br>con <span style="color: var(--primary-color);">Inteligencia Colectiva</span>
        </h1>
        
        <p style="color: var(--text-secondary); font-size: 1.3rem; max-width: 800px; margin: 0 auto 45px; line-height: 1.6;">
            La plataforma de investigación donde el <strong>pensamiento lógico</strong> se encuentra con la <strong>IA Generativa</strong>. Domina múltiples tecnologías a través de rutas colaborativas, repositorios comunitarios y mentoría inteligente adaptada a tus necesidades.
        </p>
        
        <div style="display: flex; gap: 20px; justify-content: center; align-items: center; flex-wrap: wrap;">
            <?php if (!is_logged_in()): ?>
                <a href="<?= BASE_URL ?>views/register.php" class="btn btn-primary" style="padding: 18px 45px; font-size: 1.1rem; border-radius: 50px; box-shadow: 0 15px 30px rgba(59, 130, 246, 0.3); transform: scale(1.05);">
                    <i class="fas fa-rocket"></i> Unirse a la Comunidad
                </a>
                <a href="<?= BASE_URL ?>views/login.php" class="btn btn-outline" style="padding: 18px 45px; font-size: 1.1rem; border-radius: 50px;">
                    Iniciar Sesión
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>views/learning_routes.php" class="btn btn-primary" style="padding: 18px 45px; font-size: 1.1rem; border-radius: 50px; box-shadow: 0 15px 30px rgba(59, 130, 246, 0.3);">
                    Explorar Rutas
                </a>
                <a href="<?= BASE_URL ?>views/snippets.php" class="btn btn-outline" style="padding: 18px 45px; font-size: 1.1rem; border-radius: 50px;">
                    Ver Repositorio
                </a>
            <?php endif; ?>
        </div>

        <!-- Lenguajes Soportados (Píldoras) -->
        <div style="margin-top: 60px; display: flex; justify-content: center; gap: 15px; opacity: 0.6; flex-wrap: wrap;">
            <span class="badge-tech"><i class="fab fa-node-js"></i> Node.js</span>
            <span class="badge-tech"><i class="fas fa-database"></i> SQL</span>
            <span class="badge-tech"><i class="fab fa-react"></i> React</span>
            <span class="badge-tech"><i class="fab fa-java"></i> Java</span>
            <span class="badge-tech"><i class="fab fa-python"></i> Python</span>
            <span class="badge-tech"><i class="fas fa-server"></i> Express</span>
        </div>
    </section>

    <!-- Secciones de Valor (Grid Moderno) -->
    <section class="container" style="padding: 40px 0 100px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 35px;">
            <div class="glass-card home-feature-card">
                <div class="icon-circle" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                    <i class="fas fa-route"></i>
                </div>
                <h3>Rutas Dinámicas</h3>
                <p>Crea guías interactivas mezclando bloques de texto y código real. La forma más estética de documentar tu aprendizaje.</p>
            </div>
            
            <div class="glass-card home-feature-card">
                <div class="icon-circle" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                    <i class="fas fa-brain"></i>
                </div>
                <h3>Mentoría con IA</h3>
                <p>Gemini AI analiza tus fragmentos y te ofrece una comprensión profunda, no solo soluciones automáticas. Fomenta tu autonomía.</p>
            </div>
            
            <div class="glass-card home-feature-card">
                <div class="icon-circle" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Métricas de Impacto</h3>
                <p>Mide cuántas veces tu código ayuda a otros. Un ecosistema diseñado para valorar la colaboración real entre estudiantes.</p>
            </div>
        </div>
    </section>
</div>

<style>
    .badge-tech {
        background: rgba(15, 23, 42, 0.05);
        padding: 8px 18px;
        border-radius: 30px;
        font-size: 0.85rem;
        font-weight: 600;
        color: #64748b;
        border: 1px solid rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .home-feature-card {
        padding: 40px;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        border: 1px solid rgba(255,255,255,0.8);
    }
    
    .home-feature-card:hover {
        transform: translateY(-15px);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
        border-color: var(--primary-color);
    }
    
    .icon-circle {
        width: 60px;
        height: 60px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 25px;
    }
    
    .home-feature-card h3 {
        margin-bottom: 15px;
        font-size: 1.4rem;
        font-weight: 700;
    }
    
    .home-feature-card p {
        color: #64748b;
        line-height: 1.7;
        font-size: 1rem;
    }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
