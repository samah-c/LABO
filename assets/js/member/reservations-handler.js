/**
 * reservations-handler.js - FIXED VERSION
 */

console.log('Reservations handler loading...');

// ============================================
// MODAL MANAGEMENT
// ============================================

function openReservationModal() {
    console.log('Opening reservation modal...');
    const modal = document.getElementById('reservationModal');
    if (!modal) {
        console.error('Modal not found!');
        return;
    }
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Load stats if equipment already selected
    const equipementSelect = document.getElementById('equipement_id');
    if (equipementSelect && equipementSelect.value) {
        loadEquipementStats(equipementSelect.value);
    }
}

function closeReservationModal() {
    console.log('Closing reservation modal...');
    const modal = document.getElementById('reservationModal');
    if (!modal) return;
    
    modal.classList.remove('active');
    const form = document.getElementById('reservationForm');
    if (form) form.reset();
    document.body.style.overflow = 'auto';
    
    clearAlerts();
    clearStats();
}

// Close modal when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('reservationModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeReservationModal();
            }
        });
    }
});

// Close with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('reservationModal');
        if (modal && modal.classList.contains('active')) {
            closeReservationModal();
        }
    }
});

// ============================================
// TAB MANAGEMENT
// ============================================

function showTab(tabName) {
    console.log('Switching to tab:', tabName);
    
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    const selectedTab = document.getElementById('tab-' + tabName);
    if (selectedTab) {
        selectedTab.classList.add('active');
    }
    
    // Activate button
    event.target.classList.add('active');
}

// ============================================
// DATE VALIDATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    const dateDebut = document.getElementById('date_debut');
    const dateFin = document.getElementById('date_fin');
    
    if (dateDebut) {
        dateDebut.addEventListener('change', function() {
            if (dateFin && this.value) {
                dateFin.min = this.value;
                
                // Reset end date if it's before start date
                if (dateFin.value && dateFin.value < this.value) {
                    dateFin.value = '';
                }
            }
            checkConflicts();
        });
    }
    
    if (dateFin) {
        dateFin.addEventListener('change', function() {
            checkConflicts();
        });
    }
});

// ============================================
// CONFLICT DETECTION
// ============================================

async function checkConflicts() {
    const equipementId = document.getElementById('equipement_id')?.value;
    const dateDebut = document.getElementById('date_debut')?.value;
    const dateFin = document.getElementById('date_fin')?.value;
    
    if (!equipementId || !dateDebut || !dateFin) {
        clearAlerts();
        return;
    }
    
    // Check that end date > start date
    if (dateFin <= dateDebut) {
        showAlert('La date de fin doit être postérieure à la date de début.', 'error');
        return;
    }
    
    try {
        // Note: This API endpoint needs to be created in your routes
        const response = await fetch('/TDW_project/api/membre/reservations/check-conflicts', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                equipement_id: equipementId,
                date_debut: dateDebut,
                date_fin: dateFin
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (data.conflicts && data.conflicts.length > 0) {
                showConflictAlert(data.conflicts);
            } else {
                showAlert('✓ Créneau disponible !', 'success');
            }
        }
    } catch (error) {
        console.error('Error checking conflicts:', error);
        // Don't block user on API error
    }
}

function showConflictAlert(conflicts) {
    let message = 'Attention : Ce créneau est en conflit avec :\n\n';
    
    conflicts.forEach(conflict => {
        message += `• ${conflict.membre_nom} - ${formatDate(conflict.date_debut)} à ${formatDate(conflict.date_fin)}\n`;
    });
    
    message += '\nVeuillez choisir un autre créneau.';
    showAlert(message, 'error', true);
}

// ============================================
// EQUIPMENT STATS
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    const equipementSelect = document.getElementById('equipement_id');
    if (equipementSelect) {
        equipementSelect.addEventListener('change', function() {
            const equipementId = this.value;
            
            if (equipementId) {
                loadEquipementStats(equipementId);
                checkConflicts();
            } else {
                clearStats();
            }
        });
    }
});

async function loadEquipementStats(equipementId) {
    try {
        const response = await fetch(`/TDW_project/api/membre/equipements/${equipementId}/stats`);
        const data = await response.json();
        
        if (data.success) {
            displayStats(data.stats);
        }
    } catch (error) {
        console.error('Error loading equipment stats:', error);
    }
}

function displayStats(stats) {
    let statsContainer = document.getElementById('equipement-stats');
    
    if (!statsContainer) {
        const equipementGroup = document.getElementById('equipement_id')?.closest('.form-group');
        if (!equipementGroup) return;
        
        statsContainer = document.createElement('div');
        statsContainer.id = 'equipement-stats';
        statsContainer.className = 'equipement-stats';
        equipementGroup.after(statsContainer);
    }
    
    statsContainer.innerHTML = `
        <div class="stats-header">
            <h4>Statistiques d'utilisation</h4>
        </div>
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-label">Réservations ce mois</div>
                <div class="stat-value">${stats.reservations_mois || 0}</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Taux d'occupation</div>
                <div class="stat-value">${stats.taux_occupation || 0}%</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Durée moyenne</div>
                <div class="stat-value">${stats.duree_moyenne || 0}h</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Prochaine dispo</div>
                <div class="stat-value">${stats.prochaine_dispo || 'Maintenant'}</div>
            </div>
        </div>
    `;
}

function clearStats() {
    const statsContainer = document.getElementById('equipement-stats');
    if (statsContainer) {
        statsContainer.remove();
    }
}

// ============================================
// ALERT SYSTEM
// ============================================

function showAlert(message, type = 'info', persistent = false) {
    clearAlerts();
    
    const alertContainer = document.createElement('div');
    alertContainer.className = `reservation-alert alert-${type}`;
    
    alertContainer.innerHTML = `
        <div class="alert-content">
            <span class="alert-message">${message}</span>
        </div>
        ${!persistent ? '<button class="alert-close" onclick="clearAlerts()">×</button>' : ''}
    `;
    
    const form = document.getElementById('reservationForm');
    if (form) {
        form.insertBefore(alertContainer, form.firstChild);
    }
    
    if (!persistent) {
        setTimeout(clearAlerts, 5000);
    }
}

function clearAlerts() {
    const alerts = document.querySelectorAll('.reservation-alert');
    alerts.forEach(alert => alert.remove());
}

// ============================================
// FORM VALIDATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('reservationForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const dateDebut = document.getElementById('date_debut').value;
            const dateFin = document.getElementById('date_fin').value;
            
            if (dateDebut && dateFin && dateFin <= dateDebut) {
                e.preventDefault();
                showAlert('La date de fin doit être postérieure à la date de début.', 'error');
                return false;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Réservation en cours...';
            }
        });
    }
});

// ============================================
// UTILITY FUNCTIONS
// ============================================

function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return date.toLocaleDateString('fr-FR', options);
}

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('Reservations handler initialized');
    
    // Log equipment dropdown for debugging
    const equipementSelect = document.getElementById('equipement_id');
    if (equipementSelect) {
        console.log('Equipment select found with', equipementSelect.options.length, 'options');
        
        // Log all options
        for (let i = 0; i < equipementSelect.options.length; i++) {
            console.log('Option', i, ':', equipementSelect.options[i].text);
        }
    } else {
        console.error('Equipment select not found!');
    }
});

console.log(' Reservations handler loaded successfully');