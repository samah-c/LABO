<?php
/**
 * Vue détaillée d'un utilisateur
 * À placer dans : /TDW_project/views/admin/users/view.php
 */

require_once __DIR__ . '/../../../lib/helpers.php';
require_once __DIR__ . '/../../../lib/ViewComponents.php';

ViewComponents::renderHeader([
    'title' => 'Détails de l\'utilisateur',
    'username' => session('username'),
    'role' => 'admin',
    'additionalJs' => [
        base_url('assets/js/ui.js'),
        base_url('assets/js/admin/users-handler.js')
    ]
]);
?>

<div class="container">
    <?php ViewComponents::renderBreadcrumbs([
        ['label' => 'Dashboard', 'url' => base_url('admin/dashboard')],
        ['label' => 'Utilisateurs', 'url' => base_url('admin/users/users')],
        ['label' => e($user['username'] ?? 'Détails')]
    ]); ?>
    
    <!-- En-tête utilisateur -->
    <div class="page-header">
        <div>
            <h1>👤 <?= e($user['username']) ?></h1>
            <p style="color: #6B7280; margin-top: 8px;">
                <?= e($user['email']) ?> • 
                <?php 
                $badges = [
                    'admin' => ' Administrateur',
                    'membre' => ' Membre',
                    'visiteur' => ' Visiteur'
                ];
                echo $badges[$user['role']] ?? e($user['role']);
                ?>
            </p>
        </div>
        <div class="page-actions">
            <button class="btn-secondary" onclick="editItem(<?= $user['id'] ?>)">
                 Modifier
            </button>
            <?php if ($user['id'] != session('user_id')): ?>
            <button class="btn-secondary" onclick="deleteItem(<?= $user['id'] ?>)">
                Supprimer
            </button>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="grid-2-cols">
        <!-- Informations générales -->
        <div class="card">
            <div class="card-header">
                <h2> Informations générales</h2>
            </div>
            <div class="card-body">
                <div class="info-list">
                    <div class="info-item">
                        <span class="info-label">Nom d'utilisateur</span>
                        <span class="info-value"><?= e($user['username']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?= e($user['email']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Rôle</span>
                        <span class="info-value">
                            <?php
                            $roleLabels = [
                                'admin' => ' Administrateur',
                                'membre' => ' Membre',
                                'visiteur' => ' Visiteur'
                            ];
                            echo $roleLabels[$user['role']] ?? e($user['role']);
                            ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Statut</span>
                        <span class="info-value">
                            <?php
                            $statut = $user['statut'] ?? 'actif';
                            $statusBadges = [
                                'actif' => '<span class="badge badge-success">✓ Actif</span>',
                                'suspendu' => '<span class="badge badge-warning">⚠ Suspendu</span>',
                                'inactif' => '<span class="badge badge-secondary">○ Inactif</span>'
                            ];
                            echo $statusBadges[$statut] ?? e($statut);
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistiques -->
        <div class="card">
            <div class="card-header">
                <h2> Statistiques</h2>
            </div>
            <div class="card-body">
                <div class="stats-list">
                    <div class="stat-item">
                        <span class="stat-label">Publications</span>
                        <span class="stat-value"><?= $stats['publications'] ?? 0 ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Projets</span>
                        <span class="stat-value"><?= $stats['projets'] ?? 0 ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Dernière connexion</span>
                        <span class="stat-value" style="font-size: 14px;">
                            <?= $user['derniere_connexion'] ? time_ago($user['derniere_connexion']) : 'Jamais' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Informations membre -->
    <?php if ($membre): ?>
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h2> Informations du membre</h2>
        </div>
        <div class="card-body">
            <div class="info-list">
                <?php if (!empty($membre['grade'])): ?>
                <div class="info-item">
                    <span class="info-label">Grade</span>
                    <span class="info-value"><?= LabHelpers::getGradeBadge($membre['grade']) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($membre['specialite'])): ?>
                <div class="info-item">
                    <span class="info-label">Spécialité</span>
                    <span class="info-value"><?= e($membre['specialite']) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($membre['equipe_id'])): ?>
                <div class="info-item">
                    <span class="info-label">Équipe</span>
                    <span class="info-value">
                        <a href="<?= base_url('admin/equipes/equipes/view/' . $membre['equipe_id']) ?>">
                            Voir l'équipe
                        </a>
                    </span>
                </div>
                <?php endif; ?>
                
                <div class="info-item">
                    <span class="info-label">Date d'adhésion</span>
                    <span class="info-value"><?= format_date($membre['date_adhesion']) ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Actions rapides -->
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h2> Actions rapides</h2>
        </div>
        <div class="card-body">
            <div class="actions-grid">
                <?php if ($user['id'] != session('user_id')): ?>
                <div class="action-card">
                    <h3>Changer le rôle</h3>
                    <select id="role-select" class="form-control">
                        <option value="">-- Sélectionner --</option>
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Administrateur</option>
                        <option value="membre" <?= $user['role'] === 'membre' ? 'selected' : '' ?>>Membre</option>
                        <option value="visiteur" <?= $user['role'] === 'visiteur' ? 'selected' : '' ?>>Visiteur</option>
                    </select>
                    <button class="btn-primary btn-sm" onclick="changeRole()">Appliquer</button>
                </div>
                
                <div class="action-card">
                    <h3>Changer le statut</h3>
                    <select id="status-select" class="form-control">
                        <option value="">-- Sélectionner --</option>
                        <option value="actif" <?= ($user['statut'] ?? 'actif') === 'actif' ? 'selected' : '' ?>>Actif</option>
                        <option value="suspendu" <?= ($user['statut'] ?? '') === 'suspendu' ? 'selected' : '' ?>>Suspendu</option>
                        <option value="inactif" <?= ($user['statut'] ?? '') === 'inactif' ? 'selected' : '' ?>>Inactif</option>
                    </select>
                    <button class="btn-primary btn-sm" onclick="changeStatus()">Appliquer</button>
                </div>
                <?php else: ?>
                <div class="action-card">
                    <p style="color: #9CA3AF;">
                        ℹ Vous ne pouvez pas modifier votre propre rôle ou statut
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modale -->
<?php ViewComponents::renderModal([
    'id' => 'user-modal',
    'title' => 'Modifier l\'utilisateur',
    'content' => '<div id="modal-form-container"></div>'
]); ?>

