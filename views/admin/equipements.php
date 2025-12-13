<?php
/**
 * Vue gestion des Équipements (admin)
 */

require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/ViewComponents.php';

ViewComponents::renderHeader([
    'title' => 'Gestion des Équipements',
    'username' => session('username'),
    'role' => 'admin',
    'additionalJs' => [
        base_url('assets/js/crud-handler.js')
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
            <button class="btn-primary" onclick="openAddModal()">
                 Nouvel équipement
            </button>
            <button class="btn-secondary" onclick="exportData()">
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
                    'robot' => 'robot',
                    'salle' => 'salle',
                    'Autre' => 'Autre'
                ]
            ],
            [
                'name' => 'etat',
                'label' => 'Etat',
                'options' => [
                    'libre' => 'libre',
                    'reserve' => 'résérvé',
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
                    $icons = [
         ];
                    $icon = $icons[$value] ?? '';
                    return $icon . ' ' . e($value);
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
                    return ' ' . e($value ?? '-');
                }
            ],
            [
                'key' => 'etat',
                'label' => 'Etat',
                'formatter' => function($value) {
                    $badges = [
                        'libre'=> '<span class="badge badge-success">✓ Libre</span>',
                        'reserve' => '<span class="badge badge-info"> Réséevé</span>',
                        'en_maintenance' => '<span class="badge badge-warning"> Maintenance</span>',
                        'hors_service' => '<span class="badge badge-danger">✗ Hors service</span>'
                    ];
                    return $badges[$value] ?? '<span class="badge badge-secondary">' . e($value) . '</span>';
                }
            ],
            [
                'key' => 'date_acquisition',
                'label' => 'Date d\'acquisition',
                'formatter' => function($value) {
                    return $value ? format_date($value, 'd/m/Y') : '-';
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
        'emptyMessage' => 'Aucun équipement trouvé'
    ]); ?>
    
    <?php 
    if (isset($pagination)) {
        echo Utils::renderPagination($pagination, base_url('admin/equipements'));
    }
    ?>
</div>

<!-- Modale -->
<?php ViewComponents::renderModal([
    'id' => 'equipement-modal',
    'title' => 'Ajouter un équipement',
    'content' => '<div id="modal-form-container"></div>'
]); ?>

<script>
window.crudConfig = {
    entityName: 'equipement',
    baseUrl: '<?= base_url("admin/equipements") ?>',
    apiUrl: '<?= base_url("api/admin/equipements") ?>'
};

function viewItem(id) {
    window.location.href = '<?= base_url("admin/equipements/view/") ?>' + id;
}

function editItem(id) {
    fetch(`<?= base_url("api/admin/equipements/") ?>${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadForm('edit', data.equipement);
            } else {
                CrudHandler.showToast('Erreur de chargement', 'error');
            }
        });
}

function deleteItem(id) {
    if (confirm('Confirmer la suppression de cet équipement ?')) {
        fetch(`<?= base_url("api/admin/equipements/") ?>${id}`, {
            method: 'DELETE',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                CrudHandler.showToast('Équipement supprimé', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                CrudHandler.showToast(data.message || 'Erreur', 'error');
            }
        });
    }
}

function openAddModal() {
    loadForm('add');
}

function loadForm(mode, data = null) {
    const url = mode === 'add' 
        ? '<?= base_url("admin/equipements/form") ?>'
        : `<?= base_url("admin/equipements/form/") ?>${data.id}`;
    
    fetch(url)
        .then(res => res.text())
        .then(html => {
            document.getElementById('modal-form-container').innerHTML = html;
            
            if (mode === 'edit' && data) {
                fillFormData(data);
            }
            
            attachFormSubmit();
            document.getElementById('equipement-modal').style.display = 'block';
        });
}

function fillFormData(data) {
    const form = document.querySelector('#modal-form-container form');
    if (!form) return;
    
    Object.keys(data).forEach(key => {
        const input = form.querySelector(`[name="${key}"]`);
        if (input) input.value = data[key] || '';
    });
}

function attachFormSubmit() {
    const form = document.querySelector('#modal-form-container form');
    if (!form) return;
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        
        submitBtn.disabled = true;
        submitBtn.textContent = 'Enregistrement...';
        
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            const data = await response.json();
            
            if (data.success) {
                CrudHandler.showToast('Enregistrement réussi', 'success');
                closeModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                CrudHandler.showToast(data.message || 'Erreur', 'error');
            }
        } catch (error) {
            CrudHandler.showToast('Erreur d\'enregistrement', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Enregistrer';
        }
    });
}

function closeModal() {
    document.getElementById('equipement-modal').style.display = 'none';
}

function exportData() {
    window.location.href = '<?= base_url("admin/equipements/export") ?>?format=csv';
}

// Filtres
document.getElementById('apply-filters')?.addEventListener('click', function() {
    const filters = {};
    document.querySelectorAll('.filter-select').forEach(select => {
        if (select.value) filters[select.name] = select.value;
    });
    
    const searchInput = document.getElementById('search-input');
    if (searchInput && searchInput.value) {
        filters.search = searchInput.value;
    }
    
    const params = new URLSearchParams(filters);
    window.location.href = '<?= base_url("admin/equipements") ?>?' + params.toString();
});

document.querySelector('.modal-close')?.addEventListener('click', closeModal);
</script>

<?php ViewComponents::renderFooter(); ?>