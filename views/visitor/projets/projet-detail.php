<?php
/**
 * Page de détails d'un projet (vue publique)
 * À créer dans : views/visitor/projet-detail.php
 */

ViewComponents::renderHeader([
    'title' => e($projet['titre']) . ' - Laboratoire TDW',
    'role' => 'visiteur',
    'showLogout' => false,
    'showLoginButton' => true
]);

// LE MENU HORIZONTAL EST MAINTENANT AUTOMATIQUEMENT INCLUS !
// Plus besoin du bloc <nav class="horizontal-nav"> ci-dessous
?>

<div class="visitor-container">
    <!-- En-tête du projet -->
    <section class="project-hero">
        <div class="hero-content">
            <div class="breadcrumbs">
                <a href="<?= base_url('/') ?>">Accueil</a>
                <span class="separator">›</span>
                <a href="<?= base_url('projets') ?>">Projets</a>
                <span class="separator">›</span>
                <span class="current"><?= e($projet['titre']) ?></span>
            </div>
            
            <div class="hero-header mt-lg">
                <h1 class="text-white"><?= e($projet['titre']) ?></h1>
                <div class="project-meta d-flex items-center gap-md mt-md flex-wrap">
                    <span class="badge badge-primary"><?= e($projet['thematique']) ?></span>
                    <?= LabHelpers::getProjetStatusBadge($projet['status']) ?>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <div class="project-layout">
            <!-- Contenu principal -->
            <div class="main-content">
                <!-- Description -->
                <div class="content-card">
                    <h2 class="section-title"> Description du projet</h2>
                    <p class="project-description text-gray">
                        <?= nl2br(e($projet['description'])) ?>
                    </p>
                </div>

                <!-- Équipe -->
                <div class="content-card mt-lg">
                    <div class="d-flex justify-between items-center mb-lg">
                        <h2 class="section-title" style="margin-bottom: 0;">👥 Équipe du projet</h2>
                        <span class="badge badge-gray">
                            <?= count($membres) ?> membre<?= count($membres) > 1 ? 's' : '' ?>
                        </span>
                    </div>
                    
                    <?php if (!empty($membres)): ?>
                        <div class="members-grid">
                            <?php foreach ($membres as $membre): ?>
                                <div class="member-card">
                                    <div class="member-avatar">
                                        <?= strtoupper(substr($membre['username'], 0, 2)) ?>
                                    </div>
                                    <div class="member-info">
                                        <h4><?= e($membre['username']) ?></h4>
                                        <?php if (!empty($membre['grade'])): ?>
                                            <p class="text-sm text-muted"><?= e($membre['grade']) ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($membre['role_projet'])): ?>
                                            <span class="badge badge-primary text-xs">
                                                <?= e($membre['role_projet']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="empty-message">Aucun membre n'est actuellement assigné à ce projet.</p>
                    <?php endif; ?>
                </div>

                <!-- Publications -->
                <div class="content-card mt-lg">
                    <div class="d-flex justify-between items-center mb-lg">
                        <h2 class="section-title" style="margin-bottom: 0;">Publications liées</h2>
                        <span class="badge badge-gray">
                            <?= count($publications) ?> publication<?= count($publications) > 1 ? 's' : '' ?>
                        </span>
                    </div>
                    
                    <?php if (!empty($publications)): ?>
                        <div class="publications-list">
                            <?php foreach ($publications as $pub): ?>
                                <div class="publication-card">
                                    <div class="mb-sm">
                                        <?= LabHelpers::getPublicationTypeBadge($pub['type_publication']) ?>
                                    </div>
                                    <h4 class="publication-title">
                                        <a href="<?= base_url('publications/' . $pub['id']) ?>">
                                            <?= e($pub['titre']) ?>
                                        </a>
                                    </h4>
                                    <?php if (!empty($pub['auteurs_membres'])): ?>
                                        <p class="text-sm text-gray mb-sm">
                                            <?= e($pub['auteurs_membres']) ?>
                                        </p>
                                    <?php endif; ?>
                                    <div class="publication-meta text-xs text-muted">
                                        <span><?= format_date($pub['date_publication']) ?></span>
                                        <?php if (!empty($pub['doi'])): ?>
                                            <span>• DOI: <?= e($pub['doi']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="empty-message">Aucune publication n'est encore liée à ce projet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <aside class="sidebar">
                <!-- Informations clés -->
                <div class="info-card">
                    <h3 class="text-lg text-bold mb-md">Informations</h3>
                    <div class="info-list">
                        <?php if ($responsable): ?>
                        <div class="info-item">
                            <span class="info-label text-xs text-uppercase text-muted">Responsable</span>
                            <span class="info-value text-sm text-bold">
                                <a href="<?= base_url('membres/' . $responsable['id']) ?>">
                                    <?= e($responsable['username']) ?>
                                </a>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="info-item">
                            <span class="info-label text-xs text-uppercase text-muted">Thématique</span>
                            <span class="info-value text-sm text-bold">
                                <?= e($projet['thematique']) ?>
                            </span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label text-xs text-uppercase text-muted">Statut</span>
                            <span class="info-value text-sm">
                                <?= LabHelpers::getProjetStatusBadge($projet['status']) ?>
                            </span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label text-xs text-uppercase text-muted">Date de début</span>
                            <span class="info-value text-sm text-bold">
                                <?= format_date($projet['date_debut']) ?>
                            </span>
                        </div>
                        
                        <?php if (!empty($projet['date_fin'])): ?>
                        <div class="info-item">
                            <span class="info-label text-xs text-uppercase text-muted">Date de fin</span>
                            <span class="info-value text-sm text-bold">
                                <?= format_date($projet['date_fin']) ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($projet['source_financement'])): ?>
                        <div class="info-item">
                            <span class="info-label text-xs text-uppercase text-muted">Financement</span>
                            <span class="info-value text-sm text-bold">
                                <?= e($projet['source_financement']) ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Progression -->
                <div class="info-card mt-md">
                    <h3 class="text-lg text-bold mb-md"> Progression</h3>
                    <div class="progress-display text-center">
                        <div class="progress-circle">
                            <svg viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="45" fill="none" stroke="#E5E7EB" stroke-width="10"/>
                                <circle cx="50" cy="50" r="45" fill="none" stroke="#5B7FFF" stroke-width="10"
                                        stroke-dasharray="<?= $stats['progression'] * 2.827 ?>, 282.7"
                                        transform="rotate(-90 50 50)"/>
                            </svg>
                            <div class="progress-text">
                                <span class="text-2xl text-bold text-primary"><?= $stats['progression'] ?>%</span>
                            </div>
                        </div>
                        <p class="text-xs text-muted mt-md">Avancement du projet</p>
                    </div>
                </div>

                <!-- Statistiques -->
                <div class="info-card mt-md">
                    <h3 class="text-lg text-bold mb-md">Statistiques</h3>
                    <div class="stats-list">
                        <div class="stat-row">
                            <span class="text-sm text-gray">Membres</span>
                            <span class="text-lg text-bold text-primary"><?= $stats['nb_membres'] ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="text-sm text-gray">Publications</span>
                            <span class="text-lg text-bold text-primary"><?= $stats['nb_publications'] ?></span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="info-card mt-md">
                    <a href="<?= base_url('projets') ?>" class="btn-secondary w-full text-center">
                        ← Retour aux projets
                    </a>
                </div>
            </aside>
        </div>
    </div>
</div>

<style>
/* Styles spécifiques pour la page de détails uniquement */

/* Hero section */
.project-hero {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    padding: 40px 32px;
    color: white;
}

.hero-content {
    max-width: 1400px;
    margin: 0 auto;
}

.breadcrumbs {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
}

.breadcrumbs a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: var(--transition);
}

