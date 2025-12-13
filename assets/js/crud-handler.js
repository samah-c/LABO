/**
 * crud-handler.js - Gestionnaire universel pour les opérations CRUD
 */

const CrudHandler = {
    
    /**
     * Ouvrir la modale d'ajout
     */
    openAddModal: function(entityName) {
        const baseUrl = window.location.pathname;
        this.loadForm(baseUrl + '/form');
    },
    
    /**
     * Voir un élément
     */
    view: function(id) {
        const baseUrl = window.location.pathname;
        window.location.href = baseUrl + '/view/' + id;
    },
    
    /**
     * Modifier un élément
     */
    edit: function(id) {
        const baseUrl = window.location.pathname;
        this.loadForm(baseUrl + '/form/' + id);
    },
    
    /**
     * Supprimer un élément
     */
    delete: function(id) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')) {
            return;
        }
        
        const baseUrl = window.location.pathname;
        const apiUrl = baseUrl.replace('/admin/', '/api/admin/');
        
        fetch(apiUrl + '/' + id, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showNotification(data.message || 'Suppression réussie', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                this.showNotification(data.message || 'Erreur lors de la suppression', 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            this.showNotification('Erreur de connexion', 'error');
        });
    },
    
    /**
     * Charger un formulaire dans la modale
     */
    loadForm: function(url) {
        const modal = this.getModal();
        if (!modal) {
            console.error('Modale introuvable');
            return;
        }
        
        const container = modal.querySelector('#modal-form-container, .modal-body');
        if (!container) {
            console.error('Conteneur du formulaire introuvable');
            return;
        }
        
        // Afficher un loader
        container.innerHTML = '<div class="loader">Chargement...</div>';
        modal.style.display = 'block';
        
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            container.innerHTML = html;
            this.attachFormHandler(container.querySelector('form'));
        })
        .catch(error => {
            console.error('Erreur:', error);
            container.innerHTML = '<p class="error">Erreur lors du chargement du formulaire</p>';
        });
    },
    
    /**
     * Attacher le gestionnaire de soumission du formulaire
     */
    attachFormHandler: function(form) {
        if (!form) return;
        
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitForm(form);
        });
    },
    
    /**
     * Soumettre un formulaire
     */
    submitForm: function(form) {
        const formData = new FormData(form);
        const url = form.action;
        
        // Désactiver le bouton de soumission
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Enregistrement...';
        }
        
        fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showNotification(data.message || 'Enregistrement réussi', 'success');
                this.closeModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                // Afficher les erreurs de validation
                if (data.errors) {
                    this.displayErrors(form, data.errors);
                }
                this.showNotification(data.message || 'Erreur lors de l\'enregistrement', 'error');
                
                // Réactiver le bouton
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Enregistrer';
                }
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            this.showNotification('Erreur de connexion', 'error');
            
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Enregistrer';
            }
        });
    },
    
    /**
     * Afficher les erreurs de validation
     */
    displayErrors: function(form, errors) {
        // Supprimer les anciennes erreurs
        form.querySelectorAll('.field-error').forEach(el => el.remove());
        
        // Afficher les nouvelles erreurs
        for (const [field, message] of Object.entries(errors)) {
            const input = form.querySelector(`[name="${field}"]`);
            if (input) {
                const error = document.createElement('div');
                error.className = 'field-error';
                error.textContent = message;
                input.parentNode.appendChild(error);
                input.classList.add('error');
            }
        }
    },
    
    /**
     * Obtenir la modale
     */
    getModal: function() {
        // Chercher la première modale disponible
        return document.querySelector('.modal[id$="-modal"]');
    },
    
    /**
     * Fermer la modale
     */
    closeModal: function() {
        const modal = this.getModal();
        if (modal) {
            modal.style.display = 'none';
            const container = modal.querySelector('#modal-form-container, .modal-body');
            if (container) {
                container.innerHTML = '';
            }
        }
    },
    
    /**
     * Exporter des données
     */
    export: function(baseUrl, format = 'csv') {
        window.location.href = baseUrl + '?export=' + format;
    },
    
    /**
     * Afficher une notification
     */
    showNotification: function(message, type = 'info') {
        // Supprimer les notifications existantes
        const existing = document.querySelector('.notification');
        if (existing) {
            existing.remove();
        }
        
        // Créer la notification
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button class="notification-close" onclick="this.parentElement.remove()">×</button>
        `;
        
        document.body.appendChild(notification);
        
        // Animation d'entrée
        setTimeout(() => notification.classList.add('show'), 10);
        
        // Supprimer après 5 secondes
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    },
    
    /**
     * Appliquer les filtres
     */
    applyFilters: function() {
        const searchInput = document.getElementById('search-input');
        const filterSelects = document.querySelectorAll('.filter-select');
        const params = new URLSearchParams();
        
        if (searchInput && searchInput.value) {
            params.append('search', searchInput.value);
        }
        
        filterSelects.forEach(select => {
            if (select.value) {
                params.append(select.name, select.value);
            }
        });
        
        const baseUrl = window.location.pathname;
        window.location.href = baseUrl + '?' + params.toString();
    }
};

// ============================================================
// INITIALISATION AU CHARGEMENT DE LA PAGE
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    
    // Gestion de la fermeture des modales
    document.querySelectorAll('.modal-close, .modal .btn-secondary').forEach(btn => {
        btn.addEventListener('click', () => CrudHandler.closeModal());
    });
    
    // Fermer la modale en cliquant à l'extérieur
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                CrudHandler.closeModal();
            }
        });
    });
    
    // Gestion du bouton de filtres
    const applyFiltersBtn = document.getElementById('apply-filters');
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', () => CrudHandler.applyFilters());
    }
    
    // Recherche en temps réel (optionnel)
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                CrudHandler.applyFilters();
            }
        });
    }
    
    // Gestion des touches du clavier
    document.addEventListener('keydown', function(e) {
        // ESC pour fermer la modale
        if (e.key === 'Escape') {
            CrudHandler.closeModal();
        }
    });
});

// ============================================================
// FONCTIONS GLOBALES POUR COMPATIBILITÉ
// ============================================================

function viewItem(id) {
    CrudHandler.view(id);
}

function editItem(id) {
    CrudHandler.edit(id);
}

function deleteItem(id) {
    CrudHandler.delete(id);
}

function openAddModal() {
    CrudHandler.openAddModal();
}

function closeModal() {
    CrudHandler.closeModal();
}

function exportData(format = 'csv') {
    const baseUrl = window.location.pathname;
    CrudHandler.export(baseUrl, format);
}

// ============================================================
// STYLES CSS POUR LES NOTIFICATIONS
// ============================================================

// Injecter les styles si nécessaire
if (!document.getElementById('crud-handler-styles')) {
    const style = document.createElement('style');
    style.id = 'crud-handler-styles';
    style.textContent = `
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 10000;
            opacity: 0;
            transform: translateX(400px);
            transition: all 0.3s ease;
        }
        
        .notification.show {
            opacity: 1;
            transform: translateX(0);
        }
        
        .notification-success {
            background: #2ecc71;
            color: white;
        }
        
        .notification-error {
            background: #e74c3c;
            color: white;
        }
        
        .notification-info {
            background: #3498db;
            color: white;
        }
        
        .notification-warning {
            background: #f39c12;
            color: white;
        }
        
        .notification-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            margin-left: 10px;
            line-height: 1;
        }
        
        .loader {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }
        
        .field-error {
            color: #e74c3c;
            font-size: 0.85em;
            margin-top: 5px;
        }
        
        input.error,
        select.error,
        textarea.error {
            border-color: #e74c3c;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-body {
            padding: 20px;
            max-height: 60vh;
            overflow-y: auto;
        }
        
        .modal-footer {
            padding: 20px;
            border-top: 1px solid #eee;
            text-align: right;
        }
        
        .modal-close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            background: none;
            border: none;
        }
        
        .modal-close:hover,
        .modal-close:focus {
            color: #000;
        }
    `;
    document.head.appendChild(style);
}