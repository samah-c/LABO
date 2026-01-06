/**
 * reservations-handler.js - Gestionnaire complet pour les réservations
 * À créer dans : assets/js/member/reservations-handler.js
 */

// ============================================
// GESTION DES ONGLETS
// ============================================

function showTab(tabName) {
    // Masquer tous les onglets
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Afficher l'onglet sélectionné
    document.getElementById('tab-' + tabName).classList.add('active');
    event.target.classList.add('active');
}

// ============================================
// GESTION DU MODAL
// ============================================

function openReservationModal() {
    document.getElementById('reservationModal').classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Charger les statistiques de l'équipement sélectionné si déjà sélectionné
    const equipementSelect = document.getElementById('equipement_id');
    if (equipementSelect && equipementSelect.value) {
        loadEquipementStats(equipementSelect.value);
    }
}

function closeReservationModal() {
    document.getElementById('reservationModal').classList.remove('active');
    document.getElementById('reservationForm').reset();
    document.body.style.overflow = 'auto';
    
    // Nettoyer les alertes et statistiques
    clearAlerts();
    clearStats();
}

// Fermer le modal en cliquant à l'extérieur
document.getElementById('reservationModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeReservationModal();
    }
});

// Fermer avec la touche Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('reservationModal');
        if (modal && modal.classList.contains('active')) {
            closeReservationModal();
        }
    }
});

// ============================================
// VALIDATION DES DATES
// ============================================

document.getElementById('date_debut')?.addEventListener('change', function() {
    const dateFin = document.getElementById('date_fin');
    if (dateFin && this.value) {
        dateFin.min = this.value;
        
        // Si date fin est avant date début, la réinitialiser
        if (dateFin.value && dateFin.value < this.value) {
            dateFin.value = '';
        }
    }
    
    // Vérifier les conflits si l'équipement et les deux dates sont renseignés
    checkConflicts();
});

document.getElementById('date_fin')?.addEventListener('change', function() {
    checkConflicts();
});

// ============================================
// DÉTECTION DES CONFLITS
// ============================================

async function checkConflicts() {
    const equipementId = document.getElementById('equipement_id')?.value;
    const dateDebut = document.getElementById('date_debut')?.value;
    const dateFin = document.getElementById('date_fin')?.value;
    
    if (!equipementId || !dateDebut || !dateFin) {
        clearAlerts();
        return;
    }
    
    // Vérifier que date fin > date début
    if (dateFin <= dateDebut) {
        showAlert('La date de fin doit être postérieure à la date de début.', 'error');
        return;
    }
    
    try {
        // Appel API pour vérifier les conflits
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
                // Il y a des conflits
                showConflictAlert(data.conflicts);
            } else {
                // Pas de conflits
                showAlert('✓ Créneau disponible !', 'success');
            }
        }
    } catch (error) {
        console.error('Erreur lors de la vérification des conflits:', error);
        // Ne pas bloquer l'utilisateur en cas d'erreur API
    }
}

function showConflictAlert(conflicts) {
    let message = '⚠️ Attention : Ce créneau est en conflit avec les réservations suivantes :\n\n';
    
    conflicts.forEach(conflict => {
        message += `• ${conflict.membre_nom} - ${formatDate(conflict.date_debut)} à ${formatDate(conflict.date_fin)}\n`;
    });
    
    message += '\nVeuillez choisir un autre créneau.';
    
    showAlert(message, 'error', true);
}

// ============================================
// CHARGEMENT DES STATISTIQUES D'ÉQUIPEMENT
// ============================================

