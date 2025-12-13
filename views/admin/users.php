<?php
/**
 * Vue gestion des Utilisateurs (admin)
 */

require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/ViewComponents.php';

ViewComponents::renderHeader([
    'title' => 'Gestion des Utilisateurs',
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
                        'admin' => '<span class="badge badge-red">Admin</span>',
                        'membre' => '<span class="badge badge-blue">Membre</span>',
                        'visiteur' => '<span class="badge badge-gray">Visiteur</span>'
                    ];
                    return $badges[$value] ?? e($value);
                }
            ],
            [
                'key' => 'derniere_connexion',
                'label' => 'Dernière connexion',
                'formatter' => function($value) {
                    return $value ? time_ago($value) : 'Jamais';
                }
            ],
            [
                'key' => 'statut',
                'label' => 'Statut',
                'formatter' => function($value, $row) {
                    $statut = $row['statut'] ?? 'actif';
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
        echo Utils::renderPagination($pagination, base_url('admin/users'));
    }
    ?>
</div>

<!-- Modale -->
<?php ViewComponents::renderModal([
    'id' => 'user-modal',
    'title' => 'Ajouter un utilisateur',
    'content' => '<div id="modal-form-container"></div>',
    'footer' => '<button class="btn-secondary" onclick="closeModal()">Annuler</button>'
]); ?>

<script>
window.crudConfig = {
    entityName: 'user',
    baseUrl: '<?= base_url("admin/users") ?>',
    apiUrl: '<?= base_url("api/admin/users") ?>'
};

function viewItem(id) {
    window.location.href = '<?= base_url("admin/users/view/") ?>' + id;
}

function editItem(id) {
    fetch(`<?= base_url("api/admin/users/") ?>${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadForm('edit', data.user);
            } else {
                CrudHandler.showToast('Erreur de chargement', 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            CrudHandler.showToast('Erreur de chargement', 'error');
        });
}

function deleteItem(id) {
    if (confirm('Confirmer la suppression de cet utilisateur ?')) {
        fetch(`<?= base_url("api/admin/users/") ?>${id}`, {
            method: 'DELETE',
            headers: { 
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                CrudHandler.showToast('Utilisateur supprimé', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                CrudHandler.showToast(data.message || 'Erreur de suppression', 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            CrudHandler.showToast('Erreur de suppression', 'error');
        });
    }
}

function openAddModal() {
    loadForm('add');
}

function loadForm(mode, data = null) {
    const url = mode === 'add' 
        ? '<?= base_url("admin/users/form") ?>'
        : `<?= base_url("admin/users/form/") ?>${data.id}`;
    
    fetch(url)
        .then(res => res.text())
        .then(html => {
            document.getElementById('modal-form-container').innerHTML = html;
            
            // Remplir le formulaire en mode édition
            if (mode === 'edit' && data) {
                fillFormData(data);
            }
            
            // Attacher le gestionnaire de soumission
            attachFormSubmit();
            
            document.getElementById('user-modal').style.display = 'block';
        })
        .catch(error => {
            console.error('Erreur:', error);
            CrudHandler.showToast('Erreur de chargement du formulaire', 'error');
        });
}

function fillFormData(data) {
    const form = document.querySelector('#modal-form-container form');
    if (!form) return;
    
    Object.keys(data).forEach(key => {
        const input = form.querySelector(`[name="${key}"]`);
        if (input) {
            input.value = data[key] || '';
        }
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
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                CrudHandler.showToast('Enregistrement réussi', 'success');
                closeModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                CrudHandler.showToast(data.message || 'Erreur d\'enregistrement', 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            CrudHandler.showToast('Erreur d\'enregistrement', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Enregistrer';
        }
    });
}

function closeModal() {
    document.getElementById('user-modal').style.display = 'none';
}

function exportData() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.location.href = '<?= base_url("admin/users") ?>?' + params.toString();
}

// Gestion des filtres
document.getElementById('apply-filters')?.addEventListener('click', function() {
    const filters = {};
    
    document.querySelectorAll('.filter-select').forEach(select => {
        if (select.value) {
            filters[select.name] = select.value;
        }
    });
    
    const searchInput = document.getElementById('search-input');
    if (searchInput && searchInput.value) {
        filters.search = searchInput.value;
    }
    
    const params = new URLSearchParams(filters);
    window.location.href = '<?= base_url("admin/users") ?>?' + params.toString();
});

// Fermeture modale
document.querySelector('.modal-close')?.addEventListener('click', closeModal);
window.addEventListener('click', (e) => {
    if (e.target.id === 'user-modal') {
        closeModal();
    }
});
</script>

<?php ViewComponents::renderFooter(); ?>