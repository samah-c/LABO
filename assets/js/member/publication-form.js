
/**
 * publication-form.js - Gestion du formulaire de création de publication (MODAL VERSION)
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log(' Publication form handler initialized (modal version)');

    // Load projects and members immediately
    loadProjets();
    loadMembres();
    
    // Initialize form when modal opens
    initModalListener();
    
    // Try to init now in case modal is already open
    setTimeout(() => {
        initPublicationFormModal();
        initCoAuteursManager();
    }, 100);
});

/**
 * Listen for modal open events
 */
function initModalListener() {
    // Listen for when publication modal opens
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1 && node.id === 'publication-modal') {
                    console.log('Publication modal detected in DOM');
                    setTimeout(() => {
                        initPublicationFormModal();
                        initCoAuteursManager();
                    }, 50);
                }
            });
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}

/**
 * Load membres for co-auteurs
 */
async function loadMembres() {
    try {
        const response = await fetch(BASE_URL + '/membre/publications/get-membres');
        
        if (!response.ok) {
            console.warn('Failed to load membres:', response.status);
            return;
        }
        
        const result = await response.json();
        
        if (result.success) {
            // Cache membres globally
            window.membresCache = result.membres;
            console.log(' Membres loaded:', result.membres.length);
        }
    } catch (error) {
        console.error('Error loading membres:', error);
    }
}

/**
 * Load projets
 */
async function loadProjets() {
    try {
        const response = await fetch(BASE_URL + '/membre/publications/get-projets');
        
        if (!response.ok) {
            console.warn('Failed to load projets:', response.status);
            return;
        }
        
        const result = await response.json();
        
        if (result.success) {
            // Cache projets globally
            window.projetsCache = result.projets;
            console.log('Projets loaded:', result.projets.length);
            
            // Update select if it exists
            updateProjetSelect();
        }
    } catch (error) {
        console.error('Error loading projets:', error);
    }
}

/**
 * Update projet select with cached data
 */
function updateProjetSelect() {
    const select = document.querySelector('#projet_id, [name="projet_id"]');
    if (!select || !window.projetsCache) return;
    
    // Keep first option (Aucun projet)
    const firstOption = select.options[0];
    select.innerHTML = '';
    select.appendChild(firstOption);
    
    // Add projets
    window.projetsCache.forEach(projet => {
        const option = document.createElement('option');
        option.value = projet.id;
        option.textContent = projet.titre;
        select.appendChild(option);
    });
}


/**
 * Initialize publication form in modal
 */
function initPublicationFormModal() {
    const form = document.getElementById('publication-form');
    
    if (!form) {
        console.warn('Publication form not found, will retry when modal opens');
        // Listen for modal open event
        document.addEventListener('modalOpened', function(e) {
            if (e.detail === 'publication-modal') {
                initPublicationFormModal();
            }
        });
        return;
    }
    
    console.log('✓ Found publication form:', form);
    
    // Handle form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        console.log(' Submitting publication...');
        
        // Validate
        if (!validatePublicationForm(form)) {
            return;
        }
        
        // Get submit button
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn ? submitBtn.textContent : '';
        
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Envoi en cours...';
        }
        
        try {
            const formData = new FormData(form);
            
            // Log for debugging
            console.log('Sending data:');
            for (let [key, value] of formData.entries()) {
                console.log(`  ${key}: ${value}`);
            }
            
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            console.log('Server response:', result);
            
            if (result.success) {
                showSuccess(result.message);
                
                // Close modal if closeModal function exists
                if (typeof closeModal === 'function') {
                    closeModal('publication-modal');
                }
                
                // Reload page after short delay
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showError(result.message || 'Erreur lors de la création');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            }
            
        } catch (error) {
            console.error('Error:', error);
            showError('Une erreur est survenue. Veuillez réessayer.');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        }
    });
    
    // Add real-time validation
    addRealTimeValidation(form);
}

/**
 * Validate form
 */
function validatePublicationForm(form) {
    const errors = [];
    
    // Titre
    const titre = form.querySelector('#titre, [name="titre"]');
    if (titre && (!titre.value.trim() || titre.value.trim().length < 10)) {
        errors.push('Le titre doit contenir au moins 10 caractères');
        markFieldError(titre);
    } else if (titre) {
        markFieldValid(titre);
    }
    
    // Type
    const type = form.querySelector('#type_publication, [name="type_publication"]');
    if (type && !type.value) {
        errors.push('Le type de publication est obligatoire');
        markFieldError(type);
    } else if (type) {
        markFieldValid(type);
    }
    
    // Date
    const date = form.querySelector('#date_publication, [name="date_publication"]');
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
    
    // Résumé
    const resume = form.querySelector('#resume, [name="resume"]');
    if (resume && (!resume.value.trim() || resume.value.trim().length < 50)) {
        errors.push('Le résumé doit contenir au moins 50 caractères');
        markFieldError(resume);
    } else if (resume) {
        markFieldValid(resume);
    }
    
    // Domaine
    const domaine = form.querySelector('#domaine, [name="domaine"]');
    if (domaine && !domaine.value.trim()) {
        errors.push('Le domaine est obligatoire');
        markFieldError(domaine);
    } else if (domaine) {
        markFieldValid(domaine);
    }
    
    // DOI format (if provided)
    const doi = form.querySelector('#doi, [name="doi"]');
    if (doi && doi.value.trim() && !isValidDOI(doi.value.trim())) {
        errors.push('Format DOI invalide (doit commencer par 10.)');
        markFieldError(doi);
    } else if (doi) {
        markFieldValid(doi);
    }
    
    // URL format (if provided)
    const lien = form.querySelector('#lien, [name="lien"]');
    if (lien && lien.value.trim() && !isValidUrl(lien.value.trim())) {
        errors.push('Format de lien invalide');
        markFieldError(lien);
    } else if (lien) {
        markFieldValid(lien);
    }
    
    if (errors.length > 0) {
        showError(errors.join('<br>'));
        return false;
    }
    
    return true;
}

