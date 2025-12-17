/**
 * Gestionnaire pour la page profil membre
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Gestionnaire de profil initialisé');

    // Éléments du formulaire
    const profilForm = document.getElementById('profil-form');
    const passwordForm = document.getElementById('password-form');
    const btnSave = document.getElementById('btn-save');

    // Sauvegarder les valeurs initiales pour la réinitialisation
    let initialFormData = null;
    if (profilForm) {
        initialFormData = new FormData(profilForm);
    }

    // Validation du formulaire profil
    if (profilForm) {
        profilForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!validateProfilForm()) {
                return false;
            }
            
            if (btnSave) {
                btnSave.disabled = true;
                btnSave.textContent = 'Enregistrement...';
            }
            
            // Soumettre le formulaire
            this.submit();
        });
    }

    // Validation du formulaire mot de passe
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!validatePasswordForm()) {
                return false;
            }
            
            // Soumettre le formulaire
            this.submit();
        });
    }

    // Validation en temps réel
    const emailInput = document.getElementById('email');
    if (emailInput) {
        emailInput.addEventListener('blur', function() {
            validateEmail(this.value);
        });
    }

    const telephoneInput = document.getElementById('telephone');
    if (telephoneInput) {
        telephoneInput.addEventListener('input', function() {
            formatPhoneNumber(this);
        });
    }

    // Confirmation avant de quitter si modifications non sauvegardées
    let formModified = false;
    if (profilForm) {
        const inputs = profilForm.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('change', function() {
                formModified = true;
            });
        });

        window.addEventListener('beforeunload', function(e) {
            if (formModified) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    }

    // Réinitialiser le flag lors de la soumission
    if (profilForm) {
        profilForm.addEventListener('submit', function() {
            formModified = false;
        });
    }
});

/**
 * Valider le formulaire profil
 */
function validateProfilForm() {
    const nom = document.getElementById('nom').value.trim();
    const prenom = document.getElementById('prenom').value.trim();
    const email = document.getElementById('email').value.trim();

    // Vérifier les champs obligatoires
    if (!nom) {
        showError('Veuillez saisir votre nom');
        document.getElementById('nom').focus();
        return false;
    }

    if (!prenom) {
        showError('Veuillez saisir votre prénom');
        document.getElementById('prenom').focus();
        return false;
    }

    if (!email) {
        showError('Veuillez saisir votre email');
        document.getElementById('email').focus();
        return false;
    }

    if (!validateEmail(email)) {
        return false;
    }

    return true;
}

/**
 * Valider le formulaire de changement de mot de passe
 */
function validatePasswordForm() {
    const currentPassword = document.getElementById('current_password').value;
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;

    if (!currentPassword) {
        showError('Veuillez saisir votre mot de passe actuel');
        document.getElementById('current_password').focus();
        return false;
    }

    if (!newPassword) {
        showError('Veuillez saisir un nouveau mot de passe');
        document.getElementById('new_password').focus();
        return false;
    }

    if (newPassword.length < 6) {
        showError('Le mot de passe doit contenir au moins 6 caractères');
        document.getElementById('new_password').focus();
        return false;
    }

    if (newPassword !== confirmPassword) {
        showError('Les mots de passe ne correspondent pas');
        document.getElementById('confirm_password').focus();
        return false;
    }

    if (newPassword === currentPassword) {
        showError('Le nouveau mot de passe doit être différent de l\'ancien');
        document.getElementById('new_password').focus();
        return false;
    }

    return true;
}

/**
 * Valider une adresse email
 */
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (!emailRegex.test(email)) {
        showError('Veuillez saisir une adresse email valide');
        document.getElementById('email').focus();
        return false;
    }
    
    return true;
}

/**
 * Formater le numéro de téléphone
 */
function formatPhoneNumber(input) {
    let value = input.value.replace(/\D/g, '');
    
    if (value.length > 0) {
        // Format: +213 XX XX XX XX
        if (value.startsWith('213')) {
            value = value.substring(3);
        }
        
        if (value.length > 2) {
            value = value.substring(0, 2) + ' ' + value.substring(2);
        }
        if (value.length > 5) {
            value = value.substring(0, 5) + ' ' + value.substring(5);
        }
        if (value.length > 8) {
            value = value.substring(0, 8) + ' ' + value.substring(8, 10);
        }
        
        input.value = '+213 ' + value;
    }
}

/**
 * Gérer le changement de photo
 */
