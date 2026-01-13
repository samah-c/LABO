<?php
/**
 * Vue détail d'un projet (visiteur)
 */
require_once __DIR__ . '/../../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/NavigationComponent.php';
require_once __DIR__ . '/../../../lib/components/FooterComponent.php';

class ProjetsDetailView
{
    private array $projet;
    private array $membres;
    private array $publications;
    private ?array $responsable;
    private array $stats;

    public function __construct(
        array $projet, 
        array $membres, 
        array $publications, 
        ?array $responsable = null,
        array $stats = []
    ) {
        $this->projet = $projet;
        $this->membres = $membres;
        $this->publications = $publications;
        $this->responsable = $responsable;
        $this->stats = $stats;
    }

    /**
     * Rendu complet de la vue
     */
    public function render(): void
    {
        $this->renderHeader();
        $this->renderNavigation();
        $this->renderHero();
        echo '<div class="visitor-container">';
        echo '<div class="container projet-detail-container">';
        $this->renderLayout();
        echo '</div>';
        echo '</div>';
        $this->renderFooter();
        $this->renderStyles();
    }

    /**
     * Rendu de l'en-tête
     */
    private function renderHeader(): void
    {
        HeaderComponent::render([
            'title' => e($this->projet['titre']) . ' - Laboratoire TDW',
            'role' => 'visiteur',
            'showLogout' => false,
            'showLoginButton' => true
        ]);
    }

    /**
     * Rendu de la navigation
     */
    private function renderNavigation(): void
    {
        NavigationComponent::renderHorizontalMenu();
    }

