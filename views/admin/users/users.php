<?php
/**
 * Vue gestion des Utilisateurs (admin)
 * À placer dans : /TDW_project/views/admin/users/users.php
 */

require_once __DIR__ . '/../../../lib/helpers.php';
require_once __DIR__ . '/../../../lib/ViewComponents.php';

ViewComponents::renderHeader([
    'title' => 'Gestion des Utilisateurs',
    'username' => session('username'),
    'role' => 'admin',
    'additionalJs' => [
        base_url('assets/js/ui.js'),
        base_url('assets/js/table-enhancements.js'),
        base_url('assets/js/admin/users-handler.js')
    ]
]);
?>

<div class="container">
    <?php ViewComponents::renderBreadcrumbs([
        ['label' => 'Dashboard', 'url' => base_url('admin/dashboard')],
        ['label' => 'Utilisateurs']
    ]); ?>
    
    <div class="page-header">
        <h1> Gestion des Utilisateurs</h1>
        <div class="page-actions">
            <button class="btn-primary" onclick="openAddModal()">
                 Nouvel utilisateur
            </button>
            <button class="btn-secondary" onclick="exportData()">
                 Exporter
            </button>
        </div>
    </div>
    
    <?php ViewComponents::renderFilters([
        'showSearch' => true,
        'searchPlaceholder' => 'Rechercher un utilisateur...',
        'filters' => [
            [
                'name' => 'role',
                'label' => 'Rôle',
                'options' => [
                    'admin' => 'Administrateur',
                    'membre' => 'Membre',
                    'visiteur' => 'Visiteur'
                ]
            ],
            [
                'name' => 'statut',
                'label' => 'Statut',
                'options' => [
                    'actif' => 'Actif',
                    'suspendu' => 'Suspendu',
                    'inactif' => 'Inactif'
                ]
            ]
        ]
    ]); ?>
    
    <?php ViewComponents::renderTable([
        'data' => $users ?? [],
        'columns' => [
            [
                'key' => 'username',
                'label' => 'Nom d\'utilisateur',
                'formatter' => function($value) {
                    return '<strong>' . e($value) . '</strong>';
                }
            ],
            [
                'key' => 'email',
                'label' => 'Email',
                'formatter' => function($value) {
                    return e($value);
                }
            ],
            [
                'key' => 'role',
                'label' => 'Rôle',
                'formatter' => function($value) {
                    $badges = [
                        'admin' => '<span class="badge badge-red"> Admin</span>',
                        'membre' => '<span class="badge badge-blue"> Membre</span>',
                        'visiteur' => '<span class="badge badge-gray"> Visiteur</span>'
                    ];
                    return $badges[$value] ?? e($value);
                }
            ],
            [
                'key' => 'derniere_connexion',
                'label' => 'Dernière connexion',
                'formatter' => function($value) {
                    return $value ? time_ago($value) : '<em style="color: #9CA3AF;">Jamais</em>';
                }
            ],
            [
                'key' => 'statut',
                'label' => 'Statut',
                'formatter' => function($value, $row) {
                    $statut = $value ?? 'actif';
                    $badges = [
                        'actif' => '<span class="badge badge-success">✓ Actif</span>',
                        'suspendu' => '<span class="badge badge-warning">⚠ Suspendu</span>',
                        'inactif' => '<span class="badge badge-secondary">○ Inactif</span>'
                    ];
                    return $badges[$statut] ?? e($statut);
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
                // Ne pas afficher le bouton supprimer pour l'utilisateur connecté
                if ($row['id'] == session('user_id')) {
                    return '';
                }
                return '<button class="btn-action btn-delete" 
                                onclick="deleteItem(' . $row['id'] . ')"
                                title="Supprimer">
                            🗑️
                        </button>';
            }
        ],
        'emptyMessage' => 'Aucun utilisateur trouvé'
    ]); ?>
    
    <?php 
    if (isset($pagination)) {
        echo Utils::renderPagination($pagination, base_url('admin/users/users'));
    }
    ?>
</div>

<!-- Modale -->
<?php ViewComponents::renderModal([
    'id' => 'user-modal',
    'title' => 'Ajouter un utilisateur',
    'content' => '<div id="modal-form-container"></div>'
]); ?>

<?php ViewComponents::renderFooter(); ?>