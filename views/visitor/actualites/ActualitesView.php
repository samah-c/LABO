<?php
/**
 * ActualitesView.php - Vue de la liste des actualités
 * À placer dans : /TDW_project/app/views/public/actualites/ActualitesView.php
 */

require_once __DIR__ . '/../../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/NavigationComponent.php';
require_once __DIR__ . '/../../../lib/components/FilterComponent.php';
require_once __DIR__ . '/../../../lib/components/FooterComponent.php';

class ActualitesView
{
    private array $actualites;
    private ?array $pagination;
    private array $stats;

    public function __construct(array $actualites, ?array $pagination = null)
    {
        $this->actualites = $actualites;
        $this->pagination = $pagination;
        $this->calculateStats();
    }

    /**
     * Calcule les statistiques
     */
    private function calculateStats(): void
    {
        $this->stats = [
            'total' => count($this->actualites),
            'publications' => count(array_filter($this->actualites, fn($a) => $a['type'] === 'publication')),
            'evenements' => count(array_filter($this->actualites, fn($a) => $a['type'] === 'evenement')),
            'scientifiques' => count(array_filter($this->actualites, fn($a) => 
                ($a['source'] ?? '') === 'scientifique'
            ))
        ];
    }

    /**
     * Rendu complet de la vue
     */
    public function render(): void
    {
        $this->renderHeader();
        $this->renderNavigation();
        echo '<div class="visitor-container">';
        $this->renderBanner();
        echo '<div class="container">';
        $this->renderFilters();
        $this->renderStats();
        $this->renderActualitesGrid();
        $this->renderPagination();
        echo '</div>'; // container
        echo '</div>'; // visitor-container
        $this->renderStyles();
        $this->renderFooter();
    }

    /**
     * Rendu de l'en-tête
     */
    private function renderHeader(): void
    {
        HeaderComponent::render([
            'title' => 'Actualités - Laboratoire TDW',
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
        NavigationComponent::renderHorizontalMenu('actualites');
    }

    /**
     * Rendu de la bannière
     */
    private function renderBanner(): void
    {
        ?>
        <section class="page-banner">
            <div class="banner-content">
                <h1>Actualités du Laboratoire</h1>
                <p>Suivez nos dernières publications, événements et avancées scientifiques</p>
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
            'action' => base_url('actualites'),
            'method' => 'GET',
            'showSearch' => true,
            'searchPlaceholder' => 'Rechercher une actualité...',
            'filters' => [
                [
                    'type' => 'select',
                    'name' => 'type',
                    'label' => 'Type',
                    'options' => [
                        'publication' => 'Publications',
                        'evenement' => 'Événements',
                        'scientifique' => 'Scientifiques'
                    ],
                    'defaultLabel' => 'Tous les types'
                ],
                [
                    'type' => 'select',
                    'name' => 'mois',
                    'label' => 'Période',
                    'options' => $this->getMonthOptions(),
                    'defaultLabel' => 'Toutes les périodes'
                ],
                [
                    'type' => 'select',
                    'name' => 'sort',
                    'label' => 'Trier par',
                    'options' => [
                        'date_desc' => 'Plus récentes',
                        'date_asc' => 'Plus anciennes',
                        'titre' => 'Titre (A-Z)'
                    ],
                    'defaultLabel' => 'Plus récentes'
                ]
            ]
        ]);
    }

    /**
     * Génère les options de mois
     */
    private function getMonthOptions(): array
    {
        $options = [];
        $currentYear = date('Y');
        $currentMonth = date('n');
        
        for ($i = 0; $i < 12; $i++) {
            $month = $currentMonth - $i;
            $year = $currentYear;
            
            if ($month <= 0) {
                $month += 12;
                $year--;
            }
            
            $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
            $key = "$year-$monthStr";
            $options[$key] = date('F Y', strtotime("$year-$monthStr-01"));
        }
        
        return $options;
    }

