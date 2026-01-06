<?php
/**
 * Vue de la liste des équipements (visiteur)
 */

require_once __DIR__ . '/../../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/NavigationComponent.php';
require_once __DIR__ . '/../../../lib/components/FooterComponent.php';
require_once __DIR__ . '/../../../lib/components/FilterComponent.php';
require_once __DIR__ . '/../../../lib/components/TableComponent.php';
require_once __DIR__ . '/../../../lib/LabHelpers.php';

class EquipementsView
{
    private array $equipements;
    private ?array $pagination;

    public function __construct(array $equipements, ?array $pagination = null)
    {
        $this->equipements = $equipements;
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
        $this->renderPageBanner();
        echo '<div class="container">';
        $this->renderFilters();
        $this->renderEquipementsList();
        $this->renderPagination();
        echo '</div>';
        echo '</div>';
        $this->renderFooter();
    }

    /**
     * Rendu de l'en-tête
     */
    private function renderHeader(): void
    {
        HeaderComponent::render([
            'title' => 'Équipements du Laboratoire - TDW',
            'role' => 'visiteur',
            'showLogout' => false,
            'showLoginButton' => true,
            'additionalJs' => [base_url('assets/js/visitor/equipement-handler.js')]
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
    private function renderPageBanner(): void
    {
        ?>
        <section class="page-banner">
            <div class="banner-content">
                <h1>Équipements du Laboratoire</h1>
                <p>Découvrez nos équipements de recherche et infrastructures</p>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu des filtres
     */
    private function renderFilters(): void
    {
        // Récupérer les localisations uniques
        $localisations = array_unique(array_filter(array_column($this->equipements, 'localisation')));
        $localisationsOptions = array_combine($localisations, $localisations);
        
        ?>
        <div class="filters-bar">
            <div class="search-box">
                <input type="text" 
                       id="search-input" 
                       placeholder="Rechercher un équipement..."
                       value="<?= e(get('search', '')) ?>">
            </div>
            
            <div class="filters">
                <div class="filter-item">
                    <label for="filter-type">Type</label>
                    <select id="filter-type" class="filter-select">
                        <option value="">Tous</option>
                        <option value="Ordinateur">Ordinateur</option>
                        <option value="serveur">Serveur</option>
                        <option value="Imprimante">Imprimante</option>
                        <option value="Reseau">Équipement réseau</option>
                        <option value="Laboratoire">Équipement de labo</option>
                        <option value="Robot">Robot</option>
                        <option value="Salle">Salle</option>
                    </select>
                </div>
                
                <div class="filter-item">
                    <label for="filter-etat">État</label>
                    <select id="filter-etat" class="filter-select">
                        <option value="">Tous</option>
                        <option value="libre">Disponible</option>
                        <option value="reserve">Réservé</option>
                        <option value="en_maintenance">En maintenance</option>
                    </select>
                </div>
                
                <div class="filter-item">
                    <label for="filter-localisation">Localisation</label>
                    <select id="filter-localisation" class="filter-select">
                        <option value="">Toutes</option>
                        <?php foreach ($localisations as $loc): ?>
                            <option value="<?= e($loc) ?>"><?= e($loc) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-item">
                    <label for="sort-by">Trier par</label>
                    <select id="sort-by" class="filter-select">
                        <option value="nom">Nom (A-Z)</option>
                        <option value="type">Type</option>
                        <option value="etat">État</option>
                    </select>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu de la liste des équipements
     */
    private function renderEquipementsList(): void
    {
        TableComponent::renderCardGrid([
            'items' => $this->equipements,
            'cardRenderer' => [$this, 'renderEquipementCard'],
            'emptyMessage' => 'Aucun équipement disponible',
            'gridClass' => 'equipements-grid'
        ]);
        
        ?>
        <div id="no-results" class="empty-message" style="display: none;">
            Aucun équipement ne correspond à vos critères de recherche.
            <button onclick="resetFilters()" class="btn-secondary mt-md">
                Réinitialiser les filtres
            </button>
        </div>
        <?php
    }

    /**
     * Rendu d'une carte équipement
     */
    public function renderEquipementCard(array $eq): string
    {
        $typeNormalized = $eq['type_normalized'] ?? 'Autre';
        $etatNormalized = $eq['etat_normalized'] ?? 'libre';
        $localisation = $eq['localisation'] ?? 'Non spécifié';
        
        $etatBadges = [
            'libre' => '<span class="badge badge-success">Disponible</span>',
            'reserve' => '<span class="badge badge-info">Réservé</span>',
            'en_maintenance' => '<span class="badge badge-warning">Maintenance</span>',
            'hors_service' => '<span class="badge badge-danger">Hors service</span>'
        ];
        $badge = $etatBadges[$etatNormalized] ?? '';
        
        ob_start();
        ?>
        <article class="equipement-card" 
                 data-type="<?= e($typeNormalized) ?>"
                 data-etat="<?= e($etatNormalized) ?>"
                 data-localisation="<?= e($localisation) ?>"
                 data-nom="<?= e(strtolower($eq['nom'] ?? '')) ?>">
            
            <div class="equipement-header">
                <span class="equipement-type-label"><?= e($eq['type_original'] ?? $typeNormalized) ?></span>
                <?= $badge ?>
            </div>
            
            <h3 class="equipement-title">
                <a href="<?= base_url('equipements/' . $eq['id']) ?>">
                    <?= e($eq['nom']) ?>
                </a>
            </h3>
            
            <?php if (!empty($eq['description'])): ?>
            <p class="equipement-description">
                <?= truncate($eq['description'], 120) ?>
            </p>
            <?php endif; ?>
            
            <div class="equipement-meta">
                <?php if (!empty($localisation)): ?>
                <span class="meta-item">
                    <strong>Localisation:</strong> <?= e($localisation) ?>
                </span>
                <?php endif; ?>
                
                <?php if (!empty($eq['equipe_nom'])): ?>
                <span class="meta-item">
                    <strong>Équipe:</strong> <?= e($eq['equipe_nom']) ?>
                </span>
                <?php endif; ?>
            </div>
            
            <div class="equipement-footer">
                <?php if (!empty($eq['numero_serie'])): ?>
                <span class="serial-number">
                    N° <?= e($eq['numero_serie']) ?>
                </span>
                <?php endif; ?>
                
                <a href="<?= base_url('equipements/' . $eq['id']) ?>" 
                   class="btn-primary">
                    Voir détails
                </a>
            </div>
        </article>
        <?php
        return ob_get_clean();
    }

    /**
     * Rendu de la pagination
     */
    private function renderPagination(): void
    {
        if (!$this->pagination || $this->pagination['total_pages'] <= 1) {
            return;
        }

        $p = $this->pagination;
        ?>
        <div class="pagination">
            <?php if ($p['current_page'] > 1): ?>
                <a href="?page=<?= $p['current_page'] - 1 ?>" class="page-link">
                    Précédent
                </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $p['total_pages']; $i++): ?>
                <a href="?page=<?= $i ?>" 
                   class="page-link <?= $i === $p['current_page'] ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($p['current_page'] < $p['total_pages']): ?>
                <a href="?page=<?= $p['current_page'] + 1 ?>" class="page-link">
                    Suivant
                </a>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Rendu du pied de page
     */
    private function renderFooter(): void
    {
        FooterComponent::render(['role' => 'visiteur']);
        $this->renderStyles();
    }

    /**
     * Styles de la page
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

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .filters-bar {
            background: white;
            padding: 24px;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm);
            margin-bottom: 32px;
        }

        .search-box {
            margin-bottom: 20px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 15px;
            transition: var(--transition);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .filter-item {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-item label {
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-700);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-select {
            padding: 10px 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            background: white;
            cursor: pointer;
            transition: var(--transition);
        }

        .filter-select:hover,
        .filter-select:focus {
            border-color: var(--primary);
            outline: none;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: white;
            padding: 24px;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .stat-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .stat-card h3 {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-600);
            margin: 0 0 12px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card .number {
            font-size: 36px;
            font-weight: 700;
            color: var(--primary);
            margin: 0;
        }

        .equipements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .equipement-card {
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

        .equipement-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
            border-color: var(--primary);
        }

        .equipement-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .equipement-type-label {
            display: inline-block;
            padding: 6px 14px;
            background: var(--gray-100);
            border-radius: 16px;
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-700);
        }

        .equipement-title {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            line-height: 1.4;
        }

        .equipement-title a {
            color: var(--gray-900);
            text-decoration: none;
            transition: var(--transition);
        }

        .equipement-title a:hover {
            color: var(--primary);
        }

        .equipement-description {
            line-height: 1.6;
            margin: 0;
            font-size: 14px;
            color: var(--gray-600);
        }

        .equipement-meta {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 12px;
            background: var(--gray-50);
            border-radius: 8px;
        }

        .meta-item {
            font-size: 13px;
            color: var(--gray-700);
        }

        .meta-item strong {
            color: var(--gray-900);
        }

        .equipement-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 16px;
            border-top: 1px solid var(--border-color);
        }

        .serial-number {
            font-size: 12px;
            color: var(--gray-600);
            font-family: monospace;
            background: var(--gray-100);
            padding: 4px 8px;
            border-radius: 4px;
        }

        .equipement-footer .btn-primary {
            padding: 8px 16px;
            font-size: 14px;
        }

        .empty-message {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray-600);
            font-size: 16px;
        }

        .mt-md {
            margin-top: 16px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 40px;
        }

        .page-link {
            padding: 8px 16px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            text-decoration: none;
            color: var(--gray-700);
            font-weight: 500;
            transition: var(--transition);
        }

        .page-link:hover {
            background: var(--gray-50);
            border-color: var(--primary);
            color: var(--primary);
        }

        .page-link.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        @media (max-width: 1024px) {
            .equipements-grid {
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
            
            .equipements-grid {
                grid-template-columns: 1fr;
            }
            
            .filters-bar {
                padding: 20px;
            }
            
            .filters {
                flex-direction: column;
                width: 100%;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        </style>
        <?php
    }
}