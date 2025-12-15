/**
 * parametres.js - Gestion interactive des paramètres
 * À placer dans : assets/js/parametres.js
 */

// Configuration des URLs
const BASE_URL = window.location.origin + '/TDW_project';

// Fonction de sauvegarde de la base de données
function backupDatabase() {
    if (!confirm('Créer une sauvegarde de la base de données ?')) return;
    
    const btn = event.target;
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = '⏳ Sauvegarde en cours...';
    
    fetch(`${BASE_URL}/admin/parametres/backup-database`, {
        method: 'POST',
        headers: { 
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showAlert('✓ Sauvegarde créée avec succès : ' + data.filename, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert('✗ Erreur : ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showAlert('✗ Erreur lors de la sauvegarde', 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = originalText;
    });
}

// Fonction pour afficher la modale de restauration
function showRestoreModal() {
    const modal = document.getElementById('restore-modal');
    if (modal) {
        modal.classList.add('is-open');
        modal.style.display = 'flex';
    }
}

// Fonction pour fermer la modale de restauration
function closeRestoreModal() {
    const modal = document.getElementById('restore-modal');
    if (modal) {
        modal.classList.remove('is-open');
        modal.style.display = 'none';
    }
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
    
    fetch(`${BASE_URL}/admin/parametres/restore-database`, {
        method: 'POST',
        body: formData,
        headers: { 
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
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
        console.error('Erreur:', error);
        showAlert('✗ Erreur lors de la restauration', 'error');
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

// Fonction pour vider le cache
function clearCache() {
    if (!confirm('Vider le cache ?')) return;
    
    const btn = event.target;
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = '⏳ Nettoyage...';
    
    fetch(`${BASE_URL}/admin/parametres/clear-cache`, {
        method: 'POST',
        headers: { 
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showAlert('✓ Cache vidé avec succès', 'success');
        } else {
            showAlert('✗ Erreur : ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showAlert('✗ Erreur lors du nettoyage', 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = originalText;
    });
}

// Fonction pour sauvegarder les paramètres de maintenance
function saveMaintenanceSettings() {
    const mode = document.getElementById('maintenance_mode').checked;
    const message = document.getElementById('maintenance_message').value;
    
    const btn = event.target;
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = '⏳ Enregistrement...';
    
    fetch(`${BASE_URL}/admin/parametres/save-maintenance`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ mode, message })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showAlert('✓ Paramètres de maintenance enregistrés', 'success');
        } else {
            showAlert('✗ Erreur : ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showAlert('✗ Erreur lors de l\'enregistrement', 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = originalText;
    });
}

// Fonction utilitaire pour afficher des alertes
function showAlert(message, type = 'info') {
    // Supprimer les anciennes alertes
    const oldAlerts = document.querySelectorAll('.alert');
    oldAlerts.forEach(alert => alert.remove());
    
    // Créer la nouvelle alerte
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} show`;
    alert.textContent = message;
    
    // Insérer l'alerte en haut du container
    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(alert, container.firstChild);
        
        // Auto-suppression après 5 secondes
        setTimeout(() => {
            alert.classList.remove('show');
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    } else {
        alert(message);
    }
}

// Fermer les modales en cliquant sur la croix
document.addEventListener('DOMContentLoaded', function() {
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

// Exposer les fonctions globalement
window.backupDatabase = backupDatabase;
window.showRestoreModal = showRestoreModal;
window.closeRestoreModal = closeRestoreModal;
window.restoreDatabase = restoreDatabase;
window.downloadBackup = downloadBackup;
window.clearCache = clearCache;
window.saveMaintenanceSettings = saveMaintenanceSettings;