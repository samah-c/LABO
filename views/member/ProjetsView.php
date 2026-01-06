<?php
/**
 * Vue de la liste des projets du membre
 */
require_once __DIR__ . '/../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../lib/components/NavigationComponent.php';
require_once __DIR__ . '/../../lib/components/PageHeaderComponent.php';
require_once __DIR__ . '/../../lib/components/FilterComponent.php';
require_once __DIR__ . '/../../lib/components/FooterComponent.php';
class ProjetsView
{
    private array $projets;
    private ?array $pagination;

    public function __construct(array $projets, ?array $pagination = null)
    {
        $this->projets = $projets;
        $this->pagination = $pagination;
    }

    public function render(): void
    {
        $this->renderHeader();
        $this->renderNavigation();
        echo '<div class="container">';
        $this->renderBreadcrumbs();
        $this->renderPageHeader();
        $this->renderFilters();
        $this->renderProjectsGrid();
        $this->renderPagination();
        echo '</div>';
        $this->renderStyles();
        $this->renderFooter();
    }

    private function renderHeader(): void
    {
        HeaderComponent::render([
            'title' => 'Mes Projets - Espace Membre',
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
            ['label' => 'Mes Projets']
        ]);
    }

    private function renderPageHeader(): void
    {
        PageHeaderComponent::render([
            'title' => 'Mes Projets',
            'subtitle' => 'Liste de tous mes projets de recherche',
            'actions' => []
        ]);
    }

    private function renderFilters(): void
    {
        FilterComponent::render([
            'action' => base_url('membre/projets'),
            'showSearch' => true,
            'searchPlaceholder' => 'Rechercher un projet...',
            'filters' => [
                [
                    'type' => 'select',
                    'name' => 'statut',
                    'label' => 'Statut',
                    'options' => [
                        'en_cours' => 'En cours',
                        'termine' => 'Terminé',
                        'soumis' => 'Soumis'
                    ],
                    'defaultLabel' => 'Tous les statuts'
                ]
            ]
        ]);
    }

    private function renderProjectsGrid(): void
    {
        if (empty($this->projets)) {
            $this->renderEmptyState();
            return;
        }
        ?>
        <div class="projets-grid">
            <?php foreach ($this->projets as $projet): ?>
                <?php $this->renderProjectCard($projet); ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private function renderProjectCard(array $projet): void
    {
        ?>
        <div class="projet-card">
            <div class="projet-header">
                <h3><?= e($projet['titre']) ?></h3>
                <span class="badge badge-<?= e($projet['status']) ?>">
                    <?= ucfirst(str_replace('_', ' ', $projet['status'])) ?>
                </span>
            </div>

            <div class="projet-meta">
                <div class="meta-item">
                    <span class="meta-label">Thématique</span>
                    <span class="meta-value"><?= e($projet['thematique']) ?></span>
                </div>
                
                <div class="meta-item">
                    <span class="meta-label">Mon rôle</span>
                    <span class="meta-value"><?= e($projet['role_dans_projet'] ?? 'Participant') ?></span>
                </div>
                
                <?php if (!empty($projet['date_debut'])): ?>
                <div class="meta-item">
                    <span class="meta-label">Début</span>
                    <span class="meta-value"><?= format_date($projet['date_debut'], 'd/m/Y') ?></span>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($projet['description'])): ?>
            <div class="projet-description">
                <?= e(substr($projet['description'], 0, 150)) ?>
                <?= strlen($projet['description']) > 150 ? '...' : '' ?>
            </div>
            <?php endif; ?>

            <div class="projet-footer">
                <a href="<?= base_url('membre/projets/' . $projet['id']) ?>" class="btn-view">
                    Voir les détails
                </a>
            </div>
        </div>
        <?php
    }

    private function renderEmptyState(): void
    {
        ?>
        <div class="empty-state">
            <h3>Aucun projet trouvé</h3>
            <p>Vous ne participez à aucun projet pour le moment.</p>
        </div>
        <?php
    }

    private function renderPagination(): void
    {
        if ($this->pagination && $this->pagination['total_pages'] > 1) {
            echo Utils::renderPagination($this->pagination, base_url('membre/projets'));
        }
    }

    private function renderStyles(): void
    {
        ?>
        <style>
        /* Grille des projets */
        .projets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 20px;
            margin-top: 24px;
        }

        .projet-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
        }

        .projet-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .projet-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
            gap: 12px;
        }

        .projet-header h3 {
            margin: 0;
            font-size: 17px;
            font-weight: 600;
            color: var(--gray-900);
            flex: 1;
            line-height: 1.4;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .badge-en_cours {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-termine {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-soumis {
            background: #fef3c7;
            color: #92400e;
        }

        .projet-meta {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 16px;
            padding: 12px;
            background: var(--gray-50);
            border-radius: var(--border-radius-sm);
        }

        .meta-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
        }

        .meta-label {
            color: var(--gray-600);
            font-weight: 500;
        }

        .meta-value {
            color: var(--gray-900);
            font-weight: 600;
        }

        .projet-description {
            color: var(--gray-700);
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 16px;
            flex: 1;
        }

        .projet-footer {
            display: flex;
            justify-content: flex-end;
            padding-top: 16px;
            border-top: 1px solid var(--border-color);
        }

        .btn-view {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            font-size: 14px;
            transition: var(--transition);
            padding: 8px 16px;
            border-radius: var(--border-radius-sm);
        }

        .btn-view:hover {
            background: var(--primary);
            color: white;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm);
            margin-top: 24px;
        }

        .empty-state h3 {
            margin: 0 0 8px 0;
            color: var(--gray-900);
            font-size: 18px;
        }

        .empty-state p {
            color: var(--gray-600);
            margin: 0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .projets-grid {
                grid-template-columns: 1fr;
            }

            .projet-header {
                flex-direction: column;
                align-items: flex-start;
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