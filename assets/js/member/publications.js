/**
 * publications.js - MEMBRE VERSION (logique identique à Admin)
 * Charge le formulaire via AJAX comme Admin au lieu d'utiliser ModalComponent
 */

// Global caches
window.membresCache = [];
window.projetsCache = [];
window.currentPublicationId = null;

document.addEventListener('DOMContentLoaded', function() {
    console.log('✓ Publications manager initialized');
    
    // Load data immediately
    loadProjets();
    loadMembres();
});

// ============================================================================
// DATA LOADING
// ============================================================================

async function loadMembres() {
    try {
        const response = await fetch(BASE_URL + '/membre/publications/get-membres');
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            console.error(' Invalid response from get-membres:', contentType);
            return;
        }
        
        if (!response.ok) {
            console.warn('Failed to load membres:', response.status);
            return;
        }
        
        const result = await response.json();
        
        if (result.success) {
            window.membresCache = result.membres;
            console.log('✓ Membres loaded:', result.membres.length);
        }
    } catch (error) {
        console.error('Error loading membres:', error);
    }
}

async function loadProjets() {
    try {
        const response = await fetch(BASE_URL + '/membre/publications/get-projets');
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            console.error(' Invalid response from get-projets:', contentType);
            return;
        }
        
        if (!response.ok) {
            console.warn('Failed to load projets:', response.status);
            return;
        }
        
        const result = await response.json();
        
        if (result.success) {
            window.projetsCache = result.projets;
            console.log('✓ Projets loaded:', result.projets.length);
        }
    } catch (error) {
        console.error('Error loading projets:', error);
    }
}

// ============================================================================
// EDIT PUBLICATION - LOGIQUE ADMIN
// ============================================================================

