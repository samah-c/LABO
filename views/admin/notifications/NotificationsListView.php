<?php
/**
 * Vue de la liste des notifications
 */
require_once __DIR__ . '/../../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/NavigationComponent.php';
require_once __DIR__ . '/../../../lib/components/PageHeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/TableComponent.php';
require_once __DIR__ . '/../../../lib/components/FooterComponent.php';
require_once __DIR__ . '/../../../lib/components/ModalComponent.php';

class NotificationsListView
{
    private array $notifications;
    private ?array $pagination;

    public function __construct(array $notifications, ?array $pagination = null)
    {
        $this->notifications = $notifications;
        $this->pagination = $pagination;
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
        $this->renderTable();
        $this->renderPagination();
        echo '</div>';
        $this->renderModal();
        $this->renderFooter();
    }

    /**
     * Rendu de l'en-tête
     */
    private function renderHeader(): void
    {
        HeaderComponent::render([
            'title' => 'Gestion des Notifications',
            'username' => session('username'),
            'role' => 'admin',
            'additionalJs' => [
                base_url('assets/js/ui.js'),
                base_url('assets/js/admin/notifications-handler.js')
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
            ['label' => 'Notifications']
        ]);
    }

    /**
     * Rendu de l'en-tête de page
     */
    private function renderPageHeader(): void
    {
        PageHeaderComponent::render([
            'title' => 'Gestion des Notifications',
            'description' => 'Envoyez des notifications aux membres du laboratoire',
            'actions' => [
                [
                    'type' => 'button',
                    'label' => 'Nouvelle notification',
                    'onclick' => 'openAddModal()',
                    'class' => 'btn-primary'
                ]
            ]
        ]);
    }

    /**
     * Rendu de la table
     */
    private function renderTable(): void
    {
        TableComponent::render([
            'data' => $this->notifications,
            'columns' => [
                [
                    'key' => 'titre',
                    'label' => 'Titre',
                    'formatter' => function($value, $row) {
                        $badge = $this->getPriorityBadge($row['priorite'] ?? 'normale');
                        return '<div>
                            <strong>' . htmlspecialchars($value) . '</strong> ' . $badge . '
                            <br>
                            <small style="color: #6B7280;">' . htmlspecialchars(substr($row['message'], 0, 60)) . '...</small>
                        </div>';
                    }
                ],
                [
                    'key' => 'type_notification',
                    'label' => 'Type',
                    'formatter' => function($value) {
                        return $this->getTypeBadge($value);
                    }
                ],
                [
                    'key' => 'destinataire_type',
                    'label' => 'Destinataires',
                    'formatter' => function($value, $row) {
                        return $this->getDestinataireLabel($value, $row);
                    }
                ],
                [
                    'key' => 'createur_nom',
                    'label' => 'Créé par',
                    'formatter' => function($value) {
                        return htmlspecialchars($value ?? 'Système');
                    }
                ],
                [
                    'key' => 'date_creation',
                    'label' => 'Date',
                    'formatter' => function($value) {
                        return time_ago($value);
                    }
                ]
            ],
            'actions' => [
                function($row) {
                    return '<button class="btn-action btn-delete" 
                                    onclick="deleteItem(' . $row['id'] . ')"
                                    title="Supprimer">
                                supprimer
                            </button>';
                }
            ],
            'emptyMessage' => 'Aucune notification envoyée'
        ]);
    }

    /**
     * Rendu de la pagination
     */
    private function renderPagination(): void
    {
        if ($this->pagination) {
            echo Utils::renderPagination($this->pagination, base_url('admin/notifications'));
        }
    }

    /**
     * Rendu de la modale
     */
    private function renderModal(): void
    {
        ModalComponent::render([
            'id' => 'notification-modal',
            'title' => 'Envoyer une notification',
            'content' => '<div id="modal-form-container"></div>'
        ]);
    }

    /**
     * Rendu du pied de page
     */
    private function renderFooter(): void
    {
        FooterComponent::render(['role' => 'admin']);
    }

    /**
     * Badge de priorité
     */
    private function getPriorityBadge(string $priorite): string
    {
        $badges = [
            'normale' => '',
            'importante' => '<span class="badge badge-warning" style="font-size: 11px;"> Important</span>',
            'urgente' => '<span class="badge badge-red" style="font-size: 11px;"> Urgent</span>'
        ];
        
        return $badges[$priorite] ?? '';
    }

    /**
     * Badge de type
     */
    private function getTypeBadge(string $type): string
    {
        $badges = [
            'generale' => '<span class="badge badge-gray"> Générale</span>',
            'evenement' => '<span class="badge badge-blue"> Événement</span>',
            'projet' => '<span class="badge badge-purple"> Projet</span>',
            'equipement' => '<span class="badge badge-orange"> Équipement</span>',
            'publication' => '<span class="badge badge-green"> Publication</span>',
            'systeme' => '<span class="badge badge-secondary"> Système</span>'
        ];
        
        return $badges[$type] ?? htmlspecialchars($type);
    }

    /**
     * Label destinataire
     */
    private function getDestinataireLabel(string $type, array $row): string
    {
        switch ($type) {
            case 'tous':
                return '<span class="badge badge-blue"> Tous les membres</span>';
            
            case 'role':
                $roleLabels = [
                    'admin' => ' Administrateurs',
                    'membre' => ' Membres',
                    'visiteur' => ' Visiteurs'
                ];
                $role = $row['destinataire_id'] ?? 'membre';
                return '<span class="badge badge-purple">' . ($roleLabels[$role] ?? $role) . '</span>';
            
            case 'equipe':
                return '<span class="badge badge-green"> Équipe #' . ($row['destinataire_id'] ?? '?') . '</span>';
            
            case 'individuel':
                return '<span class="badge badge-secondary"> Membre #' . ($row['destinataire_id'] ?? '?') . '</span>';
            
            default:
                return htmlspecialchars($type);
        }
    }
}