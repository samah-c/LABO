/**
 * profil-handler.js - FIXED VERSION
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('✓ Profil handler initialized');

    // Handle photo preview
    const photoInput = document.getElementById('photo-input');
    if (photoInput) {
        photoInput.addEventListener('change', handlePhotoChange);
    }

    // Optional: Add phone formatting
    const telephoneInput = document.getElementById('telephone');
    if (telephoneInput) {
        telephoneInput.addEventListener('input', formatPhoneNumber);
    }

    // Optional: Email validation on blur
    const emailInput = document.getElementById('email');
    if (emailInput) {
        emailInput.addEventListener('blur', function() {
            const email = this.value.trim();
            if (email && !isValidEmail(email)) {
                showError('Adresse email invalide');
            }
        });
    }
});

/**
 * Handle photo change with preview
 */
function handlePhotoChange(event) {
    const file = event.target.files[0];
    
    if (!file) {
        return;
    }
    
    console.log('Photo selected:', file.name, file.size, 'bytes');
    
    // Validate file type
    if (!file.type.startsWith('image/')) {
        showError('Veuillez sélectionner une image');
        event.target.value = '';
        return;
    }
    
    // Validate file size (5MB)
    if (file.size > 5 * 1024 * 1024) {
        showError('L\'image ne doit pas dépasser 5 MB');
        event.target.value = '';
        return;
    }
    
    // Preview the image
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById('avatar-preview');
        const placeholder = document.getElementById('avatar-placeholder');
        
        if (preview) {
            preview.src = e.target.result;
            console.log('✓ Preview updated (existing img)');
        } else if (placeholder) {
            // Replace placeholder with image
            const img = document.createElement('img');
            img.id = 'avatar-preview';
            img.src = e.target.result;
            img.alt = 'Photo de profil';
            img.style.width = '140px';
            img.style.height = '140px';
            img.style.borderRadius = '50%';
            img.style.objectFit = 'cover';
            img.style.border = '4px solid #e0e0e0';
            
            placeholder.parentNode.replaceChild(img, placeholder);
            console.log('✓ Preview created (replaced placeholder)');
        }
        
        showSuccess('Photo sélectionnée. Cliquez sur "Enregistrer" pour sauvegarder.');
    };
    
    reader.onerror = function() {
        showError('Erreur lors de la lecture du fichier');
        console.error('FileReader error');
    };
    
    reader.readAsDataURL(file);
}

/**
 * Format phone number (Algerian format)
 */
function formatPhoneNumber(event) {
    let value = event.target.value.replace(/\D/g, '');
    
    if (value.length > 0) {
        // Remove 213 prefix if present
        if (value.startsWith('213')) {
            value = value.substring(3);
        }
        
        // Format: +213 XX XX XX XX
        let formatted = '+213 ';
        if (value.length > 0) formatted += value.substring(0, 2);
        if (value.length > 2) formatted += ' ' + value.substring(2, 4);
        if (value.length > 4) formatted += ' ' + value.substring(4, 6);
        if (value.length > 6) formatted += ' ' + value.substring(6, 8);
        
        event.target.value = formatted.trim();
    }
}

/**
 * Validate email format
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Show error message
 */
function showError(message) {
    removeAlerts();
    
    const alert = document.createElement('div');
    alert.className = 'alert alert-error';
    alert.textContent = message;
    
    const container = document.querySelector('.profil-container') || document.querySelector('.container');
    if (container) {
        container.insertBefore(alert, container.firstChild);
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        setTimeout(() => alert.remove(), 5000);
    }
}

/**
 * Show success message
 */
function showSuccess(message) {
    removeAlerts();
    
    const alert = document.createElement('div');
    alert.className = 'alert alert-success';
    alert.textContent = message;
    
    const container = document.querySelector('.profil-container') || document.querySelector('.container');
    if (container) {
        container.insertBefore(alert, container.firstChild);
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        setTimeout(() => alert.remove(), 5000);
    }
}

/**
 * Remove existing alerts
 */
function removeAlerts() {
    const alerts = document.querySelectorAll('.alert-error, .alert-success');
    alerts.forEach(alert => alert.remove());
}

// Log when forms are submitted (for debugging)
document.addEventListener('submit', function(e) {
    if (e.target.id === 'profil-form') {
        console.log('✓ Profile form submitting...');
        const formData = new FormData(e.target);
        console.log('Form data:', Array.from(formData.entries()));
    } else if (e.target.id === 'password-form') {
        console.log('✓ Password form submitting...');
    }
}, true); // Use capture phase to log before any preventDefault