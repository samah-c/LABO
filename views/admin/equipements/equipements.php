<?php
/**
 * Vue gestion des Équipements (admin) - VERSION CORRIGÉE
 */

require_once __DIR__ . '/../../../lib/helpers.php';
require_once __DIR__ . '/../../../lib/ViewComponents.php';

ViewComponents::renderHeader([
    'title' => 'Gestion des Équipements',
    'username' => session('username'),
    'role' => 'admin',
    'additionalJs' => [
        base_url('assets/js/ui.js'),
        base_url('assets/js/admin/equipements-handler.js')
    ]
]);
?>

<div class="container">
    <?php ViewComponents::renderBreadcrumbs([
        ['label' => 'Dashboard', 'url' => base_url('admin/dashboard')],
        ['label' => 'Équipements']
    ]); ?>
    
    <div class="page-header">
        <h1> Équipements du Laboratoire</h1>
        <div class="page-actions">
            <button class="btn-secondary" onclick="window.location.href='<?= base_url('admin/equipements/equipements/dashboard') ?>'">
                Tableau de bord
            </button>
            <button class="btn-secondary" onclick="window.location.href='<?= base_url('admin/equipements/equipements/historique') ?>'">
                Historique
            </button>
            <button class="btn-secondary" onclick="window.location.href='<?= base_url('admin/equipements/equipements/rapport') ?>'">
                 Rapport
            </button>
            <button class="btn-primary" onclick="equipements.openAddModal()">
               Nouvel équipement
            </button>
            <button class="btn-secondary" onclick="equipements.export()">
                Exporter
            </button>
        </div>
    </div>
    
    <?php ViewComponents::renderFilters([
        'showSearch' => true,
        'searchPlaceholder' => 'Rechercher un équipement...',
        'filters' => [
            [
                'name' => 'type_equipement',
                'label' => 'Type',
                'options' => [
                    'Ordinateur' => 'Ordinateur',
                    'Serveur' => 'Serveur',
                    'Imprimante' => 'Imprimante',
                    'Scanner' => 'Scanner',
                    'Réseau' => 'Équipement réseau',
                    'Laboratoire' => 'Équipement de labo',
                    'robot' => 'Robot',
                    'salle' => 'Salle',
                    'Autre' => 'Autre'
                ]
            ],
            [
                'name' => 'etat',
                'label' => 'État',
                'options' => [
                    'libre' => 'Libre',
                    'reserve' => 'Réservé',
                    'en_maintenance' => 'En maintenance',
                    'hors_service' => 'Hors service'
                ]
            ],
            [
                'name' => 'localisation',
                'label' => 'Localisation',
                'options' => [
                    'Bâtiment A, 1er étage' => 'Bâtiment A, 1er étage',
                    'Salle serveurs' => 'Salle serveurs',
                    'Laboratoire robotique' => 'Laboratoire robotique',
                    'Bureau' => 'Bureau',
                    'Entrepôt' => 'Entrepôt'
                ]
            ]
        ]
    ]); ?>
    
    <?php ViewComponents::renderTable([
        'data' => $equipements ?? [],
        'columns' => [
            [
                'key' => 'nom',
                'label' => 'Nom',
                'formatter' => function($value) {
                    return '<strong>' . e($value) . '</strong>';
                }
            ],
            [
                'key' => 'type_equipement',
                'label' => 'Type',
                'formatter' => function($value) {
    
                    return e($value);
                }
            ],
            [
                'key' => 'numero_serie',
                'label' => 'N° Série',
                'formatter' => function($value) {
                    return '<code>' . e($value ?? '-') . '</code>';
                }
            ],
            [
                'key' => 'localisation',
                'label' => 'Localisation',
                'formatter' => function($value) {
                    return e($value ?? '-');
                }
            ],
            [
                'key' => 'etat',
                'label' => 'État',
                'formatter' => function($value) {
                    $badges = [
                        'libre' => '<span class="badge badge-success">✓ Libre</span>',
                        'reserve' => '<span class="badge badge-info">Réservé</span>',
                        'en_maintenance' => '<span class="badge badge-warning"> Maintenance</span>',
                        'hors_service' => '<span class="badge badge-danger">✗ Hors service</span>'
                    ];
                    return $badges[$value] ?? '<span class="badge badge-secondary">' . e($value) . '</span>';
                }
            ],
            [
                'key' => 'equipe_nom',
                'label' => 'Équipe',
                'formatter' => function($value) {
                    return $value ? e($value) : '-';
                }
            ]
        ],
        'actions' => [
            function($row) {
                return '<button class="btn-action btn-view" 
                                onclick="equipements.view(' . $row['id'] . ')" 
                                title="Voir détails">
                           voir
                        </button>';
            },
            function($row) {
                return '<button class="btn-action btn-edit" 
                                onclick="equipements.edit(' . $row['id'] . ')"
                                title="Modifier">
                            ✏️
                        </button>';
            },
            function($row) {
                return '<button class="btn-action btn-delete" 
                                onclick="equipements.delete(' . $row['id'] . ')"
                                title="Supprimer">
                            🗑️
                        </button>';
            }
        ],
        'emptyMessage' => 'Aucun équipement trouvé'
    ]); ?>
    
    <?php 
    if (isset($pagination)) {
        echo Utils::renderPagination($pagination, base_url('admin/equipements/equipements'));
    }
    ?>
</div>

<!-- Modale -->
<?php ViewComponents::renderModal([
    'id' => 'equipement-modal',
    'title' => 'Ajouter un équipement',
    'content' => '<div id="modal-form-container"></div>'
]); ?>

<?php ViewComponents::renderFooter(); ?>