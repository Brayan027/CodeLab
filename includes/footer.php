    </main>
    <footer style="margin-top: 50px; padding: 40px 0; border-top: 1px solid var(--glass-border); text-align: center; color: var(--text-secondary);">
        <div class="container">
      
        @2026
        </div>
    </footer>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-python.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-java.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/line-numbers/prism-line-numbers.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/line-numbers/prism-line-numbers.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/main.js"></script>

    <!-- Modal para Insertar Código -->
    <div id="codeModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:3000; align-items:center; justify-content:center; padding: 20px; backdrop-filter: blur(5px);">
        <div class="glass-card" style="max-width: 600px; width: 100%; background: white;">
            <h3 style="margin-bottom: 15px;"><i class="fas fa-code"></i> Insertar Bloque de Código</h3>
            <div class="form-group">
                <label>Lenguaje</label>
                <select id="codeLang" class="form-control" style="margin-bottom: 15px;">
                    <option value="javascript">JavaScript</option>
                    <option value="java">Java</option>
                    <option value="python">Python</option>
                    <option value="php">PHP</option>
                    <option value="css">CSS</option>
                    <option value="sql">SQL</option>
                    <option value="markup">HTML/XML</option>
                </select>
            </div>
            <div class="form-group">
                <label>Pega tu código aquí</label>
                <textarea id="codeBody" class="form-control" rows="10" placeholder="// Escribe o pega tu código aquí..." style="font-family: monospace; font-size: 0.9rem;"></textarea>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="confirmCodeInsertion()" class="btn btn-primary" style="flex: 2;">Insertar en el editor</button>
                <button type="button" onclick="closeCodeModal()" class="btn btn-outline" style="flex: 1;">Cancelar</button>
            </div>
        </div>
    </div>

    <!-- Contenedor de Toasts -->
    <div id="toast-container"></div>

    <!-- Modal Global de Suspensión (Solo para Docentes) -->
    <?php if (is_logged_in() && $_SESSION['rol'] == 'docente'): ?>
    <div id="modalSuspender" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; padding: 20px;">
        <div class="glass-card" style="max-width: 400px; width: 100%; background: white;">
            <h3 id="suspendTitle">Suspender Usuario</h3>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 20px;">El usuario no podrá acceder a la plataforma hasta que expire el tiempo.</p>
            <input type="hidden" id="suspendUserId">
            <div class="form-group">
                <label>Días de Suspensión (0 para levantar)</label>
                <input type="number" id="suspendDays" class="form-control" value="1" min="0">
            </div>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button onclick="confirmSuspension()" class="btn btn-primary" style="flex: 1; background: #f59e0b; border: none;">Confirmar</button>
                <button type="button" onclick="document.getElementById('modalSuspender').style.display='none'" class="btn btn-outline" style="flex: 1;">Cancelar</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
    let activeTextareaId = null;

    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-info-circle';
        toast.innerHTML = `<i class="fas ${icon}"></i> <span>${message}</span>`;
        
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }

    // Funciones de Suspensión Global
    function openSuspendModal(userId, userName) {
        if (!document.getElementById('modalSuspender')) return;
        document.getElementById('suspendUserId').value = userId;
        document.getElementById('suspendTitle').innerText = 'Suspender a ' + userName;
        document.getElementById('modalSuspender').style.display = 'flex';
    }

    function confirmSuspension() {
        const userId = document.getElementById('suspendUserId').value;
        const days = document.getElementById('suspendDays').value;

        const formData = new FormData();
        formData.append('usuario_id', userId);
        formData.append('dias', days);

        fetch('<?= BASE_URL ?>api/suspend_user.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast(data.message);
                document.getElementById('modalSuspender').style.display = 'none';
                if (typeof refreshUserList === 'function') refreshUserList(); 
            } else {
                showToast(data.error, 'info');
            }
        });
    }

    function openCodeModal(textareaId) {
        activeTextareaId = textareaId;
        document.getElementById('codeBody').value = '';
        document.getElementById('codeModal').style.display = 'flex';
    }

    function closeCodeModal() {
        document.getElementById('codeModal').style.display = 'none';
    }

    function confirmCodeInsertion() {
        const lang = document.getElementById('codeLang').value;
        const code = document.getElementById('codeBody').value;
        const textarea = document.getElementById(activeTextareaId);
        
        if (!code.trim()) {
            alert('Por favor escribe algo de código.');
            return;
        }

        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;
        const before = text.substring(0, start);
        const after  = text.substring(end, text.length);
        
        textarea.value = before + "\n```" + lang + "\n" + code + "\n```\n" + after;
        closeCodeModal();
        textarea.focus();
    }

    function toggleSave(pId, btn) {
        const formData = new FormData();
        formData.append('pregunta_id', pId);

        fetch('<?= BASE_URL ?>api/save_forum.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const icon = btn.querySelector('i');
                if (data.action === 'added') {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    btn.style.color = 'var(--accent-color)';
                    showToast('Guardado en favoritos');
                } else {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    btn.style.color = '#94a3b8';
                    showToast('Eliminado de favoritos', 'info');
                }
            } else {
                showToast(data.error, 'info');
            }
        });
    }
    </script>
</body>
</html>
