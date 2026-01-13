<?php
/**
 * EvenementsScientifiquesListView.php - Vue de la liste des événements scientifiques
 * À placer dans : /TDW_project/app/views/public/evenements/EvenementsScientifiquesListView.php
 */

require_once __DIR__ . '/../../../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../../../lib/components/NavigationComponent.php';
require_once __DIR__ . '/../../../../lib/components/FilterComponent.php';
require_once __DIR__ . '/../../../../lib/components/FooterComponent.php';

class EvenementsScientifiquesListView
{
    private array $evenements;
    private ?array $pagination;

    public function __construct(array $evenements, ?array $pagination = null)
    {
        $this->evenements = $evenements;
        $this->pagination = $pagination;
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
        $this->renderEvenementsGrid();
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
            'title' => 'Événements Scientifiques - Laboratoire TDW',
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
        NavigationComponent::renderHorizontalMenu('evenements');
    }

    /**
     * Rendu de la bannière
     */
    private function renderBanner(): void
    {
        ?>
        <section class="page-banner">
            <div class="banner-content">
                <h1>Événements Scientifiques</h1>
                <p>Ateliers, séminaires, conférences et colloques organisés par le laboratoire</p>
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
            'action' => base_url('evenements/scientifiques'),
            'method' => 'GET',
            'showSearch' => true,
            'searchPlaceholder' => 'Rechercher un événement scientifique...',
            'filters' => [
                [
                    'type' => 'select',
                    'name' => 'type',
                    'label' => 'Type',
                    'options' => [
                        'atelier' => 'Atelier',
                        'seminaire' => 'Séminaire',
                        'conference' => 'Conférence',
                        'colloque' => 'Colloque'
                    ],
                    'defaultLabel' => 'Tous les types'
                ],
                [
                    'type' => 'select',
                    'name' => 'periode',
                    'label' => 'Période',
                    'options' => [
                        'a_venir' => 'À venir',
                        'en_cours' => 'En cours',
                        'passes' => 'Passés'
                    ],
                    'defaultLabel' => 'Toutes les périodes'
                ],
                [
                    'type' => 'select',
                    'name' => 'sort',
                    'label' => 'Trier par',
                    'options' => [
                        'date_asc' => 'Date (croissante)',
                        'date_desc' => 'Date (décroissante)',
                        'titre' => 'Titre (A-Z)'
                    ],
                    'defaultLabel' => 'Date (croissante)'
                ]
            ]
        ]);
    }

    /**
     * Rendu de la grille des événements
     */
    private function renderEvenementsGrid(): void
    {
        if (empty($this->evenements)) {
            $this->renderEmptyState();
            return;
        }

        ?>
        <div id="evenements-container" class="evenements-grid">
            <?php foreach ($this->evenements as $evenement): ?>
                <?php $this->renderEvenementCard($evenement); ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Rendu d'une carte événement
     */
    private function renderEvenementCard(array $evenement): void
    {
        $typeScientifique = $evenement['type_scientifique'] ?? 'autre';
        $dateEvenement = $evenement['date_evenement'] ?? '';
        
        // Couleurs par type scientifique
        $typeColors = [
            'atelier' => '#3B82F6',
            'seminaire' => '#8B5CF6',
            'conference' => '#EC4899',
            'colloque' => '#F59E0B'
        ];
        $badgeColor = $typeColors[$typeScientifique] ?? '#6B7280';
        
        // Labels des types
        $typeLabels = [
            'atelier' => 'Atelier',
            'seminaire' => 'Séminaire',
            'conference' => 'Conférence',
            'colloque' => 'Colloque'
        ];
        $typeLabel = $typeLabels[$typeScientifique] ?? ucfirst($typeScientifique);
        
        ?>
        
        <article class="evenement-card" 
                 data-evenement-id="<?= htmlspecialchars($evenement['id']) ?>"
                 data-type="<?= htmlspecialchars($typeScientifique) ?>"
                 data-date="<?= htmlspecialchars($dateEvenement) ?>">
            
            <div class="evenement-header">
                <div class="evenement-date-badge">
                    <div class="date-day"><?= date('d', strtotime($dateEvenement)) ?></div>
                    <div class="date-month"><?= date('M', strtotime($dateEvenement)) ?></div>
                    <div class="date-year"><?= date('Y', strtotime($dateEvenement)) ?></div>
                </div>
                
                <span class="evenement-type-badge" style="background: <?= $badgeColor ?>;">
                    <?= htmlspecialchars($typeLabel) ?>
                </span>
            </div>
            
            <div class="evenement-info">
                <h3 class="evenement-titre">
                    <a href="<?= base_url('evenements/scientifiques/' . $evenement['id']) ?>">
                        <?= htmlspecialchars($evenement['titre']) ?>
                    </a>
                </h3>
                
                <?php if (!empty($evenement['theme_scientifique'])): ?>
                <div class="evenement-theme">
                    <?= htmlspecialchars($evenement['theme_scientifique']) ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($evenement['intervenant_principal'])): ?>
                <div class="evenement-intervenant">
                    Intervenant: <?= htmlspecialchars($evenement['intervenant_principal']) ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($evenement['description'])): ?>
                <p class="evenement-description">
                    <?= truncate($evenement['description'], 150) ?>
                </p>
                <?php endif; ?>
            </div>
            
            <div class="evenement-meta">
                <?php if (!empty($evenement['lieu'])): ?>
                <div class="meta-item">
                    <span class="meta-label">Lieu:</span>
                    <span class="meta-value"><?= htmlspecialchars($evenement['lieu']) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($evenement['organisateur_nom'])): ?>
                <div class="meta-item">
                    <span class="meta-label">Organisateur:</span>
                    <span class="meta-value"><?= htmlspecialchars($evenement['organisateur_nom']) ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="evenement-footer">
                <a href="<?= base_url('evenements/scientifiques/' . $evenement['id']) ?>" 
                   class="btn-primary">
                    Voir les détails
                </a>
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
            <h3>Aucun événement scientifique disponible</h3>
            <p class="text-muted">Aucun événement scientifique n'est actuellement programmé.</p>
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
            Aucun événement scientifique ne correspond à vos critères de recherche.
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

        .evenements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 24px;
            margin: 32px 0 40px;
        }

        .evenement-card {
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

        .evenement-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
            border-color: var(--primary);
        }

        .evenement-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
        }

        .evenement-date-badge {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 70px;
            padding: 12px;
            background: var(--primary);
            color: white;
            border-radius: var(--border-radius-sm);
            flex-shrink: 0;
        }

        .date-day {
            font-size: 28px;
            font-weight: 700;
            line-height: 1;
        }

        .date-month {
            font-size: 13px;
            text-transform: uppercase;
            font-weight: 600;
            margin-top: 4px;
        }

        .date-year {
            font-size: 12px;
            opacity: 0.9;
            margin-top: 2px;
        }

        .evenement-type-badge {
            padding: 6px 14px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .evenement-info {
            flex: 1;
        }

        .evenement-titre {
            margin: 0 0 8px 0;
            font-size: 20px;
            font-weight: 600;
        }

        .evenement-titre a {
            color: var(--gray-900);
            text-decoration: none;
            transition: var(--transition);
        }

        .evenement-titre a:hover {
            color: var(--primary);
        }

        .evenement-theme {
            font-size: 14px;
            font-weight: 500;
            color: var(--primary);
            margin-bottom: 6px;
        }

        .evenement-intervenant {
            font-size: 14px;
            color: var(--gray-600);
            margin-bottom: 12px;
            font-style: italic;
        }

        .evenement-description {
            line-height: 1.6;
            margin: 12px 0 0 0;
            font-size: 14px;
            color: var(--gray-600);
        }

        .evenement-meta {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 16px 0;
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
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

        .evenement-footer {
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: center;
        }

        .evenement-footer .btn-primary {
            padding: 10px 24px;
            font-size: 14px;
            width: 100%;
            text-align: center;
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
            .evenements-grid {
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
            
            .evenements-grid {
                grid-template-columns: 1fr;
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
            window.location.href = '<?= base_url('evenements/scientifiques') ?>';
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
