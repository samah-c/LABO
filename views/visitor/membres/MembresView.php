<?php
/**
 * MembresView.php - Vue de la liste des membres
 * À placer dans : /TDW_project/app/views/public/MembresView.php
 */

require_once __DIR__ . '/../../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/NavigationComponent.php';
require_once __DIR__ . '/../../../lib/components/FilterComponent.php';
require_once __DIR__ . '/../../../lib/components/TableComponent.php';
require_once __DIR__ . '/../../../lib/components/FooterComponent.php';

class MembresView
{
    private array $membres;
    private ?array $pagination;
    private array $stats;

    public function __construct(array $membres, ?array $pagination = null)
    {
        $this->membres = $membres;
        $this->pagination = $pagination;
        $this->calculateStats();
    }

    /**
     * Calcule les statistiques
     */
    private function calculateStats(): void
    {
        $this->stats = [
            'total' => count($this->membres),
            'enseignants' => count(array_filter($this->membres, fn($m) => 
                ($m['poste_normalized'] ?? '') === 'enseignant'
            )),
            'doctorants' => count(array_filter($this->membres, fn($m) => 
                ($m['poste_normalized'] ?? '') === 'doctorant'
            )),
            'etudiants' => count(array_filter($this->membres, fn($m) => 
                ($m['poste_normalized'] ?? '') === 'etudiant'
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
        $this->renderMembresGrid();
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
            'title' => 'Membres du Laboratoire - Laboratoire TDW',
            'role' => 'visiteur',
            'showLogout' => false,
            'showLoginButton' => true,
            'additionalJs' => [base_url('assets/js/visitor/membres-handler.js')]
        ]);
    }

    /**
     * Rendu de la navigation
     */
    private function renderNavigation(): void
    {
        NavigationComponent::renderHorizontalMenu('membres');
    }

    /**
     * Rendu de la bannière
     */
    private function renderBanner(): void
    {
        ?>
        <section class="page-banner">
            <div class="banner-content">
                <h1>Membres du Laboratoire</h1>
                <p>Découvrez notre équipe de chercheurs et enseignants</p>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu des filtres
     */
    private function renderFilters(): void
    {
        // Extraire les équipes uniques
        $equipes = array_unique(array_filter(array_column($this->membres, 'equipe_nom')));
        sort($equipes);
        $equipesOptions = array_combine($equipes, $equipes);

        FilterComponent::render([
            'action' => base_url('membres'),
            'method' => 'GET',
            'showSearch' => true,
            'searchPlaceholder' => 'Rechercher un membre...',
            'filters' => [
                [
                    'type' => 'select',
                    'name' => 'poste',
                    'label' => 'Poste',
                    'options' => [
                        'enseignant' => 'Enseignant',
                        'doctorant' => 'Doctorant',
                        'etudiant' => 'Étudiant',
                        'invite' => 'Invité'
                    ],
                    'defaultLabel' => 'Tous'
                ],
                [
                    'type' => 'select',
                    'name' => 'equipe',
                    'label' => 'Équipe',
                    'options' => $equipesOptions,
                    'defaultLabel' => 'Toutes'
                ],
                [
                    'type' => 'select',
                    'name' => 'grade',
                    'label' => 'Grade',
                    'options' => [
                        'Professeur' => 'Professeur',
                        'Maître de conférences A' => 'Maître de conférences A',
                        'Maître de conférences B' => 'Maître de conférences B',
                        'Doctorant' => 'Doctorant',
                        'Étudiant' => 'Étudiant'
                    ],
                    'defaultLabel' => 'Tous'
                ],
                [
                    'type' => 'select',
                    'name' => 'sort',
                    'label' => 'Trier par',
                    'options' => [
                        'name' => 'Nom (A-Z)',
                        'poste' => 'Poste',
                        'equipe' => 'Équipe'
                    ],
                    'defaultLabel' => 'Nom (A-Z)'
                ]
            ]
        ]);
    }


    /**
     * Rendu de la grille des membres
     */
    private function renderMembresGrid(): void
    {
        if (empty($this->membres)) {
            $this->renderEmptyState();
            return;
        }

        ?>
        <div id="membres-container" class="membres-grid">
            <?php foreach ($this->membres as $membre): ?>
                <?php $this->renderMembreCard($membre); ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Rendu d'une carte membre
     */
    private function renderMembreCard(array $membre): void
    {
        $posteNormalized = $membre['poste_normalized'] ?? '';
        $equipeNom = $membre['equipe_nom'] ?? '';
        $grade = $membre['grade'] ?? '';
        
        // Couleurs par poste
        $posteColors = [
            'enseignant' => '#3B82F6',
            'doctorant' => '#8B5CF6',
            'etudiant' => '#10B981',
            'invite' => '#F59E0B'
        ];
        $badgeColor = $posteColors[$posteNormalized] ?? '#6B7280';
        
        // Initiales pour l'avatar
        $initiales = strtoupper(substr($membre['username'] ?? 'U', 0, 2));
        ?>
        
        <article class="membre-card" 
                 data-membre-id="<?= htmlspecialchars($membre['id']) ?>"
                 data-poste="<?= htmlspecialchars($posteNormalized) ?>"
                 data-equipe="<?= htmlspecialchars($equipeNom) ?>"
                 data-grade="<?= htmlspecialchars($grade) ?>"
                 data-name="<?= htmlspecialchars(strtolower($membre['username'] ?? '')) ?>">
            
            <div class="membre-avatar">
                <?php if (!empty($membre['photo'])): ?>
                    <img src="<?= base_url('uploads/photos/' . $membre['photo']) ?>" 
                         alt="<?= htmlspecialchars($membre['username']) ?>">
                <?php else: ?>
                    <div class="avatar-placeholder">
                        <?= $initiales ?>
                    </div>
                <?php endif; ?>
                
                <span class="membre-status-badge" style="background: <?= $badgeColor ?>;">
                    <?= htmlspecialchars(ucfirst($membre['poste'] ?? '')) ?>
                </span>
            </div>
            
            <div class="membre-info">
                <h3 class="membre-name">
                    <a href="<?= base_url('membres/' . $membre['id']) ?>">
                        <?= htmlspecialchars($membre['username']) ?>
                    </a>
                </h3>
                
                <?php if (!empty($grade)): ?>
                <div class="membre-grade">
                    <?= htmlspecialchars($grade) ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($equipeNom)): ?>
                <div class="membre-equipe">
                    <?= htmlspecialchars($equipeNom) ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($membre['biographie'])): ?>
                <p class="membre-bio">
                    <?= truncate($membre['biographie'], 120) ?>
                </p>
                <?php endif; ?>
            </div>
            
            <div class="membre-stats">
                <div class="stat-item">
                    <span class="stat-value"><?= $membre['nb_projets'] ?? 0 ?></span>
                    <span class="stat-label">Projets</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= $membre['nb_publications'] ?? 0 ?></span>
                    <span class="stat-label">Publications</span>
                </div>
            </div>
            
            <div class="membre-footer">
                <?php if (!empty($membre['email'])): ?>
                <a href="mailto:<?= htmlspecialchars($membre['email']) ?>" 
                   class="btn-link"
                   title="Envoyer un email">
                    Email
                </a>
                <?php endif; ?>
                
                <a href="<?= base_url('membres/' . $membre['id']) ?>" 
                   class="btn-primary">
                    Voir profil
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
            <h3>Aucun membre disponible</h3>
            <p class="text-muted">Aucun membre n'est actuellement affiché.</p>
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
            Aucun membre ne correspond à vos critères de recherche.
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

        .membres-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
            margin: 32px 0 40px;
        }

        .membre-card {
            background: var(--bg-card);
            border-radius: var(--border-radius-lg);
            border: 1px solid var(--border-color);
            padding: 24px;
            transition: var(--transition);
            box-shadow: var(--shadow-xs);
            display: flex;
            flex-direction: column;
            gap: 16px;
            text-align: center;
        }

        .membre-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
            border-color: var(--primary);
        }

