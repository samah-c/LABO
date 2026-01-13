<?php
/**
 * Vue de la liste des projets (visiteur)
 */
require_once __DIR__ . '/../../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/NavigationComponent.php';
require_once __DIR__ . '/../../../lib/components/FilterComponent.php';
require_once __DIR__ . '/../../../lib/components/FooterComponent.php';

class ProjetListView
{
    private array $projets;
    private ?array $pagination;

    public function __construct(array $projets, ?array $pagination = null)
    {
        $this->projets = $projets;
        $this->pagination = $pagination;
    }

    /**
     * Rendu complet de la vue
     */
    public function render(): void
    {
        $this->renderHeader();
        $this->renderNavigation();
        $this->renderBanner();
        echo '<div class="visitor-container">';
        echo '<div class="container projets-main-container">';
        $this->renderFilters();
        $this->renderProjetsGrid();
        $this->renderNoResults();
        $this->renderPagination();
        echo '</div>';
        echo '</div>';
        $this->renderFooter();
        $this->renderScripts();
    }

    /**
     * Rendu de l'en-tête
     */
    private function renderHeader(): void
    {
        HeaderComponent::render([
            'title' => 'Projets de Recherche - Laboratoire TDW',
            'role' => 'visiteur',
            'showLogout' => false,
            'showLoginButton' => true,
            'additionalJs' => [
                base_url('assets/js/visitor/projets-handler.js')
            ]
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
     * Rendu de la bannière
     */
    private function renderBanner(): void
    {
        ?>
        <section class="page-banner">
            <div class="banner-content">
                <h1>Projets de Recherche</h1>
                <p>Découvrez nos projets innovants dans différents domaines de recherche</p>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu des filtres
     */
    private function renderFilters(): void
    {
        FilterComponent::render([
            'action' => base_url('projets'),
            'method' => 'GET',
            'showSearch' => true,
            'searchPlaceholder' => 'Rechercher un projet...',
            'filters' => [
                [
                    'type' => 'select',
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
                    ],
                    'defaultLabel' => 'Toutes les thématiques'
                ],
                [
                    'type' => 'select',
                    'name' => 'status',
                    'label' => 'Statut',
                    'options' => [
                        'en_cours' => 'En cours',
                        'termine' => 'Terminé',
                        'soumis' => 'Soumis',
                        'approuve' => 'Approuvé',
                        'rejete' => 'Rejeté'
                    ],
                    'defaultLabel' => 'Tous les statuts'
                ],
                [
                    'type' => 'select',
                    'name' => 'sort',
                    'label' => 'Trier par',
                    'options' => [
                        'recent' => 'Plus récents',
                        'title' => 'Titre (A-Z)',
                        'thematique' => 'Thématique'
                    ],
                    'defaultLabel' => null
                ]
            ]
        ]);
    }


    /**
     * Rendu de la grille de projets
     */
    private function renderProjetsGrid(): void
    {
        ?>
        <div id="projects-container" class="projects-grid">
            <?php if (!empty($this->projets)): ?>
                <?php foreach ($this->projets as $projet): ?>
                    <?php $this->renderProjetCard($projet); ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>Aucun projet disponible</h3>
                    <p class="text-muted">Aucun projet de recherche n'est actuellement publié.</p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Rendu d'une carte de projet
     */
    private function renderProjetCard(array $projet): void
    {
        $statusNormalized = $projet['status_normalized'] ?? 'en_cours';
        $thematiqueCode = $projet['thematique_code'] ?? $projet['thematique'] ?? '';
        
        $progression = LabHelpers::calculateProjectProgress(
            $projet['date_debut'], 
            $projet['date_fin'] ?? null
        );
        ?>
        <article class="project-card" 
                 data-thematique="<?= e($thematiqueCode) ?>"
                 data-status="<?= e($statusNormalized) ?>"
                 data-title="<?= e(strtolower($projet['titre'] ?? '')) ?>"
                 data-date="<?= strtotime($projet['date_debut'] ?? 'now') ?>">
            
            <div class="project-theme-badge badge badge-primary">
                <?= e($projet['thematique'] ?? 'Non défini') ?>
            </div>
            
            <div class="project-header">
                <h3 class="project-title">
                    <a href="<?= base_url('projets/' . $projet['id']) ?>">
                        <?= e($projet['titre']) ?>
                    </a>
                </h3>
                <?php 
                echo LabHelpers::getProjetStatusBadge($projet['status_original'] ?? $projet['status'] ?? 'en_cours'); 
                ?>
            </div>
            
            <p class="project-description text-gray">
                <?= truncate($projet['descriptif'] ?? $projet['description'] ?? '', 150) ?>
            </p>
            
            <div class="project-info">
                <div class="info-row">
                    <span class="info-label">Responsable:</span>
                    <span class="info-value">
                        <?= e($projet['responsable_username'] ?? $projet['responsable_nom'] ?? 'Non défini') ?>
                    </span>
                </div>
                
                <?php if (!empty($projet['nb_membres'])): ?>
                <div class="info-row">
                    <span class="info-label">Membres:</span>
                    <span class="info-value">
                        <?= $projet['nb_membres'] ?> membre<?= $projet['nb_membres'] > 1 ? 's' : '' ?>
                    </span>
                </div>
                <?php endif; ?>
                
                <div class="info-row">
                    <span class="info-label">Période:</span>
                    <span class="info-value">
                        <?= date('Y', strtotime($projet['date_debut'])) ?>
                        <?php if (!empty($projet['date_fin'])): ?>
                            - <?= date('Y', strtotime($projet['date_fin'])) ?>
                        <?php endif; ?>
                    </span>
                </div>
                
                <?php if (!empty($projet['source_financement'])): ?>
                <div class="info-row">
                    <span class="info-label">Financement:</span>
                    <span class="info-value">
                        <?= e($projet['source_financement']) ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="project-progress">
                <div class="progress">
                    <div class="progress-bar" style="width: <?= $progression ?>%"></div>
                </div>
                <span class="progress-text"><?= $progression ?>% complété</span>
            </div>
            
            <div class="project-footer">
                <a href="<?= base_url('projets/' . $projet['id']) ?>" class="btn-primary">
                    Voir les détails
                </a>
            </div>
        </article>
        <?php
    }

    /**
     * Rendu du message "aucun résultat"
     */
    private function renderNoResults(): void
    {
        ?>
        <div id="no-results" class="empty-message" style="display: none;">
            Aucun projet ne correspond à vos critères de recherche.
            <button onclick="resetFilters()" class="btn-secondary mt-md">
                Réinitialiser les filtres
            </button>
        </div>
        <?php
    }

    /**
     * Rendu de la pagination
     */
    private function renderPagination(): void
    {
        if ($this->pagination && $this->pagination['total_pages'] > 1) {
            echo Utils::renderPagination($this->pagination, base_url('projets'));
        }
    }

    /**
     * Rendu du footer
     */
    private function renderFooter(): void
    {
        FooterComponent::render(['role' => 'visiteur']);
    }

    /**
     * Rendu des scripts et styles
     */
    private function renderScripts(): void
    {
        ?>
        <style>
        /* Ajustements pour le layout visiteur */
        .visitor-container {
            padding-top: 0 !important;
        }

        .projets-main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px 20px;
        }

        /* Banner */
        .page-banner {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 60px 32px;
            text-align: center;
            color: white;
            margin-top: 108px;
        }

        .banner-content h1 {
            font-size: 42px;
            font-weight: 700;
            margin: 0 0 12px 0;
            letter-spacing: -0.5px;
        }

        .banner-content p {
            font-size: 18px;
            opacity: 0.95;
            margin: 0;
        }

        /* Grille des projets */
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .project-card {
            background: var(--bg-card);
            border-radius: var(--border-radius-lg);
            border: 1px solid var(--border-color);
            padding: 24px;
            transition: var(--transition);
            box-shadow: var(--shadow-xs);
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .project-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
            border-color: var(--primary);
        }

        .project-theme-badge {
            position: absolute;
            top: 16px;
            right: 16px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 16px;
            padding-right: 120px;
        }

        .project-title {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            line-height: 1.4;
            flex: 1;
        }

        .project-title a {
            color: var(--gray-900);
            text-decoration: none;
            transition: var(--transition);
        }

        .project-title a:hover {
            color: var(--primary);
        }

        .project-description {
            line-height: 1.6;
            margin: 0 0 20px 0;
            font-size: 14px;
        }

        .project-info {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
            padding: 16px;
            background: var(--gray-50);
            border-radius: var(--border-radius-sm);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .info-label {
            color: var(--gray-600);
            font-weight: 500;
        }

        .info-value {
            color: var(--gray-900);
            text-align: right;
        }

        .project-progress {
            margin-bottom: 20px;
        }

        .progress {
            height: 8px;
            background: var(--gray-200);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 8px;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            transition: width 0.3s ease;
        }

        .progress-text {
            font-size: 12px;
            color: var(--gray-600);
            text-align: center;
            display: block;
        }

        .project-footer {
            margin-top: auto;
            padding-top: 16px;
            border-top: 1px solid var(--border-color);
        }

        .project-footer .btn-primary {
            width: 100%;
            padding: 10px 20px;
            font-size: 14px;
        }

        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: white;
            padding: 24px;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm);
            text-align: center;
        }

        .stat-card h3 {
            font-size: 14px;
            color: var(--gray-600);
            margin: 0 0 12px 0;
            font-weight: 500;
        }

        .stat-card .number {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary);
        }

        /* Empty state */
        .empty-state, .empty-message {
            text-align: center;
            padding: 60px 20px;
            grid-column: 1 / -1;
        }

        .empty-state h3 {
            font-size: 24px;
            color: var(--gray-900);
            margin-bottom: 12px;
        }

        .text-muted {
            color: var(--gray-600);
        }

        .text-gray {
            color: var(--gray-700);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .projects-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .banner-content h1 {
                font-size: 32px;
            }
            
            .banner-content p {
                font-size: 16px;
            }
            
            .projects-grid {
                grid-template-columns: 1fr;
            }
            
            .project-header {
                flex-direction: column;
                padding-right: 0;
            }
            
            .project-theme-badge {
                position: static;
                display: inline-block;
                margin-bottom: 12px;
            }
        }
        </style>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialisation des gestionnaires d'événements si nécessaire
        });

        function resetFilters() {
            window.location.href = '<?= base_url('projets') ?>';
        }
        </script>
        <?php
    }
}