    /**
     * Rendu des statistiques
     */
    private function renderStats(): void
    {
        ?>
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total</h3>
                <div class="number"><?= $this->stats['total'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Publications</h3>
                <div class="number"><?= $this->stats['publications'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Événements</h3>
                <div class="number"><?= $this->stats['evenements'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Scientifiques</h3>
                <div class="number"><?= $this->stats['scientifiques'] ?></div>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu de la grille des actualités
     */
    private function renderActualitesGrid(): void
    {
        if (empty($this->actualites)) {
            $this->renderEmptyState();
            return;
        }

        ?>
        <div class="actualites-grid">
            <?php foreach ($this->actualites as $actualite): ?>
                <?php $this->renderActualiteCard($actualite); ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Rendu d'une carte actualité
     */
    private function renderActualiteCard(array $actualite): void
    {
        $type = $actualite['type'] ?? 'scientifique';
        $source = $actualite['source'] ?? 'laboratoire';
        
        // Couleurs par type
        $typeColors = [
            'publication' => '#3B82F6',
            'evenement' => '#8B5CF6',
            'scientifique' => '#10B981',
            'laboratoire' => '#F59E0B'
        ];
        $badgeColor = $typeColors[$type] ?? $typeColors[$source] ?? '#6B7280';
        
        // Labels
        $typeLabels = [
            'publication' => 'Publication',
            'evenement' => 'Événement',
            'scientifique' => 'Actualité Scientifique',
            'laboratoire' => 'Actualité Laboratoire'
        ];
        $typeLabel = $typeLabels[$type] ?? $typeLabels[$source] ?? 'Actualité';
        
        // Date formatée
        $date = $actualite['date'] ?? $actualite['date_publication'] ?? date('Y-m-d');
        $dateFormatted = format_date($date, 'd F Y');
        
        // Image
        $hasImage = !empty($actualite['data']['image'] ?? $actualite['image'] ?? null);
        $imagePath = $hasImage ? base_url('uploads/actualites/' . ($actualite['data']['image'] ?? $actualite['image'])) : null;
        
        // URL de détail
        $detailUrl = $this->getDetailUrl($actualite);
        ?>
        
        <article class="actualite-card">
            <?php if ($imagePath): ?>
            <div class="actualite-image">
                <img src="<?= htmlspecialchars($imagePath) ?>" 
                     alt="<?= htmlspecialchars($actualite['data']['titre'] ?? $actualite['titre'] ?? 'Actualité') ?>">
                <span class="actualite-badge" style="background: <?= $badgeColor ?>;">
                    <?= htmlspecialchars($typeLabel) ?>
                </span>
            </div>
            <?php else: ?>
            <div class="actualite-image-placeholder">
                <span class="actualite-badge" style="background: <?= $badgeColor ?>;">
                    <?= htmlspecialchars($typeLabel) ?>
                </span>
            </div>
            <?php endif; ?>
            
            <div class="actualite-content">
                <div class="actualite-meta">
                    <span class="actualite-date"><?= htmlspecialchars($dateFormatted) ?></span>
                    <?php if (!empty($actualite['data']['auteur_nom'] ?? null)): ?>
                    <span class="actualite-author">
                        Par <?= htmlspecialchars($actualite['data']['auteur_nom']) ?>
                    </span>
                    <?php endif; ?>
                </div>
                
                <h3 class="actualite-title">
                    <a href="<?= htmlspecialchars($detailUrl) ?>">
                        <?= htmlspecialchars($actualite['data']['titre'] ?? $actualite['titre'] ?? 'Sans titre') ?>
                    </a>
                </h3>
                
                <?php if (!empty($actualite['data']['description'] ?? $actualite['description'] ?? null)): ?>
                <p class="actualite-description">
                    <?= truncate($actualite['data']['description'] ?? $actualite['description'], 150) ?>
                </p>
                <?php endif; ?>
                
                <a href="<?= htmlspecialchars($detailUrl) ?>" class="btn-primary btn-small">
                    Lire la suite
                </a>
            </div>
        </article>
        <?php
    }

    /**
     * Génère l'URL de détail selon le type
     */
    private function getDetailUrl(array $actualite): string
    {
        $type = $actualite['type'] ?? 'scientifique';
        $id = $actualite['data']['id'] ?? $actualite['id'] ?? 0;
        
        switch ($type) {
            case 'publication':
                return base_url('publications/' . $id);
            case 'evenement':
                return base_url('evenements/' . $id);
            default:
                return base_url('actualites/' . $id);
        }
    }

    /**
     * Rendu de l'état vide
     */
    private function renderEmptyState(): void
    {
        ?>
        <div class="empty-state">
            <h3>Aucune actualité disponible</h3>
            <p class="text-muted">Aucune actualité ne correspond à vos critères de recherche.</p>
        </div>
        <?php
    }

    /**
     * Rendu de la pagination
     */
    private function renderPagination(): void
    {
        if (!$this->pagination || $this->pagination['total_pages'] <= 1) {
            return;
        }

        $current = $this->pagination['current_page'];
        $total = $this->pagination['total_pages'];
        ?>
        
        <div class="pagination">
            <?php if ($current > 1): ?>
                <a href="?page=<?= $current - 1 ?>" class="page-link">
                    Précédent
                </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total; $i++): ?>
                <a href="?page=<?= $i ?>" 
                   class="page-link <?= $i === $current ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($current < $total): ?>
                <a href="?page=<?= $current + 1 ?>" class="page-link">
                    Suivant
                </a>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Rendu des styles CSS
     */
    private function renderStyles(): void
    {
        ?>
        <style>
        .page-banner {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 60px 32px;
            text-align: center;
            color: white;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 32px 0;
        }

        .stat-card {
            background: white;
            padding: 24px;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm);
            text-align: center;
            transition: var(--transition);
        }

        .stat-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .stat-card h3 {
            font-size: 14px;
            color: var(--gray-600);
            margin: 0 0 12px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card .number {
            font-size: 36px;
            font-weight: 700;
            color: var(--primary);
        }

        .actualites-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 24px;
            margin: 32px 0 40px;
        }

        .actualite-card {
            background: white;
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
        }

        .actualite-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
        }

        .actualite-image {
            position: relative;
            width: 100%;
            height: 200px;
            overflow: hidden;
        }

        .actualite-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .actualite-card:hover .actualite-image img {
            transform: scale(1.05);
        }

        .actualite-image-placeholder {
            position: relative;
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .actualite-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            padding: 6px 14px;
            border-radius: 16px;
            font-size: 11px;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: var(--shadow-sm);
        }

        .actualite-content {
            padding: 24px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .actualite-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            font-size: 13px;
            color: var(--gray-600);
        }

        .actualite-date {
            font-weight: 500;
        }

        .actualite-author {
            color: var(--primary);
        }

        .actualite-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            line-height: 1.4;
        }

        .actualite-title a {
            color: var(--gray-900);
            text-decoration: none;
            transition: var(--transition);
        }

        .actualite-title a:hover {
            color: var(--primary);
        }

        .actualite-description {
            color: var(--gray-600);
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
            flex: 1;
        }

        .btn-small {
            padding: 8px 16px;
            font-size: 13px;
            align-self: flex-start;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm);
            margin: 32px 0;
        }

        .empty-state h3 {
            font-size: 24px;
            color: var(--gray-900);
            margin-bottom: 12px;
        }

        .text-muted {
            color: var(--gray-600);
        }

        @media (max-width: 768px) {
            .banner-content h1 {
                font-size: 32px;
            }
            
            .banner-content p {
                font-size: 16px;
            }
            
            .actualites-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        </style>
        <?php
    }

    /**
     * Rendu du pied de page
     */
    private function renderFooter(): void
    {
        FooterComponent::render(['role' => 'visiteur']);
    }
}
