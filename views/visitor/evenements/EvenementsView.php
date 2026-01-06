<?php
/**
 * Vue de la liste des événements (visiteur)
 */

require_once __DIR__ . '/../../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/NavigationComponent.php';
require_once __DIR__ . '/../../../lib/components/FooterComponent.php';
require_once __DIR__ . '/../../../lib/components/TableComponent.php';
require_once __DIR__ . '/../../../lib/LabHelpers.php';
class EvenementsView
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
        $this->renderPageBanner();
        echo '<div class="container">';
        $this->renderFilters();
        $this->renderStatistics();
        $this->renderEvenementsList();
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
            'title' => 'Événements du Laboratoire - TDW',
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
     * Rendu de la bannière
     */
    private function renderPageBanner(): void
    {
        ?>
        <section class="page-banner">
            <div class="banner-content">
                <h1>Événements du Laboratoire</h1>
                <p>Découvrez nos conférences, séminaires et événements scientifiques</p>
            </div>
        </section>
        <?php
    }

  private function renderFilters(): void
{
    ?>
  <div class="filters-bar">
    <div class="filters-row-main">
        <div class="search-box-full">
            <input type="text" 
                   id="search-input" 
                   placeholder="🔍 Rechercher un événement..."
                   value="<?= e(get('search', '')) ?>">
        </div>
    </div>
    
    <div class="filters-row-secondary">
        <div class="filter-item">
                <label for="filter-type">Type d'événement</label>
                <select id="filter-type" class="filter-select">
                    <option value="">Tous les types</option>
                    <option value="conference">Conférence</option>
                    <option value="seminaire">Séminaire</option>
                    <option value="workshop">Workshop</option>
                    <option value="soutenance">Soutenance</option>
                    <option value="colloque">Colloque</option>
                    <option value="reunion">Réunion</option>
                </select>
            </div>
            
            <div class="filter-item">
                <label for="filter-mois">Mois</label>
                <select id="filter-mois" class="filter-select">
                    <option value="">Tous les mois</option>
                    <?php
                    $mois = [
                        '01' => 'Janvier', '02' => 'Février', '03' => 'Mars',
                        '04' => 'Avril', '05' => 'Mai', '06' => 'Juin',
                        '07' => 'Juillet', '08' => 'Août', '09' => 'Septembre',
                        '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'
                    ];
                    foreach ($mois as $num => $nom):
                    ?>
                        <option value="<?= date('Y') . '-' . $num ?>"><?= $nom ?> <?= date('Y') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-item">
                <label for="sort-by">Trier par</label>
                <select id="sort-by" class="filter-select">
                    <option value="date_asc">Date (Plus proche)</option>
                    <option value="date_desc">Date (Plus éloigné)</option>
                    <option value="titre">Titre (A-Z)</option>
                </select>
            </div>
        </div>
    </div>
    <?php
}

    /**
     * Rendu des statistiques
     */
    private function renderStatistics(): void
    {
        $aujourd_hui = date('Y-m-d');
        
        $stats = [
            [
                'label' => 'Événements Total',
                'value' => count($this->evenements)
            ],
            [
                'label' => 'À venir',
                'value' => count(array_filter($this->evenements, fn($e) => 
                    ($e['date_evenement'] ?? '') >= $aujourd_hui
                ))
            ],
            [
                'label' => 'Ce mois-ci',
                'value' => count(array_filter($this->evenements, fn($e) => 
                    date('Y-m', strtotime($e['date_evenement'] ?? 'now')) === date('Y-m')
                ))
            ],
            [
                'label' => 'Passés',
                'value' => count(array_filter($this->evenements, fn($e) => 
                    ($e['date_evenement'] ?? '') < $aujourd_hui
                ))
            ]
        ];

        TableComponent::renderStatsCards($stats);
    }

    /**
     * Rendu de la liste des événements
     */
    private function renderEvenementsList(): void
    {
        TableComponent::renderCardGrid([
            'items' => $this->evenements,
            'cardRenderer' => [$this, 'renderEvenementCard'],
            'emptyMessage' => 'Aucun événement disponible',
            'gridClass' => 'evenements-grid'
        ]);
        
        ?>
        <div id="no-results" class="empty-message" style="display: none;">
            Aucun événement ne correspond à vos critères de recherche.
            <button onclick="resetFilters()" class="btn-secondary mt-md">
                Réinitialiser les filtres
            </button>
        </div>
        <?php
    }

    /**
     * Rendu d'une carte événement
     */
    public function renderEvenementCard(array $event): string
    {
        $type = $event['type_evenement'] ?? 'conference';
        $dateEvenement = $event['date_evenement'] ?? '';
        $heureDebut = $event['heure_debut'] ?? '';
        $lieu = $event['lieu'] ?? 'À déterminer';
        $organisateur = $event['organisateur_nom'] ?? 'Non spécifié';
        
        // Déterminer si l'événement est passé ou à venir
        $aujourd_hui = date('Y-m-d');
        $estPasse = $dateEvenement < $aujourd_hui;
        $estAujourdhui = $dateEvenement === $aujourd_hui;
        
        // Badges de type
        $typeBadges = [
            'conference' => '<span class="badge badge-primary">Conférence</span>',
            'seminaire' => '<span class="badge badge-info">Séminaire</span>',
            'workshop' => '<span class="badge badge-success">Workshop</span>',
            'soutenance' => '<span class="badge badge-warning">Soutenance</span>',
            'colloque' => '<span class="badge badge-purple">Colloque</span>',
            'reunion' => '<span class="badge badge-secondary">Réunion</span>'
        ];
        $badge = $typeBadges[$type] ?? '<span class="badge badge-secondary">' . ucfirst($type) . '</span>';
        
        // Badge de statut temporel
        $statusBadge = '';
        if ($estAujourdhui) {
            $statusBadge = '<span class="badge badge-danger pulse">Aujourd\'hui</span>';
        } elseif (!$estPasse) {
            $statusBadge = '<span class="badge badge-success">À venir</span>';
        } else {
            $statusBadge = '<span class="badge badge-muted">Passé</span>';
        }
        
        ob_start();
        ?>
        <article class="evenement-card <?= $estPasse ? 'event-past' : 'event-upcoming' ?>" 
                 data-type="<?= e($type) ?>"
                 data-date="<?= e($dateEvenement) ?>"
                 data-titre="<?= e(strtolower($event['titre'] ?? '')) ?>">
            
            <div class="evenement-header">
                <div class="evenement-badges">
                    <?= $badge ?>
                    <?= $statusBadge ?>
                </div>
            </div>
            
            <div class="evenement-date-card">
                <div class="date-jour"><?= date('d', strtotime($dateEvenement)) ?></div>
                <div class="date-mois"><?= date('M', strtotime($dateEvenement)) ?></div>
                <div class="date-annee"><?= date('Y', strtotime($dateEvenement)) ?></div>
            </div>
            
            <h3 class="evenement-title">
                <a href="<?= base_url('evenements/' . $event['id']) ?>">
                    <?= e($event['titre']) ?>
                </a>
            </h3>
            
            <?php if (!empty($event['description'])): ?>
            <p class="evenement-description">
                <?= truncate($event['description'], 120) ?>
            </p>
            <?php endif; ?>
            
            <div class="evenement-meta">
                <div class="meta-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <span><?= $heureDebut ? date('H:i', strtotime($heureDebut)) : 'Heure à définir' ?></span>
                </div>
                
                <div class="meta-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    <span><?= e($lieu) ?></span>
                </div>
                
                <?php if ($organisateur && $organisateur !== 'Non spécifié'): ?>
                <div class="meta-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <span><?= e($organisateur) ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="evenement-footer">
                <a href="<?= base_url('evenements/' . $event['id']) ?>" 
                   class="btn-primary btn-sm">
                    Voir les détails
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
        $this->renderScript();
    }

    /**
     * Script pour le filtrage côté client
     */
    private function renderScript(): void
    {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-input');
            const filterType = document.getElementById('filter-type');
            const filterMois = document.getElementById('filter-mois');
            const sortBy = document.getElementById('sort-by');
            const cards = document.querySelectorAll('.evenement-card');
            const noResults = document.getElementById('no-results');

            function filterEvents() {
                const searchTerm = searchInput.value.toLowerCase();
                const selectedType = filterType.value;
                const selectedMois = filterMois.value;
                let visibleCount = 0;

                cards.forEach(card => {
                    const titre = card.dataset.titre || '';
                    const type = card.dataset.type || '';
                    const date = card.dataset.date || '';
                    const mois = date.substring(0, 7);

                    const matchSearch = titre.includes(searchTerm);
                    const matchType = !selectedType || type === selectedType;
                    const matchMois = !selectedMois || mois === selectedMois;

                    if (matchSearch && matchType && matchMois) {
                        card.style.display = '';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                noResults.style.display = visibleCount === 0 ? 'block' : 'none';
            }

            function sortEvents() {
                const container = document.querySelector('.evenements-grid');
                const cardsArray = Array.from(cards);
                const sortValue = sortBy.value;

                cardsArray.sort((a, b) => {
                    if (sortValue === 'date_asc') {
                        return (a.dataset.date || '').localeCompare(b.dataset.date || '');
                    } else if (sortValue === 'date_desc') {
                        return (b.dataset.date || '').localeCompare(a.dataset.date || '');
                    } else if (sortValue === 'titre') {
                        return (a.dataset.titre || '').localeCompare(b.dataset.titre || '');
                    }
                    return 0;
                });

                cardsArray.forEach(card => container.appendChild(card));
            }

            searchInput.addEventListener('input', filterEvents);
            filterType.addEventListener('change', filterEvents);
            filterMois.addEventListener('change', filterEvents);
            sortBy.addEventListener('change', () => {
                sortEvents();
                filterEvents();
            });

            window.resetFilters = function() {
                searchInput.value = '';
                filterType.value = '';
                filterMois.value = '';
                sortBy.value = 'date_asc';
                filterEvents();
            };
        });
        </script>
        <?php
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

.filters-row {
    margin-bottom: 20px;
}

.filters-row:last-child {
    margin-bottom: 0;
}

.search-box-full {
    width: 100%;
}

.search-box-full input {
    width: 700px;
    padding: 14px 20px;
    border: 2px solid var(--border-color);
    border-radius: 10px;
    font-size: 15px;
    transition: var(--transition);
}

.filters-row-main {
    margin-bottom: 20px;
}

.filters-row-secondary {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}

@media (max-width: 768px) {
    .filters-row-secondary {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .filters-row:last-child {
        grid-template-columns: 1fr;
    }
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
            border-color: #667eea;
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
            color: #667eea;
            margin: 0;
        }

        .evenements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .evenement-card {
            background: white;
            border-radius: var(--border-radius-lg);
            border: 1px solid var(--border-color);
            padding: 24px;
            transition: var(--transition);
            box-shadow: var(--shadow-xs);
            display: flex;
            flex-direction: column;
            gap: 16px;
            position: relative;
        }

        .evenement-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
            border-color: #667eea;
        }

        .event-past {
            opacity: 0.7;
        }

        .evenement-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .evenement-badges {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .evenement-date-card {
    position: absolute;
    top: 24px;
    right: 24px;
    background: var(--gray-100);
    color: var(--gray-700);
    padding: 8px;
    border-radius: 8px;
    text-align: center;
    box-shadow: var(--shadow-sm);
    min-width: 55px;
    border: 1px solid var(--border-color);
}

.date-jour {
    font-size: 20px;
    font-weight: 700;
    line-height: 1;
    color: var(--gray-900);
}

.date-mois {
    font-size: 11px;
    text-transform: uppercase;
    margin-top: 2px;
    font-weight: 600;
    color: var(--gray-600);
}

.date-annee {
    font-size: 10px;
    margin-top: 2px;
    color: var(--gray-500);
}
        .badge-purple {
            background: #9333ea;
            color: white;
        }

        .badge-muted {
            background: var(--gray-400);
            color: white;
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .evenement-title {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            line-height: 1.4;
            padding-right: 80px;
        }

        .evenement-title a {
            color: var(--gray-900);
            text-decoration: none;
            transition: var(--transition);
        }

        .evenement-title a:hover {
            color: #667eea;
        }

        .evenement-description {
            line-height: 1.6;
            margin: 0;
            font-size: 14px;
            color: var(--gray-600);
        }

        .evenement-meta {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--gray-700);
        }

        .meta-item svg {
            color: #667eea;
            flex-shrink: 0;
        }

        .evenement-footer {
            display: flex;
            justify-content: flex-end;
            margin-top: auto;
            padding-top: 16px;
            border-top: 1px solid var(--border-color);
        }

        .btn-sm {
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
            border-color: #667eea;
            color: #667eea;
        }

        .page-link.active {
            background: #667eea;
            border-color: #667eea;
            color: white;
        }

        @media (max-width: 1024px) {
            .evenements-grid {
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
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
            
            .filters-bar {
                padding: 20px;
            }
            
            .filters {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .evenement-date-card {
                min-width: 60px;
                padding: 10px;
            }

            .date-jour {
                font-size: 24px;
            }
        }
        </style>
        <?php
    }
}