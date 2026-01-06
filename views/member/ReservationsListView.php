<?php
/**
 * Vue de la liste des réservations du membre
 */
require_once __DIR__ . '/../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../lib/components/NavigationComponent.php';
require_once __DIR__ . '/../../lib/components/PageHeaderComponent.php';
require_once __DIR__ . '/../../lib/components/TableComponent.php';
require_once __DIR__ . '/../../lib/components/ModalComponent.php';
require_once __DIR__ . '/../../lib/components/FormComponent.php';
require_once __DIR__ . '/../../lib/components/FooterComponent.php';

class ReservationsListView
{
    private array $actives;
    private array $historique;
    private array $equipements;
    private array $stats;

    public function __construct(array $actives, array $historique, array $equipements, array $stats = [])
    {
        $this->actives = $actives;
        $this->historique = $historique;
        $this->equipements = $equipements;
        $this->stats = $stats;
    }

    public function render(): void
    {
        $this->renderHeader();
        $this->renderNavigation();
        echo '<div class="container">';
        $this->renderBreadcrumbs();
        $this->renderPageHeader();
        $this->renderFlashMessages();
        $this->renderStatsCards();
        $this->renderTabs();
        echo '</div>';
        $this->renderModal();
        $this->renderStyles();
        $this->renderScripts();
        $this->renderFooter();
    }

    private function renderHeader(): void
    {
        HeaderComponent::render([
            'title' => 'Mes Réservations - Espace Membre',
            'username' => session('username'),
            'role' => 'membre',
            'showLogout' => true,
            'additionalJs' => [base_url('assets/js/member/reservations-handler.js')]
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
            ['label' => 'Mes Réservations']
        ]);
    }

    private function renderPageHeader(): void
    {
        PageHeaderComponent::render([
            'title' => ' Mes Réservations',
            'subtitle' => 'Gérez vos réservations d\'équipements',
            'actions' => [
                [
                    'type' => 'modal',
                    'label' => 'Nouvelle réservation',
                    'modalId' => 'reservation-modal',
                    'class' => 'btn-primary'
                ]
            ]
        ]);
    }

    private function renderFlashMessages(): void
    {
        if (has_flash('success')) {
            echo '<div class="alert alert-success">' . flash('success') . '</div>';
        }
        if (has_flash('error')) {
            echo '<div class="alert alert-error">' . flash('error') . '</div>';
        }
    }

    private function renderStatsCards(): void
    {
        $totalActives = count($this->actives);
        $totalHistorique = count($this->historique);
        $enAttente = count(array_filter($this->actives, fn($r) => $r['statut'] === 'en_attente'));
        $confirmees = count(array_filter($this->actives, fn($r) => $r['statut'] === 'confirme'));

        TableComponent::renderStatsCards([
            [
                'label' => 'Réservations actives',
                'value' => $totalActives
            ],
            [
                'label' => 'En attente',
                'value' => $enAttente
            ],
            [
                'label' => 'Confirmées',
                'value' => $confirmees
            ],
            [
                'label' => 'Historique',
                'value' => $totalHistorique
            ]
        ]);
    }

    private function renderTabs(): void
    {
        ?>
        <div class="tabs-container">
            <div class="tabs">
                <button class="tab-button active" onclick="showTab('actives')">
                    Réservations actives <span class="tab-count"><?= count($this->actives) ?></span>
                </button>
                <button class="tab-button" onclick="showTab('historique')">
                    Historique <span class="tab-count"><?= count($this->historique) ?></span>
                </button>
            </div>

            <div id="tab-actives" class="tab-content active">
                <?php $this->renderActiveReservations(); ?>
            </div>

            <div id="tab-historique" class="tab-content">
                <?php $this->renderHistorique(); ?>
            </div>
        </div>
        <?php
    }