function handlePhotoChange(event) {
    const file = event.target.files[0];
    
    if (!file) {
        return;
    }
    
    // Vérifier le type de fichier
    if (!file.type.startsWith('image/')) {
        showError('Veuillez sélectionner une image');
        return;
    }
    
    // Vérifier la taille (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
        showError('L\'image ne doit pas dépasser 5 MB');
        return;
    }
    
    // Prévisualiser l'image
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById('avatar-preview');
        const placeholder = document.getElementById('avatar-placeholder');
        
        if (preview) {
            preview.src = e.target.result;
        } else if (placeholder) {
            // Créer un nouvel élément img
            const img = document.createElement('img');
            img.id = 'avatar-preview';
            img.src = e.target.result;
            img.alt = 'Photo de profil';
            img.style.width = '140px';
            img.style.height = '140px';
            img.style.borderRadius = '50%';
            img.style.objectFit = 'cover';
            img.style.border = '4px solid var(--border-color)';
            
            placeholder.parentNode.replaceChild(img, placeholder);
        }
        
        showSuccess('Photo sélectionnée. N\'oubliez pas d\'enregistrer vos modifications.');
    };
    
    reader.readAsDataURL(file);
}

/**
 * Réinitialiser le formulaire
 */
function resetForm() {
    const profilForm = document.getElementById('profil-form');
    
    if (confirm('Êtes-vous sûr de vouloir annuler vos modifications ?')) {
        if (profilForm) {
            profilForm.reset();
            
            // Réinitialiser aussi la prévisualisation de la photo si modifiée
            const photoInput = document.getElementById('photo-input');
            if (photoInput) {
                photoInput.value = '';
            }
            
            // Recharger la page pour restaurer l'état initial
            window.location.reload();
        }
    }
}

/**
 * Afficher un message d'erreur
 */
function showError(message) {
    // Supprimer les alertes existantes
    const existingAlerts = document.querySelectorAll('.alert-error');
    existingAlerts.forEach(alert => alert.remove());
    
    // Créer une nouvelle alerte
    const alert = document.createElement('div');
    alert.className = 'alert alert-error';
    alert.textContent = message;
    
    // Insérer en haut de la page
    const container = document.querySelector('.profil-container');
    if (container) {
        container.insertBefore(alert, container.firstChild);
        
        // Faire défiler vers le haut
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        // Supprimer après 5 secondes
        setTimeout(() => {
            alert.remove();
        }, 5000);
    }
}

/**
 * Afficher un message de succès
 */
function showSuccess(message) {
    // Supprimer les alertes existantes
    const existingAlerts = document.querySelectorAll('.alert-success');
    existingAlerts.forEach(alert => alert.remove());
    
    // Créer une nouvelle alerte
    const alert = document.createElement('div');
    alert.className = 'alert alert-success';
    alert.textContent = message;
    
    // Insérer en haut de la page
    const container = document.querySelector('.profil-container');
    if (container) {
        container.insertBefore(alert, container.firstChild);
        
        // Faire défiler vers le haut
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        // Supprimer après 5 secondes
        setTimeout(() => {
            alert.remove();
        }, 5000);
    }
}

/**
 * Activer/désactiver l'édition inline (fonctionnalité future)
 */
function toggleEdit(fieldId) {
    const field = document.getElementById(fieldId);
    if (field) {
        field.disabled = !field.disabled;
        field.focus();
    }
}

/**
 * Prévisualiser les modifications avant sauvegarde
 */
function previewChanges() {
    const form = document.getElementById('profil-form');
    if (!form) return;
    
    const formData = new FormData(form);
    const changes = [];
    
    for (let [key, value] of formData.entries()) {
        const input = form.elements[key];
        if (input && input.defaultValue !== value) {
            changes.push({
                field: key,
                oldValue: input.defaultValue,
                newValue: value
            });
        }
    }
    
    if (changes.length > 0) {
        console.log('Modifications détectées:', changes);
        return true;
    }
    
    return false;
}

/**
 * Validation côté client pour éviter les soumissions multiples
 */
let isSubmitting = false;

document.addEventListener('submit', function(e) {
    if (isSubmitting) {
        e.preventDefault();
        return false;
    }
    
    if (e.target.id === 'profil-form' || e.target.id === 'password-form') {
        isSubmitting = true;
        
        // Réactiver après 3 secondes en cas d'erreur
        setTimeout(() => {
            isSubmitting = false;
        }, 3000);
    }
});