<script>
function changeRole() {
    const select = document.getElementById('role-select');
    const newRole = select.value;
    
    if (!newRole) {
        alert('Veuillez sélectionner un rôle');
        return;
    }
    
    if (users) {
        users.changeRole(<?= $user['id'] ?>, newRole);
    }
}

function changeStatus() {
    const select = document.getElementById('status-select');
    const newStatus = select.value;
    
    if (!newStatus) {
        alert('Veuillez sélectionner un statut');
        return;
    }
    
    if (users) {
        users.changeStatus(<?= $user['id'] ?>, newStatus);
    }
}
</script>

<style>
.grid-2-cols {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 24px;
    margin-bottom: 24px;
}

.card {
    background: white;
    border-radius: 12px;
    border: 1px solid #E5E7EB;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.card-header {
    padding: 20px 24px;
    border-bottom: 1px solid #E5E7EB;
}

.card-header h2 {
    font-size: 18px;
    font-weight: 600;
    color: #111827;
    margin: 0;
}

.card-body {
    padding: 24px;
}

.info-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #F9FAFB;
    border-radius: 8px;
}

.info-label {
    color: #6B7280;
    font-weight: 500;
}

.info-value {
    font-weight: 600;
    color: #111827;
}

.stats-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #F9FAFB;
    border-radius: 8px;
}

.stat-label {
    color: #6B7280;
    font-weight: 500;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #5B7FFF;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
}

.action-card {
    padding: 16px;
    background: #F9FAFB;
    border-radius: 8px;
    border: 1px solid #E5E7EB;
}

.action-card h3 {
    font-size: 14px;
    font-weight: 600;
    color: #111827;
    margin: 0 0 12px 0;
}

.action-card .form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #D1D5DB;
    border-radius: 6px;
    margin-bottom: 12px;
}

.btn-sm {
    padding: 6px 14px;
    font-size: 13px;
}
</style>

<?php ViewComponents::renderFooter(); ?>