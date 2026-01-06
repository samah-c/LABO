<?php

/**
 * Vue détaillée d'une publication
 */
require_once __DIR__ . '/../../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/NavigationComponent.php';  
require_once __DIR__ . '/../../../lib/components/PageHeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/TableComponent.php';
require_once __DIR__ . '/../../../lib/components/FilterComponent.php';
require_once __DIR__ . '/../../../lib/components/FooterComponent.php';
require_once __DIR__ . '/../../../lib/components/ModalComponent.php';

class PublicationDetailView
{
    private array $publication;
    private array $auteurs;
    private ?array $projet;

    public function __construct(
        array $publication,
        array $auteurs,
        ?array $projet = null
    ) {
        $this->publication = $publication;
        $this->auteurs = $auteurs;
        $this->projet = $projet;
    }

    /**
     * Rendu complet de la vue
     */
    public function render(): void
    {
        $this->renderHeader();
        $this->renderNavigation();
        echo '<div class="container">';
        $this->renderBreadcrumbs();
        $this->renderDetailHeader();
        echo '<div class="detail-layout">';
        $this->renderInfoCard();
        $this->renderResumeCard();
        echo '<div class="two-column-grid">';
        $this->renderAuteursCard();
        $this->renderProjetCard();
        echo '</div>';
        echo '</div>';
        echo '</div>';
        $this->renderModal();
        $this->renderStyles();
        $this->renderScripts();
        $this->renderFooter();
    }

    /**
     * Rendu de l'en-tête
     */
    private function renderHeader(): void
    {
        HeaderComponent::render([
            'title' => 'Détails - ' . ($this->publication['titre'] ?? 'Publication'),
            'username' => session('username'),
            'role' => 'admin',
            'additionalJs' => [
                base_url('assets/js/admin/publications-handler.js')
            ]
        ]);
    }

     private function renderNavigation(): void
    {
        NavigationComponent::renderSidebar('admin');
    }


    /**
     * Rendu du fil d'Ariane
     */
    private function renderBreadcrumbs(): void
    {
        NavigationComponent::renderBreadcrumbs([
            ['label' => 'Dashboard', 'url' => base_url('admin/dashboard')],
            ['label' => 'Publications', 'url' => base_url('admin/publications/publications')],
            ['label' => 'Détails']
        ]);
    }