async function editPublication(publicationId) {
    console.log(' Loading publication for edit:', publicationId);
    
    try {
        //  Charger les données
        const response = await fetch(`${BASE_URL}/membre/publications/get/${publicationId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            console.error(' Invalid response content-type:', contentType);
            showError('Erreur lors du chargement de la publication');
            return;
        }
        
        const result = await response.json();
        
        if (!result.success) {
            showError(result.message || 'Erreur lors du chargement');
            return;
        }
        
        const publication = result.publication;
        console.log(' Publication loaded:', publication);
        
        //  Ouvrir la modal SANS déclencher l'intercepteur
        const modal = document.getElementById('publication-modal');
        if (modal) {
            modal.style.display = 'flex';
        }
        
        // Charger le formulaire pré-rempli
        window.currentPublicationId = publicationId;
        await loadFormInModal(publication);
        
    } catch (error) {
        console.error(' Error loading publication:', error);
        showError('Une erreur est survenue lors du chargement');
    }
}

// ============================================================================
// CHARGER LE FORMULAIRE DANS LA MODAL (COMME ADMIN)
// ============================================================================

async function loadFormInModal(publication = null) {
    const modal = document.getElementById('publication-modal');
    
    if (!modal) {
        console.error(' Modal not found!');
        showError('Modal introuvable');
        return;
    }
    
    // 1️ Trouver le conteneur du formulaire
    let container = modal.querySelector('.modal-body');
    if (!container) {
        // Si pas de .modal-body, créer un conteneur
        const modalContent = modal.querySelector('.modal-content');
        if (modalContent) {
            // Vider tout sauf le header
            const header = modalContent.querySelector('.modal-header');
            modalContent.innerHTML = '';
            if (header) {
                modalContent.appendChild(header);
            }
            
            container = document.createElement('div');
            container.className = 'modal-body';
            container.style.cssText = 'padding: 24px; max-height: 70vh; overflow-y: auto;';
            modalContent.appendChild(container);
        }
    }
    
    if (!container) {
        console.error(' No container found in modal');
        showError('Conteneur du formulaire introuvable');
        return;
    }
    
    // 2️ Afficher un loader
    container.innerHTML = `
        <div class="loader">
            <div class="spinner"></div>
            <p>Chargement du formulaire...</p>
        </div>
    `;

    // 4️ Construire le formulaire dynamiquement
    setTimeout(() => {
        buildPublicationForm(container, publication);
    }, 100);
}

// ============================================================================
// CONSTRUIRE LE FORMULAIRE DYNAMIQUEMENT
// ============================================================================

function buildPublicationForm(container, publication = null) {
    const isEdit = publication !== null;
    const formAction = isEdit 
        ? `${BASE_URL}/membre/publications/update/${publication.id}`
        : `${BASE_URL}/membre/publications/nouveau`;
    
    // Mettre à jour le titre de la modal
    const modalTitle = document.querySelector('#publication-modal .modal-title, #publication-modal h2');
    if (modalTitle) {
        modalTitle.textContent = isEdit ? 'Modifier la publication' : 'Nouvelle Publication';
    }
    
    // Sauvegarder le projet_id pour le sélectionner après
    const savedProjetId = isEdit ? publication.projet_id : null;
    
    // Générer le HTML du formulaire
    container.innerHTML = `
        <form id="publication-form" action="${formAction}" method="POST">
            <h4 style="margin: 20px 0 15px 0; color: #2c3e50;">
                <i class="fas fa-info-circle"></i> Informations générales
            </h4>
            
            <div class="form-group">
                <label for="titre">Titre *</label>
                <input type="text" 
                       id="titre" 
                       name="titre" 
                       class="form-control"
                       value="${isEdit ? escapeHtml(publication.titre) : ''}" 
                       required 
                       placeholder="Titre complet de la publication">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="type_publication">Type de publication *</label>
                    <select id="type_publication" name="type_publication" class="form-control" required>
                        <option value="">Sélectionner un type</option>
                        <option value="article" ${isEdit && publication.type_publication === 'article' ? 'selected' : ''}>Article</option>
                        <option value="rapport" ${isEdit && publication.type_publication === 'rapport' ? 'selected' : ''}>Rapport</option>
                        <option value="these" ${isEdit && publication.type_publication === 'these' ? 'selected' : ''}>Thèse</option>
                        <option value="communication" ${isEdit && publication.type_publication === 'communication' ? 'selected' : ''}>Communication</option>
                        <option value="poster" ${isEdit && publication.type_publication === 'poster' ? 'selected' : ''}>Poster</option>
                        <option value="autre" ${isEdit && publication.type_publication === 'autre' ? 'selected' : ''}>Autre</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="date_publication">Date de publication *</label>
                    <input type="date" 
                           id="date_publication" 
                           name="date_publication" 
                           class="form-control"
                           value="${isEdit ? publication.date_publication : ''}" 
                           required
                           max="${new Date().toISOString().split('T')[0]}">
                </div>
            </div>
            
            <div class="form-group">
                <label for="resume">Résumé *</label>
                <textarea id="resume" 
                          name="resume" 
                          class="form-control"
                          rows="5" 
                          required 
                          placeholder="Résumé de la publication (minimum 50 caractères)">${isEdit ? escapeHtml(publication.resume) : ''}</textarea>
                <div class="char-counter" style="text-align: right; font-size: 0.85rem; margin-top: 4px; color: #6b7280;">
                    ${isEdit ? publication.resume.length : 0} caractères
                </div>
            </div>
            
            <div class="form-group">
                <label for="domaine">Domaine *</label>
                <input type="text" 
                       id="domaine" 
                       name="domaine" 
                       class="form-control"
                       value="${isEdit ? escapeHtml(publication.domaine) : ''}" 
                       required
                       placeholder="ex: Sécurité, Cloud Computing, IA...">
            </div>
            
            <h4 style="margin: 20px 0 15px 0; color: #2c3e50;">
                <i class="fas fa-link"></i> Références
            </h4>
            
            <div class="form-group">
                <label for="doi">DOI</label>
                <input type="text" 
                       id="doi" 
                       name="doi" 
                       class="form-control"
                       value="${isEdit && publication.doi ? escapeHtml(publication.doi) : ''}" 
                       placeholder="ex: 10.1234/example.2024.001">
            </div>
            
            <div class="form-group">
                <label for="lien">Lien vers la publication</label>
                <input type="url" 
                       id="lien" 
                       name="lien" 
                       class="form-control"
                       value="${isEdit && publication.lien ? escapeHtml(publication.lien) : ''}" 
                       placeholder="https://...">
            </div>
            
            <div class="form-group">
                <label for="lien_telechargement">Lien de téléchargement</label>
                <input type="url" 
                       id="lien_telechargement" 
                       name="lien_telechargement" 
                       class="form-control"
                       value="${isEdit && publication.lien_telechargement ? escapeHtml(publication.lien_telechargement) : ''}" 
                       placeholder="https://...">
            </div>
            
            <h4 style="margin: 20px 0 15px 0; color: #2c3e50;">
                <i class="fas fa-project-diagram"></i> Projet associé
            </h4>
            
            <div class="form-group">
                <label for="projet_id">Projet (optionnel)</label>
                <select id="projet_id" name="projet_id" class="form-control">
                    <option value="">Aucun projet</option>
                </select>
            </div>
            
            <h4 style="margin: 20px 0 15px 0; color: #2c3e50;">
                <i class="fas fa-users"></i> Co-auteurs
            </h4>
            
            <div id="coauteurs-container"></div>
            
            <button type="button" id="add-coauteur-btn" class="btn btn-secondary btn-sm" style="margin-top: 10px;">
                <i class="fas fa-plus"></i> Ajouter un co-auteur
            </button>
            
            <small class="form-text" style="display: block; margin-top: 10px; color: #666;">
                Vous serez automatiquement ajouté comme auteur principal
            </small>
            
            <div class="modal-footer" style="margin-top: 30px; display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('publication-modal')">
                    Annuler
                </button>
                <button type="submit" class="btn btn-primary">
                    ${isEdit ? 'Mettre à jour' : 'Soumettre la publication'}
                </button>
            </div>
        </form>
        
        <style>
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
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
        </style>
    `;
    
    // Peupler le select des projets
    updateProjetSelectInForm();
    
    // Sélectionner le projet sauvegardé
    if (savedProjetId) {
        const projetSelect = document.getElementById('projet_id');
        if (projetSelect) {
            projetSelect.value = savedProjetId;
        }
    }
    
    // Ajouter les co-auteurs si en mode édition
    if (isEdit && publication.co_auteurs && publication.co_auteurs.length > 0) {
        const container = document.getElementById('coauteurs-container');
        publication.co_auteurs.forEach((membreId) => {
            addCoAuteurField(container, membreId);
        });
    }
    
    // Initialiser le gestionnaire de co-auteurs
    initCoAuteursManager();
    
    // Ajouter validation en temps réel
    addRealTimeValidation();
    
    // Attacher le gestionnaire de soumission
    attachFormSubmitHandler();
    
    console.log(' Form built and initialized');
}

function updateProjetSelectInForm() {
    const select = document.getElementById('projet_id');
    if (!select || !window.projetsCache) return;
    
    const firstOption = select.options[0];
    const currentValue = select.value; // Sauvegarder la valeur actuelle
    
    select.innerHTML = '';
    select.appendChild(firstOption);
    
    window.projetsCache.forEach(projet => {
        const option = document.createElement('option');
        option.value = projet.id;
        option.textContent = projet.titre;
        
        // Restaurer la sélection si elle existe
        if (currentValue && currentValue == projet.id) {
            option.selected = true;
        }
        
        select.appendChild(option);
    });
}

// ============================================================================
// ATTACHER LE GESTIONNAIRE DE SOUMISSION
// ============================================================================

function attachFormSubmitHandler() {
    const form = document.getElementById('publication-form');
    
    if (!form) {
        console.error(' Form not found');
        return;
    }
    
    console.log(' Attaching submit handler');
    
    // Retirer tous les anciens event listeners
    const newForm = form.cloneNode(true);
    form.parentNode.replaceChild(newForm, form);
    
    // Attacher le nouveau event listener
    newForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log(' Submitting publication via AJAX...');
        
        if (!validatePublicationForm(this)) {
            console.log(' Validation failed');
            return false;
        }
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn ? submitBtn.textContent : '';
        
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-small"></span> Envoi en cours...';
        }
        
        try {
            const formData = new FormData(this);
            
            console.log(' Sending to:', this.action);
            
            const response = await fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            });
            
            console.log(' Response status:', response.status);
            console.log(' Response headers:', response.headers.get('content-type'));
            
            const contentType = response.headers.get('content-type');
            
            // Vérifier si c'est du JSON
            if (!contentType || !contentType.includes('application/json')) {
                console.error(' Invalid response content-type:', contentType);
                
                // Essayer de lire le texte pour déboguer
                const text = await response.text();
                console.error('Response text:', text.substring(0, 500));
                
                throw new Error('Réponse serveur invalide (pas JSON)');
            }
            
            const result = await response.json();
            console.log('✓ Server response:', result);
            
            if (result.success) {
                showSuccess(result.message);
                
                // Fermer la modal
                const modal = document.getElementById('publication-modal');
                if (modal) {
                    modal.style.display = 'none';
                }
                
                // Recharger après 1 seconde
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
                
            } else {
                showError(result.message || 'Erreur lors de la création');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            }
            
        } catch (error) {
            console.error(' Error:', error);
            showError('Une erreur est survenue. Veuillez réessayer.');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }
        
        return false;
    });
    
    // Réinitialiser les autres gestionnaires
    const container = document.getElementById('coauteurs-container');
    if (container) {
        initCoAuteursManager();
    }
    
    // Compteur de caractères pour le résumé
    const resume = newForm.querySelector('#resume');
    if (resume) {
        const counter = resume.parentNode.querySelector('.char-counter');
        if (counter) {
            resume.addEventListener('input', function() {
                const length = this.value.length;
                counter.textContent = `${length} caractères`;
                counter.style.color = length < 50 ? '#e74c3c' : '#27ae60';
            });
        }
    }
}

// ============================================================================
// FORM VALIDATION
// ============================================================================

function validatePublicationForm(form) {
    const errors = [];
    
    const titre = form.querySelector('#titre');
    if (titre && (!titre.value.trim() || titre.value.trim().length < 10)) {
        errors.push('Le titre doit contenir au moins 10 caractères');
        markFieldError(titre);
    } else if (titre) {
        markFieldValid(titre);
    }
    
    const type = form.querySelector('#type_publication');
    if (type && !type.value) {
        errors.push('Le type de publication est obligatoire');
        markFieldError(type);
    } else if (type) {
        markFieldValid(type);
    }
    
    const date = form.querySelector('#date_publication');
    if (date && !date.value) {
        errors.push('La date de publication est obligatoire');
        markFieldError(date);
    } else if (date) {
        const selectedDate = new Date(date.value);
        const today = new Date();
        if (selectedDate > today) {
            errors.push('La date ne peut pas être dans le futur');
            markFieldError(date);
        } else {
            markFieldValid(date);
        }
    }
    
    const resume = form.querySelector('#resume');
    if (resume && (!resume.value.trim() || resume.value.trim().length < 50)) {
        errors.push('Le résumé doit contenir au moins 50 caractères');
        markFieldError(resume);
    } else if (resume) {
        markFieldValid(resume);
    }
    
    const domaine = form.querySelector('#domaine');
    if (domaine && !domaine.value.trim()) {
        errors.push('Le domaine est obligatoire');
        markFieldError(domaine);
    } else if (domaine) {
        markFieldValid(domaine);
    }
    
    const doi = form.querySelector('#doi');
    if (doi && doi.value.trim() && !isValidDOI(doi.value.trim())) {
        errors.push('Format DOI invalide (doit commencer par 10.)');
        markFieldError(doi);
    } else if (doi && doi.value.trim()) {
        markFieldValid(doi);
    }
    
    const lien = form.querySelector('#lien');
    if (lien && lien.value.trim() && !isValidUrl(lien.value.trim())) {
        errors.push('Format de lien invalide');
        markFieldError(lien);
    } else if (lien && lien.value.trim()) {
        markFieldValid(lien);
    }
    
    if (errors.length > 0) {
        showError(errors.join('<br>'));
        return false;
    }
    
    return true;
}

function addRealTimeValidation() {
    const form = document.getElementById('publication-form');
    if (!form) return;
    
    const titre = form.querySelector('#titre');
    if (titre) {
        titre.addEventListener('blur', function() {
            if (this.value.trim() && this.value.trim().length < 10) {
                markFieldError(this);
            } else if (this.value.trim().length >= 10) {
                markFieldValid(this);
            }
        });
    }
    
    const date = form.querySelector('#date_publication');
    if (date) {
        date.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const today = new Date();
            if (selectedDate > today) {
                markFieldError(this);
            } else {
                markFieldValid(this);
            }
        });
    }
    
    const doi = form.querySelector('#doi');
    if (doi) {
        doi.addEventListener('blur', function() {
            if (this.value.trim() && !isValidDOI(this.value.trim())) {
                markFieldError(this);
            } else if (this.value.trim()) {
                markFieldValid(this);
            }
        });
    }
    
    const lien = form.querySelector('#lien');
    if (lien) {
        lien.addEventListener('blur', function() {
            if (this.value.trim() && !isValidUrl(this.value.trim())) {
                markFieldError(this);
            } else if (this.value.trim()) {
                markFieldValid(this);
            }
        });
    }
}

function markFieldError(field) {
    field.style.borderColor = '#e74c3c';
    field.style.backgroundColor = '#fee';
}

function markFieldValid(field) {
    field.style.borderColor = '#27ae60';
    field.style.backgroundColor = '#efe';
    
    setTimeout(() => {
        field.style.borderColor = '';
        field.style.backgroundColor = '';
    }, 2000);
}

function isValidDOI(doi) {
    return /^10\.\d{4,}\/\S+$/.test(doi);
}

function isValidUrl(url) {
    try {
        new URL(url);
        return true;
    } catch {
        return false;
    }
}

// ============================================================================
// CO-AUTEURS MANAGEMENT
// ============================================================================

function initCoAuteursManager() {
    const addBtn = document.getElementById('add-coauteur-btn');
    const container = document.getElementById('coauteurs-container');
    
    if (!addBtn || !container) {
        console.log('Co-auteurs elements not found');
        return;
    }
    
    console.log('✓ Co-auteurs manager initialized');
    
    // Remove existing listener
    const newBtn = addBtn.cloneNode(true);
    addBtn.parentNode.replaceChild(newBtn, addBtn);
    
    newBtn.addEventListener('click', function(e) {
        e.preventDefault();
        addCoAuteurField(container);
    });
    
    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-coauteur') || 
            e.target.closest('.remove-coauteur')) {
            const btn = e.target.classList.contains('remove-coauteur') ? 
                        e.target : e.target.closest('.remove-coauteur');
            btn.closest('.coauteur-item').remove();
        }
    });
}

function addCoAuteurField(container, selectedMembreId = null) {
    const div = document.createElement('div');
    div.className = 'coauteur-item';
    div.style.cssText = 'display: flex; gap: 10px; margin-bottom: 10px; align-items: flex-end;';
    
    div.innerHTML = `
        <div style="flex: 1;">
            <select name="co_auteurs[]" class="form-control" style="width: 100%;">
                <option value="">Sélectionner un membre</option>
            </select>
        </div>
        <button type="button" class="btn btn-danger btn-sm remove-coauteur" style="height: 38px;">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(div);
    
    const select = div.querySelector('select');
    populateCoAuteurSelect(select, selectedMembreId);
}

function populateCoAuteurSelect(select, selectedMembreId = null) {
    const membres = window.membresCache || [];
    
    membres.forEach(membre => {
        const option = document.createElement('option');
        option.value = membre.id;
        option.textContent = `${membre.nom} ${membre.prenom}`;
        
        if (selectedMembreId && membre.id == selectedMembreId) {
            option.selected = true;
        }
        
        select.appendChild(option);
    });
}

// ============================================================================
// PUBLICATION DELETION
// ============================================================================

async function deletePublication(publicationId, titre) {
    const confirmed = await showConfirmDialog(
        'Supprimer la publication',
        `Êtes-vous sûr de vouloir supprimer cette publication ?\n\n"${titre}"\n\nCette action est irréversible.`,
        'Supprimer',
        'danger'
    );
    
    if (!confirmed) return;
    
    console.log(' Deleting publication:', publicationId);
    
    try {
        const response = await fetch(`${BASE_URL}/membre/publications/delete/${publicationId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const contentType = response.headers.get('content-type');
        
        if (!contentType || !contentType.includes('application/json')) {
            console.error(' Invalid response content-type:', contentType);
            showError('Erreur serveur: réponse invalide (pas JSON)');
            return;
        }
        
        const result = await response.json();
        console.log('✓ Delete response:', result);
        
        if (result.success) {
            showSuccess(result.message || 'Publication supprimée avec succès');
            
            const card = document.querySelector(`[data-publication-id="${publicationId}"]`);
            if (card) {
                card.style.transition = 'all 0.3s ease-out';
                card.style.opacity = '0';
                card.style.transform = 'translateX(-20px)';
                
                setTimeout(() => {
                    card.remove();
                    
                    const publicationsList = document.querySelector('.publications-list');
                    if (publicationsList && publicationsList.children.length === 0) {
                        showEmptyState();
                    }
                }, 300);
            }
        } else {
            showError(result.message || 'Erreur lors de la suppression');
        }
        
    } catch (error) {
        console.error('Error deleting publication:', error);
        showError('Une erreur est survenue lors de la suppression. Veuillez réessayer.');
    }
}

// ============================================================================
// UI HELPERS
// ============================================================================

function showSuccess(message) {
    const container = document.querySelector('.container');
    if (!container) {
        alert(message);
        return;
    }
    
    const existingAlerts = container.querySelectorAll('.alert-success, .alert-error');
    existingAlerts.forEach(alert => alert.remove());
    
    const alert = document.createElement('div');
    alert.className = 'alert alert-success';
    alert.innerHTML = `
        <i class="fas fa-check-circle"></i>
        <span>${escapeHtml(message)}</span>
        <button onclick="this.parentElement.remove()" class="alert-close">×</button>
    `;
    alert.style.cssText = `
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #6ee7b7;
        border-radius: 8px;
        margin-bottom: 24px;
        animation: slideDown 0.3s ease-out;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.15);
    `;
    
    container.insertBefore(alert, container.firstChild);
    window.scrollTo({ top: 0, behavior: 'smooth' });
    
    setTimeout(() => {
        if (alert.parentNode) {
            alert.style.transition = 'all 0.3s ease-out';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        }
    }, 5000);
}

function showError(message) {
    const container = document.querySelector('.container');
    if (!container) {
        alert(message);
        return;
    }
    
    const existingAlerts = container.querySelectorAll('.alert-success, .alert-error');
    existingAlerts.forEach(alert => alert.remove());
    
    const alert = document.createElement('div');
    alert.className = 'alert alert-error';
    alert.innerHTML = `
        <i class="fas fa-exclamation-circle"></i>
        <span>${escapeHtml(message)}</span>
        <button onclick="this.parentElement.remove()" class="alert-close">×</button>
    `;
    alert.style.cssText = `
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
        border-radius: 8px;
        margin-bottom: 24px;
        animation: slideDown 0.3s ease-out;
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.15);
    `;
    
    container.insertBefore(alert, container.firstChild);
    window.scrollTo({ top: 0, behavior: 'smooth' });
    
    setTimeout(() => {
        if (alert.parentNode) {
            alert.style.transition = 'all 0.3s ease-out';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        }
    }, 8000);
}

function showConfirmDialog(title, message, confirmText = 'Confirmer', type = 'primary') {
    return new Promise((resolve) => {
        const dialog = document.createElement('div');
        dialog.className = 'confirm-dialog';
        dialog.innerHTML = `
            <div class="confirm-dialog-overlay"></div>
            <div class="confirm-dialog-content">
                <div class="confirm-dialog-header">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>${escapeHtml(title)}</h3>
                </div>
                <p>${escapeHtml(message)}</p>
                <div class="confirm-dialog-buttons">
                    <button class="btn-secondary" id="confirm-cancel">Annuler</button>
                    <button class="btn-${type}" id="confirm-ok">${escapeHtml(confirmText)}</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(dialog);
        
        const style = document.createElement('style');
        style.textContent = `
            .confirm-dialog {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
                animation: fadeIn 0.2s ease;
            }
            
            .confirm-dialog-overlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(2px);
            }
            
            .confirm-dialog-content {
                background: white;
                padding: 24px;
                border-radius: 12px;
                max-width: 450px;
                width: 90%;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                position: relative;
                z-index: 1;
                animation: slideUp 0.3s ease;
            }
            
            .confirm-dialog-header {
                display: flex;
                align-items: center;
                gap: 12px;
                margin-bottom: 16px;
            }
            
            .confirm-dialog-header i {
                font-size: 24px;
                color: #f59e0b;
            }
            
            .confirm-dialog-content h3 {
                margin: 0;
                color: #1f2937;
                font-size: 18px;
                font-weight: 600;
            }
            
            .confirm-dialog-content p {
                margin: 0 0 24px;
                color: #6b7280;
                line-height: 1.6;
                white-space: pre-line;
            }
            
            .confirm-dialog-buttons {
                display: flex;
                gap: 12px;
                justify-content: flex-end;
            }
            
            .confirm-dialog-buttons button {
                padding: 10px 20px;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 600;
                font-size: 14px;
                transition: all 0.2s ease;
            }
            
            .btn-secondary {
                background: #e5e7eb;
                color: #374151;
            }
            
            .btn-secondary:hover {
                background: #d1d5db;
            }
            
            .btn-danger {
                background: #ef4444;
                color: white;
            }
            
            .btn-danger:hover {
                background: #dc2626;
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `;
        document.head.appendChild(style);
        
        document.getElementById('confirm-ok').addEventListener('click', () => {
            dialog.style.animation = 'fadeOut 0.2s ease';
            setTimeout(() => {
                dialog.remove();
                style.remove();
                resolve(true);
            }, 200);
        });
        
        document.getElementById('confirm-cancel').addEventListener('click', () => {
            dialog.style.animation = 'fadeOut 0.2s ease';
            setTimeout(() => {
                dialog.remove();
                style.remove();
                resolve(false);
            }, 200);
        });
        
        dialog.querySelector('.confirm-dialog-overlay').addEventListener('click', () => {
            dialog.style.animation = 'fadeOut 0.2s ease';
            setTimeout(() => {
                dialog.remove();
                style.remove();
                resolve(false);
            }, 200);
        });
        
        const escHandler = (e) => {
            if (e.key === 'Escape') {
                dialog.style.animation = 'fadeOut 0.2s ease';
                setTimeout(() => {
                    dialog.remove();
                    style.remove();
                    resolve(false);
                }, 200);
                document.removeEventListener('keydown', escHandler);
            }
        };
        document.addEventListener('keydown', escHandler);
    });
}

