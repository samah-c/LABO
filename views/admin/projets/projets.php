<?php
/**
 * Vue générique pour Projets (admin) - FIXED with inline JS
 * À placer dans : /TDW_project/views/admin/projets/projets.php
 */

require_once __DIR__ . '/../../../lib/helpers.php';
require_once __DIR__ . '/../../../lib/ViewComponents.php';

ViewComponents::renderHeader([
    'title' => 'Gestion des Projets',
    'username' => session('username'),
    'role' => 'admin',
    'additionalJs' => [
        base_url('assets/js/crud-handler.js'),
        base_url('assets/js/table-enhancements.js'),
        base_url('assets/js/ui.js')
    ]
]);
?>

<div class="container">
    <?php ViewComponents::renderBreadcrumbs([
        ['label' => 'Dashboard', 'url' => base_url('admin/dashboard')],
        ['label' => 'Projets']
    ]); ?>
    
    <div class="page-header">
        <h1>Projets de Recherche</h1>
        <div class="page-actions">
            <button class="btn-primary" onclick="projets.openAddModal()">
                Nouveau projet
            </button>
            <button class="btn-secondary" onclick="projets.export()">
                 Exporter
            </button>
        </div>
    </div>
    
    <?php ViewComponents::renderFilters([
        'showSearch' => true,
        'searchPlaceholder' => 'Rechercher un projet...',
        'filters' => [
            [
                'name' => 'thematique',
                'label' => 'Thématique',
                'options' => [
                    'IA' => 'Intelligence Artificielle',
                    'Securite' => 'Sécurité Informatique',
                    'Cloud' => 'Cloud Computing',
                    'Reseaux' => 'Réseaux',
                    'Systemes' => 'Systèmes Embarqués',
                    'Big Data' => 'Big Data',
                    'IoT' => 'Internet des Objets'
                ]
            ],
            [
                'name' => 'status',
                'label' => 'Statut',
                'options' => [
                    'en_cours' => 'En cours',
                    'termine' => 'Terminé',
                    'soumis' => 'Soumis',
                    'approuvé' => 'Approuvé',
                    'rejeté' => 'Rejeté'
                ]
            ],
            [
                'name' => 'annee',
                'label' => 'Année',
                'options' => array_combine(
                    range(date('Y'), 2020),
                    range(date('Y'), 2020)
                )
            ]
        ]
    ]); ?>
    
    <?php ViewComponents::renderTable([
        'data' => $projets ?? [],
        'columns' => [
            [
                'key' => 'titre',
                'label' => 'Titre du projet',
                'formatter' => function($value) {
                    return '<strong>' . e($value) . '</strong>';
                }
            ],
            [
                'key' => 'thematique',
                'label' => 'Thématique',
                'formatter' => function($value) {
                    return  e($value);
                }
            ],
            [
                'key' => 'responsable_nom',
                'label' => 'Responsable',
                'formatter' => function($value) {
                    return $value ? e($value) : '<em style="color: #9CA3AF;">Non assigné</em>';
                }
            ],
            [
                'key' => 'status',
                'label' => 'Statut',
                'formatter' => function($value) {
                    return LabHelpers::getProjetStatusBadge($value);
                }
            ],
            [
                'key' => 'date_debut',
                'label' => 'Période',
                'formatter' => function($value, $row) {
                    $debut = format_date($value, 'm/Y');
                    $fin = !empty($row['date_fin']) ? format_date($row['date_fin'], 'm/Y') : '...';
                    return "$debut - $fin";
                }
            ],
            [
                'key' => 'progression',
                'label' => 'Progression',
                'formatter' => function($value, $row) {
                    $progress = LabHelpers::calculateProjectProgress(
                        $row['date_debut'], 
                        $row['date_fin'] ?? null
                    );
                    return "<div class='progress' style='min-width: 100px;'>
                                <div class='progress-bar' style='width: {$progress}%'>
                                    {$progress}%
                                </div>
                            </div>";
                }
            ]
        ],
        'actions' => [
            function($row) {
                return '<button class="btn-action btn-view" 
                                onclick="projets.view(' . $row['id'] . ')" 
                                title="Voir détails">
                             Voir
                        </button>';
            },
            function($row) {
                return '<button class="btn-action btn-edit" 
                                onclick="projets.edit(' . $row['id'] . ')"
                                title="Modifier">
                            ✏️
                        </button>';
            },
            function($row) {
                return '<button class="btn-action btn-delete" 
                                onclick="projets.delete(' . $row['id'] . ')"
                                title="Supprimer">
                            🗑️
                        </button>';
            }
        ],
        'emptyMessage' => 'Aucun projet trouvé'
    ]); ?>
    
    <?php 
    if (isset($pagination)) {
        echo Utils::renderPagination($pagination, base_url('admin/projets/projets'));
    }
    ?>
