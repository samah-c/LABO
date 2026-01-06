<?php
/**
 * Vue du détail d'un équipement (visiteur)
 */

require_once __DIR__ . '/../../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/NavigationComponent.php';
require_once __DIR__ . '/../../../lib/components/FooterComponent.php';
require_once __DIR__ . '/../../../lib/LabHelpers.php';

class EquipementDetailView
{
    private array $equipement;
    private ?array $stats;

    public function __construct(array $equipement, ?array $stats = null)
    {
        $this->equipement = $equipement;
        $this->stats = $stats;
    }

    /**
     * Rendu complet de la vue
     */
    public function render(): void
    {
        $this->renderHeader();
        $this->renderNavigation();
        echo '<div class="visitor-container">';
        echo '<div class="container detail-container">';
        $this->renderBreadcrumb();
        $this->renderEquipementHeader();
        $this->renderDetailLayout();
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
            'title' => $this->equipement['nom'] . ' - Laboratoire TDW',
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
     * Rendu du fil d'Ariane
     */
    private function renderBreadcrumb(): void
    {
        NavigationComponent::renderBreadcrumbs([
            ['url' => base_url(), 'label' => 'Accueil'],
            ['url' => base_url('equipements'), 'label' => 'Équipements'],
            ['label' => 'Détail']
        ]);
    }

    /**
     * Rendu de l'en-tête de l'équipement
     */
    private function renderEquipementHeader(): void
    {
        $eq = $this->equipement;
        $etatBadges = [
            'libre' => '<span class="badge badge-success">Disponible</span>',
            'reserve' => '<span class="badge badge-info">Réservé</span>',
            'en_maintenance' => '<span class="badge badge-warning">En maintenance</span>',
            'hors_service' => '<span class="badge badge-danger">Hors service</span>'
        ];
        $badge = $etatBadges[$eq['etat']] ?? '';
        ?>
        <div class="equipement-detail-header">
            <div class="equipement-type-badge">
                <?= e($eq['type_equipement']) ?>
            </div>
            
            <h1><?= e($eq['nom']) ?></h1>
            
            <div class="equipement-meta-header">
                <?= $badge ?>
                
                <?php if (!empty($eq['numero_serie'])): ?>
                <span class="meta-item">
                    N° série: <code><?= e($eq['numero_serie']) ?></code>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu du layout principal
     */
    private function renderDetailLayout(): void
    {
        ?>
        <div class="detail-layout">
            <div class="main-content">
                <?php 
                $this->renderDescription();
                $this->renderTechnicalInfo();
                $this->renderStatistics();
                ?>
            </div>
            
            <aside class="sidebar-content">
                <?php 
                $this->renderTeamInfo();
                $this->renderBackButton();
                ?>
            </aside>
        </div>
        <?php
    }

    /**
     * Rendu de la description
     */
    private function renderDescription(): void
    {
        if (empty($this->equipement['description'])) {
            return;
        }
        ?>
        <section class="detail-card">
            <h2>Description</h2>
            <div class="description-content">
                <?= nl2br(e($this->equipement['description'])) ?>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu des informations techniques
     */
    private function renderTechnicalInfo(): void
    {
        $eq = $this->equipement;
        $etats = [
            'libre' => 'Disponible',
            'reserve' => 'Réservé',
            'en_maintenance' => 'En maintenance',
            'hors_service' => 'Hors service'
        ];
        ?>
        <section class="detail-card">
            <h2>Informations techniques</h2>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Type d'équipement</strong>
                    <span><?= e($eq['type_equipement']) ?></span>
                </div>
                
                <?php if (!empty($eq['numero_serie'])): ?>
                <div class="info-item">
                    <strong>Numéro de série</strong>
                    <span><code><?= e($eq['numero_serie']) ?></code></span>
                </div>
                <?php endif; ?>
                
                <div class="info-item">
                    <strong>État actuel</strong>
                    <span><?= $etats[$eq['etat']] ?? e($eq['etat']) ?></span>
                </div>
                
                <?php if (!empty($eq['localisation'])): ?>
                <div class="info-item">
                    <strong>Localisation</strong>
                    <span><?= e($eq['localisation']) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($eq['date_acquisition'])): ?>
                <div class="info-item">
                    <strong>Date d'acquisition</strong>
                    <span><?= format_date($eq['date_acquisition'], 'd F Y') ?></span>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu des statistiques
     */
    private function renderStatistics(): void
    {
        if (!$this->stats) {
            return;
        }
        ?>
        <section class="detail-card">
            <h2>Statistiques d'utilisation</h2>
            <div class="stats-list">
                <div class="stat-item">
                    <span class="stat-label">Réservations totales</span>
                    <span class="stat-value"><?= $this->stats['nb_reservations_total'] ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Réservations actives</span>
                    <span class="stat-value"><?= $this->stats['nb_reservations_actives'] ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Taux d'utilisation</span>
                    <span class="stat-value"><?= $this->stats['taux_utilisation'] ?>%</span>
                </div>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu des informations d'équipe
     */
    private function renderTeamInfo(): void
    {
        if (empty($this->equipement['equipe_nom'])) {
            return;
        }
        ?>
        <section class="detail-card">
            <h2>Équipe assignée</h2>
            <div class="equipe-card">
                <h3><?= e($this->equipement['equipe_nom']) ?></h3>
                <?php if (!empty($this->equipement['equipe_id'])): ?>
                <a href="<?= base_url('equipes/' . $this->equipement['equipe_id']) ?>" 
                   class="btn-link mt-md">
                    Voir l'équipe
                </a>
                <?php endif; ?>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu du bouton retour
     */
    private function renderBackButton(): void
    {
        ?>
        <a href="<?= base_url('equipements') ?>" class="btn-secondary btn-block">
            Retour aux équipements
        </a>
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
        .detail-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .breadcrumbs {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            color: var(--gray-600);
            padding: 12px;
            background: white;
            border-radius: 8px;
            box-shadow: var(--shadow-xs);
        }

        .breadcrumbs a {
            color: var(--primary);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumbs a:hover {
            color: var(--primary-dark);
        }

        .breadcrumbs .separator {
            color: var(--gray-400);
            user-select: none;
        }

        .breadcrumbs .current {
            color: var(--gray-900);
            font-weight: 500;
        }

        .equipement-detail-header {
            background: white;
            padding: 40px;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: 30px;
            text-align: center;
        }

        .equipement-detail-header h1 {
            font-size: 32px;
            font-weight: 700;
            color: var(--gray-900);
            margin: 20px 0;
            line-height: 1.3;
        }

        .equipement-type-badge {
            display: inline-block;
            padding: 8px 16px;
            background: var(--gray-100);
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-700);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .equipement-meta-header {
            display: flex;
            justify-content: center;
            gap: 24px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .meta-item {
            font-size: 15px;
            color: var(--gray-600);
        }

        .meta-item code {
            background: var(--gray-100);
            padding: 2px 8px;
            border-radius: 4px;
            font-family: monospace;
        }

        .detail-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
        }

        .detail-card {
            background: white;
            padding: 30px;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
        }

        .detail-card h2 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--gray-900);
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 12px;
        }

        .description-content {
            line-height: 1.8;
            color: var(--gray-700);
            font-size: 16px;
        }

        .info-grid {
            display: grid;
            gap: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding: 12px;
            background: var(--gray-50);
            border-radius: 8px;
        }

        .info-item strong {
            color: var(--gray-600);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-item span {
            color: var(--gray-900);
            font-size: 15px;
        }

        .info-item code {
            background: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-family: monospace;
            border: 1px solid var(--border-color);
        }

        .stats-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            background: var(--gray-50);
            border-radius: 8px;
        }

        .stat-label {
            color: var(--gray-600);
            font-weight: 500;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
        }

        .equipe-card {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .equipe-card h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0;
        }

        .btn-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: var(--transition);
        }

        .btn-link:hover {
            color: var(--primary-dark);
        }

        .btn-block {
            display: block;
            width: 100%;
            text-align: center;
            padding: 12px;
            border-radius: 8px;
        }

        .mt-md {
            margin-top: 12px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .detail-layout {
                grid-template-columns: 1fr;
            }
            
            .sidebar-content {
                order: -1;
            }
        }

        @media (max-width: 768px) {
            .equipement-detail-header {
                padding: 24px 20px;
            }
            
            .equipement-detail-header h1 {
                font-size: 24px;
            }
            
            .equipement-meta-header {
                flex-direction: column;
                gap: 12px;
            }
            
            .detail-card {
                padding: 20px;
            }

            .breadcrumbs {
                font-size: 12px;
            }
        }
        </style>
        <?php
    }
}