    /**
     * Rendu de l'en-tête de détail
     */
    private function renderDetailHeader(): void
    {
        $statut = $this->publication['statut_validation'] ?? 'en_attente';
        ?>
        <div class="detail-header">
            <div class="title-row">
                <h1><?= e($this->publication['titre']) ?></h1>
                <button class="btn-delete" onclick="deleteItem(<?= $this->publication['id'] ?>)">
                     Supprimer
                </button>
            </div>

            <div class="detail-actions">
                <a href="<?= base_url('admin/publications/publications') ?>" class="btn-secondary">
                    Retour
                </a>
                
                <?php if ($statut === 'en_attente'): ?>
                    <button class="btn-success" onclick="validerPublication(<?= $this->publication['id'] ?>)">
                        Valider
                    </button>
                    <button class="btn-warning" onclick="rejeterPublication(<?= $this->publication['id'] ?>)">
                        Rejeter
                    </button>
                <?php endif; ?>
                
                <button class="btn-primary" onclick="editItem(<?= $this->publication['id'] ?>)">
                     Modifier
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu de la carte d'informations
     */
    private function renderInfoCard(): void
    {
        $statut = $this->publication['statut_validation'] ?? 'en_attente';
        $badgeClass = $statut === 'valide' ? 'success' : ($statut === 'rejete' ? 'danger' : 'orange');
        $statutText = $statut === 'valide' ? 'Validé' : ($statut === 'rejete' ? 'Rejeté' : 'En attente');
        ?>
        <div class="detail-card">
            <h2> Informations</h2>
            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">Type</span>
                    <span class="badge blue"><?= e($this->publication['type_publication']) ?></span>
                </div>

                <div class="info-row">
                    <span class="info-label">Date</span>
                    <span><?= date('d/m/Y', strtotime($this->publication['date_publication'])) ?></span>
                </div>

                <?php if (!empty($this->publication['domaine'])): ?>
                <div class="info-row">
                    <span class="info-label">Domaine</span>
                    <span class="badge gray"><?= e($this->publication['domaine']) ?></span>
                </div>
                <?php endif; ?>

                <div class="info-row">
                    <span class="info-label">Statut</span>
                    <span class="badge <?= $badgeClass ?>"><?= $statutText ?></span>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu de la carte de résumé
     */
    private function renderResumeCard(): void
    {
        ?>
        <div class="detail-card">
            <h2> Résumé</h2>
            <div class="resume-content">
                <?= nl2br(e($this->publication['resume'])) ?>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu de la carte des auteurs
     */
    private function renderAuteursCard(): void
    {
        ?>
        <div class="detail-card">
            <h2>Auteurs</h2>

            <?php foreach ($this->auteurs as $auteur): ?>
            <div class="author-item">
                <div class="author-avatar">
                    <?= strtoupper($auteur['username'][0]) ?>
                </div>
                <div>
                    <div class="author-name"><?= e($auteur['username']) ?></div>
                    <div class="author-team"><?= e($auteur['equipe_nom']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Rendu de la carte du projet
     */
    private function renderProjetCard(): void
    {
        if (!$this->projet) {
            return;
        }
        ?>
        <div class="detail-card">
            <h2>Projet associé</h2>
            <strong><?= e($this->projet['titre']) ?></strong>
            <div class="badge gray" style="margin-top:10px;">
                <?= e($this->projet['thematique']) ?>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu de la modale
     */
    private function renderModal(): void
    {
        ModalComponent::render([
            'id' => 'publication-modal',
            'title' => 'Modifier la publication',
            'content' => '<div id="modal-form-container"></div>'
        ]);
    }

    /**
     * Rendu des styles CSS
     */
    private function renderStyles(): void
    {
        ?>
        <style>
        .detail-header {
            background:white;
            padding:28px;
            border-radius:12px;
            margin-bottom:30px;
            box-shadow:0 2px 10px rgba(0,0,0,.08);
        }

        .title-row {
            display:flex;
            justify-content:space-between;
            align-items:center;
        }

        .btn-delete {
            background:#dc2626;
            color:white;
            border:none;
            padding:8px 14px;
            border-radius:6px;
            cursor:pointer;
        }

        .detail-actions {
            margin-top:16px;
            display:flex;
            gap:12px;
        }

        .btn-success {
            background:#10B981;
            color:white;
            border:none;
            padding:10px 20px;
            border-radius:6px;
            cursor:pointer;
            font-weight:600;
        }

        .btn-success:hover {
            background:#059669;
        }

        .btn-warning {
            background:#F59E0B;
            color:white;
            border:none;
            padding:10px 20px;
            border-radius:6px;
            cursor:pointer;
            font-weight:600;
        }

        .btn-warning:hover {
            background:#D97706;
        }

        .detail-layout {
            display:flex;
            flex-direction:column;
            gap:24px;
        }

        .two-column-grid {
            display:grid;
            grid-template-columns: 1fr 1fr;
            gap:24px;
        }

        .detail-card {
            background:white;
            padding:24px;
            border-radius:12px;
            box-shadow:0 2px 10px rgba(0,0,0,.08);
        }

        .detail-card h2 {
            margin-bottom:16px;
            border-bottom:2px solid #eee;
            padding-bottom:8px;
        }

        .info-grid {
            display:grid;
            grid-template-columns: repeat(4, 1fr);
            gap:24px;
        }

        .info-row {
            display:flex;
            flex-direction:column;
            gap:8px;
        }

        .info-label {
            color:#6b7280;
            font-weight:600;
        }

        .author-item {
            display:flex;
            align-items:center;
            gap:12px;
            padding:10px;
            background:#f9fafb;
            border-radius:8px;
            margin-bottom:10px;
        }

        .author-avatar {
            width:40px;
            height:40px;
            background:linear-gradient(135deg,#667eea,#764ba2);
            color:white;
            border-radius:50%;
            display:flex;
            align-items:center;
            justify-content:center;
            font-weight:bold;
        }

        .author-name {
            font-weight:600;
        }

        .author-team {
            font-size:13px;
            color:#6b7280;
        }

        .resume-content {
            line-height:1.7;
            color:#374151;
        }

        .badge {
            padding:4px 12px;
            border-radius:12px;
            font-size:13px;
            color:white;
            display:inline-block;
        }

        .blue { background:#3b82f6; }
        .gray { background:#6b7280; }
        .orange { background:#f59e0b; }
        .success { background:#10B981; }
        .danger { background:#EF4444; }

        @media(max-width:768px){
            .two-column-grid {
                grid-template-columns:1fr;
            }
            
            .info-grid {
                grid-template-columns:repeat(2, 1fr);
                gap:16px;
            }
        }

        @media(max-width:480px){
            .info-grid {
                grid-template-columns:1fr;
            }
        }
        </style>
        <?php
    }

    /**
     * Rendu des scripts JavaScript
     */
    private function renderScripts(): void
    {
        ?>
        <script>
        // Attendre que le handler soit chargé
        function editItem(id){
            console.log('editItem appelé avec ID:', id);
            console.log('window.publications existe?', !!window.publications);
            
            if (window.publications) {
                window.publications.edit(id);
            } else {
                console.error('Handler publications non chargé, attente...');
                setTimeout(() => {
                    if (window.publications) {
                        window.publications.edit(id);
                    } else {
                        console.error('Handler toujours non chargé, redirection...');
                        location.href = "<?= base_url('admin/publications/publications') ?>";
                    }
                }, 100);
            }
        }

        function deleteItem(id){
            if (window.publications) {
                window.publications.delete(id);
            } else {
                alert("Erreur: Handler non initialisé");
            }
        }

        function validerPublication(id){
            if (window.publications) {
                window.publications.valider(id);
            } else {
                alert("Erreur: Handler non initialisé");
            }
        }

        function rejeterPublication(id){
            if (window.publications) {
                window.publications.rejeter(id);
            } else {
                alert("Erreur: Handler non initialisé");
            }
        }

        // Vérifier que le script est bien chargé
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Page détail chargée');
            console.log('Handler publications:', window.publications);
        });
        </script>
        <?php
    }

    /**
     * Rendu du pied de page
     */
    private function renderFooter(): void
    {
        FooterComponent::render(['role' => 'admin']);
    }
}