    private function renderActiveReservations(): void
    {
        if (empty($this->actives)) {
            $this->renderEmptyState('actives');
            return;
        }
        ?>
        <div class="reservations-grid">
            <?php foreach ($this->actives as $reservation): ?>
                <?php $this->renderReservationCard($reservation); ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private function renderReservationCard(array $reservation): void
    {
        $statusConfig = [
            'confirme' => ['color' => 'success', 'label' => 'Confirmée'],
            'en_attente' => ['color' => 'warning', 'label' => 'En attente'],
            'annule' => ['color' => 'danger', 'label' => 'Annulée']
        ];

        $status = $statusConfig[$reservation['statut']] ?? ['color' => 'secondary', 'label' => $reservation['statut']];
        ?>
        <div class="card reservation-card">
            <div class="card-header">
                <div class="header-left">
                    <h3>🔧 <?= e($reservation['equipement_nom']) ?></h3>
                    <span class="badge badge-<?= $status['color'] ?>">
                        <?= $status['label'] ?>
                    </span>
                </div>
                <span class="type-badge"><?= e($reservation['type_equipement']) ?></span>
            </div>

            <div class="card-body">
                <div class="info-list">
                    <div class="info-item">
                        <span class="info-label"> Début</span>
                        <span class="info-value"><?= format_date($reservation['date_debut'], 'd/m/Y H:i') ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"> Fin</span>
                        <span class="info-value"><?= format_date($reservation['date_fin'], 'd/m/Y H:i') ?></span>
                    </div>
                    <?php if (!empty($reservation['motif'])): ?>
                    <div class="info-item full-width">
                        <span class="info-label"> Motif</span>
                        <span class="info-value motif-text"><?= e($reservation['motif']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($reservation['statut'] !== 'annule'): ?>
            <div class="card-footer">
                <form method="POST" action="<?= base_url('membre/reservations/annuler/' . $reservation['id']) ?>" 
                      onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn-danger-outline">
                        Annuler la réservation
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function renderHistorique(): void
    {
        if (empty($this->historique)) {
            $this->renderEmptyState('historique');
            return;
        }
        ?>
        <div class="card">
            <div class="card-body">
                <div class="historique-list">
                    <?php foreach ($this->historique as $reservation): ?>
                        <?php $this->renderHistoriqueItem($reservation); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }

    private function renderHistoriqueItem(array $reservation): void
    {
        $statusConfig = [
            'terminee' => ['color' => 'secondary', 'label' => 'Terminée'],
            'annule' => ['color' => 'danger', 'label' => 'Annulée']
        ];
        
        $status = $statusConfig[$reservation['statut']] ?? ['color' => 'secondary', 'label' => $reservation['statut']];
        ?>
        <div class="historique-item <?= $reservation['statut'] ?>">
            <div class="item-icon">
                <?= $status['icon'] ?>
            </div>
            <div class="item-content">
                <div class="item-header">
                    <div>
                        <h4> <?= e($reservation['equipement_nom']) ?></h4>
                        <span class="item-type"><?= e($reservation['type_equipement']) ?></span>
                    </div>
                    <span class="badge badge-<?= $status['color'] ?>">
                        <?= $status['label'] ?>
                    </span>
                </div>
                <div class="item-dates">
                    <span><?= format_date($reservation['date_debut'], 'd/m/Y H:i') ?></span>
                    <span class="separator">→</span>
                    <span><?= format_date($reservation['date_fin'], 'd/m/Y H:i') ?></span>
                </div>
                <?php if (!empty($reservation['motif'])): ?>
                <div class="item-motif">
                    <strong>Motif:</strong> <?= e($reservation['motif']) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    private function renderEmptyState(string $type): void
    {
        $config = [
            'actives' => [
                'title' => 'Aucune réservation active',
                'message' => 'Vous n\'avez pas de réservation en cours.',
                'showButton' => true
            ],
            'historique' => [
                'title' => 'Aucun historique',
                'message' => 'Vous n\'avez pas encore d\'historique de réservations.',
                'showButton' => false
            ]
        ];
        
        $data = $config[$type];
        ?>
        <div class="empty-state">
            <h3><?= $data['title'] ?></h3>
            <p><?= $data['message'] ?></p>
            <?php if ($data['showButton']): ?>
            <button class="btn-primary" onclick="openModal('reservation-modal')">
                Créer une réservation
            </button>
            <?php endif; ?>
        </div>
        <?php
    }

    private function renderModal(): void
    {
        $equipementsOptions = [''];
        foreach ($this->equipements as $equipement) {
            $equipementsOptions[$equipement['id']] = $equipement['nom'] . ' - ' . $equipement['type_equipement'];
        }

        ModalComponent::renderFormModal([
            'id' => 'reservation-modal',
            'title' => 'Nouvelle Réservation',
            'size' => 'medium',
            'form' => [
                'action' => base_url('membre/reservations/creer'),
                'method' => 'POST',
                'fields' => [
                    [
                        'type' => 'select',
                        'name' => 'equipement_id',
                        'label' => 'Équipement',
                        'required' => true,
                        'options' => $equipementsOptions,
                        'placeholder' => 'Sélectionner un équipement...'
                    ],
                    [
                        'type' => 'datetime-local',
                        'name' => 'date_debut',
                        'label' => 'Date début',
                        'required' => true,
                        'attributes' => ['min' => date('Y-m-d\TH:i')]
                    ],
                    [
                        'type' => 'datetime-local',
                        'name' => 'date_fin',
                        'label' => 'Date fin',
                        'required' => true,
                        'attributes' => ['min' => date('Y-m-d\TH:i')]
                    ],
                    [
                        'type' => 'textarea',
                        'name' => 'motif',
                        'label' => 'Motif de la réservation',
                        'placeholder' => 'Expliquez brièvement l\'objectif de cette réservation...',
                        'attributes' => ['rows' => 3]
                    ]
                ],
                'submitText' => 'Réserver',
                'cancelUrl' => null
            ]
        ]);
    }

    private function renderStyles(): void
    {
        ?>
        <style>
        /* Alerts */
        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-success {
            background: #D1FAE5;
            color: #065F46;
            border: 1px solid #6EE7B7;
        }

        .alert-error {
            background: #FEE2E2;
            color: #991B1B;
            border: 1px solid #FCA5A5;
        }

        /* Stats Cards - style dashboard */
        .stats-grid {
            margin: 24px 0;
        }

        /* Tabs Container */
        .tabs-container {
            margin-top: 24px;
        }

        .tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            border-bottom: 2px solid #E5E7EB;
            background: white;
            padding: 0 24px;
            border-radius: 12px 12px 0 0;
        }

        .tab-button {
            padding: 16px 24px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-weight: 600;
            color: #6B7280;
            transition: all 0.2s;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tab-button:hover {
            color: #111827;
            background: #F9FAFB;
        }

        .tab-button.active {
            color: #5B7FFF;
            border-bottom-color: #5B7FFF;
        }

        .tab-count {
            background: #E5E7EB;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
        }

        .tab-button.active .tab-count {
            background: #5B7FFF;
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Reservations Grid */
        .reservations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 24px;
        }

        /* Card Style */
        .card {
            background: white;
            border-radius: 12px;
            border: 1px solid #E5E7EB;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .reservation-card {
            transition: all 0.2s;
        }

        .reservation-card:hover {
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #E5E7EB;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            background: #F9FAFB;
        }

        .header-left {
            flex: 1;
            min-width: 0;
        }

        .card-header h3 {
            margin: 0 0 8px 0;
            font-size: 16px;
            font-weight: 600;
            color: #111827;
            word-break: break-word;
        }

        .type-badge {
            padding: 6px 12px;
            background: white;
            color: #374151;
            border: 1px solid #D1D5DB;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .badge-success {
            background: #D1FAE5;
            color: #065F46;
        }

        .badge-warning {
            background: #FEF3C7;
            color: #92400E;
        }

        .badge-danger {
            background: #FEE2E2;
            color: #991B1B;
        }

        .badge-secondary {
            background: #F3F4F6;
            color: #6B7280;
        }

        .card-body {
            padding: 24px;
        }

        .info-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 12px;
            background: #F9FAFB;
            border-radius: 8px;
            gap: 12px;
        }

        .info-item.full-width {
            flex-direction: column;
            gap: 8px;
        }

        .info-label {
            color: #6B7280;
            font-weight: 500;
            font-size: 13px;
            flex-shrink: 0;
        }

        .info-value {
            color: #111827;
            font-weight: 600;
            font-size: 14px;
            text-align: right;
        }

        .motif-text {
            text-align: left;
            font-weight: 400;
            color: #374151;
            line-height: 1.5;
        }

        .card-footer {
            padding: 16px 24px;
            border-top: 1px solid #E5E7EB;
            background: #F9FAFB;
        }

        .btn-danger-outline {
            width: 100%;
            padding: 10px 16px;
            background: white;
            color: #DC2626;
            border: 2px solid #DC2626;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-danger-outline:hover {
            background: #DC2626;
            color: white;
        }

        /* Historique List */
        .historique-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .historique-item {
            display: flex;
            gap: 16px;
            padding: 20px;
            background: #F9FAFB;
            border-radius: 8px;
            border-left: 4px solid #D1D5DB;
            transition: all 0.2s;
        }

        .historique-item:hover {
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .historique-item.terminee {
            border-left-color: #10B981;
        }

        .historique-item.annule {
            border-left-color: #EF4444;
            opacity: 0.8;
        }

        .item-icon {
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
            border: 2px solid #E5E7EB;
        }

        .item-content {
            flex: 1;
            min-width: 0;
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            gap: 12px;
        }

        .item-header h4 {
            margin: 0 0 4px 0;
            font-size: 16px;
            font-weight: 600;
            color: #111827;
        }

        .item-type {
            font-size: 12px;
            color: #6B7280;
            text-transform: uppercase;
            font-weight: 600;
        }

        .item-dates {
            display: flex;
            gap: 8px;
            align-items: center;
            font-size: 13px;
            color: #6B7280;
            margin-bottom: 8px;
            flex-wrap: wrap;
        }

        .separator {
            color: #D1D5DB;
            font-weight: 700;
        }

        .item-motif {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #E5E7EB;
            font-size: 13px;
            color: #6B7280;
            line-height: 1.5;
        }

        .item-motif strong {
            font-weight: 600;
            color: #111827;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 12px;
            border: 2px dashed #E5E7EB;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-state h3 {
            margin: 0 0 8px 0;
            color: #111827;
            font-size: 20px;
            font-weight: 600;
        }

        .empty-state p {
            color: #6B7280;
            margin: 0 0 24px 0;
            font-size: 15px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .reservations-grid {
                grid-template-columns: 1fr;
            }
            
            .tabs {
                padding: 0 16px;
                flex-wrap: wrap;
            }
            
            .tab-button {
                padding: 12px 16px;
                font-size: 14px;
            }
            
            .item-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .item-dates {
                font-size: 12px;
            }
        }
        </style>
        <?php
    }

    private function renderScripts(): void
    {
        ?>
        <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById('tab-' + tabName).classList.add('active');
            event.target.classList.add('active');
        }
        </script>
        <?php
    }

    private function renderFooter(): void
    {
        FooterComponent::render(['role' => 'membre']);
    }
}