document.getElementById('equipement_id')?.addEventListener('change', function() {
    const equipementId = this.value;
    
    if (equipementId) {
        loadEquipementStats(equipementId);
        checkConflicts();
    } else {
        clearStats();
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
        console.error('Erreur lors du chargement des statistiques:', error);
    }
}

function displayStats(stats) {
    // Vérifier si le conteneur existe, sinon le créer
    let statsContainer = document.getElementById('equipement-stats');
    
    if (!statsContainer) {
        // Créer le conteneur après le select d'équipement
        const equipementGroup = document.getElementById('equipement_id').closest('.form-group');
        statsContainer = document.createElement('div');
        statsContainer.id = 'equipement-stats';
        statsContainer.className = 'equipement-stats';
        equipementGroup.after(statsContainer);
    }
    
    statsContainer.innerHTML = `
        <div class="stats-header">
            <h4>📊 Statistiques d'utilisation</h4>
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
        ${stats.reservations_recentes && stats.reservations_recentes.length > 0 ? `
        <div class="recent-reservations">
            <h5>Réservations à venir</h5>
            <ul>
                ${stats.reservations_recentes.map(res => `
                    <li>
                        <span class="res-date">${formatDate(res.date_debut)}</span>
                        <span class="res-member">${res.membre_nom}</span>
                    </li>
                `).join('')}
            </ul>
        </div>
        ` : ''}
    `;
}

function clearStats() {
    const statsContainer = document.getElementById('equipement-stats');
    if (statsContainer) {
        statsContainer.remove();
    }
}

// ============================================
// SYSTÈME D'ALERTES
// ============================================

function showAlert(message, type = 'info', persistent = false) {
    clearAlerts();
    
    const alertContainer = document.createElement('div');
    alertContainer.className = `reservation-alert alert-${type}`;
    alertContainer.innerHTML = `
        <div class="alert-content">
            <span class="alert-icon">${getAlertIcon(type)}</span>
            <span class="alert-message">${message}</span>
        </div>
        ${!persistent ? '<button class="alert-close" onclick="clearAlerts()">×</button>' : ''}
    `;
    
    // Insérer avant le formulaire
    const form = document.getElementById('reservationForm');
    form.insertBefore(alertContainer, form.firstChild);
    
    // Auto-fermeture après 5 secondes si non persistant
    if (!persistent) {
        setTimeout(() => {
            clearAlerts();
        }, 5000);
    }
}

function clearAlerts() {
    const alerts = document.querySelectorAll('.reservation-alert');
    alerts.forEach(alert => alert.remove());
}

function getAlertIcon(type) {
    const icons = {
        'success': '✓',
        'error': '⚠️',
        'warning': '⚠️',
        'info': 'ℹ️'
    };
    return icons[type] || 'ℹ️';
}

// ============================================
// VALIDATION DU FORMULAIRE
// ============================================

document.getElementById('reservationForm')?.addEventListener('submit', function(e) {
    const dateDebut = document.getElementById('date_debut').value;
    const dateFin = document.getElementById('date_fin').value;
    
    // Vérification finale des dates
    if (dateDebut && dateFin && dateFin <= dateDebut) {
        e.preventDefault();
        showAlert('La date de fin doit être postérieure à la date de début.', 'error');
        return false;
    }
    
    // Afficher un indicateur de chargement
    const submitBtn = this.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Réservation en cours...';
    }
});

// ============================================
// FILTRAGE ET RECHERCHE DANS L'HISTORIQUE
// ============================================

