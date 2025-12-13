<?php
/**
 * Vue gestion des Événements (admin)
 */

require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/ViewComponents.php';

ViewComponents::renderHeader([
    'title' => 'Gestion des Événements',
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
                    'Conférence' => 'Conférence',
                    'Atelier' => 'Atelier',
                    'Séminaire' => 'Séminaire',
                    'Soutenance' => 'Soutenance',
                    'Journée d\'étude' => 'Journée d\'étude'
                ]
            ],
            [
                'name' => 'statut',
                'label' => 'Statut',
                'options' => [
                    'à venir' => 'À venir',
                    'en cours' => 'En cours',
                    'terminé' => 'Terminé'
                ]
            ]
        ]
    ]); ?>
    
    <?php ViewComponents::renderTable([
        'data' => $evenements ?? [],
        'columns' => [
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
                    return LabHelpers::getEvenementTypeBadge($value);
                }
            ],
            [
                'key' => 'date_evenement',
                'label' => 'Date',
                'formatter' => function($value) {
                    return format_date($value, 'd/m/Y H:i');
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
                    $isUpcoming = LabHelpers::isUpcoming($row['date_evenement']);
                    if ($isUpcoming) {
                        return '<span class="badge badge-info">À venir</span>';
                    } else {
                        return '<span class="badge badge-secondary">Terminé</span>';
                    }
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
        'emptyMessage' => 'Aucun événement trouvé'
    ]); ?>
    
    <?php 
    if (isset($pagination)) {
        echo Utils::renderPagination($pagination, base_url('admin/evenements'));
    }
    ?>
</div>

<!-- Modale -->
<?php ViewComponents::renderModal([
    'id' => 'evenement-modal',
    'title' => 'Ajouter un événement',
    'content' => '<div id="modal-form-container"></div>'
]); ?>

<script>
window.crudConfig = {
    entityName: 'evenement',
    baseUrl: '<?= base_url("admin/evenements") ?>',
    apiUrl: '<?= base_url("api/admin/evenements") ?>'
};

function viewItem(id) {
    window.location.href = '<?= base_url("admin/evenements/view/") ?>' + id;
}

function editItem(id) {
    fetch(`<?= base_url("api/admin/evenements/") ?>${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadForm('edit', data.evenement);
            } else {
                CrudHandler.showToast('Erreur de chargement', 'error');
            }
        });
}

function deleteItem(id) {
    if (confirm('Confirmer la suppression de cet événement ?')) {
        fetch(`<?= base_url("api/admin/evenements/") ?>${id}`, {
            method: 'DELETE',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                CrudHandler.showToast('Événement supprimé', 'success');
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
        ? '<?= base_url("admin/evenements/form") ?>'
        : `<?= base_url("admin/evenements/form/") ?>${data.id}`;
    
    fetch(url)
        .then(res => res.text())
        .then(html => {
            document.getElementById('modal-form-container').innerHTML = html;
            
            if (mode === 'edit' && data) {
                fillFormData(data);
            }
            
            attachFormSubmit();
            document.getElementById('evenement-modal').style.display = 'block';
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
    document.getElementById('evenement-modal').style.display = 'none';
}

function exportData() {
    window.location.href = '<?= base_url("admin/evenements/export") ?>?format=csv';
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
    window.location.href = '<?= base_url("admin/evenements") ?>?' + params.toString();
});

document.querySelector('.modal-close')?.addEventListener('click', closeModal);
</script>

<?php ViewComponents::renderFooter(); ?>