    /**
     * Rendu de la section hero
     */
    private function renderHero(): void
    {
        ?>
        <section class="project-hero">
            <div class="hero-content">
                <?php $this->renderBreadcrumbs(); ?>
                
                <div class="hero-header">
                    <h1><?= e($this->projet['titre']) ?></h1>
                    <div class="project-meta">
                        <span class="badge badge-primary"><?= e($this->projet['thematique']) ?></span>
                        <?= LabHelpers::getProjetStatusBadge($this->projet['status']) ?>
                    </div>
                </div>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu du fil d'Ariane
     */
    private function renderBreadcrumbs(): void
    {
        NavigationComponent::renderBreadcrumbs([
            ['label' => 'Accueil', 'url' => base_url('/')],
            ['label' => 'Projets', 'url' => base_url('projets')],
            ['label' => $this->projet['titre']]
        ]);
    }

    /**
     * Rendu du layout (colonnes principale + sidebar)
     */
    private function renderLayout(): void
    {
        ?>
        <div class="project-layout">
            <!-- Contenu principal -->
            <div class="main-content">
                <?php 
                $this->renderDescriptionSection();
                $this->renderMembresSection();
                $this->renderPublicationsSection();
                ?>
            </div>

            <!-- Sidebar -->
            <aside class="sidebar">
                <?php 
                $this->renderInfoSection();
                $this->renderProgressionSection();
                $this->renderStatsSection();
                $this->renderBackButton();
                ?>
            </aside>
        </div>
        <?php
    }

    /**
     * Rendu de la section description
     */
    private function renderDescriptionSection(): void
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

    /**
     * Rendu de la section membres
     */
    private function renderMembresSection(): void
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
                        <?php $this->renderMembreCard($membre); ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty-message">Aucun membre n'est actuellement assigné à ce projet.</p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Rendu d'une carte de membre
     */
    private function renderMembreCard(array $membre): void
    {
        ?>
        <div class="member-card">
            <div class="member-avatar">
                <?= strtoupper(substr($membre['username'], 0, 2)) ?>
            </div>
            <div class="member-info">
                <h4><?= e($membre['username']) ?></h4>
                <?php if (!empty($membre['grade'])): ?>
                    <p class="member-grade"><?= e($membre['grade']) ?></p>
                <?php endif; ?>
                <?php if (!empty($membre['role_projet'])): ?>
                    <span class="badge badge-primary">
                        <?= e($membre['role_projet']) ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu de la section publications
     */
    private function renderPublicationsSection(): void
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
                        <?php $this->renderPublicationCard($pub); ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty-message">Aucune publication n'est encore liée à ce projet.</p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Rendu d'une carte de publication
     */
    private function renderPublicationCard(array $pub): void
    {
        ?>
        <div class="publication-card">
            <div class="publication-badge">
                <?= LabHelpers::getPublicationTypeBadge($pub['type_publication']) ?>
            </div>
            <h4 class="publication-title">
                <a href="<?= base_url('publications/' . $pub['id']) ?>">
                    <?= e($pub['titre']) ?>
                </a>
            </h4>
            <?php if (!empty($pub['auteurs_membres'])): ?>
                <p class="publication-authors">
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
        <?php
    }

    /**
     * Rendu de la section informations
     */
    private function renderInfoSection(): void
    {
        ?>
        <div class="info-card">
            <h3 class="sidebar-title">Informations</h3>
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
                    <span class="info-value">
                        <?= e($this->projet['thematique']) ?>
                    </span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Statut</span>
                    <span class="info-value">
                        <?= LabHelpers::getProjetStatusBadge($this->projet['status']) ?>
                    </span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Date de début</span>
                    <span class="info-value">
                        <?= format_date($this->projet['date_debut']) ?>
                    </span>
                </div>
                
                <?php if (!empty($this->projet['date_fin'])): ?>
                <div class="info-item">
                    <span class="info-label">Date de fin</span>
                    <span class="info-value">
                        <?= format_date($this->projet['date_fin']) ?>
                    </span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($this->projet['source_financement'])): ?>
                <div class="info-item">
                    <span class="info-label">Financement</span>
                    <span class="info-value">
                        <?= e($this->projet['source_financement']) ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu de la section progression
     */
    private function renderProgressionSection(): void
    {
        $progression = $this->stats['progression'] ?? 0;
        ?>
        <div class="info-card">
            <h3 class="sidebar-title">Progression</h3>
            <div class="progress-display">
                <div class="progress-circle">
                    <svg viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="45" fill="none" stroke="#E5E7EB" stroke-width="10"/>
                        <circle cx="50" cy="50" r="45" fill="none" stroke="#5B7FFF" stroke-width="10"
                                stroke-dasharray="<?= $progression * 2.827 ?>, 282.7"
                                transform="rotate(-90 50 50)"/>
                    </svg>
                    <div class="progress-text">
                        <span class="progress-value"><?= $progression ?>%</span>
                    </div>
                </div>
                <p class="progress-label">Avancement du projet</p>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu de la section statistiques
     */
    private function renderStatsSection(): void
    {
        ?>
        <div class="info-card">
            <h3 class="sidebar-title">Statistiques</h3>
            <div class="stats-list">
                <div class="stat-row">
                    <span class="stat-label">Membres</span>
                    <span class="stat-value"><?= $this->stats['nb_membres'] ?? 0 ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Publications</span>
                    <span class="stat-value"><?= $this->stats['nb_publications'] ?? 0 ?></span>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu du bouton retour
     */
    private function renderBackButton(): void
    {
        ?>
        <div class="info-card">
            <a href="<?= base_url('projets') ?>" class="btn-secondary btn-block">
                Retour aux projets
            </a>
        </div>
        <?php
    }

    /**
     * Rendu du footer
     */
    private function renderFooter(): void
    {
        FooterComponent::render(['role' => 'visiteur']);
    }

    /**
     * Rendu des styles
     */
    private function renderStyles(): void
    {
        ?>
        <style>
        .visitor-container {
            padding-top: 0 !important;
        }

        .projet-detail-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px 20px;
        }

        /* Hero section */
        .project-hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 40px 32px;
            color: white;
            margin-top: 108px;
        }

        .hero-content {
            max-width: 1400px;
            margin: 0 auto;
        }

        .breadcrumbs {
            margin-bottom: 24px;
        }

        .hero-header h1 {
            font-size: 36px;
            font-weight: 700;
            margin: 0 0 16px 0;
            letter-spacing: -0.5px;
            color: white;
        }

        .project-meta {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
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
            margin-bottom: 40px;
        }

        /* Cartes de contenu */
        .content-card {
            background: var(--bg-card);
            border-radius: var(--border-radius-lg);
            padding: 32px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            margin-bottom: 24px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin: 0 0 20px 0;
            color: var(--gray-900);
        }

        .section-header .section-title {
            margin-bottom: 0;
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
        }

        .member-grade {
            font-size: 12px;
            color: var(--gray-600);
            margin: 4px 0;
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

        .publication-badge {
            margin-bottom: 8px;
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

        .publication-authors {
            font-size: 14px;
            color: var(--gray-700);
            margin: 8px 0;
        }

        .publication-meta {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            font-size: 12px;
            color: var(--gray-600);
        }

        /* Sidebar */
        .info-card {
            background: var(--bg-card);
            border-radius: var(--border-radius-lg);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            margin-bottom: 16px;
        }

        .sidebar-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 20px 0;
            color: var(--gray-900);
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
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gray-600);
        }

        .info-value {
            color: var(--gray-900);
            font-size: 14px;
        }

        .info-value a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .info-value a:hover {
            text-decoration: underline;
        }

        /* Progression */
        .progress-display {
            text-align: center;
        }

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

        .progress-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
        }

        .progress-label {
            font-size: 12px;
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
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
        }

        /* Boutons */
        .btn-block {
            display: block;
            width: 100%;
            text-align: center;
            padding: 12px;
            border-radius: 8px;
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
        }
        </style>
        <?php
    }
}