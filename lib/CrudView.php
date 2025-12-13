<?php
/**
 * CrudView.php - Classe de base pour les vues CRUD
 * 
 * Cette classe permet de générer automatiquement des vues CRUD
 * pour n'importe quelle entité (projets, publications, équipements, etc.)
 */

require_once __DIR__ . '/ViewComponents.php';
require_once __DIR__ . '/helpers.php';

abstract class CrudView {
    protected $entityName;        // 'projet', 'publication', etc.
    protected $entityNamePlural;  // 'projets', 'publications', etc.
    protected $baseUrl;           // 'admin/projets'
    
    /**
     * Configuration de l'entité (à définir dans les classes enfants)
     */
    abstract protected function getColumns();
    abstract protected function getFormFields();
    abstract protected function getFilters();
    
    /**
     * Rendu de la page principale
     */
    public function render($data = []) {
        $items = $data['items'] ?? [];
        $pagination = $data['pagination'] ?? null;
        
        // Header
        ViewComponents::renderHeader([
            'title' => "Gestion des " . ucfirst($this->entityNamePlural),
            'username' => session('username'),
            'role' => 'admin',
            'additionalJs' => [
                base_url("assets/js/crud-handler.js")
            ]
        ]);
        ?>
        
        <div class="container">
            <!-- Breadcrumbs -->
            <?php ViewComponents::renderBreadcrumbs([
                ['label' => 'Dashboard', 'url' => base_url('admin/dashboard')],
                ['label' => ucfirst($this->entityNamePlural)]
            ]); ?>
            
            <!-- Header -->
            <div class="page-header">
                <div class="page-actions">
                    <button class="btn-primary" 
                            onclick="CrudHandler.openAddModal('<?= $this->entityName ?>')">
                         Nouveau
                    </button>
                    <button class="btn-secondary" 
                            onclick="CrudHandler.export('<?= $this->baseUrl ?>')">
                        Exporter
                    </button>
                </div>
            </div>
            
            <!-- Filtres -->
            <?php 
            $filters = $this->getFilters();
            if (!empty($filters)) {
                ViewComponents::renderFilters([
                    'showSearch' => true,
                    'searchPlaceholder' => "Rechercher un(e) {$this->entityName}...",
                    'filters' => $filters
                ]);
            }
            ?>
            
            <!-- Table -->
            <?php ViewComponents::renderTable([
                'data' => $items,
                'columns' => $this->getColumns(),
                'actions' => $this->getActions(),
                'emptyMessage' => "Aucun(e) {$this->entityName} trouvé(e)"
            ]); ?>
            
            <!-- Pagination -->
            <?php 
            if ($pagination) {
                echo Utils::renderPagination($pagination, base_url($this->baseUrl));
            }
            ?>
        </div>
        
        <!-- Modale -->
        <?php $this->renderModal(); ?>
        
        <script>
        // Configuration pour le handler JavaScript
        window.crudConfig = {
            entityName: '<?= $this->entityName ?>',
            baseUrl: '<?= base_url($this->baseUrl) ?>',
            apiUrl: '<?= base_url("api/admin/{$this->entityNamePlural}") ?>'
        };
        </script>
        
        <?php
        ViewComponents::renderFooter();
    }
    
    /**
     * Actions par défaut
     */
    protected function getActions() {
        return [
            function($row) {
                return '<button class="btn-action btn-view" 
                                onclick="CrudHandler.view(' . $row['id'] . ')"
                                title="Voir">
                            voir
                        </button>';
            },
            function($row) {
                return '<button class="btn-action btn-edit" 
                                onclick="CrudHandler.edit(' . $row['id'] . ')"
                                title="Modifier">
                            ✏️
                        </button>';
            },
            function($row) {
                return '<button class="btn-action btn-delete" 
                                onclick="CrudHandler.delete(' . $row['id'] . ')"
                                title="Supprimer">
                            🗑️
                        </button>';
            }
        ];
    }
    
    /**
     * Modale par défaut
     */
    protected function renderModal() {
        ViewComponents::renderModal([
            'id' => $this->entityName . '-modal',
            'title' => 'Ajouter un(e) ' . $this->entityName,
            'content' => '<div id="modal-form-container"></div>'
        ]);
    }
    
    /**
     * Rendu du formulaire
     */
    public function renderForm($data = []) {
        ViewComponents::renderForm([
            'action' => base_url($this->baseUrl . '/save'),
            'method' => 'POST',
            'fields' => $this->getFormFields(),
            'submitText' => 'Enregistrer'
        ]);
    }
}


?>