function showEmptyState() {
    const container = document.querySelector('.publications-list');
    if (!container) return;
    
    container.innerHTML = `
        <div class="empty-state" style="text-align: center; padding: 60px 20px; background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <i class="fas fa-inbox" style="font-size: 48px; color: #cbd5e0; margin-bottom: 16px;"></i>
            <h3 style="margin: 0 0 8px 0; color: #2d3748; font-size: 18px;">Aucune publication trouvée</h3>
            <p style="color: #718096; margin: 0 0 24px 0;">Commencez par soumettre votre première publication.</p>
            <button class="btn-primary" onclick="openModal('publication-modal')">
                <i class="fas fa-plus"></i>
                Soumettre une publication
            </button>
        </div>
    `;
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================================================
// NOUVELLE PUBLICATION (bouton "Nouvelle publication")
// ============================================================================

// Variable pour éviter la boucle infinie
let isLoadingForm = false;

// Intercepter l'ouverture de la modal pour nouvelle publication
const originalOpenModal = window.openModal;

if (originalOpenModal) {
    window.openModal = function(modalId) {
        if (modalId === 'publication-modal' && !isLoadingForm) {
            console.log(' Opening modal for NEW publication');
            window.currentPublicationId = null;
            
            // Marquer qu'on est en train de charger
            isLoadingForm = true;
            
            // Appeler la fonction originale pour ouvrir la modal
            originalOpenModal(modalId);
            
            // Charger le formulaire vide
            setTimeout(() => {
                loadFormInModal(null).then(() => {
                    isLoadingForm = false;
                });
            }, 100);
        } else if (modalId !== 'publication-modal') {
            // Pour les autres modals, appeler normalement
            originalOpenModal(modalId);
        }
    };
    
    console.log('openModal interceptor installed');
}

// ============================================================================
// ANIMATIONS CSS
// ============================================================================

const animationStyle = document.createElement('style');
animationStyle.textContent = `
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }
    
    .alert-close {
        background: none;
        border: none;
        font-size: 24px;
        line-height: 1;
        cursor: pointer;
        color: inherit;
        opacity: 0.6;
        padding: 0;
        margin-left: auto;
        transition: opacity 0.2s;
    }
    
    .alert-close:hover {
        opacity: 1;
    }
    
    .spinner-small {
        display: inline-block;
        width: 14px;
        height: 14px;
        border: 2px solid rgba(255,255,255,0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
        vertical-align: middle;
        margin-right: 8px;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(animationStyle);

console.log('Publications manager fully loaded (MEMBRE VERSION - Admin Logic)')