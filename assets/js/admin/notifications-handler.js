/**
 * Handler pour la gestion des notifications
 */

// Détection automatique de baseUrl et normalisation
let baseUrl = window.baseUrl || (() => {
    const path = window.location.pathname;
    const match = path.match(/^(\/[^\/]+\/)/);
    return match ? match[1] : '/';
})();

// S'assurer que baseUrl se termine par /
if (!baseUrl.endsWith('/')) {
    baseUrl += '/';
}

class NotificationHandler {
    constructor() {
        this.modal = document.getElementById('notification-modal');
        this.modalContainer = document.getElementById('modal-form-container');
    }

    /**
     * Ouvrir la modale d'ajout
     */
    openAddModal() {
        this.loadForm();
        this.openModal();
    }

    /**
     * Charger le formulaire
     */
    async loadForm(id = null) {
        try {
            const url = id 
                ? `${baseUrl}admin/notifications/form/${id}`
                : `${baseUrl}admin/notifications/form`;
            
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error('Erreur lors du chargement du formulaire');
            }
            
            const html = await response.text();
            this.modalContainer.innerHTML = html;
            
            // Attacher l'événement de soumission
            const form = document.getElementById('notification-form');
            if (form) {
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.submitForm(form);
                });
            }
            
            // Initialiser l'affichage des champs de destinataire
            if (typeof toggleDestinataire === 'function') {
                toggleDestinataire();
            }
        } catch (error) {
            console.error('Erreur:', error);
            showNotification('Erreur lors du chargement du formulaire', 'error');
        }
    }

    /**
     * Soumettre le formulaire
     */
    async submitForm(form) {
        try {
            const formData = new FormData(form);
            
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification(data.message || 'Notification envoyée avec succès', 'success');
                this.closeModal();
                
                // Recharger la page après 1 seconde
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                if (data.errors) {
                    // Afficher les erreurs de validation
                    Object.keys(data.errors).forEach(field => {
                        const input = form.querySelector(`[name="${field}"]`);
                        if (input) {
                            input.classList.add('error');
                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'error-message';
                            errorDiv.textContent = data.errors[field];
                            input.parentNode.appendChild(errorDiv);
                        }
                    });
                }
                showNotification(data.message || 'Erreur lors de l\'envoi', 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            showNotification('Erreur lors de l\'envoi de la notification', 'error');
        }
    }

    /**
     * Supprimer une notification
     */
    async deleteItem(id) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette notification ?')) {
            return;
        }
        
        try {
            const response = await fetch(`${baseUrl}admin/notifications/delete/${id}`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification(data.message || 'Notification supprimée', 'success');
                
                // Recharger la page
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showNotification(data.message || 'Erreur lors de la suppression', 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            showNotification('Erreur lors de la suppression', 'error');
        }
    }

    /**
     * Ouvrir la modale
     */
    openModal() {
        if (this.modal) {
            this.modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }

    /**
     * Fermer la modale
     */
    closeModal() {
        if (this.modal) {
            this.modal.style.display = 'none';
            document.body.style.overflow = '';
            this.modalContainer.innerHTML = '';
        }
    }
}

// Initialisation
const notifications = new NotificationHandler();

// Fonctions globales
function openAddModal() {
    notifications.openAddModal();
}

function deleteItem(id) {
    notifications.deleteItem(id);
}

function closeModal() {
    notifications.closeModal();
}

/**
 * Fonction pour gérer l'affichage des champs de destinataire
 */
function toggleDestinataire() {
    const type = document.getElementById('destinataire_type')?.value;
    
    if (!type) return;
    
    // Cacher tous les champs conditionnels
    const roleSelect = document.getElementById('role-select');
    const equipeSelect = document.getElementById('equipe-select');
    const membreSelect = document.getElementById('membre-select');
    
    if (roleSelect) roleSelect.style.display = 'none';
    if (equipeSelect) equipeSelect.style.display = 'none';
    if (membreSelect) membreSelect.style.display = 'none';
    
    // Afficher le champ approprié
    switch(type) {
        case 'role':
            if (roleSelect) roleSelect.style.display = 'block';
            break;
        case 'equipe':
            if (equipeSelect) equipeSelect.style.display = 'block';
            break;
        case 'individuel':
            if (membreSelect) membreSelect.style.display = 'block';
            break;
    }
}