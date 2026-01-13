/**
 * publications-handler.js - Gestionnaire complet pour les publications
 * Gère CRUD + validation + rapports bibliographiques
 */

class PublicationsHandler {
    constructor() {
        this.baseUrl = '/TDW_project/admin/publications/publications';
        this.apiUrl = '/TDW_project/api/admin/publications/publications';
        this.currentPublicationId = null;
        this.init();
    }
    
    init() {
        this.attachEventListeners();
        this.initFilters();
        this.checkEmptyTable();
    }
    
    // ========================================
    // GESTION DES ÉVÉNEMENTS
    // ========================================
    
    attachEventListeners() {
        // Bouton ajouter publication
        const addBtn = document.querySelector('[onclick*="openAddModal"]');
        if (addBtn) {
            addBtn.onclick = () => this.openAddModal();
        }
        
        // Bouton exporter
        const exportBtn = document.querySelector('[onclick*="exportData"]');
        if (exportBtn) {
            exportBtn.onclick = () => this.export();
        }
        
        // Bouton rapport
        const rapportBtn = document.querySelector('[onclick*="genererRapport"]');
        if (rapportBtn) {
            rapportBtn.onclick = () => this.ouvrirModalRapport();
        }
        
        // Fermeture modale
        const closeBtn = document.querySelector('.modal-close');
        if (closeBtn) {
            closeBtn.onclick = () => this.closeModal();
        }
        
        // Clic en dehors de la modale
        const modal = document.getElementById('publication-modal');
        if (modal) {
            modal.onclick = (e) => {
                if (e.target === modal) {
                    this.closeModal();
                }
            };
        }
        
        // Touche Échap
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
            }
        });
    }
    
    // ========================================
    // OPÉRATIONS CRUD
    // ========================================
    
    /**
     * Voir les détails d'une publication
     */
    view(id) {
        window.location.href = `${this.baseUrl}/view/${id}`;
    }
    
    /**
     * Ouvrir la modale d'ajout
     */
    openAddModal() {
        this.loadForm(null);
    }
    
    /**
     * Modifier une publication
     */
    edit(id) {
        this.loadForm(id);
    }
    
    /**
     * Supprimer une publication
     */
    async delete(id) {
        const confirmed = await this.showConfirmDialog(
            'Supprimer la publication',
            'Êtes-vous sûr de vouloir supprimer cette publication ? Cette action est irréversible.',
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
                this.showNotification(data.message || 'Publication supprimée avec succès', 'success');
                
                // Supprimer visuellement la ligne
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
    
    /**
     * Charger le formulaire dans la modale
     */
    async loadForm(id = null) {
    const modal = document.getElementById('publication-modal');
    const container = document.getElementById('modal-form-container');
    
    if (!modal || !container) {
        console.error('Modale ou conteneur introuvable');
        return;
    }
    
    // Mettre à jour le titre
    const modalTitle = modal.querySelector('.modal-header h2');
    if (modalTitle) {
        modalTitle.textContent = id ? 'Modifier la publication' : 'Ajouter une publication';
    }
    
    // Afficher loader
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
        
        this.currentPublicationId = id;
        
        //  CORRECTION CRITIQUE : Attacher l'event listener au formulaire
        setTimeout(() => {
            this.attachFormSubmitHandler();
        }, 100);
        
    } catch (error) {
        console.error('Erreur:', error);
        container.innerHTML = `
            <div class="error-message">
                <p> Erreur lors du chargement du formulaire</p>
                <button class="btn-secondary" onclick="publications.closeModal()">Fermer</button>
            </div>
        `;
    }
}

// 🔧 NOUVELLE MÉTHODE : Attacher le gestionnaire de soumission
attachFormSubmitHandler() {
    const form = document.getElementById('publication-form');
    
    if (!form) {
        console.error(' Formulaire non trouvé');
        return;
    }
    
    console.log(' Formulaire trouvé, attachement du handler AJAX');
    
    // Retirer tous les anciens event listeners
    const newForm = form.cloneNode(true);
    form.parentNode.replaceChild(newForm, form);
    
    // Attacher le nouveau event listener
    newForm.addEventListener('submit', (e) => {
        e.preventDefault();
        e.stopPropagation();
        console.log(' Soumission AJAX interceptée');
        this.submitForm(newForm);
        return false;
    });
}

// REMPLACER la méthode submitForm :

async submitForm(form) {
    console.log(' Début de soumission...');
    
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn ? submitBtn.innerHTML : '';
    
    // Désactiver le bouton
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-small"></span> Enregistrement...';
    }
    
    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
        
        //  VÉRIFIER LE CONTENT-TYPE
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            console.error(' Réponse non-JSON reçue');
            throw new Error('Réponse serveur invalide');
        }
        
        const data = await response.json();
        console.log(' Réponse JSON:', data);
        
        if (data.success) {
            this.showNotification(data.message || 'Publication enregistrée avec succès', 'success');
            this.closeModal();
            
            // Recharger après 1 seconde
            setTimeout(() => {
                window.location.reload();
            }, 1000);
            
        } else {
            // Afficher les erreurs
            if (data.errors) {
                this.displayErrors(form, data.errors);
            }
            this.showNotification(data.message || 'Erreur lors de l\'enregistrement', 'error');
            
            // Réactiver le bouton
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }
        
    } catch (error) {
        console.error(' Erreur de soumission:', error);
        this.showNotification('Erreur de connexion au serveur', 'error');
        
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }
}
    
    /**
     * Soumettre le formulaire
     */
    async submitForm(form) {
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        
        // Désactiver le bouton
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-small"></span> Enregistrement...';
        }
        
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification(data.message || 'Publication enregistrée avec succès', 'success');
                this.closeModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                if (data.errors) {
                    this.displayErrors(form, data.errors);
                }
                this.showNotification(data.message || 'Erreur lors de l\'enregistrement', 'error');
                
                // Réactiver le bouton
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = ' Enregistrer';
                }
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.showNotification('Erreur de connexion', 'error');
            
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = ' Enregistrer';
            }
        }
    }
    
    /**
     * Afficher les erreurs de validation
     */
    displayErrors(form, errors) {
        // Supprimer les anciennes erreurs
        form.querySelectorAll('.field-error').forEach(el => el.remove());
        form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
        
        // Afficher les nouvelles erreurs
        for (const [field, message] of Object.entries(errors)) {
            const input = form.querySelector(`[name="${field}"]`);
            if (input) {
                input.classList.add('error');
                const errorDiv = document.createElement('div');
                errorDiv.className = 'field-error';
                errorDiv.textContent = message;
                input.parentElement.appendChild(errorDiv);
            }
        }
    }
    
    /**
     * Fermer la modale
     */
    closeModal() {
        const modal = document.getElementById('publication-modal');
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
                this.currentPublicationId = null;
                
                if (content) {
                    content.style.transform = '';
                    content.style.opacity = '';
                }
            }, 200);
        }
    }
    
    // ========================================
    // VALIDATION DES PUBLICATIONS - CORRIGÉ POUR AJAX
    // ========================================
    
    /**
     * Valider une publication - VERSION AJAX CORRIGÉE
     */
    async valider(id) {
        const confirmed = await this.showConfirmDialog(
            'Valider la publication',
            'Confirmer la validation de cette publication ? Elle sera visible publiquement.',
            'Valider',
            'success'
        );
        
        if (!confirmed) return;
        
        try {
            // IMPORTANT : Utiliser fetch avec POST pour éviter la redirection
            const response = await fetch(`${this.apiUrl}/${id}/valider`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification(data.message || 'Publication validée avec succès', 'success');
                
                // Recharger la page après un court délai
                setTimeout(() => location.reload(), 1000);
            } else {
                this.showNotification(data.message || 'Erreur lors de la validation', 'error');
            }
        } catch (error) {
            console.error('Erreur validation:', error);
            this.showNotification('Erreur de connexion au serveur', 'error');
        }
    }
    
    /**
     * Rejeter une publication - VERSION AJAX CORRIGÉE
     */
    async rejeter(id) {
        const confirmed = await this.showConfirmDialog(
            'Rejeter la publication',
            'Confirmer le rejet de cette publication ?',
            'Rejeter',
            'warning'
        );
        
        if (!confirmed) return;
        
        try {
            // IMPORTANT : Utiliser fetch avec POST pour éviter la redirection
            const response = await fetch(`${this.apiUrl}/${id}/rejeter`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification(data.message || 'Publication rejetée', 'warning');
                
                // Recharger la page après un court délai
                setTimeout(() => location.reload(), 1000);
            } else {
                this.showNotification(data.message || 'Erreur lors du rejet', 'error');
            }
        } catch (error) {
            console.error('Erreur rejet:', error);
            this.showNotification('Erreur de connexion au serveur', 'error');
        }
    }
    
    // ========================================
    // RAPPORTS BIBLIOGRAPHIQUES
    // ========================================
    
    /**
     * Ouvrir la modale de génération de rapport
     */
    ouvrirModalRapport() {
        const modal = document.getElementById('publication-modal');
        const container = document.getElementById('modal-form-container');
        
        if (!modal || !container) return;
        
        const modalTitle = modal.querySelector('.modal-header h2');
        if (modalTitle) {
            modalTitle.textContent = 'Générer un rapport bibliographique';
        }
        
        container.innerHTML = `
            <form id="rapport-form">
                <div class="form-group">
                    <label>Type de rapport *</label>
                    <select id="rapport-type" name="type" required>
                        <option value="annee">Par année</option>
                        <option value="auteur">Par auteur</option>
                        <option value="complet">Rapport complet</option>
                    </select>
                </div>
                
                <div class="form-group" id="annee-group">
                    <label>Année</label>
                    <select name="annee">
                        <option value="">Année en cours</option>
                        ${this.generateYearOptions()}
                    </select>
                </div>
                
                <div class="form-group" id="auteur-group" style="display: none;">
                    <label>Auteur</label>
                    <select name="auteur" id="auteur-select">
                        <option value="">-- Sélectionner --</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Format</label>
                    <select name="format">
                        <option value="html">Afficher à l'écran (HTML)</option>
                        <option value="pdf">Télécharger PDF</option>
                        <option value="csv">Télécharger CSV</option>
                    </select>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="publications.closeModal()">
                        Annuler
                    </button>
                    <button type="submit" class="btn-primary">
                         Générer le rapport
                    </button>
                </div>
            </form>
        `;
        
        modal.style.display = 'flex';
        
        // Charger les auteurs
        this.loadAuteurs();
        
        // Gestion du type de rapport
        document.getElementById('rapport-type').addEventListener('change', (e) => {
            const anneeGroup = document.getElementById('annee-group');
            const auteurGroup = document.getElementById('auteur-group');
            
            if (e.target.value === 'auteur') {
                anneeGroup.style.display = 'none';
                auteurGroup.style.display = 'block';
            } else if (e.target.value === 'annee') {
                anneeGroup.style.display = 'block';
                auteurGroup.style.display = 'none';
            } else {
                anneeGroup.style.display = 'none';
                auteurGroup.style.display = 'none';
            }
        });
        
        // Soumission du formulaire
        document.getElementById('rapport-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.genererRapport(new FormData(e.target));
        });
    }
    
    /**
     * Générer les options d'années
     */
    generateYearOptions() {
        const currentYear = new Date().getFullYear();
        let options = '';
        for (let year = currentYear; year >= currentYear - 10; year--) {
            options += `<option value="${year}">${year}</option>`;
        }
        return options;
    }
    
    /**
     * Charger la liste des auteurs
     */
    async loadAuteurs() {
        try {
            const response = await fetch('/TDW_project/api/admin/membres', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (data.success && data.membres) {
                const select = document.getElementById('auteur-select');
                if (select) {
                    data.membres.forEach(membre => {
                        const option = document.createElement('option');
                        option.value = membre.id;
                        option.textContent = `${membre.username} ${membre.grade ? '- ' + membre.grade : ''}`;
                        select.appendChild(option);
                    });
                }
            }
        } catch (error) {
            console.error('Erreur chargement auteurs:', error);
        }
    }
    
    /**
     * Générer le rapport
     */
    genererRapport(formData) {
        const params = new URLSearchParams(formData);
        window.open(`${this.baseUrl}/rapport?${params.toString()}`, '_blank');
        this.closeModal();
    }
    
    // ========================================
    // FILTRES ET RECHERCHE
    // ========================================
    
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
    
    /**
     * Appliquer les filtres
     */
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
    
    /**
     * Recherche en temps réel
     */
    liveSearch(query) {
        const rows = document.querySelectorAll('.table tbody tr, .data-table tbody tr');
        const lowerQuery = query.toLowerCase();
        let visibleCount = 0;
        
        rows.forEach(row => {
            if (row.classList.contains('empty-row') || 
                row.querySelector('.empty-cell')) {
                return;
            }
            
            const text = row.textContent.toLowerCase();
            const matches = text.includes(lowerQuery);
            
            row.style.display = matches ? '' : 'none';
            if (matches) visibleCount++;
        });
        
        this.updateEmptyState(visibleCount === 0 && query);
    }
    
    // ========================================
    // EXPORT
    // ========================================
    
    export() {
        const params = new URLSearchParams(window.location.search);
        params.set('export', 'csv');
        window.location.href = `${this.baseUrl}?${params.toString()}`;
    }
    
    // ========================================
    // UTILITAIRES UI
    // ========================================
    
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
            
            document.body.appendChild(dialog);
            
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

// ========================================
// INITIALISATION
// ========================================

let publications;

document.addEventListener('DOMContentLoaded', () => {
    publications = new PublicationsHandler();
    window.publications = publications;
    console.log(' Gestionnaire de publications initialisé');
});

// ========================================
// FONCTIONS GLOBALES
// ========================================

function viewItem(id) {
    if (publications) publications.view(id);
}

function editItem(id) {
    if (publications) publications.edit(id);
}

function deleteItem(id) {
    if (publications) publications.delete(id);
}

function openAddModal() {
    if (publications) publications.openAddModal();
}

function closeModal() {
    if (publications) publications.closeModal();
}

function exportData() {
    if (publications) publications.export();
}

function validerPublication(id) {
    if (publications) publications.valider(id);
}

function rejeterPublication(id) {
    if (publications) publications.rejeter(id);
}

function genererRapport() {
    if (publications) publications.ouvrirModalRapport();
}

// ========================================
// STYLES CSS
// ========================================

if (!document.getElementById('publications-handler-styles')) {
    const style = document.createElement('style');
    style.id = 'publications-handler-styles';
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
        
        .spinner-small {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .field-error {
            color: #EF4444;
            font-size: 12px;
            margin-top: 4px;
        }
        
        input.error,
        select.error,
        textarea.error {
            border-color: #EF4444 !important;
            background: rgba(239, 68, 68, 0.05);
        }
        
        .confirm-dialog-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        .btn-success {
            background: #10B981;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .btn-danger {
            background: #EF4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #DC2626;
        }
    `;
    document.head.appendChild(style);
}