</div>

<!-- Modale -->
<?php ViewComponents::renderModal([
    'id' => 'projet-modal',
    'title' => 'Ajouter un projet',
    'content' => '<div id="modal-form-container"></div>'
]); ?>

<style>
.progress {
    width: 100%;
    height: 24px;
    background: #E5E7EB;
    border-radius: 12px;
    overflow: hidden;
    position: relative;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #5B7FFF, #667eea);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 600;
    transition: width 0.3s ease;
}
</style>

<!-- INLINE PROJETS HANDLER -->
<script>
/**
 * Projets Handler - Inline version
 */
class ProjetsHandler {
    constructor() {
        this.baseUrl = '<?= base_url("admin/projets/projets") ?>';
        this.apiUrl = '<?= base_url("api/admin/projets") ?>';
        this.currentProjetId = null;
        this.init();
    }
    
    init() {
        this.attachEventListeners();
        console.log(' Gestionnaire de projets initialisé');
    }
    
    attachEventListeners() {
        const modal = document.getElementById('projet-modal');
        if (modal) {
            modal.onclick = (e) => {
                if (e.target === modal) this.closeModal();
            };
        }
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.closeModal();
        });
    }
    
    // ========================================
    // CRUD OPERATIONS
    // ========================================
    
    view(id) {
        window.location.href = `${this.baseUrl}/view/${id}`;
    }
    
    openAddModal() {
        this.loadForm(null);
    }
    
    edit(id) {
        this.loadForm(id);
    }
    
    async delete(id) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce projet ?')) {
            return;
        }
        
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
                this.showNotification(data.message || 'Projet supprimé', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                this.showNotification(data.message || 'Erreur', 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.showNotification('Erreur de connexion', 'error');
        }
    }
    
    async loadForm(id = null) {
        const modal = document.getElementById('projet-modal');
        const container = document.getElementById('modal-form-container');
        
        if (!modal || !container) return;
        
        const modalTitle = modal.querySelector('.modal-header h2');
        if (modalTitle) {
            modalTitle.textContent = id ? 'Modifier le projet' : 'Ajouter un projet';
        }
        
        container.innerHTML = '<div style="text-align: center; padding: 40px;">Chargement...</div>';
        modal.style.display = 'flex';
        
        try {
            const url = id ? `${this.baseUrl}/form/${id}` : `${this.baseUrl}/form`;
            
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            if (!response.ok) throw new Error('Erreur de chargement');
            
            const html = await response.text();
            container.innerHTML = html;
            this.currentProjetId = id;
            
        } catch (error) {
            console.error('Erreur:', error);
            container.innerHTML = '<p style="color: red;">Erreur lors du chargement</p>';
        }
    }
    
    closeModal() {
        const modal = document.getElementById('projet-modal');
        if (modal) {
            modal.style.display = 'none';
            const container = document.getElementById('modal-form-container');
            if (container) container.innerHTML = '';
            this.currentProjetId = null;
        }
    }
    
    // ========================================
    // MEMBRE MANAGEMENT
    // ========================================
    
    async openAddMembreModal(projetId) {
        this.currentProjetId = projetId;
        
        try {
            const response = await fetch(`${this.apiUrl}/${projetId}/membres-disponibles`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showMembreSelectionModal(data.membres);
            } else {
                this.showNotification('Erreur lors du chargement', 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.showNotification('Erreur de connexion', 'error');
        }
    }
    
    showMembreSelectionModal(membres) {
        const modal = document.getElementById('projet-modal');
        const container = document.getElementById('modal-form-container');
        
        if (!modal || !container) return;
        
        const modalTitle = modal.querySelector('.modal-header h2');
        if (modalTitle) modalTitle.textContent = 'Ajouter un membre';
        
        let html = `
            <form id="add-membre-form" onsubmit="projets.addMembre(event)">
                <div class="form-group">
                    <label for="membre-select">Sélectionner un membre *</label>
                    <select id="membre-select" name="membre_id" required>
                        <option value="">-- Choisir un membre --</option>
        `;
        
        if (membres.length === 0) {
            html += `<option value="" disabled>Aucun membre disponible</option>`;
        } else {
            membres.forEach(membre => {
                html += `
                    <option value="${membre.id}">
                        ${this.escapeHtml(membre.username)}
                        ${membre.grade ? ' - ' + this.escapeHtml(membre.grade) : ''}
                    </option>
                `;
            });
        }
        
        html += `
                    </select>
                </div>
                <div class="form-group">
                    <label for="role-select">Rôle dans le projet</label>
                    <select id="role-select" name="role">
                        <option value="Participant">Participant</option>
                        <option value="Co-responsable">Co-responsable</option>
                        <option value="Chercheur">Chercheur</option>
                        <option value="Doctorant">Doctorant</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="projets.closeModal()">
                        Annuler
                    </button>
                    <button type="submit" class="btn-primary" ${membres.length === 0 ? 'disabled' : ''}>
                        Ajouter
                    </button>
                </div>
            </form>
        `;
        
        container.innerHTML = html;
        modal.style.display = 'flex';
    }
    
    async addMembre(event) {
        event.preventDefault();
        
        const membreId = document.getElementById('membre-select').value;
        const role = document.getElementById('role-select').value;
        
        if (!membreId) {
            this.showNotification('Veuillez sélectionner un membre', 'warning');
            return;
        }
        
        try {
            const response = await fetch(`${this.apiUrl}/add-membre`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    projet_id: this.currentProjetId,
                    membre_id: membreId,
                    role: role
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('Membre ajouté', 'success');
                this.closeModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                this.showNotification(data.message || 'Erreur', 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.showNotification('Erreur de connexion', 'error');
        }
    }
    
    async removeMembre(projetId, membreId, membreName) {
        if (!confirm(`Retirer ${membreName} de ce projet ?`)) return;
        
        try {
            const response = await fetch(`${this.apiUrl}/remove-membre`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    projet_id: projetId,
                    membre_id: membreId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('Membre retiré', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                this.showNotification(data.message || 'Erreur', 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.showNotification('Erreur de connexion', 'error');
        }
    }
    
    // ========================================
    // EXPORT & REPORT
    // ========================================
    
    export() {
        const params = new URLSearchParams(window.location.search);
        params.set('export', 'csv');
        window.location.href = `${this.baseUrl}?${params.toString()}`;
    }
    
    genererRapport(id) {
        window.open(`${this.baseUrl}/rapport/${id}`, '_blank');
    }
    
    // ========================================
    // UTILITIES
    // ========================================
    
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
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
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize
let projets;
document.addEventListener('DOMContentLoaded', () => {
    projets = new ProjetsHandler();
});
</script>

<style>
@keyframes slideIn {
    from { transform: translateX(400px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@keyframes slideOut {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(400px); opacity: 0; }
}
</style>

<?php ViewComponents::renderFooter(['role' => 'admin']); ?>