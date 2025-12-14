<?php
/**
 * Vue générique pour Projets (admin)
 */

require_once __DIR__ . '/../../../lib/helpers.php';
require_once __DIR__ . '/../../../lib/ViewComponents.php';

ViewComponents::renderHeader([
    'title' => 'Gestion des Projets',
    'username' => session('username'),
    'role' => 'admin'
]);
?>

<div class="container">
    <?php ViewComponents::renderBreadcrumbs([
        ['label' => 'Dashboard', 'url' => base_url('admin/dashboard')],
        ['label' => 'Projets']
    ]); ?>
    
    <div class="page-header">
        <h1>Projets de Recherche</h1>
        <div class="page-actions">
            <button class="btn-primary" onclick="openAddModal()">
                 Nouveau projet
            </button>
            <button class="btn-secondary" onclick="exportData()">
                 Exporter
            </button>
        </div>
    </div>
    
    <?php ViewComponents::renderFilters([
        'showSearch' => true,
        'searchPlaceholder' => 'Rechercher un projet...',
        'filters' => [
            [
                'name' => 'thematique',
                'label' => 'Thématique',
                'options' => [
                    'IA' => 'Intelligence Artificielle',
                    'Securite' => 'Sécurité Informatique',
                    'Cloud' => 'Cloud Computing',
                    'Reseaux' => 'Réseaux'
                ]
            ],
            [
                'name' => 'statut',
                'label' => 'Statut',
                'options' => [
                    'en_cours' => 'En cours',
                    'termine' => 'Terminé',
                    'soumis' => 'Soumis'
                ]
            ],
            [
                'name' => 'annee',
                'label' => 'Année',
                'options' => array_combine(
                    range(date('Y'), 2020),
                    range(date('Y'), 2020)
                )
            ]
        ]
    ]); ?>
    
    <?php ViewComponents::renderTable([
        'data' => $projets ?? [],
        'columns' => [
            [
                'key' => 'titre',
                'label' => 'Titre du projet',
                'formatter' => function($value) {
                    return '<strong>' . e($value) . '</strong>';
                }
            ],
            [
                'key' => 'thematique',
                'label' => 'Thématique',
                'formatter' => function($value) {
                    $icon = LabHelpers::getThematiqueIcon($value);
                    return $icon . ' ' . e($value);
                }
            ],
            [
                'key' => 'responsable_nom',
                'label' => 'Responsable',
                'formatter' => function($value) {
                    return e($value);
                }
            ],
            [
                'key' => 'statut',
                'label' => 'Statut',
                'formatter' => function($value) {
                    return LabHelpers::getProjetStatusBadge($value);
                }
            ],
            [
                'key' => 'date_debut',
                'label' => 'Période',
                'formatter' => function($value, $row) {
                    $debut = format_date($value, 'm/Y');
                    $fin = format_date($row['date_fin'] ?? '', 'm/Y');
                    return "$debut - $fin";
                }
            ],
            [
                'key' => 'progress',
                'label' => 'Progression',
                'formatter' => function($value, $row) {
                    $progress = LabHelpers::calculateProjectProgress(
                        $row['date_debut'], 
                        $row['date_fin']
                    );
                    return "<div class='progress'>
                                <div class='progress-bar' style='width: {$progress}%'>
                                    {$progress}%
                                </div>
                            </div>";
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
        'emptyMessage' => 'Aucun projet trouvé'
    ]); ?>
    
    <?php 
    if (isset($pagination)) {
        echo Utils::renderPagination($pagination, base_url('admin/projets'));
    }
    ?>
</div>

<!-- Modale -->
<?php ViewComponents::renderModal([
    'id' => 'projet-modal',
    'title' => 'Ajouter un projet',
    'content' => '<div id="modal-form-container"></div>',
    'footer' => '<button class="btn-secondary" onclick="closeModal()">Annuler</button>'
]); ?>

<script>
// Même structure JS que publications
function viewItem(id) {
    window.location.href = '<?= base_url("admin/projets/view/") ?>' + id;
}

function editItem(id) {
    fetch(`<?= base_url("api/admin/projets/") ?>${id}`)
        .then(res => res.json())
        .then(data => {
            // Charger le formulaire
            document.getElementById('projet-modal').style.display = 'block';
        });
}

function deleteItem(id) {
    if (confirm('Confirmer la suppression ?')) {
        fetch(`<?= base_url("api/admin/projets/") ?>${id}`, {
            method: 'DELETE',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) location.reload();
        });
    }
}

function openAddModal() {
    // Charger le formulaire vide
    loadForm('add');
}

function loadForm(mode, id = null) {
    const url = mode === 'add' 
        ? '<?= base_url("admin/projets/form") ?>'
        : `<?= base_url("admin/projets/form/") ?>${id}`;
    
    fetch(url)
        .then(res => res.text())
        .then(html => {
            document.getElementById('modal-form-container').innerHTML = html;
            document.getElementById('projet-modal').style.display = 'block';
        });
}

function closeModal() {
    document.getElementById('projet-modal').style.display = 'none';
}

function exportData() {
    window.location.href = '<?= base_url("admin/projets/export") ?>?format=csv';
}
</script>

<?php ViewComponents::renderFooter(); ?>