<?php
/**
 * Page détail d'un membre (visiteur)
 * À placer dans : views/visitor/membre-detail.php
 */

require_once __DIR__ . '/../../../lib/helpers.php';
require_once __DIR__ . '/../../../lib/ViewComponents.php';

ViewComponents::renderHeader([
    'title' => $membre['username'] . ' - Laboratoire TDW',
    'role' => 'visiteur',
    'showLogout' => false,
    'showLoginButton' => true
]);

// Initiales pour l'avatar
$initiales = strtoupper(substr($membre['username'] ?? 'U', 0, 2));

// Couleurs par poste
$posteColors = [
    'enseignant' => '#3B82F6',
    'doctorant' => '#8B5CF6',
    'etudiant' => '#10B981',
    'invite' => '#F59E0B'
];
$badgeColor = $posteColors[$membre['poste'] ?? 'enseignant'] ?? '#6B7280';
?>

<div class="visitor-container">
    <div class="container detail-container">
        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="<?= base_url() ?>">Accueil</a>
            <span>›</span>
            <a href="<?= base_url('membres') ?>">Membres</a>
            <span>›</span>
            <span><?= e($membre['username']) ?></span>
        </nav>

        <!-- Header du profil -->
        <div class="membre-profile-header">
            <div class="profile-avatar-large">
                <?php if (!empty($membre['photo'])): ?>
                    <img src="<?= base_url('uploads/' . $membre['photo']) ?>" 
                         alt="<?= e($membre['username']) ?>">
                <?php else: ?>
                    <div class="avatar-placeholder-large">
                        <?= $initiales ?>
                    </div>
                <?php endif; ?>
                
                <span class="profile-badge" style="background: <?= $badgeColor ?>;">
                    <?= e(ucfirst($membre['poste'])) ?>
                </span>
            </div>
            
            <div class="profile-info">
                <h1><?= e($membre['username']) ?></h1>
                
                <?php if (!empty($membre['grade'])): ?>
                <div class="profile-grade"><?= e($membre['grade']) ?></div>
                <?php endif; ?>
                
                <?php if (!empty($membre['equipe_nom'])): ?>
                <div class="profile-equipe">
                    Membre de l'équipe <strong><?= e($membre['equipe_nom']) ?></strong>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($membre['email'])): ?>
                <div class="profile-contact">
                    <a href="mailto:<?= e($membre['email']) ?>" class="contact-email">
                        <?= e($membre['email']) ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="profile-stats">
                <div class="stat-box">
                    <div class="stat-number"><?= count($projets) ?></div>
                    <div class="stat-label">Projets</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?= count($publications) ?></div>
                    <div class="stat-label">Publications</div>
                </div>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="detail-layout">
            <!-- Colonne principale -->
            <div class="main-content">
                <!-- Biographie -->
                <?php if (!empty($membre['biographie'])): ?>
                <section class="detail-card">
                    <h2>Biographie</h2>
                    <div class="bio-content">
                        <?= nl2br(e($membre['biographie'])) ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Projets -->
                <?php if (!empty($projets)): ?>
                <section class="detail-card">
                    <h2>Projets (<?= count($projets) ?>)</h2>
                    <div class="items-list">
                        <?php foreach ($projets as $projet): ?>
                        <div class="item-card">
                            <div class="item-header">
                                <h3>
                                    <a href="<?= base_url('projets/' . $projet['id']) ?>">
                                        <?= e($projet['titre']) ?>
                                    </a>
                                </h3>
                                <span class="badge" style="background: var(--primary);">
                                    <?= e($projet['thematique']) ?>
                                </span>
                            </div>
                            
                            <?php if (!empty($projet['description'])): ?>
                            <p class="item-description">
                                <?= truncate($projet['description'], 150) ?>
                            </p>
                            <?php endif; ?>
                            
                            <div class="item-meta">
                                <span class="status-badge status-<?= e($projet['status']) ?>">
                                    <?= e(ucfirst(str_replace('_', ' ', $projet['status']))) ?>
                                </span>
                                <?php if (!empty($projet['role_projet'])): ?>
                                <span class="role-badge">
                                    <?= e($projet['role_projet']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Publications -->
                <?php if (!empty($publications)): ?>
                <section class="detail-card">
                    <h2>Publications (<?= count($publications) ?>)</h2>
                    <div class="items-list">
                        <?php foreach ($publications as $pub): ?>
                        <div class="item-card publication-item">
                            <div class="item-header">
                                <span class="pub-type-badge" style="background: <?php
                                    $typeColors = [
                                        'Article' => '#3B82F6',
                                        'Conference' => '#8B5CF6',
                                        'These' => '#EC4899',
                                        'Rapport' => '#F59E0B',
                                        'Livre' => '#10B981',
                                        'Chapitre' => '#6366F1'
                                    ];
                                    echo $typeColors[$pub['type_publication']] ?? '#6B7280';
                                ?>;">
                                    <?= e($pub['type_publication']) ?>
                                </span>
                                <span class="pub-year">
                                    <?= date('Y', strtotime($pub['date_publication'])) ?>
                                </span>
                            </div>
                            
                            <h3>
                                <a href="<?= base_url('publications/' . $pub['id']) ?>">
                                    <?= e($pub['titre']) ?>
                                </a>
                            </h3>
                            
                            <?php if (!empty($pub['resume'])): ?>
                            <p class="item-description">
                                <?= truncate($pub['resume'], 120) ?>
                            </p>
                            <?php endif; ?>
                            
                            <div class="item-meta">
                                <?php if (!empty($pub['domaine'])): ?>
                                <span class="meta-badge">
                                    <?= e($pub['domaine']) ?>
                                </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($pub['doi'])): ?>
                                <a href="https://doi.org/<?= e($pub['doi']) ?>" 
                                   target="_blank" 
                                   class="doi-link">
                                    DOI
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Message si aucune activité -->
                <?php if (empty($projets) && empty($publications)): ?>
                <section class="detail-card">
                    <div class="empty-state">
                        <p>Aucune activité publique disponible pour ce membre.</p>
                    </div>
                </section>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <aside class="sidebar-content">
                <!-- Informations -->
                <section class="detail-card">
                    <h2>Informations</h2>
                    <div class="info-list">
                        <div class="info-item">
                            <strong>Poste</strong>
                            <span><?= e(ucfirst($membre['poste'])) ?></span>
                        </div>
                        
                        <?php if (!empty($membre['grade'])): ?>
                        <div class="info-item">
                            <strong>Grade</strong>
                            <span><?= e($membre['grade']) ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($membre['equipe_nom'])): ?>
                        <div class="info-item">
                            <strong>Équipe</strong>
                            <span><?= e($membre['equipe_nom']) ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($membre['date_adhesion'])): ?>
                        <div class="info-item">
                            <strong>Membre depuis</strong>
                            <span><?= format_date($membre['date_adhesion'], 'F Y') ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Contact -->
                <?php if (!empty($membre['email'])): ?>
                <a href="mailto:<?= e($membre['email']) ?>" 
                   class="btn-primary btn-block">
                    Envoyer un email
                </a>
                <?php endif; ?>

                <!-- Retour -->
                <a href="<?= base_url('membres') ?>" class="btn-secondary btn-block">
                    Retour aux membres
                </a>
            </aside>
        </div>
    </div>
