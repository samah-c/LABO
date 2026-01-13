<?php
/**
 * OffresListView.php - Vue de la liste des offres et opportunités
 */

require_once __DIR__ . '/../../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/NavigationComponent.php';
require_once __DIR__ . '/../../../lib/components/FilterComponent.php';
require_once __DIR__ . '/../../../lib/components/FooterComponent.php';

class OffresListView
{
    private array $offres;
    private ?array $pagination;
    private array $stats;

    public function __construct(array $offres, ?array $pagination = null)
    {
        $this->offres = $offres;
        $this->pagination = $pagination;
        $this->calculateStats();
    }

    /**
     * Calcule les statistiques
     */
    private function calculateStats(): void
    {
        $this->stats = [
            'total' => count($this->offres),
            'stages' => count(array_filter($this->offres, fn($o) => 
                ($o['type_offre'] ?? '') === 'stage'
            )),
            'theses' => count(array_filter($this->offres, fn($o) => 
                ($o['type_offre'] ?? '') === 'these'
            )),
            'emplois' => count(array_filter($this->offres, fn($o) => 
                ($o['type_offre'] ?? '') === 'emploi'
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
        $this->renderOffresGrid();
        $this->renderNoResults();
        $this->renderPagination();
        echo '</div>'; // container
        echo '</div>'; // visitor-container
        $this->renderStyles();
        $this->renderScripts();
        $this->renderFooter();
    }

    /**
     * Rendu de l'en-tête
     */
    private function renderHeader(): void
    {
        HeaderComponent::render([
            'title' => 'Offres et Opportunités - Laboratoire TDW',
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
        NavigationComponent::renderHorizontalMenu('offres');
    }

    /**
     * Rendu de la bannière
     */
    private function renderBanner(): void
    {
        ?>
        <section class="page-banner">
            <div class="banner-content">
                <h1>Offres et Opportunités</h1>
                <p>Découvrez les opportunités de stages, thèses, emplois et collaborations</p>
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
            'action' => base_url('offres'),
            'method' => 'GET',
            'showSearch' => true,
            'searchPlaceholder' => 'Rechercher une offre...',
            'filters' => [
                [
                    'type' => 'select',
                    'name' => 'type',
                    'label' => 'Type',
                    'options' => [
                        'stage' => 'Stage',
                        'these' => 'Thèse',
                        'bourse' => 'Bourse',
                        'collaboration' => 'Collaboration',
                        'emploi' => 'Emploi',
                        'postdoc' => 'Post-Doc'
                    ],
                    'defaultLabel' => 'Tous les types'
                ],
                [
                    'type' => 'select',
                    'name' => 'statut',
                    'label' => 'Statut',
                    'options' => [
                        'active' => 'Active',
                        'expiree' => 'Expirée'
                    ],
                    'defaultLabel' => 'Tous les statuts'
                ],
                [
                    'type' => 'select',
                    'name' => 'sort',
                    'label' => 'Trier par',
                    'options' => [
                        'date' => 'Date de publication',
                        'expiration' => 'Date d\'expiration',
                        'titre' => 'Titre'
                    ],
                    'defaultLabel' => 'Date de publication'
                ]
            ]
        ]);
    }

    /**
     * Rendu de la grille des offres
     */
    private function renderOffresGrid(): void
    {
        if (empty($this->offres)) {
            $this->renderEmptyState();
            return;
        }

        ?>
        <div id="offres-container" class="offres-grid">
            <?php foreach ($this->offres as $offre): ?>
                <?php $this->renderOffreCard($offre); ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Rendu d'une carte offre
     */
    private function renderOffreCard(array $offre): void
    {
        $typeOffre = $offre['type_offre'] ?? '';
        $titre = $offre['titre'] ?? '';
        $description = $offre['description'] ?? '';
        $lieu = $offre['lieu'] ?? '';
        $duree = $offre['duree'] ?? '';
        $dateExpiration = $offre['date_expiration'] ?? '';
        
        // Couleurs par type
        $typeColors = [
            'stage' => ['bg' => 'rgba(33, 150, 243, 0.1)', 'color' => '#1976d2'],
            'these' => ['bg' => 'rgba(156, 39, 176, 0.1)', 'color' => '#7b1fa2'],
            'bourse' => ['bg' => 'rgba(255, 193, 7, 0.1)', 'color' => '#f57c00'],
            'collaboration' => ['bg' => 'rgba(76, 175, 80, 0.1)', 'color' => '#388e3c'],
            'emploi' => ['bg' => 'rgba(244, 67, 54, 0.1)', 'color' => '#d32f2f'],
            'postdoc' => ['bg' => 'rgba(0, 188, 212, 0.1)', 'color' => '#0097a7']
        ];
        
        $colors = $typeColors[$typeOffre] ?? ['bg' => 'rgba(107, 114, 128, 0.1)', 'color' => '#6B7280'];
        
        // Labels pour les types
        $typeLabels = [
            'stage' => 'Stage',
            'these' => 'Thèse',
            'bourse' => 'Bourse',
            'collaboration' => 'Collaboration',
            'emploi' => 'Emploi',
            'postdoc' => 'Post-Doc'
        ];
        
        $typeLabel = $typeLabels[$typeOffre] ?? ucfirst($typeOffre);
        
        // Vérifier si l'offre est expirée
        $isExpired = !empty($dateExpiration) && strtotime($dateExpiration) < time();
        ?>
        
        <article class="offre-card <?= $isExpired ? 'expired' : '' ?>" 
                 data-offre-id="<?= htmlspecialchars($offre['id']) ?>"
                 data-type="<?= htmlspecialchars($typeOffre) ?>"
                 data-lieu="<?= htmlspecialchars($lieu) ?>">
            
            <div class="offre-header">
                <span class="offre-type-badge" 
                      style="background: <?= $colors['bg'] ?>; color: <?= $colors['color'] ?>;">
                    <?= htmlspecialchars($typeLabel) ?>
                </span>
                
                <?php if (!empty($dateExpiration)): ?>
                    <div class="offre-expiration <?= $isExpired ? 'expired' : '' ?>">
                        <?= $isExpired ? 'Expirée' : 'Expire le ' . date('d/m/Y', strtotime($dateExpiration)) ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="offre-content">
                <h3 class="offre-title">
                    <a href="<?= base_url('offres/' . $offre['id']) ?>">
                        <?= htmlspecialchars($titre) ?>
                    </a>
                </h3>
                
                <?php if (!empty($description)): ?>
                <p class="offre-description">
                    <?= truncate($description, 150) ?>
                </p>
                <?php endif; ?>
                
                <div class="offre-meta">
                    <?php if (!empty($lieu)): ?>
                    <div class="meta-item">
                        <span class="meta-label">Lieu:</span>
                        <span class="meta-value"><?= htmlspecialchars($lieu) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($duree)): ?>
                    <div class="meta-item">
                        <span class="meta-label">Durée:</span>
                        <span class="meta-value"><?= htmlspecialchars($duree) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="offre-footer">
                <?php if (!empty($offre['contact_email'])): ?>
                <a href="mailto:<?= htmlspecialchars($offre['contact_email']) ?>" 
                   class="btn-link"
                   title="Envoyer un email">
                    Contacter
                </a>
                <?php endif; ?>
                
            </div>
        </article>
        <?php
    }

    /**
     * Rendu de l'état vide
     */
    private function renderEmptyState(): void
    {
        ?>
        <div class="empty-state">
            <h3>Aucune offre disponible</h3>
            <p class="text-muted">Aucune offre n'est actuellement affichée.</p>
        </div>
        <?php
    }

    /**
     * Rendu du message "aucun résultat"
     */
    private function renderNoResults(): void
    {
        ?>
        <div id="no-results" class="empty-message" style="display: none;">
            Aucune offre ne correspond à vos critères de recherche.
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

        .offres-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 24px;
            margin: 32px 0 40px;
        }

        .offre-card {
            background: var(--bg-card);
            border-radius: var(--border-radius-lg);
            border: 2px solid var(--border-color);
            overflow: hidden;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
        }

        .offre-card:hover {
            box-shadow: var(--shadow-xl);
            transform: translateY(-4px);
            border-color: var(--primary);
        }

        .offre-card.expired {
            opacity: 0.7;
        }

        .offre-card.expired:hover {
            transform: translateY(-2px);
        }

        .offre-header {
            background: var(--gray-50);
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        .offre-type-badge {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
        }

        .offre-expiration {
            font-size: 12px;
            font-weight: 500;
            color: var(--gray-600);
        }

        .offre-expiration.expired {
            color: var(--danger);
            font-weight: 600;
        }

        .offre-content {
            padding: 24px;
            flex: 1;
        }

        .offre-title {
            margin: 0 0 12px 0;
            font-size: 18px;
            font-weight: 600;
            line-height: 1.4;
        }

        .offre-title a {
            color: var(--gray-900);
            text-decoration: none;
            transition: var(--transition);
        }

        .offre-title a:hover {
            color: var(--primary);
        }

        .offre-description {
            color: var(--gray-600);
            font-size: 14px;
            line-height: 1.6;
            margin: 0 0 16px 0;
        }

        .offre-meta {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding-top: 16px;
            border-top: 1px solid var(--border-color);
        }

        .meta-item {
            display: flex;
            gap: 8px;
            font-size: 13px;
        }

        .meta-label {
            color: var(--gray-500);
            font-weight: 500;
        }

        .meta-value {
            color: var(--gray-700);
        }

        .offre-footer {
            display: flex;
            gap: 10px;
            padding: 16px 24px;
            background: var(--gray-50);
            border-top: 1px solid var(--border-color);
        }

        .btn-link {
            padding: 8px 16px;
            font-size: 13px;
            color: var(--primary);
            text-decoration: none;
            border: 1px solid var(--primary);
            border-radius: 6px;
            transition: var(--transition);
            flex: 1;
            text-align: center;
        }

        .btn-link:hover {
            background: var(--primary);
            color: white;
        }

        .offre-footer .btn-primary {
            padding: 8px 20px;
            font-size: 14px;
            flex: 1;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm);
        }

        .empty-state h3 {
            font-size: 24px;
            color: var(--gray-900);
            margin-bottom: 12px;
        }

        .text-muted {
            color: var(--gray-600);
        }

        .empty-message {
            text-align: center;
            padding: 40px 20px;
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm);
            margin: 32px 0;
        }

        .mt-md {
            margin-top: 16px;
        }

        @media (max-width: 1024px) {
            .offres-grid {
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
            
            .offres-grid {
                grid-template-columns: 1fr;
            }
            
            .offre-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
        </style>
        <?php
    }

    /**
     * Rendu des scripts JavaScript
     */
    private function renderScripts(): void
    {
        ?>
        <script>
        function resetFilters() {
            window.location.href = '<?= base_url('offres') ?>';
        }
        </script>
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
