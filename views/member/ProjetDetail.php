<?php
/**
 * Vue des détails d'un projet (membre)
 */
require_once __DIR__ . '/../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../lib/components/NavigationComponent.php';
require_once __DIR__ . '/../../lib/components/PageHeaderComponent.php';
require_once __DIR__ . '/../../lib/components/FilterComponent.php';
require_once __DIR__ . '/../../lib/components/FooterComponent.php';

class ProjetDetail
{
    private array $projet;
    private array $membres;
    private array $publications;
    private ?array $responsable;
    private array $stats;

    public function __construct(array $projet, array $membres, array $publications, ?array $responsable, array $stats)
    {
        $this->projet = $projet;
        $this->membres = $membres;
        $this->publications = $publications;
        $this->responsable = $responsable;
        $this->stats = $stats;
    }

    public function render(): void
    {
        $this->renderHeader();
        $this->renderNavigation();
        echo '<div class="container">';
        $this->renderBreadcrumbs();
        $this->renderProjectHero();
        $this->renderProjectLayout();
        echo '</div>';
        $this->renderStyles();
        $this->renderFooter();
    }

    private function renderHeader(): void
    {
        HeaderComponent::render([
            'title' => 'Détails du Projet - Espace Membre',
            'username' => session('username'),
            'role' => 'membre',
            'showLogout' => true
        ]);
    }

    private function renderNavigation(): void
    {
        NavigationComponent::renderSidebar('membre');
    }

    private function renderBreadcrumbs(): void
    {
        NavigationComponent::renderBreadcrumbs([
            ['label' => 'Tableau de bord', 'url' => base_url('membre/dashboard')],
            ['label' => 'Mes Projets', 'url' => base_url('membre/projets')],
            ['label' => e($this->projet['titre'])]
        ]);
    }

    private function renderProjectHero(): void
    {
        ?>
        <div class="project-hero-compact">
            <div class="hero-header-content">
                <h1><?= e($this->projet['titre']) ?></h1>
                <div class="project-meta-inline">
                    <span class="badge badge-primary"><?= e($this->projet['thematique']) ?></span>
                    <?= LabHelpers::getProjetStatusBadge($this->projet['status']) ?>
                </div>
            </div>
        </div>
        <?php
    }

    private function renderProjectLayout(): void
    {
        ?>
        <div class="project-detail-layout">
            <div class="main-content-area">
                <?php $this->renderDescription(); ?>
                <?php $this->renderTeam(); ?>
                <?php $this->renderPublications(); ?>
            </div>
            
            <aside class="sidebar-area">
                <?php $this->renderInfoCard(); ?>
                <?php $this->renderProgressCard(); ?>
                <?php $this->renderStatsCard(); ?>
            </aside>
        </div>
        <?php
    }

    private function renderDescription(): void
    {
        ?>
        <div class="content-card">
            <h2 class="section-title">Description du projet</h2>
            <p class="project-description">
                <?= nl2br(e($this->projet['description'])) ?>
            </p>
        </div>
        <?php
    }