        .membre-avatar {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 12px;
        }

        .membre-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--border-color);
        }

        .avatar-placeholder {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: 700;
            border: 4px solid var(--border-color);
        }

        .membre-status-badge {
            position: absolute;
            bottom: 0;
            right: 0;
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 11px;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 3px solid white;
        }

        .membre-info {
            flex: 1;
        }

        .membre-name {
            margin: 0 0 8px 0;
            font-size: 20px;
            font-weight: 600;
        }

        .membre-name a {
            color: var(--gray-900);
            text-decoration: none;
            transition: var(--transition);
        }

        .membre-name a:hover {
            color: var(--primary);
        }

        .membre-grade {
            font-size: 14px;
            font-weight: 500;
            color: var(--primary);
            margin-bottom: 6px;
        }

        .membre-equipe {
            font-size: 14px;
            color: var(--gray-600);
            margin-bottom: 12px;
            padding: 4px 12px;
            background: var(--gray-100);
            border-radius: 12px;
            display: inline-block;
        }

        .membre-bio {
            line-height: 1.6;
            margin: 12px 0 0 0;
            font-size: 14px;
            color: var(--gray-600);
            text-align: left;
        }

        .membre-stats {
            display: flex;
            justify-content: space-around;
            padding: 16px 0;
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-label {
            font-size: 12px;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .membre-footer {
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: center;
        }

        .btn-link {
            padding: 8px 16px;
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

        .membre-footer .btn-primary {
            padding: 8px 20px;
            font-size: 14px;
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
            .membres-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .banner-content h1 {
                font-size: 32px;
            }
            
            .banner-content p {
                font-size: 16px;
            }
            
            .membres-grid {
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
        // Fonction de réinitialisation des filtres
        function resetFilters() {
            window.location.href = '<?= base_url('membres') ?>';
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