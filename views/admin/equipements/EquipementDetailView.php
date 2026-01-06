<?php
/**
 * Vue détaillée d'un équipement
 */

require_once __DIR__ . '/../../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/NavigationComponent.php';  
require_once __DIR__ . '/../../../lib/components/PageHeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/TableComponent.php';
require_once __DIR__ . '/../../../lib/components/FilterComponent.php';
require_once __DIR__ . '/../../../lib/components/FooterComponent.php';

class EquipementDetailView
{
    private array $equipement;
    private array $stats;
    private array $creneaux;

    public function __construct(array $equipement, array $stats, array $creneaux)
    {
        $this->equipement = $equipement;
        $this->stats = $stats;
        $this->creneaux = $creneaux;
    }

    /**
     * Rendu complet de la vue
     */
    public function render(): void
    {
        $this->renderHeader();
        $this->renderNavigation();
        echo '<div class="container">';
        $this->renderBreadcrumbs();
        $this->renderPageHeader();
        echo '<div class="grid-2-cols">';
        $this->renderGeneralInfo();
        $this->renderStats();
        echo '</div>';
        $this->renderDescription();
        $this->renderReservationsHistory();
        echo '</div>';
        $this->renderModal();
        $this->renderStyles();
        $this->renderFooter();
    }

    /**
     * Rendu de l'en-tête
     */
    private function renderHeader(): void
    {
        HeaderComponent::render([
            'title' => 'Détails de l\'équipement',
            'username' => session('username'),
            'role' => 'admin',
            'additionalJs' => [
                'https://code.jquery.com/jquery-3.6.0.min.js',
                base_url('assets/js/ui.js'),
                base_url('assets/js/admin/equipements-handler.js')
            ]
        ]);
    }

    /**
     * Rendu de la navigation
     */
    private function renderNavigation(): void
    {
        NavigationComponent::renderSidebar('admin');
    }

    /**
     * Rendu du fil d'Ariane
     */
    private function renderBreadcrumbs(): void
    {
        NavigationComponent::renderBreadcrumbs([
            ['label' => 'Dashboard', 'url' => base_url('admin/dashboard')],
            ['label' => 'Équipements', 'url' => base_url('admin/equipements/equipements')],
            ['label' => htmlspecialchars($this->equipement['nom'] ?? 'Détails')]
        ]);
    }

    /**
     * Rendu de l'en-tête de page
     */
    private function renderPageHeader(): void
    {
        $subtitle = htmlspecialchars($this->equipement['type_equipement']);
        if (!empty($this->equipement['numero_serie'])) {
            $subtitle .= ' - N° série: <code>' . htmlspecialchars($this->equipement['numero_serie']) . '</code>';
        }

        PageHeaderComponent::render([
            'title' => $this->equipement['nom'],
            'subtitle' => $subtitle,
            'actions' => [
                [
                    'type' => 'button',
                    'label' => 'Modifier',
                    'onclick' => 'equipements.edit(' . $this->equipement['id'] . ')'
                ],
                [
                    'type' => 'button',
                    'label' => 'Maintenance',
                    'onclick' => 'equipements.openMaintenanceModal(' . $this->equipement['id'] . ')'
                ],
                [
                    'type' => 'button',
                    'label' => 'Supprimer',
                    'onclick' => 'equipements.delete(' . $this->equipement['id'] . ')',
                    'class' => 'btn-danger'
                ]
            ]
        ]);
    }