function filterHistory(searchTerm) {
    const historyItems = document.querySelectorAll('#tab-historique .reservation-item');
    const term = searchTerm.toLowerCase();
    
    historyItems.forEach(item => {
        const text = item.textContent.toLowerCase();
        if (text.includes(term)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}

// Ajouter une barre de recherche dans l'historique
function addHistorySearch() {
    const historyTab = document.getElementById('tab-historique');
    if (!historyTab || historyTab.querySelector('.history-search')) return;
    
    const searchBar = document.createElement('div');
    searchBar.className = 'history-search';
    searchBar.innerHTML = `
        <input type="text" 
               placeholder="Rechercher dans l'historique..." 
               class="search-input"
               onkeyup="filterHistory(this.value)">
    `;
    
    historyTab.insertBefore(searchBar, historyTab.firstChild);
}

// ============================================
// AFFICHAGE DES STATISTIQUES GLOBALES
// ============================================

async function loadGlobalStats() {
    try {
        const response = await fetch('/TDW_project/api/membre/reservations/stats');
        const data = await response.json();
        
        if (data.success) {
            displayGlobalStats(data.stats);
        }
    } catch (error) {
        console.error('Erreur lors du chargement des statistiques globales:', error);
    }
}

function displayGlobalStats(stats) {
    const container = document.querySelector('.container');
    if (!container) return;
    
    // Vérifier si le widget existe déjà
    let statsWidget = document.getElementById('global-stats-widget');
    
    if (!statsWidget) {
        statsWidget = document.createElement('div');
        statsWidget.id = 'global-stats-widget';
        statsWidget.className = 'stats-widget';
        
        // Insérer après les onglets
        const tabs = document.querySelector('.tabs');
        if (tabs) {
            tabs.after(statsWidget);
        }
    }
    
    statsWidget.innerHTML = `
        <div class="widget-header">
            <h3>📊 Mes statistiques de réservation</h3>
            <button class="widget-toggle" onclick="toggleStatsWidget()">−</button>
        </div>
        <div class="widget-content">
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-number">${stats.total_reservations || 0}</div>
                    <div class="stat-label">Réservations totales</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">${stats.reservations_mois || 0}</div>
                    <div class="stat-label">Ce mois</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">${stats.heures_utilisees || 0}h</div>
                    <div class="stat-label">Heures utilisées</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">${stats.taux_annulation || 0}%</div>
                    <div class="stat-label">Taux d'annulation</div>
                </div>
            </div>
            ${stats.equipements_favoris && stats.equipements_favoris.length > 0 ? `
            <div class="favorite-equipments">
                <h4>Équipements les plus réservés</h4>
                <ul>
                    ${stats.equipements_favoris.map(eq => `
                        <li>
                            <span class="eq-name">${eq.nom}</span>
                            <span class="eq-count">${eq.count} fois</span>
                        </li>
                    `).join('')}
                </ul>
            </div>
            ` : ''}
        </div>
    `;
}

function toggleStatsWidget() {
    const widget = document.getElementById('global-stats-widget');
    const content = widget?.querySelector('.widget-content');
    const toggle = widget?.querySelector('.widget-toggle');
    
    if (content && toggle) {
        if (content.style.display === 'none') {
            content.style.display = 'block';
            toggle.textContent = '−';
        } else {
            content.style.display = 'none';
            toggle.textContent = '+';
        }
    }
}

// ============================================
// NOTIFICATIONS ET RAPPELS
// ============================================

function checkUpcomingReservations() {
    const now = new Date();
    const tomorrow = new Date(now.getTime() + 24 * 60 * 60 * 1000);
    
    // Récupérer les réservations actives
    const activeCards = document.querySelectorAll('#tab-actives .reservation-card');
    
    activeCards.forEach(card => {
        const dateDebutStr = card.querySelector('.detail-row:first-child .value')?.textContent;
        if (!dateDebutStr) return;
        
        const dateDebut = parseDate(dateDebutStr);
        
        // Si la réservation est dans moins de 24h
        if (dateDebut && dateDebut <= tomorrow && dateDebut > now) {
            addReminderBadge(card, dateDebut);
        }
    });
}

function addReminderBadge(card, date) {
    const header = card.querySelector('.reservation-header');
    if (!header || header.querySelector('.reminder-badge')) return;
    
    const badge = document.createElement('span');
    badge.className = 'reminder-badge';
    badge.innerHTML = '🔔 Bientôt';
    badge.title = 'Cette réservation commence bientôt';
    
    header.appendChild(badge);
}

// ============================================
// UTILITAIRES
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

function parseDate(dateString) {
    // Format attendu: "dd/mm/yyyy hh:mm"
    const parts = dateString.match(/(\d{2})\/(\d{2})\/(\d{4})\s+(\d{2}):(\d{2})/);
    if (!parts) return null;
    
    return new Date(parts[3], parts[2] - 1, parts[1], parts[4], parts[5]);
}

function calculateDuration(dateDebut, dateFin) {
    const debut = new Date(dateDebut);
    const fin = new Date(dateFin);
    const diffMs = fin - debut;
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
    const diffMinutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
    
    return `${diffHours}h${diffMinutes > 0 ? diffMinutes + 'min' : ''}`;
}

// ============================================
// EXPORT DE DONNÉES
// ============================================

function exportReservations(format = 'csv') {
    const reservations = [];
    
    document.querySelectorAll('.reservation-item, .reservation-card').forEach(item => {
        const titre = item.querySelector('h3, h4')?.textContent;
        const dates = item.querySelectorAll('.detail-row .value, .item-details span');
        
        if (titre && dates.length >= 2) {
            reservations.push({
                equipement: titre,
                debut: dates[0].textContent,
                fin: dates[1].textContent
            });
        }
    });
    
    if (format === 'csv') {
        exportToCSV(reservations);
    } else if (format === 'json') {
        exportToJSON(reservations);
    }
}

function exportToCSV(data) {
    const headers = ['Équipement', 'Date début', 'Date fin'];
    const rows = data.map(r => [r.equipement, r.debut, r.fin]);
    
    let csv = headers.join(',') + '\n';
    rows.forEach(row => {
        csv += row.map(cell => `"${cell}"`).join(',') + '\n';
    });
    
    downloadFile('reservations.csv', csv, 'text/csv');
}

function exportToJSON(data) {
    const json = JSON.stringify(data, null, 2);
    downloadFile('reservations.json', json, 'application/json');
}

function downloadFile(filename, content, type) {
    const blob = new Blob([content], { type });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

// ============================================
// INITIALISATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('Gestionnaire de réservations initialisé');
    
    // Ajouter la barre de recherche dans l'historique
    addHistorySearch();
    
    // Charger les statistiques globales
    loadGlobalStats();
    
    // Vérifier les réservations à venir
    checkUpcomingReservations();
    
    // Rafraîchir les rappels toutes les 5 minutes
    setInterval(checkUpcomingReservations, 5 * 60 * 1000);
});

// ============================================
// STYLES CSS ADDITIONNELS (à ajouter dans reservations.php)
// ============================================

const additionalStyles = `
<style>
.equipement-stats {
    margin: 20px 0;
    padding: 16px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.stats-header h4 {
    margin: 0 0 12px 0;
    font-size: 14px;
    font-weight: 600;
    color: var(--gray-900);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 16px;
}

.stat-item {
    text-align: center;
    padding: 12px;
    background: white;
    border-radius: 6px;
}

.stat-label {
    font-size: 11px;
    color: var(--gray-600);
    margin-bottom: 4px;
}

.stat-value {
    font-size: 18px;
    font-weight: 700;
    color: var(--primary);
}

.recent-reservations {
    padding-top: 12px;
    border-top: 1px solid #e2e8f0;
}

.recent-reservations h5 {
    margin: 0 0 8px 0;
    font-size: 13px;
    font-weight: 600;
}

.recent-reservations ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.recent-reservations li {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
    font-size: 12px;
    color: var(--gray-700);
}

.reservation-alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #6ee7b7;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

.alert-content {
    display: flex;
    align-items: center;
    gap: 8px;
}

.alert-close {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: inherit;
    opacity: 0.6;
}

.alert-close:hover {
    opacity: 1;
}

.stats-widget {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin: 20px 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.widget-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.widget-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
}

.widget-toggle {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--gray-500);
}

.stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 20px;
}

.stat-card {
    text-align: center;
    padding: 16px;
    background: var(--gray-50);
    border-radius: 8px;
}

.stat-number {
    font-size: 32px;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 8px;
}

.stat-label {
    font-size: 13px;
    color: var(--gray-600);
}

.favorite-equipments {
    padding-top: 16px;
    border-top: 1px solid var(--border-color);
}

.favorite-equipments h4 {
    margin: 0 0 12px 0;
    font-size: 14px;
    font-weight: 600;
}

.favorite-equipments ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.favorite-equipments li {
    display: flex;
    justify-content: space-between;
    padding: 8px 12px;
    background: var(--gray-50);
    border-radius: 6px;
    margin-bottom: 8px;
}

.history-search {
    margin-bottom: 16px;
}

.search-input {
    width: 100%;
    padding: 10px 16px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    font-size: 14px;
}

.reminder-badge {
    display: inline-block;
    padding: 4px 8px;
    background: #fef3c7;
    color: #92400e;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    margin-left: 8px;
}

@media (max-width: 768px) {
    .stats-grid,
    .stats-row {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
`;

console.log('Module de réservations chargé avec succès');