/**
 * equipements-handler.js - Gestionnaire pour les équipements
 * VERSION CORRIGÉE avec les bonnes routes
 */

class EquipementsHandler {
    constructor() {
        this.baseUrl = '/TDW_project/admin/equipements/equipements';
        this.apiUrl = '/TDW_project/api/admin/equipements';
        this.currentEquipementId = null;
        this.init();
    }
    
    init() {
        this.attachEventListeners();
        this.initFilters();
        this.checkEmptyTable();
    }
    
    attachEventListeners() {
        const closeBtn = document.querySelector('.modal-close');
        if (closeBtn) {
            closeBtn.onclick = () => this.closeModal();
        }
        
        const modal = document.getElementById('equipement-modal');
        if (modal) {
            modal.onclick = (e) => {
                if (e.target === modal) {
                    this.closeModal();
                }
            };
        }
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
            }
        });
    }
    
    // NAVIGATION
    view(id) {
        window.location.href = `${this.baseUrl}/view/${id}`;
    }
    
    voirDashboard() {
        window.location.href = `${this.baseUrl}/dashboard`;
    }
    
    voirHistorique(equipementId = null) {
        const url = equipementId 
            ? `${this.baseUrl}/historique/${equipementId}`
            : `${this.baseUrl}/historique`;
        window.location.href = url;
    }
    
    voirRapport() {
        window.location.href = `${this.baseUrl}/rapport`;
    }
    
    // CRUD OPERATIONS
    openAddModal() {
        this.loadForm(null);
    }
    
    edit(id) {
        this.loadForm(id);
    }
    
    async delete(id) {
        const confirmed = await this.showConfirmDialog(
            'Supprimer l\'équipement',
            'Êtes-vous sûr de vouloir supprimer cet équipement ? Cette action est irréversible.',
            'Supprimer',
            'danger'
        );
        
        if (!confirmed) return;
        
        try {
            const response = await fetch(`${this.apiUrl}/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification(data.message || 'Équipement supprimé avec succès', 'success');
                
                const row = document.querySelector(`tr[data-id="${id}"]`);
                if (row) {
                    row.style.transition = 'opacity 0.3s ease';
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.remove();
                        this.checkEmptyTable();
                    }, 300);
                } else {
                    setTimeout(() => location.reload(), 1000);
                }
            } else {
                this.showNotification(data.message || 'Erreur lors de la suppression', 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.showNotification('Erreur de connexion au serveur', 'error');
        }
    }
    
    async loadForm(id = null) {
        const modal = document.getElementById('equipement-modal');
        const container = document.getElementById('modal-form-container');
        
        if (!modal || !container) {
            console.error('Modale ou conteneur introuvable');
            return;
        }
        
        const modalTitle = modal.querySelector('.modal-header h2');
        if (modalTitle) {
            modalTitle.textContent = id ? 'Modifier l\'équipement' : 'Ajouter un équipement';
        }
        
        container.innerHTML = `
            <div class="loader">
                <div class="spinner"></div>
                <p>Chargement du formulaire...</p>
            </div>
        `;
        
        modal.style.display = 'flex';
        
        try {
            const url = id ? `${this.baseUrl}/form/${id}` : `${this.baseUrl}/form`;
            
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error('Erreur de chargement');
            }
            
            const html = await response.text();
            container.innerHTML = html;
            
            this.currentEquipementId = id;
            this.attachFormSubmit();
            
        } catch (error) {
            console.error('Erreur:', error);
            container.innerHTML = `
                <div class="error-message">
                    <p>Erreur lors du chargement du formulaire</p>
                    <button class="btn-secondary" onclick="equipements.closeModal()">Fermer</button>
                </div>
            `;
        }
    }
    
    attachFormSubmit() {
        const form = document.getElementById('equipement-form');
        if (!form) return;
        
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Enregistrement...';
            
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.showNotification(data.message || 'Enregistrement réussi', 'success');
                    this.closeModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    this.showNotification(data.message || 'Erreur d\'enregistrement', 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = this.currentEquipementId ? 'Mettre à jour' : 'Créer l\'équipement';
                }
            } catch (error) {
                console.error('Erreur:', error);
                this.showNotification('Erreur de connexion', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = this.currentEquipementId ? 'Mettre à jour' : 'Créer l\'équipement';
            }
        });
    }
    
    closeModal() {
        const modal = document.getElementById('equipement-modal');
        if (modal) {
            const content = modal.querySelector('.modal-content');
            if (content) {
                content.style.transform = 'scale(0.9)';
                content.style.opacity = '0';
            }
            
            setTimeout(() => {
                modal.style.display = 'none';
                const container = document.getElementById('modal-form-container');
                if (container) {
                    container.innerHTML = '';
                }
                this.currentEquipementId = null;
                
                if (content) {
                    content.style.transform = '';
                    content.style.opacity = '';
                }
            }, 200);
        }
    }
    
    // MAINTENANCE
    async openMaintenanceModal(equipementId) {
        this.currentEquipementId = equipementId;
        
        const modal = document.getElementById('equipement-modal');
        const container = document.getElementById('modal-form-container');
        
        if (!modal || !container) return;
        
        const modalTitle = modal.querySelector('.modal-header h2');
        if (modalTitle) {
            modalTitle.textContent = 'Planifier une maintenance';
        }
        
        const html = `
            <form id="maintenance-form" onsubmit="equipements.planifierMaintenance(event)">
                <div class="form-group">
                    <label for="type_maintenance">Type de maintenance *</label>
                    <select id="type_maintenance" name="type_maintenance" required>
                        <option value="">-- Sélectionner --</option>
                        <option value="preventive">Préventive</option>
                        <option value="corrective">Corrective</option>
                        <option value="calibration">Calibration</option>
                        <option value="nettoyage">Nettoyage</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="date_prevue">Date prévue *</label>
                    <input type="date" id="date_prevue" name="date_prevue" required>
                </div>
                <div class="form-group">
                    <label for="technicien">Technicien</label>
                    <input type="text" id="technicien" name="technicien" placeholder="Nom du technicien">
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3" placeholder="Détails de la maintenance"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="equipements.closeModal()">
                        Annuler
                    </button>
                    <button type="submit" class="btn-primary">
                        Planifier
                    </button>
                </div>
            </form>
        `;
        
        container.innerHTML = html;
        modal.style.display = 'flex';
    }
    
    async planifierMaintenance(event) {
        event.preventDefault();
        
        const form = document.getElementById('maintenance-form');
        const formData = new FormData(form);
        
        const data = {
            equipement_id: this.currentEquipementId,
            type_maintenance: formData.get('type_maintenance'),
            date_prevue: formData.get('date_prevue'),
            technicien: formData.get('technicien'),
            description: formData.get('description')
        };
        
        try {
            const response = await fetch(`${this.apiUrl}/planifier-maintenance`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Maintenance planifiée avec succès', 'success');
                this.closeModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                this.showNotification(result.message || 'Erreur', 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.showNotification('Erreur de connexion', 'error');
        }
    }
    
    // FILTRES
    initFilters() {
        const applyBtn = document.getElementById('apply-filters');
        const searchInput = document.getElementById('search-input');
        
        if (applyBtn) {
            applyBtn.addEventListener('click', () => this.applyFilters());
        }
        
        if (searchInput) {
            let timeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    this.liveSearch(e.target.value);
                }, 300);
            });
            
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.applyFilters();
                }
            });
        }
    }
    
    applyFilters() {
        const params = new URLSearchParams();
        
        const searchInput = document.getElementById('search-input');
        if (searchInput && searchInput.value) {
            params.append('search', searchInput.value);
        }
        
        document.querySelectorAll('.filter-select').forEach(select => {
            if (select.value) {
                params.append(select.name, select.value);
            }
        });
        
        window.location.href = `${this.baseUrl}?${params.toString()}`;
    }
    
    liveSearch(query) {
        const rows = document.querySelectorAll('.table tbody tr, .data-table tbody tr');
        const lowerQuery = query.toLowerCase();
        let visibleCount = 0;
        
        rows.forEach(row => {
            if (row.classList.contains('empty-row') || 
                row.querySelector('.empty-cell') ||
                row.querySelector('.empty-message')) {
                return;
            }
            
            const text = row.textContent.toLowerCase();
            const matches = text.includes(lowerQuery);
            
            row.style.display = matches ? '' : 'none';
            if (matches) visibleCount++;
        });
        
        this.updateEmptyState(visibleCount === 0 && query);
    }
    
    // EXPORT
    export() {
        const params = new URLSearchParams(window.location.search);
        params.set('export', 'csv');
        window.location.href = `${this.baseUrl}?${params.toString()}`;
    }
    
    // UTILITAIRES UI
    checkEmptyTable() {
        const tbody = document.querySelector('.table tbody, .data-table tbody');
        if (!tbody) return;
        
        const rows = tbody.querySelectorAll('tr:not(.empty-row):not(.no-results-row)');
        const container = tbody.closest('.table-container');
        
        if (rows.length === 0) {
            container?.classList.add('empty');
        } else {
            container?.classList.remove('empty');
        }
    }
    
    updateEmptyState(isEmpty) {
        const tbody = document.querySelector('.table tbody, .data-table tbody');
        if (!tbody) return;
        
        const emptyRow = tbody.querySelector('.no-results-row');
        
        if (isEmpty) {
            if (!emptyRow) {
                const row = document.createElement('tr');
                row.className = 'no-results-row';
                row.innerHTML = `
                    <td colspan="100" class="empty-message" style="text-align: center; padding: 40px; color: #9CA3AF;">
                         Aucun résultat trouvé
                    </td>
                `;
                tbody.appendChild(row);
            }
        } else {
            emptyRow?.remove();
        }
    }
    
    showNotification(message, type = 'info') {
        if (window.toast) {
            window.toast.show(message, type);
            return;
        }
        
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span>${this.escapeHtml(message)}</span>
            <button onclick="this.parentElement.remove()">✕</button>
        `;
        
        notification.style.cssText = `
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
            animation: slideIn 0.3s ease;
        `;
        
        const colors = {
            success: '#10B981',
            error: '#EF4444',
            warning: '#F59E0B',
            info: '#3B82F6'
        };
        
        notification.style.background = colors[type] || colors.info;
        notification.style.color = 'white';
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }
    
    showConfirmDialog(title, message, confirmText = 'Confirmer', type = 'primary') {
        return new Promise((resolve) => {
            const dialog = document.createElement('div');
            dialog.className = 'confirm-dialog';
            dialog.innerHTML = `
                <div class="confirm-dialog-overlay"></div>
                <div class="confirm-dialog-content">
                    <h3>${this.escapeHtml(title)}</h3>
                    <p>${this.escapeHtml(message)}</p>
                    <div class="confirm-dialog-buttons">
                        <button class="btn-secondary" id="confirm-cancel">Annuler</button>
                        <button class="btn-${type}" id="confirm-ok">${this.escapeHtml(confirmText)}</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(dialog);
            
            dialog.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
            `;
            
            const overlay = dialog.querySelector('.confirm-dialog-overlay');
            overlay.style.cssText = `
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
            `;
            
            const content = dialog.querySelector('.confirm-dialog-content');
            content.style.cssText = `
                background: white;
                padding: 24px;
                border-radius: 12px;
                max-width: 400px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                position: relative;
                z-index: 1;
            `;
            
            document.getElementById('confirm-ok').addEventListener('click', () => {
                dialog.remove();
                resolve(true);
            });
            
            document.getElementById('confirm-cancel').addEventListener('click', () => {
                dialog.remove();
                resolve(false);
            });
            
            overlay.addEventListener('click', () => {
                dialog.remove();
                resolve(false);
            });
        });
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// INITIALISATION
let equipements;

document.addEventListener('DOMContentLoaded', () => {
    equipements = new EquipementsHandler();
    console.log('✅ Gestionnaire d\'équipements initialisé');
});

// FONCTIONS GLOBALES
function viewItem(id) {
    if (equipements) equipements.view(id);
}

function editItem(id) {
    if (equipements) equipements.edit(id);
}

function deleteItem(id) {
    if (equipements) equipements.delete(id);
}

function openAddModal() {
    if (equipements) equipements.openAddModal();
}

function closeModal() {
    if (equipements) equipements.closeModal();
}

function exportData() {
    if (equipements) equipements.export();
}

// ANIMATIONS CSS
if (!document.getElementById('equipements-handler-styles')) {
    const style = document.createElement('style');
    style.id = 'equipements-handler-styles';
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(400px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(400px); opacity: 0; }
        }
        
        .loader {
            text-align: center;
            padding: 40px;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #5B7FFF;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .error-message {
            text-align: center;
            padding: 30px;
            color: #EF4444;
        }
        
        .confirm-dialog-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        .confirm-dialog-content h3 {
            margin: 0 0 15px;
            color: #1F2937;
        }
        
        .confirm-dialog-content p {
            margin: 0 0 20px;
            color: #6B7280;
            line-height: 1.5;
        }
        
        .btn-danger {
            background: #EF4444;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .btn-danger:hover {
            background: #DC2626;
        }
    `;
    document.head.appendChild(style);
}