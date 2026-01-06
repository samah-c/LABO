<?php
/**
 * Vue de la liste des publications (visiteur)
 */
require_once __DIR__ . '/../../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/NavigationComponent.php';
require_once __DIR__ . '/../../../lib/components/FilterComponent.php';
require_once __DIR__ . '/../../../lib/components/FooterComponent.php';

class PublicationsListView
{
    private array $publications;
    private ?array $pagination;

    public function __construct(array $publications, ?array $pagination = null)
    {
        $this->publications = $publications;
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
        echo '<div class="container publications-main-container">';
        $this->renderFilters();
        $this->renderPublicationsGrid();
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
            'title' => 'Publications Scientifiques - Laboratoire TDW',
            'role' => 'visiteur',
            'showLogout' => false,
            'showLoginButton' => true,
            'additionalJs' => [
                base_url('assets/js/visitor/publications-handler.js')
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
                <h1> Publications Scientifiques</h1>
                <p>Découvrez nos travaux de recherche et publications académiques</p>
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
            'action' => base_url('publications'),
            'method' => 'GET',
            'showSearch' => true,
            'searchPlaceholder' => 'Rechercher une publication...',
            'filters' => [
                [
                    'type' => 'select',
                    'name' => 'type',
                    'label' => 'Type',
                    'options' => [
                        'Article' => 'Article',
                        'Conference' => 'Conférence',
                        'These' => 'Thèse',
                        'Rapport' => 'Rapport',
                        'Livre' => 'Livre',
                        'Chapitre' => 'Chapitre'
                    ],
                    'defaultLabel' => 'Tous les types'
                ],
                [
                    'type' => 'select',
                    'name' => 'domaine',
                    'label' => 'Domaine',
                    'options' => [
                        'IA' => 'Intelligence Artificielle',
                        'Securite' => 'Sécurité',
                        'Reseaux' => 'Réseaux',
                        'Blockchain' => 'Blockchain',
                        'IoT' => 'IoT',
                        'BigData' => 'Big Data'
                    ],
                    'defaultLabel' => 'Tous les domaines'
                ],
                [
                    'type' => 'select',
                    'name' => 'annee',
                    'label' => 'Année',
                    'options' => $this->getYearOptions(),
                    'defaultLabel' => 'Toutes les années'
                ],
                [
                    'type' => 'select',
                    'name' => 'sort',
                    'label' => 'Trier par',
                    'options' => [
                        'recent' => 'Plus récentes',
                        'title' => 'Titre (A-Z)',
                        'type' => 'Type'
                    ],
                    'defaultLabel' => null
                ]
            ]
        ]);
    }

    /**
     * Génère les options d'années
     */
    private function getYearOptions(): array
    {
        $options = [];
        for ($year = date('Y'); $year >= 2020; $year--) {
            $options[$year] = (string)$year;
        }
        return $options;
    }


    /**
     * Rendu de la grille de publications
     */
    private function renderPublicationsGrid(): void
    {
        ?>
        <div id="publications-container" class="publications-grid">
            <?php if (!empty($this->publications)): ?>
                <?php foreach ($this->publications as $pub): ?>
                    <?php $this->renderPublicationCard($pub); ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>Aucune publication disponible</h3>
                    <p class="text-muted">Aucune publication n'est actuellement publiée.</p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Rendu d'une carte de publication
     */
    private function renderPublicationCard(array $pub): void
    {
        $typeNormalized = $pub['type_normalized'] ?? 'Article';
        $domaineNormalized = $pub['domaine_normalized'] ?? '';
        $annee = $pub['annee_publication'] ?? date('Y');
        
        $typeColors = [
            'Article' => '#3B82F6',
            'Conference' => '#8B5CF6',
            'These' => '#EC4899',
            'Rapport' => '#F59E0B',
            'Livre' => '#10B981',
            'Chapitre' => '#6366F1'
        ];
        $badgeColor = $typeColors[$typeNormalized] ?? '#6B7280';
        ?>
        <article class="publication-card" 
                 data-type="<?= e($typeNormalized) ?>"
                 data-domaine="<?= e($domaineNormalized) ?>"
                 data-annee="<?= e($annee) ?>"
                 data-title="<?= e(strtolower($pub['titre'] ?? '')) ?>"
                 data-date="<?= strtotime($pub['date_publication'] ?? 'now') ?>">
            
            <div class="publication-header">
                <span class="publication-type-badge" style="background: <?= $badgeColor ?>;">
                    <?= e($pub['type_original'] ?? $typeNormalized) ?>
                </span>
                <span class="publication-year"><?= e($annee) ?></span>
            </div>
            
            <h3 class="publication-title">
                <a href="<?= base_url('publications/' . $pub['id']) ?>">
                    <?= e($pub['titre']) ?>
                </a>
            </h3>
            
            <?php if (!empty($pub['auteurs_noms'])): ?>
            <div class="publication-authors">
                 <?= e($pub['auteurs_noms']) ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($pub['resume'])): ?>
            <p class="publication-resume">
                <?= truncate($pub['resume'], 150) ?>
            </p>
            <?php endif; ?>
            
            <div class="publication-meta">
                <?php if (!empty($pub['domaine_original'])): ?>
                <span class="meta-badge">
                    <?= e($pub['domaine_original']) ?>
                </span>
                <?php endif; ?>
                
                <?php if (!empty($pub['projet_titre'])): ?>
                <span class="meta-info">
                     <?= e(truncate($pub['projet_titre'], 30)) ?>
                </span>
                <?php endif; ?>
            </div>
            
            <div class="publication-footer">
                <?php if (!empty($pub['doi'])): ?>
                <a href="https://doi.org/<?= e($pub['doi']) ?>" 
                   target="_blank" 
                   class="btn-link"
                   title="Voir sur DOI">
                     DOI
                </a>
                <?php endif; ?>
                
                <?php if (!empty($pub['lien'])): ?>
                <a href="<?= e($pub['lien']) ?>" 
                   target="_blank" 
                   class="btn-link"
                   title="Télécharger">
                     PDF
                </a>
                <?php endif; ?>
                
                <a href="<?= base_url('publications/' . $pub['id']) ?>" 
                   class="btn-primary">
                    Voir détails
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
            Aucune publication ne correspond à vos critères de recherche.
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
            echo Utils::renderPagination($this->pagination, base_url('publications'));
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

        .publications-main-container {
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
            margin-top: 108px; /* Compense le header + menu fixe */
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

        /* Grille des publications */
        .publications-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .publication-card {
            background: var(--bg-card);
            border-radius: var(--border-radius-lg);
            border: 1px solid var(--border-color);
            padding: 24px;
            transition: var(--transition);
            box-shadow: var(--shadow-xs);
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .publication-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
            border-color: var(--primary);
        }

        .publication-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .publication-type-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .publication-year {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-600);
        }

        .publication-title {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            line-height: 1.4;
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
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--gray-700);
        }

        .publication-resume {
            line-height: 1.6;
            margin: 0;
            font-size: 14px;
            color: var(--gray-600);
        }

        .publication-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .meta-badge {
            display: inline-block;
            padding: 4px 10px;
            background: var(--gray-100);
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            color: var(--gray-700);
        }

        .meta-info {
            font-size: 13px;
            color: var(--gray-600);
        }

        .publication-footer {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: auto;
            padding-top: 16px;
            border-top: 1px solid var(--border-color);
        }

        .btn-link {
            padding: 6px 12px;
            font-size: 13px;
            color: var(--primary);
            text-decoration: none;
            border: 1px solid var(--primary);
            border-radius: 6px;
            transition: var(--transition);
        }

        .btn-link:hover {
            background: var(--primary);
            color: white;
        }

        .publication-footer .btn-primary {
            margin-left: auto;
            padding: 8px 16px;
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

        /* Responsive */
        @media (max-width: 1024px) {
            .publications-grid {
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
            
            .publications-grid {
                grid-template-columns: 1fr;
            }
        }
        </style>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const detailLinks = document.querySelectorAll('.publication-footer .btn-primary');
            
            detailLinks.forEach((link) => {
                const card = link.closest('.publication-card');
                const titre = card ? card.querySelector('.publication-title a')?.textContent?.trim() : 'Inconnu';
            });
        });

        function resetFilters() {
            window.location.href = '<?= base_url('publications') ?>';
        }
        </script>
        <?php
    }
}