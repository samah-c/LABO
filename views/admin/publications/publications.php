<?php
/**
 * Vue générique pour Publications (admin)
 */

require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/ViewComponents.php';

// Header
ViewComponents::renderHeader([
    'title' => 'Gestion des Publications',
    'username' => session('username'),
    'role' => 'admin',
    'additionalJs' => [
        base_url('assets/js/admin-publications.js')
    ]
]);
?>

<div class="container">
    <!-- Breadcrumbs -->
    <?php ViewComponents::renderBreadcrumbs([
        ['label' => 'Dashboard', 'url' => base_url('admin/dashboard')],
        ['label' => 'Publications']
    ]); ?>
    
    <!-- Titre et actions -->
    <div class="page-header">
        <h1>Publications</h1>
        <div class="page-actions">
            <button class="btn-primary" onclick="openAddModal()">
                 Nouvelle publication
            </button>
            <button class="btn-secondary" onclick="exportData()">
                 Exporter CSV
            </button>
        </div>
    </div>
    
    <!-- Filtres -->
    <?php ViewComponents::renderFilters([
        'showSearch' => true,
        'searchPlaceholder' => 'Rechercher une publication...',
        'filters' => [
            [
                'name' => 'type',
                'label' => 'Type',
                'options' => [
                    'Article' => 'Article',
                    'Conférence' => 'Conférence',
                    'Thèse' => 'Thèse',
                    'Rapport' => 'Rapport'
                ]
            ],
            [
                'name' => 'annee',
                'label' => 'Année',
                'options' => array_combine(
                    range(date('Y'), 2020),
                    range(date('Y'), 2020)
                )
            ],
            [
                'name' => 'domaine',
                'label' => 'Domaine',
                'options' => [
                    'IA' => 'Intelligence Artificielle',
                    'Securite' => 'Sécurité',
                    'Reseaux' => 'Réseaux'
                ]
            ]
        ]
    ]); ?>
    
    <!-- Table des publications -->
    <?php ViewComponents::renderTable([
        'data' => $publications ?? [],
        'columns' => [
            [
                'key' => 'titre',
                'label' => 'Titre',
                'formatter' => function($value, $row) {
                    return '<strong>' . e($value) . '</strong>';
                }
            ],
            [
                'key' => 'type_publication',
                'label' => 'Type',
                'formatter' => function($value) {
                    return LabHelpers::getPublicationTypeBadge($value);
                }
            ],
            [
                'key' => 'date_publication',
                'label' => 'Date',
                'formatter' => function($value) {
                    return format_date($value);
                }
            ],
            [
                'key' => 'auteurs',
                'label' => 'Auteurs',
                'formatter' => function($value, $row) {
                    // Récupérer les auteurs depuis la relation
                    $auteurs = $row['auteurs_noms'] ?? 'Non défini';
                    return truncate($auteurs, 50);
                }
            ],
            [
                'key' => 'doi',
                'label' => 'DOI',
                'formatter' => function($value) {
                    return $value ? '<code>' . e($value) . '</code>' : '-';
                }
            ]
        ],
        'actions' => [
            function($row) {
                return '<button class="btn-action btn-view" 
                                onclick="viewItem(' . $row['id'] . ')">
                            voir
                        </button>';
            },
            function($row) {
                return '<button class="btn-action btn-edit" 
                                onclick="editItem(' . $row['id'] . ')">
                            ✏️
                        </button>';
            },
            function($row) {
                return '<button class="btn-action btn-delete" 
                                onclick="deleteItem(' . $row['id'] . ')">
                            🗑️
                        </button>';
            }
        ],
        'emptyMessage' => 'Aucune publication trouvée'
    ]); ?>
    
    <!-- Pagination -->
    <?php 
    if (isset($pagination)) {
        echo Utils::renderPagination($pagination, base_url('admin/publications'));
    }
    ?>
</div>

<!-- Modale d'ajout/édition -->
<?php ViewComponents::renderModal([
    'id' => 'publication-modal',
    'title' => 'Ajouter une publication',
    'content' => ViewComponents::renderForm([
        'action' => base_url('admin/publications/save'),
        'method' => 'POST',
        'fields' => [
            [
                'type' => 'text',
                'name' => 'titre',
                'label' => 'Titre',
                'required' => true,
                'placeholder' => 'Titre de la publication'
            ],
            [
                'type' => 'select',
                'name' => 'type_publication',
                'label' => 'Type',
                'required' => true,
                'options' => [
                    'Article' => 'Article',
                    'Conférence' => 'Conférence',
                    'Thèse' => 'Thèse',
                    'Rapport' => 'Rapport'
                ]
            ],
            [
                'type' => 'date',
                'name' => 'date_publication',
                'label' => 'Date de publication',
                'required' => true
            ],
            [
                'type' => 'textarea',
                'name' => 'resume',
                'label' => 'Résumé',
                'required' => true
            ],
            [
                'type' => 'text',
                'name' => 'doi',
                'label' => 'DOI',
                'placeholder' => '10.1000/xyz123'
            ],
            [
                'type' => 'text',
                'name' => 'lien',
                'label' => 'Lien de téléchargement',
                'placeholder' => 'https://...'
            ]
        ],
        'submitText' => 'Enregistrer',
        'cancelUrl' => null
    ])
]); ?>

<script>
// Fonctions JavaScript pour gérer les actions
function viewItem(id) {
    window.location.href = '<?= base_url("admin/publications/view/") ?>' + id;
}

function editItem(id) {
    // Charger les données via AJAX et remplir le formulaire
    fetch(`<?= base_url("api/admin/publications/") ?>${id}`)
        .then(res => res.json())
        .then(data => {
            // Remplir le formulaire
            document.getElementById('publication-modal').style.display = 'block';
        });
}

function deleteItem(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette publication ?')) {
        fetch(`<?= base_url("api/admin/publications/") ?>${id}`, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur lors de la suppression');
            }
        });
    }
}

function openAddModal() {
    document.getElementById('publication-modal').style.display = 'block';
}

function exportData() {
    window.location.href = '<?= base_url("admin/publications/export") ?>?format=csv';
}

// Gestion de la modale
document.querySelector('.modal-close').addEventListener('click', function() {
    document.getElementById('publication-modal').style.display = 'none';
});

// Filtres en temps réel
document.getElementById('apply-filters').addEventListener('click', function() {
    const filters = {};
    document.querySelectorAll('.filter-select').forEach(select => {
        if (select.value) {
            filters[select.name] = select.value;
        }
    });
    
    const params = new URLSearchParams(filters);
    window.location.href = '<?= current_url() ?>?' + params.toString();
});
</script>

<?php ViewComponents::renderFooter(); ?>