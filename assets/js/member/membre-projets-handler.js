/**
 * membre-projets-handler.js - Gestionnaire pour l'édition de projets (membre responsable)
 */

class MembreProjetsHandler {
    constructor() {
        this.baseUrl = window.location.origin + '/TDW_project/membre/projets';
        this.currentProjetId = null;
        this.init();
    }
    
    init() {
        this.attachEventListeners();
        console.log(' Gestionnaire de projets membre initialisé');
    }
    
    attachEventListeners() {
        const modal = document.getElementById('projet-modal');
        if (modal) {
            modal.onclick = (e) => {
                if (e.target === modal) this.closeModal();
            };
        }
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.closeModal();
        });
    }
    
    async edit(id) {
        await this.loadForm(id);
    }
    
    async loadForm(id) {
        const modal = document.getElementById('projet-modal');
        const container = document.getElementById('modal-form-container');
        
        if (!modal || !container) {
            console.error('Modal ou conteneur introuvable');
            return;
        }
        
        const modalTitle = modal.querySelector('.modal-header h2');
        if (modalTitle) {
            modalTitle.textContent = 'Modifier le projet';
        }
        
        container.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="spinner"></div>Chargement...</div>';
        modal.style.display = 'flex';
        
        try {
            const url = `${this.baseUrl}/form/${id}`;
            
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            if (!response.ok) throw new Error('Erreur de chargement');
            
            const html = await response.text();
            container.innerHTML = html;
            this.currentProjetId = id;
            
            // Attacher l'événement de soumission
            this.attachFormSubmit();
            
        } catch (error) {
            console.error('Erreur:', error);
            container.innerHTML = '<p style="color: red; text-align: center; padding: 40px;">Erreur lors du chargement du formulaire</p>';
        }
    }
    
    attachFormSubmit() {
        const form = document.getElementById('projet-form');
        if (!form) return;
        
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            
            // Désactiver le bouton pendant l'envoi
            submitBtn.disabled = true;
            submitBtn.textContent = 'Enregistrement...';
            
            try {
                const formData = new FormData(form);
                
                const response = await fetch(`${this.baseUrl}/save`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.showNotification(data.message || 'Projet mis à jour avec succès', 'success');
                    this.closeModal();
                    
                    // Recharger la page après un court délai
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    // Afficher les erreurs de validation
                    if (data.errors) {
                        let errorMsg = 'Erreurs de validation :\n';
                        for (let field in data.errors) {
                            errorMsg += `- ${data.errors[field]}\n`;
                        }
                        this.showNotification(errorMsg, 'error');
                    } else {
                        this.showNotification(data.message || 'Erreur lors de l\'enregistrement', 'error');
                    }
                    
                    // Réactiver le bouton
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            } catch (error) {
                console.error('Erreur:', error);
                this.showNotification('Erreur de connexion au serveur', 'error');
                
                // Réactiver le bouton
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    }
    
    closeModal() {
        const modal = document.getElementById('projet-modal');
        if (modal) {
            modal.style.display = 'none';
            const container = document.getElementById('modal-form-container');
            if (container) container.innerHTML = '';
            this.currentProjetId = null;
        }
    }
    
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            z-index: 10000;
            animation: slideIn 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        
        const colors = {
            success: '#10B981',
            error: '#EF4444',
            warning: '#F59E0B',
            info: '#3B82F6'
        };
        
        notification.style.background = colors[type] || colors.info;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialisation globale
let projets;
document.addEventListener('DOMContentLoaded', () => {
    projets = new MembreProjetsHandler();
});