    private function renderTeam(): void
    {
        ?>
        <div class="content-card">
            <div class="section-header">
                <h2 class="section-title">Équipe du projet</h2>
                <span class="badge badge-gray">
                    <?= count($this->membres) ?> membre<?= count($this->membres) > 1 ? 's' : '' ?>
                </span>
            </div>
            
            <?php if (!empty($this->membres)): ?>
                <div class="members-grid">
                    <?php foreach ($this->membres as $membre): ?>
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
                                    <span class="badge badge-primary-sm">
                                        <?= e($membre['role_projet']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty-message">Aucun membre assigné à ce projet.</p>
            <?php endif; ?>
        </div>
        <?php
    }

    private function renderPublications(): void
    {
        ?>
        <div class="content-card">
            <div class="section-header">
                <h2 class="section-title">Publications liées</h2>
                <span class="badge badge-gray">
                    <?= count($this->publications) ?> publication<?= count($this->publications) > 1 ? 's' : '' ?>
                </span>
            </div>
            
            <?php if (!empty($this->publications)): ?>
                <div class="publications-list">
                    <?php foreach ($this->publications as $pub): ?>
                        <div class="publication-item">
                            <div class="pub-header">
                                <?= LabHelpers::getPublicationTypeBadge($pub['type_publication']) ?>
                                <h4 class="publication-title">
                                    <a href="<?= base_url('publications/' . $pub['id']) ?>">
                                        <?= e($pub['titre']) ?>
                                    </a>
                                </h4>
                            </div>
                            <?php if (!empty($pub['auteurs_membres'])): ?>
                                <p class="text-sm text-gray">
                                    <?= e($pub['auteurs_membres']) ?>
                                </p>
                            <?php endif; ?>
                            <div class="publication-meta">
                                <span><?= format_date($pub['date_publication']) ?></span>
                                <?php if (!empty($pub['doi'])): ?>
                                    <span>DOI: <?= e($pub['doi']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty-message">Aucune publication liée à ce projet.</p>
            <?php endif; ?>
        </div>
        <?php
    }

    private function renderInfoCard(): void
    {
        ?>
        <div class="info-card">
            <h3 class="card-title">Informations</h3>
            <div class="info-list">
                <?php if ($this->responsable): ?>
                <div class="info-item">
                    <span class="info-label">Responsable</span>
                    <span class="info-value">
                        <a href="<?= base_url('membres/' . $this->responsable['id']) ?>">
                            <?= e($this->responsable['username']) ?>
                        </a>
                    </span>
                </div>
                <?php endif; ?>
                
                <div class="info-item">
                    <span class="info-label">Thématique</span>
                    <span class="info-value"><?= e($this->projet['thematique']) ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Statut</span>
                    <span class="info-value">
                        <?= LabHelpers::getProjetStatusBadge($this->projet['status']) ?>
                    </span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Date de début</span>
                    <span class="info-value"><?= format_date($this->projet['date_debut']) ?></span>
                </div>
                
                <?php if (!empty($this->projet['date_fin'])): ?>
                <div class="info-item">
                    <span class="info-label">Date de fin</span>
                    <span class="info-value"><?= format_date($this->projet['date_fin']) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($this->projet['source_financement'])): ?>
                <div class="info-item">
                    <span class="info-label">Financement</span>
                    <span class="info-value"><?= e($this->projet['source_financement']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    private function renderProgressCard(): void
    {
        ?>
        <div class="info-card">
            <h3 class="card-title">Progression</h3>
            <div class="progress-display">
                <div class="progress-circle">
                    <svg viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="45" fill="none" stroke="#E5E7EB" stroke-width="10"/>
                        <circle cx="50" cy="50" r="45" fill="none" stroke="#5B7FFF" stroke-width="10"
                                stroke-dasharray="<?= $this->stats['progression'] * 2.827 ?>, 282.7"
                                transform="rotate(-90 50 50)"/>
                    </svg>
                    <div class="progress-text">
                        <span class="progress-value"><?= $this->stats['progression'] ?>%</span>
                    </div>
                </div>
                <p class="progress-label">Avancement du projet</p>
            </div>
        </div>
        <?php
    }

    private function renderStatsCard(): void
    {
        ?>
        <div class="info-card">
            <h3 class="card-title">Statistiques</h3>
            <div class="stats-list">
                <div class="stat-row">
                    <span class="stat-label">Membres</span>
                    <span class="stat-value"><?= $this->stats['nb_membres'] ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Publications</span>
                    <span class="stat-value"><?= $this->stats['nb_publications'] ?></span>
                </div>
            </div>
        </div>
        <?php
    }

    private function renderStyles(): void
    {
        ?>
        <style>
        /* Hero compact */
        .project-hero-compact {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 32px;
            border-radius: var(--border-radius-lg);
            color: white;
            margin-bottom: 24px;
        }

        .hero-header-content h1 {
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 12px 0;
        }

        .project-meta-inline {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .project-meta-inline .badge {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        /* Layout principal */
        .project-detail-layout {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 24px;
            margin-bottom: 40px;
        }

        /* Content cards */
        .content-card {
            background: var(--bg-card);
            border-radius: var(--border-radius-lg);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            margin-bottom: 20px;
        }

        .content-card:last-child {
            margin-bottom: 0;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 16px 0;
            color: var(--gray-900);
        }

        .section-header .section-title {
            margin: 0;
        }

        .project-description {
            line-height: 1.7;
            font-size: 15px;
            color: var(--gray-700);
            margin: 0;
        }

        /* Members grid */
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

        .badge-primary-sm {
            display: inline-block;
            padding: 2px 8px;
            font-size: 11px;
            background: var(--primary);
            color: white;
            border-radius: 8px;
        }

        /* Publications */
        .publications-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .publication-item {
            padding: 16px;
            background: var(--gray-50);
            border-radius: var(--border-radius-sm);
            border-left: 3px solid var(--primary);
            transition: var(--transition);
        }

        .publication-item:hover {
            box-shadow: var(--shadow-sm);
            background: white;
        }

        .pub-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }

        .publication-title {
            margin: 0;
            font-size: 15px;
            font-weight: 600;
            flex: 1;
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
            gap: 12px;
            font-size: 13px;
            color: var(--gray-600);
            margin-top: 8px;
        }

        /* Sidebar cards */
        .info-card {
            background: var(--bg-card);
            border-radius: var(--border-radius-lg);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            margin-bottom: 16px;
        }

        .info-card:last-child {
            margin-bottom: 0;
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 16px 0;
            color: var(--gray-900);
        }

        .info-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .info-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 14px;
            color: var(--gray-900);
            font-weight: 500;
        }

        .info-value a {
            color: var(--primary);
            text-decoration: none;
        }

        .info-value a:hover {
            text-decoration: underline;
        }

        /* Progress circle */
        .progress-display {
            text-align: center;
        }

        .progress-circle {
            width: 120px;
            height: 120px;
            margin: 0 auto 12px;
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

        .progress-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
        }

        .progress-label {
            font-size: 13px;
            color: var(--gray-600);
            margin: 0;
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

        .stat-label {
            font-size: 14px;
            color: var(--gray-700);
        }

        .stat-value {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
        }

        /* Empty message */
        .empty-message {
            text-align: center;
            padding: 32px 20px;
            color: var(--gray-500);
            font-size: 14px;
            background: var(--gray-50);
            border-radius: var(--border-radius-sm);
            margin: 0;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .project-detail-layout {
                grid-template-columns: 1fr;
            }
            
            .sidebar-area {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 16px;
            }
        }

        @media (max-width: 768px) {
            .project-hero-compact {
                padding: 20px;
            }

            .hero-header-content h1 {
                font-size: 22px;
            }
            
            .members-grid {
                grid-template-columns: 1fr;
            }

            .sidebar-area {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
    }

    private function renderFooter(): void
    {
        FooterComponent::render(['role' => 'membre']);
    }
}