.breadcrumbs a:hover {
    color: white;
}

.breadcrumbs .separator {
    color: rgba(255, 255, 255, 0.5);
}

.breadcrumbs .current {
    color: white;
    font-weight: 500;
}

.hero-header h1 {
    font-size: 36px;
    font-weight: 700;
    margin: 0;
    letter-spacing: -0.5px;
}

.project-meta .badge {
    background: rgba(255, 255, 255, 0.2);
    color: white;
}

/* Layout principal */
.project-layout {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 32px;
    margin-top: 32px;
    margin-bottom: 40px;
}

.main-content {
    display: flex;
    flex-direction: column;
}

/* Cartes de contenu */
.content-card {
    background: var(--bg-card);
    border-radius: var(--border-radius-lg);
    padding: 32px;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
}

.section-title {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 20px;
    color: var(--gray-900);
}

.project-description {
    line-height: 1.8;
    font-size: 15px;
    color: var(--gray-700);
}

/* Grille des membres */
.members-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
}

.member-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: var(--gray-50);
    border-radius: var(--border-radius-sm);
    border: 1px solid var(--border-color);
    transition: var(--transition);
}

.member-card:hover {
    border-color: var(--primary);
    box-shadow: var(--shadow-sm);
}

.member-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, #5B7FFF, #667eea);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 16px;
    flex-shrink: 0;
}

.member-info {
    flex: 1;
    min-width: 0;
}

.member-info h4 {
    margin: 0 0 4px 0;
    font-size: 14px;
    font-weight: 600;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Liste des publications */
.publications-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.publication-card {
    padding: 20px;
    background: var(--gray-50);
    border-radius: var(--border-radius-sm);
    border-left: 3px solid var(--primary);
    transition: var(--transition);
}

.publication-card:hover {
    box-shadow: var(--shadow-sm);
    background: white;
}

.publication-title {
    margin: 8px 0;
    font-size: 16px;
    font-weight: 600;
}

.publication-title a {
    color: var(--gray-900);
    text-decoration: none;
    transition: var(--transition);
}

.publication-title a:hover {
    color: var(--primary);
}

.publication-meta {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

/* Sidebar */
.sidebar {
    display: flex;
    flex-direction: column;
}

.info-card {
    background: var(--bg-card);
    border-radius: var(--border-radius-lg);
    padding: 24px;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
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

.info-label {
    font-weight: 600;
    letter-spacing: 0.5px;
}

.info-value {
    color: var(--gray-700);
}

.info-value a {
    color: var(--primary);
    text-decoration: none;
    font-weight: 500;
}

.info-value a:hover {
    text-decoration: underline;
}

/* Cercle de progression */
.progress-circle {
    width: 120px;
    height: 120px;
    margin: 0 auto 16px;
    position: relative;
}

.progress-circle svg {
    width: 100%;
    height: 100%;
}

.progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

/* Stats */
.stats-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.stat-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: var(--gray-50);
    border-radius: var(--border-radius-sm);
}

/* Message vide */
.empty-message {
    text-align: center;
    padding: 40px 20px;
    color: var(--gray-500);
    font-size: 14px;
    background: var(--gray-50);
    border-radius: var(--border-radius-sm);
}

/* Responsive */
@media (max-width: 1024px) {
    .project-layout {
        grid-template-columns: 1fr;
    }
    
    .sidebar {
        order: -1;
    }
}

@media (max-width: 768px) {
    .hero-header h1 {
        font-size: 28px;
    }
    
    .content-card {
        padding: 20px;
    }
    
    .members-grid {
        grid-template-columns: 1fr;
    }
    
    .breadcrumbs {
        flex-wrap: wrap;
    }
}
</style>

<?php ViewComponents::renderFooter(['role' => 'visiteur']); ?>