    /**
     * Rendu des informations générales
     */
    private function renderGeneralInfo(): void
    {
        $badges = [
            'libre' => '<span class="badge badge-success">Libre</span>',
            'reserve' => '<span class="badge badge-info">Réservé</span>',
            'en_maintenance' => '<span class="badge badge-warning">Maintenance</span>',
            'hors_service' => '<span class="badge badge-danger">Hors service</span>'
        ];
        ?>
        <div class="card">
            <div class="card-header">
                <h2>Informations générales</h2>
            </div>
            <div class="card-body">
                <div class="info-list">
                    <div class="info-item">
                        <span class="info-label">État</span>
                        <span class="info-value">
                            <?= $badges[$this->equipement['etat']] ?? '<span class="badge badge-secondary">' . htmlspecialchars($this->equipement['etat']) . '</span>' ?>
                        </span>
                    </div>
                    
                    <?php if (!empty($this->equipement['localisation'])): ?>
                    <div class="info-item">
                        <span class="info-label">Localisation</span>
                        <span class="info-value"><?= htmlspecialchars($this->equipement['localisation']) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($this->equipement['equipe_nom'])): ?>
                    <div class="info-item">
                        <span class="info-label">Équipe assignée</span>
                        <span class="info-value">
                            <a href="<?= base_url('admin/equipes/equipes/view/' . $this->equipement['equipe_id']) ?>">
                                <?= htmlspecialchars($this->equipement['equipe_nom']) ?>
                            </a>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($this->equipement['date_acquisition'])): ?>
                    <div class="info-item">
                        <span class="info-label">Date d'acquisition</span>
                        <span class="info-value"><?= format_date($this->equipement['date_acquisition']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu des statistiques
     */
    private function renderStats(): void
    {
        ?>
        <div class="card">
            <div class="card-header">
                <h2>Statistiques d'utilisation</h2>
            </div>
            <div class="card-body">
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
            </div>
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
        <div class="card" style="margin-top: 24px;">
            <div class="card-header">
                <h2>Description</h2>
            </div>
            <div class="card-body">
                <p style="line-height: 1.6; color: #374151;">
                    <?= nl2br(htmlspecialchars($this->equipement['description'])) ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu de l'historique des réservations
     */
    private function renderReservationsHistory(): void
    {
        ?>
        <div class="card" style="margin-top: 24px;">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h2>Historique des réservations (<?= count($this->creneaux) ?>)</h2>
                <a href="<?= base_url('admin/equipements/equipements/historique/' . $this->equipement['id']) ?>" 
                   class="btn-secondary btn-sm">
                    Voir tout l'historique
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($this->creneaux)): ?>
                    <p style="color: #9CA3AF; text-align: center; padding: 20px;">
                        Aucune réservation pour cet équipement
                    </p>
                <?php else: ?>
                    <div class="reservations-list">
                        <?php 
                        $creneaux_recents = array_slice($this->creneaux, 0, 10);
                        foreach ($creneaux_recents as $creneau): 
                        ?>
                            <div class="reservation-item">
                                <div class="reservation-info">
                                    <div>
                                        <strong><?= htmlspecialchars($creneau['membre_nom']) ?></strong>
                                        <?php if (!empty($creneau['membre_poste'])): ?>
                                            <span style="color: #6B7280;"> - <?= htmlspecialchars($creneau['membre_poste']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="color: #6B7280; font-size: 14px; margin-top: 4px;">
                                        Du <?= format_date($creneau['date_debut'], 'd/m/Y H:i') ?> 
                                        au <?= format_date($creneau['date_fin'], 'd/m/Y H:i') ?>
                                    </div>
                                    <?php if (!empty($creneau['motif'])): ?>
                                        <div style="color: #6B7280; font-size: 13px; margin-top: 4px;">
                                            <?= htmlspecialchars($creneau['motif']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <?php
                                    $statut_badges = [
                                        'confirme' => '<span class="badge badge-success">Confirmé</span>',
                                        'en_attente' => '<span class="badge badge-warning">En attente</span>',
                                        'annule' => '<span class="badge badge-danger">Annulé</span>',
                                        'termine' => '<span class="badge badge-secondary">Terminé</span>'
                                    ];
                                    echo $statut_badges[$creneau['statut']] ?? '<span class="badge">' . htmlspecialchars($creneau['statut']) . '</span>';
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu de la modale
     */
    private function renderModal(): void
    {
        ModalComponent::render([
            'id' => 'equipement-modal',
            'title' => 'Action',
            'content' => '<div id="modal-form-container"></div>',
            'size' => 'large'
        ]);
    }

    /**
     * Rendu des styles CSS
     */
    private function renderStyles(): void
    {
        ?>
        <style>
        .grid-2-cols {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }

        .card {
            background: white;
            border-radius: 12px;
            border: 1px solid #E5E7EB;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #E5E7EB;
        }

        .card-header h2 {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
            margin: 0;
        }

        .card-body {
            padding: 24px;
        }

        .info-list, .stats-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .info-item, .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: #F9FAFB;
            border-radius: 8px;
        }

        .info-label, .stat-label {
            color: #6B7280;
            font-weight: 500;
        }

        .info-value {
            color: #111827;
            font-weight: 600;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #5B7FFF;
        }

        .reservations-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .reservation-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            background: #F9FAFB;
            border-radius: 8px;
            border: 1px solid #E5E7EB;
        }

        .reservation-info {
            flex: 1;
        }

        .btn-sm {
            padding: 6px 14px;
            font-size: 13px;
        }

        .btn-danger {
            background: #EF4444;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
        }

        .btn-danger:hover {
            background: #DC2626;
        }
        </style>
        <?php
    }

    /**
     * Rendu du pied de page
     */
    private function renderFooter(): void
    {
        FooterComponent::render(['role' => 'admin']);
    }
}