<?php
/**
 * Vue gestion des Équipes (admin) 
 */

require_once __DIR__ . '/../../../lib/helpers.php';
require_once __DIR__ . '/../../../lib/ViewComponents.php';

ViewComponents::renderHeader([
    'title' => 'Gestion des Équipes',
    'username' => session('username'),
    'role' => 'admin',
    'additionalJs' => [
        base_url('assets/js/ui.js'),
        base_url('assets/js/table-enhancements.js'),
        base_url('assets/js/admin/equipes-handler.js')
    ]
]);
?>

<div class="container">
    <?php ViewComponents::renderBreadcrumbs([
        ['label' => 'Dashboard', 'url' => base_url('admin/dashboard')],
        ['label' => 'Équipes']
    ]); ?>
    
    <div class="page-header">
        <h1> Équipes de Recherche</h1>
        <div class="page-actions">
            <button class="btn-primary" onclick="openAddModal()">
                 Nouvelle équipe
            </button>
            <button class="btn-secondary" onclick="exportData()">
                Exporter
            </button>
        </div>
    </div>
    
    <?php ViewComponents::renderFilters([
        'showSearch' => true,
        'searchPlaceholder' => 'Rechercher une équipe...',
        'filters' => [
            [
                'name' => 'domaine',
                'label' => 'Domaine',
                'options' => [
                    'Intelligence Artificielle' => 'Intelligence Artificielle',
                    'Sécurité' => 'Sécurité',
                    'Cloud' => 'Cloud',
                    'Réseaux' => 'Réseaux',
                    'Systèmes Embarqués' => 'Systèmes Embarqués',
                    'Big Data' => 'Big Data'
                ]
            ]
        ]
    ]); ?>
    
    <?php ViewComponents::renderTable([
        'data' => $equipes ?? [],
        'columns' => [
            [
                'key' => 'nom',
                'label' => 'Nom de l\'équipe',
                'formatter' => function($value, $row) {
                    return '<strong>' . e($value) . '</strong>';
                }
            ],
            [
                'key' => 'chef_nom',
                'label' => 'Chef d\'équipe',
                'formatter' => function($value, $row) {
                    return $value ? e($value) : '<em style="color: #9CA3AF;">Non assigné</em>';
                }
            ],
            [
                'key' => 'nb_membres',
                'label' => 'Membres',
                'formatter' => function($value, $row) {
                    $count = intval($value);
                    $badge_class = $count > 0 ? 'badge-blue' : 'badge-gray';
                    return '<span class="badge ' . $badge_class . '">' . $count . ' membre' . ($count > 1 ? 's' : '') . '</span>';
                }
            ],
            [
                'key' => 'domaine',
                'label' => 'Domaine',
                'formatter' => function($value) {
                    return e($value);
                }
            ],
            [
                'key' => 'date_creation',
                'label' => 'Création',
                'formatter' => function($value) {
                    return format_date($value);
                }
            ]
        ],
        'actions' => [
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
        ],
        'emptyMessage' => 'Aucune équipe trouvée'
    ]); ?>
    
    <?php 
    if (isset($pagination)) {
        echo Utils::renderPagination($pagination, base_url('admin/equipes/equipes'));
    }
    ?>
</div>

<!-- Modale -->
<?php ViewComponents::renderModal([
    'id' => 'equipe-modal',
    'title' => 'Ajouter une équipe',
    'content' => '<div id="modal-form-container"></div>'
]); ?>

<?php ViewComponents::renderFooter(); ?>