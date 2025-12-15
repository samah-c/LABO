<?php
/**
 * Vue gestion des Événements (admin)
 */

require_once __DIR__ . '/../../../lib/helpers.php';
require_once __DIR__ . '/../../../lib/ViewComponents.php';

ViewComponents::renderHeader([
    'title' => 'Gestion des Événements',
    'username' => session('username'),
    'role' => 'admin',
    'additionalJs' => [
        base_url('assets/js/admin/evenements-handler.js')
    ]
]);
?>

<div class="container">
    <?php ViewComponents::renderBreadcrumbs([
        ['label' => 'Dashboard', 'url' => base_url('admin/dashboard')],
        ['label' => 'Événements']
    ]); ?>
    
    <div class="page-header">
        <h1>Événements Scientifiques</h1>
        <div class="page-actions">
            <button class="btn-primary" onclick="openAddModal()">
                Nouvel événement
            </button>
            <button class="btn-secondary" onclick="exportData()">
                Exporter
            </button>
        </div>
    </div>
    
    <?php ViewComponents::renderFilters([
        'showSearch' => true,
        'searchPlaceholder' => 'Rechercher un événement...',
        'filters' => [
            [
                'name' => 'type_evenement',
                'label' => 'Type',
                'options' => [
                    'conference' => 'Conférence',
                    'atelier' => 'Atelier',
                    'seminaire' => 'Séminaire',
                    'soutenance' => 'Soutenance',
                    'autre' => 'Autre'
                ]
            ],
            [
                'name' => 'statut',
                'label' => 'Statut',
                'options' => [
                    'a_venir' => 'À venir',
                    'termine' => 'Terminé'
                ]
            ]
        ]
    ]); ?>
    
    <?php 
    $tableColumns = [
        [
            'key' => 'titre',
            'label' => 'Titre',
            'formatter' => function($value) {
                return '<strong>' . e($value) . '</strong>';
            }
        ],
        [
            'key' => 'type_evenement',
            'label' => 'Type',
            'formatter' => function($value) {
                $colors = [
                    'conference' => '#3B82F6',
                    'atelier' => '#8B5CF6',
                    'seminaire' => '#10B981',
                    'soutenance' => '#F59E0B',
                    'autre' => '#6B7280'
                ];
                $labels = [
                    'conference' => 'Conférence',
                    'atelier' => 'Atelier',
                    'seminaire' => 'Séminaire',
                    'soutenance' => 'Soutenance',
                    'autre' => 'Autre'
                ];
                $color = $colors[$value] ?? '#6B7280';
                $label = $labels[$value] ?? $value;
                return '<span class="badge" style="background: ' . $color . ';">' . e($label) . '</span>';
            }
        ],
        [
            'key' => 'date_evenement',
            'label' => 'Date',
            'formatter' => function($value) {
                return date('d/m/Y H:i', strtotime($value));
            }
        ],
        [
            'key' => 'organisateur_nom',
            'label' => 'Organisateur',
            'formatter' => function($value) {
                return e($value ?? 'Non défini');
            }
        ],
        [
            'key' => 'lieu',
            'label' => 'Lieu',
            'formatter' => function($value) {
                return e($value ?? '-');
            }
        ],
        [
            'key' => 'statut',
            'label' => 'Statut',
            'formatter' => function($value, $row) {
                $isUpcoming = strtotime($row['date_evenement']) > time();
                if ($isUpcoming) {
                    return '<span class="badge badge-info">À venir</span>';
                } else {
                    return '<span class="badge badge-secondary">Terminé</span>';
                }
            }
        ]
    ];
    
    $tableActions = [
        function($row) {
            return '<button class="btn-action btn-view" 
                            onclick="viewItem(' . $row['id'] . ')" 
                            title="Voir détails">
                        voir
                    </button>';
        },
        function($row) {
            return '<button class="btn-action btn-edit" 
                            onclick="editItem(' . $row['id'] . ')"
                            title="Modifier">
                         ✏️
                    </button>';
        },
        function($row) {
            return '<button class="btn-action btn-delete" 
                            onclick="deleteItem(' . $row['id'] . ')"
                            title="Supprimer">
                        🗑️
                    </button>';
        }
    ];
    
    ViewComponents::renderTable([
        'data' => $evenements ?? [],
        'columns' => $tableColumns,
        'actions' => $tableActions,
        'emptyMessage' => 'Aucun événement trouvé'
    ]); 
    ?>
    
    <?php 
    if (isset($pagination)) {
        echo Utils::renderPagination($pagination, base_url('admin/evenements/evenements'));
    }
    ?>
</div>

<!-- Modale -->
<?php ViewComponents::renderModal([
    'id' => 'evenement-modal',
    'title' => 'Ajouter un événement',
    'content' => '<div id="modal-form-container"></div>'
]); ?>

<style>
.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    color: white;
}

.badge-info {
    background: #3B82F6;
}

.badge-secondary {
    background: #6B7280;
}
</style>

<?php ViewComponents::renderFooter(); ?>