</div>

<style>
.detail-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 24px;
    font-size: 14px;
    color: var(--gray-600);
}

.breadcrumb a {
    color: var(--primary);
    text-decoration: none;
    transition: var(--transition);
}

.breadcrumb a:hover {
    color: var(--primary-dark);
}

.membre-profile-header {
    background: white;
    padding: 40px;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
    margin-bottom: 30px;
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 30px;
    align-items: center;
}

.profile-avatar-large {
    position: relative;
    width: 160px;
    height: 160px;
}

.profile-avatar-large img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 5px solid var(--border-color);
}

.avatar-placeholder-large {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    font-weight: 700;
    border: 5px solid var(--border-color);
}

.profile-badge {
    position: absolute;
    bottom: 5px;
    right: 5px;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    color: white;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: 4px solid white;
}

.profile-info h1 {
    font-size: 32px;
    font-weight: 700;
    margin: 0 0 8px 0;
    color: var(--gray-900);
}

.profile-grade {
    font-size: 18px;
    font-weight: 500;
    color: var(--primary);
    margin-bottom: 8px;
}

.profile-equipe {
    font-size: 15px;
    color: var(--gray-600);
    margin-bottom: 12px;
}

.profile-contact {
    margin-top: 12px;
}

.contact-email {
    color: var(--primary);
    text-decoration: none;
    font-size: 15px;
    transition: var(--transition);
}

