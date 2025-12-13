
class AjaxLogin {
    constructor(formSelector) {
        this.form = document.querySelector(formSelector);
        this.submitBtn = this.form.querySelector('button[type="submit"]');
        this.originalBtnText = this.submitBtn.textContent;
        
        this.init();
    }
    
    init() {
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
    }
    
    async handleSubmit(e) {
        e.preventDefault();
        
        // Validation de base
        const username = this.form.querySelector('#username').value.trim();
        const password = this.form.querySelector('#password').value;
        
        if (!username || !password) {
            this.showAlert('Veuillez remplir tous les champs', 'error');
            return;
        }
        
        // Désactiver le bouton et afficher le loader
        this.setLoading(true);
        
        try {
            const response = await this.login(username, password);
            
            if (response.success) {
                this.showAlert('Connexion réussie ! Redirection...', 'success');
                
                // Animation de succès
                this.form.classList.add('login-success');
                
                // Redirection après 1 seconde
                setTimeout(() => {
                    window.location.href = response.redirect;
                }, 1000);
            } else {
                this.showAlert(response.message || 'Identifiants incorrects', 'error');
                this.setLoading(false);
                
                // Effet de secousse sur erreur
                this.form.classList.add('shake');
                setTimeout(() => this.form.classList.remove('shake'), 500);
            }
        } catch (error) {
            console.error('Erreur de connexion:', error);
            this.showAlert('Erreur de connexion. Veuillez réessayer.', 'error');
            this.setLoading(false);
        }
    }
    
    async login(username, password) {
        const formData = new FormData();
        formData.append('username', username);
        formData.append('password', password);
        formData.append('ajax', '1');
        
        const response = await fetch('/TDW_project/auth/login', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            throw new Error('Erreur réseau');
        }
        
        return await response.json();
    }
    
    setLoading(isLoading) {
        this.submitBtn.disabled = isLoading;
        
        if (isLoading) {
            this.submitBtn.innerHTML = `
                <span class="spinner"></span>
                <span>Connexion...</span>
            `;
        } else {
            this.submitBtn.textContent = this.originalBtnText;
        }
    }
    
    showAlert(message, type = 'info') {
        // Supprimer les anciennes alertes
        const oldAlerts = this.form.querySelectorAll('.alert');
        oldAlerts.forEach(alert => alert.remove());
        
        // Créer la nouvelle alerte
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.textContent = message;
        
        // Insérer avant le formulaire
        this.form.insertBefore(alert, this.form.firstChild);
        
        // Animation d'entrée
        setTimeout(() => alert.classList.add('show'), 10);
        
        // Auto-fermeture après 5 secondes (sauf succès)
        if (type !== 'success') {
            setTimeout(() => {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        }
    }
}

// Initialiser le système AJAX
document.addEventListener('DOMContentLoaded', () => {
    new AjaxLogin('form');
});