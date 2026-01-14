<?php
/**
 * Vue de la liste des publications du membre
 */
require_once __DIR__ . '/../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../lib/components/NavigationComponent.php';
require_once __DIR__ . '/../../lib/components/PageHeaderComponent.php';
require_once __DIR__ . '/../../lib/components/FilterComponent.php';
require_once __DIR__ . '/../../lib/components/ModalComponent.php';
require_once __DIR__ . '/../../lib/components/FormComponent.php';
require_once __DIR__ . '/../../lib/components/FooterComponent.php';

class PublicationsView
{
    private array $publications;
    private ?array $pagination;

    public function __construct(array $publications, ?array $pagination = null)
    {
        $this->publications = $publications;
        $this->pagination = $pagination;
    }

    public function render(): void
    {
        $this->renderHeader();
        $this->renderNavigation();
        echo '<div class="container">';
        $this->renderBreadcrumbs();
        $this->renderPageHeader();
        $this->renderFlashMessages();
        $this->renderFilters();
        $this->renderPublicationsList();
        $this->renderPagination();
        echo '</div>';
        $this->renderModal();
        $this->renderStyles();
        $this->renderFooter();
    }

    private function renderHeader(): void
    {
        HeaderComponent::render([
            'title' => 'Mes Publications - Espace Membre',
            'username' => session('username'),
            'role' => 'membre',
            'showLogout' => true,
            'showNotifications' => true,
            'additionalJs' => [
            base_url('assets/js/member/membre-notifications.js') 
        ]
        ]);
    }

    private function renderNavigation(): void
    {
        NavigationComponent::renderSidebar('membre');
    }

    private function renderBreadcrumbs(): void
    {
        NavigationComponent::renderBreadcrumbs([
            ['label' => 'Tableau de bord', 'url' => base_url('membre/dashboard')],
            ['label' => 'Mes Publications']
        ]);
    }

    private function renderPageHeader(): void
    {
        PageHeaderComponent::render([
            'title' => 'Mes Publications',
            'subtitle' => 'Gérez vos publications scientifiques',
            'actions' => [
                [
                    'type' => 'modal',
                    'label' => 'Nouvelle publication',
                    'modalId' => 'publication-modal',
                    'class' => 'btn-primary'
                ]
            ]
        ]);
    }

    private function renderFlashMessages(): void
    {
        if (has_flash('success')) {
            echo '<div class="alert alert-success">' . flash('success') . '</div>';
        }
        if (has_flash('error')) {
            echo '<div class="alert alert-error">' . flash('error') . '</div>';
        }
    }

    private function renderFilters(): void
    {
        FilterComponent::render([
            'action' => base_url('membre/publications'),
            'showSearch' => true,
            'searchPlaceholder' => 'Rechercher une publication...',
            'filters' => [
                [
                    'type' => 'select',
                    'name' => 'statut',
                    'label' => 'Statut',
                    'options' => [
                        'en_attente' => 'En attente',
                        'valide' => 'Validé',
                        'rejete' => 'Rejeté'
                    ],
                    'defaultLabel' => 'Tous les statuts'
                ],
                [
                    'type' => 'select',
                    'name' => 'type',
                    'label' => 'Type',
                    'options' => [
                        'article' => 'Article',
                        'rapport' => 'Rapport',
                        'these' => 'Thèse',
                        'communication' => 'Communication',
                        'poster' => 'Poster'
                    ],
                    'defaultLabel' => 'Tous les types'
                ]
            ]
        ]);
    }