.contact-email:hover {
    color: var(--primary-dark);
    text-decoration: underline;
}

.profile-stats {
    display: flex;
    gap: 20px;
}

.stat-box {
    text-align: center;
    padding: 20px;
    background: var(--gray-50);
    border-radius: 12px;
    min-width: 100px;
}

.stat-number {
    font-size: 32px;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 4px;
}

.stat-label {
    font-size: 13px;
    color: var(--gray-600);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detail-layout {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 30px;
}

.detail-card {
    background: white;
    padding: 30px;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-sm);
    margin-bottom: 24px;
}

.detail-card h2 {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 20px;
    color: var(--gray-900);
    border-bottom: 2px solid var(--border-color);
    padding-bottom: 12px;
}

.bio-content {
    line-height: 1.8;
    color: var(--gray-700);
    font-size: 16px;
}

.items-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.item-card {
    padding: 20px;
    background: var(--gray-50);
    border-radius: 12px;
    border: 1px solid var(--border-color);
    transition: var(--transition);
}

.item-card:hover {
    background: white;
    box-shadow: var(--shadow-sm);
    border-color: var(--primary);
}

.item-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 12px;
}

.item-card h3 {
    font-size: 16px;
    font-weight: 600;
    margin: 0;
    flex: 1;
}

.item-card h3 a {
    color: var(--gray-900);
    text-decoration: none;
    transition: var(--transition);
}

.item-card h3 a:hover {
    color: var(--primary);
}

.item-description {
    color: var(--gray-600);
    font-size: 14px;
    line-height: 1.6;
    margin: 12px 0;
}

.item-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    margin-top: 12px;
}

.badge, .meta-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    color: white;
}

.meta-badge {
    background: var(--gray-600);
}

.status-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-en_cours {
    background: #3B82F6;
    color: white;
}

.status-termine {
    background: #10B981;
    color: white;
}

.status-soumis {
    background: #F59E0B;
    color: white;
}

.role-badge {
    padding: 4px 12px;
    background: var(--primary);
    color: white;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.pub-type-badge {
    padding: 6px 14px;
    border-radius: 16px;
    font-size: 11px;
    font-weight: 600;
    color: white;
    text-transform: uppercase;
}

.pub-year {
    font-size: 14px;
    font-weight: 600;
    color: var(--gray-600);
}

.doi-link {
    color: var(--primary);
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
}

.doi-link:hover {
    text-decoration: underline;
}

.info-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.info-item strong {
    color: var(--gray-600);
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-item span {
    color: var(--gray-900);
    font-size: 15px;
}

.btn-block {
    display: block;
    width: 100%;
    text-align: center;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 12px;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--gray-600);
}

/* Responsive */
@media (max-width: 1024px) {
    .detail-layout {
        grid-template-columns: 1fr;
    }
    
    .sidebar-content {
        order: -1;
    }
    
    .membre-profile-header {
        grid-template-columns: 1fr;
        text-align: center;
        justify-items: center;
    }
    
    .profile-stats {
        justify-content: center;
    }
}

@media (max-width: 768px) {
    .membre-profile-header {
        padding: 24px 20px;
    }
    
    .profile-info h1 {
        font-size: 24px;
    }
    
    .detail-card {
        padding: 20px;
    }
}
</style>

<?php ViewComponents::renderFooter(['role' => 'visiteur']); ?>