<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    .teacher-container { display: flex; gap: 30px; margin-top: 30px; min-height: 80vh; }
    .teacher-sidebar { width: 260px; flex-shrink: 0; }
    .teacher-content { flex: 1; }
    .sidebar-nav { list-style: none; padding: 0; position: sticky; top: 100px; }
    .sidebar-nav li { margin-bottom: 10px; }
    .sidebar-nav a { 
        display: flex; align-items: center; gap: 12px; padding: 15px 20px; 
        border-radius: 12px; color: var(--text-primary); text-decoration: none;
        background: var(--glass-bg); border: 1px solid var(--glass-border);
        width: 100%; text-align: left; cursor: pointer; transition: 0.3s;
        font-weight: 500;
    }
    .sidebar-nav a:hover, .sidebar-nav .active {
        background: var(--primary-color); color: white; border-color: var(--primary-color);
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
    }
</style>

<aside class="teacher-sidebar">
    <ul class="sidebar-nav">
        <li>
            <a href="<?= BASE_URL ?>views/teacher_dashboard.php" class="<?= $current_page == 'teacher_dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-th-large"></i> Panel Principal
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>views/teacher_mail.php" class="<?= $current_page == 'teacher_mail.php' ? 'active' : '' ?>">
                <i class="fas fa-envelope"></i> Mensajería
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>views/research_dashboard.php" class="<?= $current_page == 'research_dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-microscope"></i> Investigación
            </a>
        </li>
        <li style="margin-top: 30px;">
            <a href="<?= BASE_URL ?>views/teacher_dashboard.php?action=new_group" style="background: rgba(16, 185, 129, 0.1); color: #10b981; border-color: #10b981;">
                <i class="fas fa-plus"></i> Nuevo Grupo
            </a>
        </li>
    </ul>
</aside>