/**
 * Add real-time validation
 */
function addRealTimeValidation(form) {
    // Titre
    const titre = form.querySelector('#titre, [name="titre"]');
    if (titre) {
        titre.addEventListener('blur', function() {
            if (this.value.trim() && this.value.trim().length < 10) {
                markFieldError(this);
            } else if (this.value.trim().length >= 10) {
                markFieldValid(this);
            }
        });
    }
    
    // Résumé with character counter
    const resume = form.querySelector('#resume, [name="resume"]');
    if (resume) {
        // Add counter
        let counter = resume.parentNode.querySelector('.char-counter');
        if (!counter) {
            counter = document.createElement('div');
            counter.className = 'char-counter';
            counter.style.cssText = 'text-align: right; font-size: 0.85rem; margin-top: 4px;';
            resume.parentNode.appendChild(counter);
        }
        
        resume.addEventListener('input', function() {
            const length = this.value.length;
            counter.textContent = `${length} caractères`;
            counter.style.color = length < 50 ? '#e74c3c' : '#27ae60';
        });
        
        resume.addEventListener('blur', function() {
            if (this.value.trim() && this.value.trim().length < 50) {
                markFieldError(this);
            } else if (this.value.trim().length >= 50) {
                markFieldValid(this);
            }
        });
    }
    
    // Date
    const date = form.querySelector('#date_publication, [name="date_publication"]');
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
    
    // DOI
    const doi = form.querySelector('#doi, [name="doi"]');
    if (doi) {
        doi.addEventListener('blur', function() {
            if (this.value.trim() && !isValidDOI(this.value.trim())) {
                markFieldError(this);
            } else {
                markFieldValid(this);
            }
        });
    }
    
    // Lien (URL)
    const lien = form.querySelector('#lien, [name="lien"]');
    if (lien) {
        lien.addEventListener('blur', function() {
            if (this.value.trim() && !isValidUrl(this.value.trim())) {
                markFieldError(this);
            } else {
                markFieldValid(this);
            }
        });
    }
}

/**
 * Initialize co-auteurs manager
 */
function initCoAuteursManager() {
    const addBtn = document.getElementById('add-coauteur-btn');
    const container = document.getElementById('coauteurs-container');
    
    if (!addBtn || !container) {
        console.log('Co-auteurs elements not found yet');
        return;
    }
    
    console.log('✓ Co-auteurs manager initialized');
    
    addBtn.addEventListener('click', function(e) {
        e.preventDefault();
        addCoAuteurField(container);
    });
    
    // Handle remove (delegated)
    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-coauteur') || 
            e.target.closest('.remove-coauteur')) {
            const btn = e.target.classList.contains('remove-coauteur') ? 
                        e.target : e.target.closest('.remove-coauteur');
            btn.closest('.coauteur-item').remove();
        }
    });
}

/**
 * Add co-auteur field
 */
function addCoAuteurField(container) {
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
    
    // Populate with cached membres
    const select = div.querySelector('select');
    populateCoAuteurSelect(select);
}

/**
 * Populate co-auteur select
 */
function populateCoAuteurSelect(select) {
    const membres = window.membresCache || [];
    
    membres.forEach(membre => {
        const option = document.createElement('option');
        option.value = membre.id;
        option.textContent = `${membre.nom} ${membre.prenom}`;
        select.appendChild(option);
    });
}


/**
 * Validation helpers
 */
function isValidUrl(url) {
    try {
        new URL(url);
        return true;
    } catch {
        return false;
    }
}

function isValidDOI(doi) {
    return /^10\.\d{4,}\/\S+$/.test(doi);
}

function markFieldError(field) {
    if (field) {
        field.style.borderColor = '#e74c3c';
    }
}

function markFieldValid(field) {
    if (field) {
        field.style.borderColor = '#27ae60';
    }
}

/**
 * Show messages
 */
function showSuccess(message) {
    // Try to find existing alert container
    let container = document.querySelector('.container');
    if (!container) {
        container = document.querySelector('.modal-body');
    }
    
    const alert = document.createElement('div');
    alert.className = 'alert alert-success';
    alert.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
    alert.style.cssText = 'margin: 15px 0; padding: 12px 16px; border-radius: 6px;';
    
    if (container) {
        container.insertBefore(alert, container.firstChild);
    }
    
    setTimeout(() => alert.remove(), 5000);
}

function showError(message) {
    // Try to find existing alert container
    let container = document.querySelector('.container');
    if (!container) {
        container = document.querySelector('.modal-body');
    }
    
    const alert = document.createElement('div');
    alert.className = 'alert alert-error';
    alert.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
    alert.style.cssText = 'margin: 15px 0; padding: 12px 16px; border-radius: 6px;';
    
    if (container) {
        container.insertBefore(alert, container.firstChild);
    }
    
    setTimeout(() => alert.remove(), 8000);
}

// Log when script is fully loaded
console.log('✓ publication-form.js loaded successfully');