    private function renderPublicationsList(): void
    {
        if (empty($this->publications)) {
            $this->renderEmptyState();
            return;
        }
        ?>
        <div class="publications-list">
            <?php foreach ($this->publications as $publication): ?>
                <?php $this->renderPublicationCard($publication); ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

private function renderPublicationCard(array $publication): void
{
    ?>
    <div class="publication-card" data-publication-id="<?= $publication['id'] ?>">
        <div class="publication-header">
            <div class="header-content">
                <h3><?= e($publication['titre']) ?></h3>
                <div class="publication-badges">
                    <span class="badge badge-type"><?= e($publication['type_publication']) ?></span>
                    <span class="badge badge-<?= e($publication['statut_validation']) ?>">
                        <?= ucfirst(str_replace('_', ' ', $publication['statut_validation'])) ?>
                    </span>
                    <?php if (!empty($publication['date_publication'])): ?>
                    <span class="meta-date">
                        <?= format_date($publication['date_publication'], 'd/m/Y') ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Actions buttons -->
            <div class="publication-actions">
                <?php if (isset($publication['ordre_auteur']) && $publication['ordre_auteur'] == 1): ?>
                    <!-- Bouton Modifier -->
                    <button 
                        class="btn-icon btn-icon-primary" 
                        onclick="editPublication(<?= $publication['id'] ?>)"
                        title="Modifier la publication"
                        aria-label="Modifier la publication"
                    >
                        <i class="fas fa-edit"></i>
                        Modifier
                    </button>
                    
                    <!-- Bouton Supprimer -->
                    <button 
                        class="btn-icon btn-icon-danger" 
                        onclick="deletePublication(<?= $publication['id'] ?>, '<?= addslashes($publication['titre']) ?>')"
                        title="Supprimer la publication"
                        aria-label="Supprimer la publication"
                    >
                        <i class="fas fa-trash-alt"></i>
                        Supprimer
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($publication['resume'])): ?>
        <div class="publication-resume">
            <?= e(substr($publication['resume'], 0, 200)) ?>
            <?= strlen($publication['resume']) > 200 ? '...' : '' ?>
        </div>
        <?php endif; ?>

        <div class="publication-details">
            <?php if (!empty($publication['doi'])): ?>
            <div class="detail-item">
                <strong>DOI:</strong> <?= e($publication['doi']) ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($publication['lien'])): ?>
            <div class="detail-item">
                <a href="<?= e($publication['lien']) ?>" target="_blank" class="link-external">
                    <i class="fas fa-external-link-alt"></i>
                    Lien vers la publication
                </a>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($publication['lien_telechargement'])): ?>
            <div class="detail-item">
                <a href="<?= e($publication['lien_telechargement']) ?>" target="_blank" class="link-external">
                    <i class="fas fa-download"></i>
                    Télécharger
                </a>
            </div>
            <?php endif; ?>
        </div>

        <div class="publication-footer">
            <div class="order-info">
                <?php if (isset($publication['ordre_auteur'])): ?>
                Position: Auteur n°<?= $publication['ordre_auteur'] ?>
                <?php endif; ?>
            </div>
            
            <?php if ($publication['statut_validation'] === 'en_attente'): ?>
            <span class="status-info">En attente de validation</span>
            <?php elseif ($publication['statut_validation'] === 'valide'): ?>
            <span class="status-info status-success">Publication validée</span>
            <?php elseif ($publication['statut_validation'] === 'rejete'): ?>
            <span class="status-info status-error">Publication rejetée</span>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
    private function renderEmptyState(): void
    {
        ?>
        <div class="empty-state">
            <h3>Aucune publication trouvée</h3>
            <p>Commencez par soumettre votre première publication.</p>
            <button class="btn-primary" onclick="openModal('publication-modal')">
                Soumettre une publication
            </button>
        </div>
        <?php
    }

    private function renderPagination(): void
    {
        if ($this->pagination && $this->pagination['total_pages'] > 1) {
            echo Utils::renderPagination($this->pagination, base_url('membre/publications'));
        }
    }

    private function renderModal(): void
    {
        ModalComponent::renderFormModal([
            'id' => 'publication-modal',
            'title' => 'Nouvelle Publication',
            'size' => 'large',
            'form' => [
                'id' => 'publication-form',
                'action' => base_url('membre/publications/nouveau'),
                'method' => 'POST',
                'fields' => [
                    [
                        'type' => 'html',
                        'content' => '<h4 style="margin: 20px 0 15px 0; color: #2c3e50;"><i class="fas fa-info-circle"></i> Informations générales</h4>'
                    ],
                    [
                        'type' => 'text',
                        'id' => 'titre',
                        'name' => 'titre',
                        'label' => 'Titre *',
                        'required' => true,
                        'placeholder' => 'Titre complet de la publication'
                    ],
                    [
                        'type' => 'select',
                        'id' => 'type_publication',
                        'name' => 'type_publication',
                        'label' => 'Type de publication *',
                        'required' => true,
                        'options' => [
                            '' => 'Sélectionner un type',
                            'article' => 'Article',
                            'rapport' => 'Rapport',
                            'these' => 'Thèse',
                            'communication' => 'Communication',
                            'poster' => 'Poster',
                            'autre' => 'Autre'
                        ]
                    ],
                    [
                        'type' => 'date',
                        'id' => 'date_publication',
                        'name' => 'date_publication',
                        'label' => 'Date de publication *',
                        'required' => true,
                        'attributes' => ['max' => date('Y-m-d')]
                    ],
                    [
                        'type' => 'textarea',
                        'id' => 'resume',
                        'name' => 'resume',
                        'label' => 'Résumé *',
                        'required' => true,
                        'attributes' => ['rows' => 5, 'placeholder' => 'Résumé de la publication (minimum 50 caractères)']
                    ],
                    [
                        'type' => 'text',
                        'id' => 'domaine',
                        'name' => 'domaine',
                        'label' => 'Domaine *',
                        'required' => true,
                        'placeholder' => 'ex: Sécurité, Cloud Computing, IA...'
                    ],
                    [
                        'type' => 'html',
                        'content' => '<h4 style="margin: 20px 0 15px 0; color: #2c3e50;"><i class="fas fa-link"></i> Références</h4>'
                    ],
                    [
                        'type' => 'text',
                        'id' => 'doi',
                        'name' => 'doi',
                        'label' => 'DOI',
                        'placeholder' => 'ex: 10.1234/example.2024.001'
                    ],
                    [
                        'type' => 'url',
                        'id' => 'lien',
                        'name' => 'lien',
                        'label' => 'Lien vers la publication',
                        'placeholder' => 'https://...'
                    ],
                    [
                        'type' => 'url',
                        'id' => 'lien_telechargement',
                        'name' => 'lien_telechargement',
                        'label' => 'Lien de téléchargement',
                        'placeholder' => 'https://...'
                    ],
                    [
                        'type' => 'html',
                        'content' => '<h4 style="margin: 20px 0 15px 0; color: #2c3e50;"><i class="fas fa-project-diagram"></i> Projet associé</h4>'
                    ],
                    [
                        'type' => 'select',
                        'id' => 'projet_id',
                        'name' => 'projet_id',
                        'label' => 'Projet (optionnel)',
                        'options' => [
                            '' => 'Aucun projet'
                        ]
                    ],
                    [
                        'type' => 'html',
                        'content' => '
                            <h4 style="margin: 20px 0 15px 0; color: #2c3e50;">
                                <i class="fas fa-users"></i> Co-auteurs
                            </h4>
                            <div id="coauteurs-container"></div>
                            <button type="button" id="add-coauteur-btn" class="btn btn-secondary btn-sm" style="margin-top: 10px;">
                                <i class="fas fa-plus"></i> Ajouter un co-auteur
                            </button>
                            <small class="form-text" style="display: block; margin-top: 10px; color: #666;">
                                Vous serez automatiquement ajouté comme auteur principal
                            </small>
                        '
                    ]
                ],
                'submitText' => 'Soumettre la publication',
                'cancelUrl' => null
            ]
        ]);
    }

 private function renderStyles(): void
{
    ?>
    <style>
    /* Alerts */
    .alert {
        padding: 16px 20px;
        border-radius: var(--border-radius-sm);
        margin-bottom: 24px;
        font-size: 14px;
    }

    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #6ee7b7;
    }

    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
    }

    /* Publications list */
    .publications-list {
        display: flex;
        flex-direction: column;
        gap: 16px;
        margin-top: 24px;
    }

    .publication-card {
        background: white;
        border-radius: var(--border-radius-lg);
        padding: 20px;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-color);
        transition: var(--transition);
    }

    .publication-card:hover {
        box-shadow: var(--shadow-md);
        transform: translateY(-1px);
    }

    /* Publication Header */
    .publication-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 16px;
        gap: 16px;
    }

    .header-content {
        flex: 1;
        min-width: 0;
    }

    .publication-header h3 {
        margin: 0 0 12px 0;
        font-size: 17px;
        font-weight: 600;
        color: var(--gray-900);
    }

    .publication-badges {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
    }

    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-type {
        background: var(--gray-100);
        color: var(--gray-700);
        text-transform: capitalize;
    }

    .badge-en_attente {
        background: #fef3c7;
        color: #92400e;
    }

    .badge-valide {
        background: #d1fae5;
        color: #065f46;
    }

    .badge-rejete {
        background: #fee2e2;
        color: #991b1b;
    }

    .meta-date {
        color: var(--gray-600);
        font-size: 13px;
    }

    /* Publication Actions - AMÉLIORÉ */
    .publication-actions {
        display: flex;
        gap: 8px;
        flex-shrink: 0;
        align-items: flex-start;
    }

    .btn-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 90px;
        height: 38px;
        padding: 0;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 14px;
        position: relative;
    }

    .btn-icon:hover {
        transform: translateY(-2px);
    }

    .btn-icon:active {
        transform: translateY(0);
    }

    .btn-icon-danger {
        color: #ef4444;
        background: #fee2e2;
        border: 1px solid #fecaca;
    }

    .btn-icon-danger:hover {
        background: #fecaca;
        color: #dc2626;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
    }

    .btn-icon-danger:active {
        background: #fca5a5;
    }

    /* Assurer que l'icône est visible */
    .btn-icon i {
        pointer-events: none;
        font-size: inherit;
        line-height: 1;
    }


    .btn-icon-danger:not(:has(i))::before {
        display: block;
    }

    .publication-resume {
        margin: 16px 0;
        color: var(--gray-700);
        line-height: 1.6;
        font-size: 14px;
    }

    .publication-details {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin: 16px 0;
        padding: 12px;
        background: var(--gray-50);
        border-radius: var(--border-radius-sm);
    }

    .detail-item {
        font-size: 13px;
        color: var(--gray-700);
    }

    .detail-item strong {
        font-weight: 600;
    }

    .link-external {
        color: var(--primary);
        text-decoration: none;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .link-external:hover {
        text-decoration: underline;
    }

    .publication-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 16px;
        border-top: 1px solid var(--border-color);
        font-size: 13px;
    }

    .order-info {
        color: var(--gray-600);
        font-weight: 500;
    }

    .status-info {
        font-weight: 500;
        color: var(--gray-700);
    }

    .status-success {
        color: #065f46;
    }

    .status-error {
        color: #991b1b;
    }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-sm);
        margin-top: 24px;
    }

    .empty-state h3 {
        margin: 0 0 8px 0;
        color: var(--gray-900);
        font-size: 18px;
    }

    .empty-state p {
        color: var(--gray-600);
        margin: 0 0 24px 0;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .publication-header {
            flex-direction: column;
        }
        
        .publication-actions {
            width: 100%;
            justify-content: flex-end;
        }
        
        .publication-footer {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
    }

    /* Animation pour le hover du bouton */
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-2px); }
        75% { transform: translateX(2px); }
    }

    .btn-icon-danger:hover {
        animation: shake 0.3s ease;
    }

    .btn-icon-primary {
    color: #3b82f6;
    background: #dbeafe;
    border: 1px solid #93c5fd;
}

.btn-icon-primary:hover {
    background: #bfdbfe;
    color: #2563eb;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
}
    </style>
    <?php
}

    private function renderFooter(): void
    {
        FooterComponent::render(['role' => 'membre']);
        ?>
        <script>
            const BASE_URL = '<?= base_url() ?>';
        </script>
        <script src="<?= base_url('assets/js/member/publications.js') ?>"></script>
        <?php
    }
}