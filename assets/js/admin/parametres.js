/**
 * parametres.js - Version avec meilleur diagnostic des erreurs
 */

const BASE_URL = window.location.origin + '/TDW_project';

// Fonction de sauvegarde de la base de données
function backupDatabase() {
    if (!confirm('Créer une sauvegarde de la base de données ?')) return;
    
    const btn = event.target;
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = '⏳ Sauvegarde en cours...';
    
    fetch(`${BASE_URL}/api/admin/database/backup`, {
        method: 'POST',
        headers: { 
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        // Vérifier le type de contenu
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            console.error('Response is not JSON:', contentType);
            return response.text().then(text => {
                console.error('Response body:', text);
                throw new Error('La réponse du serveur n\'est pas au format JSON');
            });
        }
        
        return response.json();
    })
    .then(data => {
        console.log('Parsed JSON:', data);
        
        if (data.success) {
            showAlert(`✓ Sauvegarde créée avec succès : ${data.filename} (${data.size_formatted || ''})`, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            console.error('Backup failed:', data);
            showAlert('✗ Erreur : ' + (data.message || 'Erreur inconnue'), 'error');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        showAlert('✗ Erreur de communication : ' + error.message, 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = originalText;
    });
}

// Fonction pour vider le cache
function clearCache() {
    if (!confirm('Vider le cache ?')) return;
    
    const btn = event.target;
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = '⏳ Nettoyage...';
    
    fetch(`${BASE_URL}/api/admin/cache/clear`, {
        method: 'POST',
        headers: { 
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.error('Clear cache response:', text);
                throw new Error('La réponse du serveur n\'est pas au format JSON');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showAlert('✓ Cache vidé avec succès', 'success');
        } else {
            showAlert('✗ Erreur : ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Clear cache error:', error);
        showAlert('✗ Erreur : ' + error.message, 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = originalText;
    });
}

// Fonction de restauration de la base de données
function restoreDatabase() {
    if (!confirm('⚠️ ATTENTION : Cela remplacera TOUTES les données actuelles. Continuer ?')) return;
    
    const form = document.getElementById('restore-form');
    const formData = new FormData(form);
    
    const btn = event.target;
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = '⏳ Restauration...';
    
    fetch(`${BASE_URL}/api/admin/database/restore`, {
        method: 'POST',
        body: formData,
        headers: { 
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.error('Restore response:', text);
                throw new Error('La réponse du serveur n\'est pas au format JSON');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showAlert('✓ Base de données restaurée avec succès', 'success');
            closeRestoreModal();
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert('✗ Erreur : ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Restore error:', error);
        showAlert('✗ Erreur : ' + error.message, 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = originalText;
    });
}

// Fonction pour télécharger un backup
function downloadBackup(filename) {
    window.location.href = `${BASE_URL}/admin/parametres/download-backup/${filename}`;
}

// Fonction pour sauvegarder les paramètres de maintenance
function saveMaintenanceSettings() {
    const mode = document.getElementById('maintenance_mode').checked;
    const message = document.getElementById('maintenance_message').value;
    
    const btn = event.target;
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = '⏳ Enregistrement...';
    
    fetch(`${BASE_URL}/api/admin/maintenance/save`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ mode, message })
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.error('Maintenance response:', text);
                throw new Error('La réponse du serveur n\'est pas au format JSON');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showAlert('✓ Paramètres de maintenance enregistrés', 'success');
        } else {
            showAlert('✗ Erreur : ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Maintenance error:', error);
        showAlert('✗ Erreur : ' + error.message, 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = originalText;
    });
}

// Fonctions pour les modales
function showRestoreModal() {
    const modal = document.getElementById('restore-modal');
    if (modal) {
        modal.classList.add('is-open');
        modal.style.display = 'flex';
    }
}

function closeRestoreModal() {
    const modal = document.getElementById('restore-modal');
    if (modal) {
        modal.classList.remove('is-open');
        modal.style.display = 'none';
    }
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('is-open');
        modal.style.display = 'flex';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('is-open');
        modal.style.display = 'none';
    }
}

// Fonction utilitaire pour afficher des alertes
function showAlert(message, type = 'info') {
    // Supprimer les anciennes alertes
    const oldAlerts = document.querySelectorAll('.custom-alert');
    oldAlerts.forEach(alert => alert.remove());
    
    // Créer la nouvelle alerte
    const alert = document.createElement('div');
    alert.className = `custom-alert custom-alert-${type}`;
    alert.textContent = message;
    
    // Style inline pour l'alerte
    Object.assign(alert.style, {
        position: 'fixed',
        top: '20px',
        right: '20px',
        padding: '15px 20px',
        borderRadius: '8px',
        color: 'white',
        fontSize: '14px',
        fontWeight: '500',
        zIndex: '10000',
        minWidth: '300px',
        maxWidth: '500px',
        boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
        animation: 'slideIn 0.3s ease-out',
        backgroundColor: type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'
    });
    
    // Insérer l'alerte
    document.body.appendChild(alert);
    
    // Auto-suppression après 5 secondes
    setTimeout(() => {
        alert.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => alert.remove(), 300);
    }, 5000);
}

// Initialisation au chargement du DOM
document.addEventListener('DOMContentLoaded', function() {
    console.log('Parametres.js loaded');
    
    // Fermer les modales en cliquant sur la croix
    const modalCloses = document.querySelectorAll('.modal-close');
    modalCloses.forEach(close => {
        close.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.classList.remove('is-open');
                modal.style.display = 'none';
            }
        });
    });
    
    // Fermer les modales en cliquant à l'extérieur
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('is-open');
                this.style.display = 'none';
            }
        });
    });
    
    // Prévisualisation du logo
    const logoInput = document.getElementById('logo');
    if (logoInput) {
        logoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    let preview = document.querySelector('.logo-preview');
                    if (!preview) {
                        preview = document.createElement('div');
                        preview.className = 'logo-preview';
                        logoInput.parentNode.appendChild(preview);
                    }
                    preview.innerHTML = `<img src="${e.target.result}" alt="Prévisualisation" style="max-width: 200px; margin-top: 10px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">`;
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Prévisualisation des couleurs
    const primaryColor = document.getElementById('primary_color');
    const secondaryColor = document.getElementById('secondary_color');
    
    if (primaryColor) {
        primaryColor.addEventListener('input', function() {
            document.documentElement.style.setProperty('--primary', this.value);
        });
    }
    
    if (secondaryColor) {
        secondaryColor.addEventListener('input', function() {
            document.documentElement.style.setProperty('--secondary', this.value);
        });
    }
});

// Ajouter les animations CSS
if (!document.getElementById('parametres-animations')) {
    const style = document.createElement('style');
    style.id = 'parametres-animations';
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
}

// Exposer les fonctions globalement
window.backupDatabase = backupDatabase;
window.showRestoreModal = showRestoreModal;
window.closeRestoreModal = closeRestoreModal;
window.restoreDatabase = restoreDatabase;
window.downloadBackup = downloadBackup;
window.clearCache = clearCache;
window.saveMaintenanceSettings = saveMaintenanceSettings;
window.openModal = openModal;
window.closeModal = closeModal;
window.showAlert = showAlert;