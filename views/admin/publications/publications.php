<?php
/**
 * Vue générique pour Publications (admin)
 * Avec validation et rapports bibliographiques
 */

require_once __DIR__ . '/../../../lib/helpers.php';
require_once __DIR__ . '/../../../lib/ViewComponents.php';

// Header
ViewComponents::renderHeader([
    'title' => 'Gestion des Publications',
    'username' => session('username'),
    'role' => 'admin',
    'additionalJs' => [
        base_url('assets/js/admin/publications-handler.js')
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
        <h1> Publications</h1>
        <div class="page-actions">
            <button class="btn-primary" onclick="openAddModal()">
                 Nouvelle publication
            </button>
            <button class="btn-secondary" onclick="genererRapport()">
                 Rapport bibliographique
            </button>
            <button class="btn-secondary" onclick="exportData()">
                Exporter CSV
            </button>
        </div>
    </div>
    
    <!-- Statistiques rapides -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-details">
                <div class="stat-value"><?= count($publications) ?></div>
                <div class="stat-label">Total publications</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-details">
                <div class="stat-value">
                    <?= count(array_filter($publications, fn($p) => ($p['statut_validation'] ?? 'en_attente') === 'valide')) ?>
                </div>
                <div class="stat-label">Validées</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-details">
                <div class="stat-value">
                    <?= count(array_filter($publications, fn($p) => ($p['statut_validation'] ?? 'en_attente') === 'en_attente')) ?>
                </div>
                <div class="stat-label">En attente</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-details">
                <div class="stat-value">
                    <?= count(array_filter($publications, fn($p) => ($p['statut_validation'] ?? 'en_attente') === 'rejete')) ?>
                </div>
                <div class="stat-label">Rejetées</div>
            </div>
        </div>
    </div>
    
    <!-- Filtres -->
    <?php 
    $filterOptions = [
        [
            'name' => 'type',
            'label' => 'Type',
            'options' => array_combine($types ?? [], $types ?? [])
        ],
        [
            'name' => 'annee',
            'label' => 'Année',
            'options' => array_combine($annees ?? [], $annees ?? [])
        ],
        [
            'name' => 'domaine',
            'label' => 'Domaine',
            'options' => array_combine($domaines ?? [], $domaines ?? [])
        ],
        [
            'name' => 'statut',
            'label' => 'Statut',
            'options' => [
                'en_attente' => 'En attente',
                'valide' => 'Validé',
                'rejete' => 'Rejeté'
            ]
        ]
    ];
    
    if (!empty($projets)) {
        $projetOptions = [];
        foreach ($projets as $projet) {
            $projetOptions[$projet['id']] = $projet['titre'];
        }
        $filterOptions[] = [
            'name' => 'projet_id',
            'label' => 'Projet',
            'options' => $projetOptions
        ];
    }
    
    ViewComponents::renderFilters([
        'showSearch' => true,
        'searchPlaceholder' => 'Rechercher une publication...',
        'filters' => $filterOptions
    ]); 
    ?>
    
    <!-- Table des publications -->
    <?php 
    $tableColumns = [
        [
            'key' => 'titre',
            'label' => 'Titre',
            'formatter' => function($value, $row) {
                $statut = $row['statut_validation'] ?? 'en_attente';
                $icon = $statut === 'valide' ? '' : ($statut === 'rejete' ? '' : '');
                return '<div><strong>' . e($value) . '</strong><br><small style="color: #6B7280;">' . $icon . ' ' . ucfirst(str_replace('_', ' ', $statut)) . '</small></div>';
            }
        ],
        [
            'key' => 'type_publication',
            'label' => 'Type',
            'formatter' => function($value) {
                $colors = [
                    'Article' => '#3B82F6',
                    'Conférence' => '#8B5CF6',
                    'Thèse' => '#EC4899',
                    'Rapport' => '#F59E0B',
                    'Livre' => '#10B981',
                    'Chapitre' => '#6366F1'
                ];
                $color = $colors[$value] ?? '#6B7280';
                return '<span class="badge" style="background: ' . $color . ';">' . e($value) . '</span>';
            }
        ],
        [
            'key' => 'date_publication',
            'label' => 'Date',
            'formatter' => function($value) {
                return date('d/m/Y', strtotime($value));
            }
        ],
        [
            'key' => 'auteurs',
            'label' => 'Auteurs',
            'formatter' => function($value, $row) {
                $auteurs = $row['auteurs_noms'] ?? 'Non défini';
                if (strlen($auteurs) > 50) {
                    return '<span title="' . e($auteurs) . '">' . e(substr($auteurs, 0, 50)) . '...</span>';
                }
                return e($auteurs);
            }
        ],
        [
            'key' => 'domaine',
            'label' => 'Domaine',
            'formatter' => function($value) {
                return $value ? '<span class="badge badge-secondary">' . e($value) . '</span>' : '-';
            }
        ]
    ];
    
    if (!empty($publications[0]['doi'])) {
        $tableColumns[] = [
            'key' => 'doi',
            'label' => 'DOI',
            'formatter' => function($value) {
                return $value ? '<code style="font-size: 11px;">' . e($value) . '</code>' : '-';
            }
        ];
    }
    
    $tableActions = [
        function($row) {
            return '<button class="btn-action btn-view" 
                            onclick="viewItem(' . $row['id'] . ')" 
                            title="Voir les détails">
                        voir
                    </button>';
        },
        function($row) {
            $statut = $row['statut_validation'] ?? 'en_attente';
            if ($statut === 'en_attente') {
                return '<button class="btn-action btn-success" 
                                onclick="validerPublication(' . $row['id'] . ')" 
                                title="Valider">
                            valider
                        </button>';
            }
            return '';
        },
        function($row) {
            $statut = $row['statut_validation'] ?? 'en_attente';
            if ($statut === 'en_attente' || $statut === 'valide') {
                return '<button class="btn-action btn-warning" 
                                onclick="rejeterPublication(' . $row['id'] . ')" 
                                title="Rejeter">
                            rejeter
                        </button>';
            }
            return '';
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
        'data' => $publications ?? [],
        'columns' => $tableColumns,
        'actions' => $tableActions,
        'emptyMessage' => 'Aucune publication trouvée'
    ]); 
    ?>
    
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
    'content' => '<div id="modal-form-container"></div>'
]); ?>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
}

.stat-details {
    flex: 1;
}

.stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #1F2937;
}

.stat-label {
    font-size: 13px;
    color: #6B7280;
    margin-top: 4px;
}

.btn-success {
    background: #10B981;
    color: white;
}

.btn-success:hover {
    background: #059669;
}

.btn-warning {
    background: #F59E0B;
    color: white;
}

.btn-warning:hover {
    background: #D97706;
}

.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    color: white;
}

.badge-secondary {
    background: #6B7280;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .page-actions {
        flex-direction: column;
        gap: 10px;
    }
    
    .page-actions button {
        width: 100%;
    }
}
</style>

<?php ViewComponents::renderFooter(); ?>