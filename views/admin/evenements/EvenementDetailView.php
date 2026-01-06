<?php
/**
 * EvenementDetailView.php - Vue détaillée d'un événement
 */
class EvenementDetailView
{
    private array $evenement;
    private ?array $organisateur;

    public function __construct(array $evenement, ?array $organisateur = null)
    {
        $this->evenement = $evenement;
        $this->organisateur = $organisateur;
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
        $this->renderPageHeader();
        $this->renderDetailLayout();
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
            'title' => 'Détails - ' . ($this->evenement['titre'] ?? 'Événement'),
            'username' => session('username'),
            'role' => 'admin',
            'additionalJs' => [
                base_url('assets/js/admin/evenements-handler.js')
            ]
        ]);
    }

    /**
     * Rendu de la navigation
     */
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
            ['label' => 'Événements', 'url' => base_url('admin/evenements/evenements')],
            ['label' => 'Détails']
        ]);
    }

    /**
     * Rendu de l'en-tête de page
     */
    private function renderPageHeader(): void
    {
        $titleHtml = '
            <div class="title-row">
                <h1>' . htmlspecialchars($this->evenement['titre']) . '</h1>
                <button class="btn-delete" onclick="deleteItem(' . $this->evenement['id'] . ')">
                    Supprimer
                </button>
            </div>
        ';

        PageHeaderComponent::render([
            'titleHtml' => $titleHtml,
            'actions' => [
                [
                    'type' => 'link',
                    'label' => 'Retour',
                    'url' => base_url('admin/evenements/evenements'),
                    'class' => 'btn-secondary'
                ],
                [
                    'type' => 'button',
                    'label' => 'Modifier',
                    'onclick' => 'editItem(' . $this->evenement['id'] . ')',
                    'class' => 'btn-primary'
                ]
            ]
        ]);
    }

    /**
     * Rendu de la mise en page détaillée
     */
    private function renderDetailLayout(): void
    {
        echo '<div class="detail-layout">';
        $this->renderInformationsCard();
        $this->renderDescriptionCard();
        $this->renderTwoColumnCards();
        echo '</div>';
    }

    /**
     * Rendu de la carte Informations
     */
    private function renderInformationsCard(): void
    {
        $types = [
            'conference' => 'Conférence',
            'atelier' => 'Atelier',
            'seminaire' => 'Séminaire',
            'soutenance' => 'Soutenance',
            'autre' => 'Autre'
        ];
        $typeLabel = $types[$this->evenement['type_evenement']] ?? $this->evenement['type_evenement'];
        
        $isUpcoming = strtotime($this->evenement['date_evenement']) > time();
        $badgeClass = $isUpcoming ? 'info' : 'secondary';
        $statutText = $isUpcoming ? 'À venir' : 'Terminé';
        ?>
        <div class="detail-card">
            <h2> Informations</h2>
            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">Type</span>
                    <span class="badge blue"><?= htmlspecialchars($typeLabel) ?></span>
                </div>

                <div class="info-row">
                    <span class="info-label">Date</span>
                    <span><?= date('d/m/Y à H:i', strtotime($this->evenement['date_evenement'])) ?></span>
                </div>

                <div class="info-row">
                    <span class="info-label">Lieu</span>
                    <span><?= htmlspecialchars($this->evenement['lieu']) ?></span>
                </div>

                <div class="info-row">
                    <span class="info-label">Statut</span>
                    <span class="badge <?= $badgeClass ?>"><?= $statutText ?></span>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu de la carte Description
     */
    private function renderDescriptionCard(): void
    {
        ?>
        <div class="detail-card">
            <h2> Description</h2>
            <div class="description-content">
                <?= nl2br(htmlspecialchars($this->evenement['description'])) ?>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu des cartes en deux colonnes
     */
    private function renderTwoColumnCards(): void
    {
        echo '<div class="two-column-grid">';
        
        if ($this->organisateur) {
            $this->renderOrganisateurCard();
        }
        
        if (!empty($this->evenement['lien_inscription'])) {
            $this->renderInscriptionCard();
        }
        
        echo '</div>';
    }

    /**
     * Rendu de la carte Organisateur
     */
    private function renderOrganisateurCard(): void
    {
        ?>
        <div class="detail-card">
            <h2>👤 Organisateur</h2>
            <div class="organisateur-info">
                <div class="organisateur-avatar">
                    <?= strtoupper($this->organisateur['username'][0]) ?>
                </div>
                <div>
                    <div class="organisateur-name"><?= htmlspecialchars($this->organisateur['username']) ?></div>
                    <?php if ($this->organisateur['grade']): ?>
                        <div class="organisateur-grade"><?= htmlspecialchars($this->organisateur['grade']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu de la carte Inscription
     */
    private function renderInscriptionCard(): void
    {
        ?>
        <div class="detail-card">
            <h2> Inscription</h2>
            <a href="<?= htmlspecialchars($this->evenement['lien_inscription']) ?>" 
               target="_blank" 
               class="inscription-link">
                 Lien d'inscription
            </a>
        </div>
        <?php
    }

    /**
     * Rendu de la modale
     */
    private function renderModal(): void
    {
        ModalComponent::render([
            'id' => 'evenement-modal',
            'title' => 'Modifier l\'événement',
            'content' => '<div id="modal-form-container"></div>',
            'size' => 'medium'
        ]);
    }

    /**
     * Rendu des styles
     */
    private function renderStyles(): void
    {
        ?>
        <style>
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

        .organisateur-info {
            display:flex;
            align-items:center;
            gap:12px;
            padding:10px;
            background:#f9fafb;
            border-radius:8px;
        }

        .organisateur-avatar {
            width:50px;
            height:50px;
            background:linear-gradient(135deg,#667eea,#764ba2);
            color:white;
            border-radius:50%;
            display:flex;
            align-items:center;
            justify-content:center;
            font-weight:bold;
            font-size:20px;
        }

        .organisateur-name {
            font-weight:600;
            font-size:16px;
        }

        .organisateur-grade {
            font-size:13px;
            color:#6b7280;
        }

        .description-content {
            line-height:1.7;
            color:#374151;
        }

        .inscription-link {
            display:inline-block;
            padding:12px 24px;
            background:#3B82F6;
            color:white;
            text-decoration:none;
            border-radius:8px;
            font-weight:600;
            transition:background 0.3s;
        }

        .inscription-link:hover {
            background:#2563EB;
        }

        .badge {
            padding:4px 12px;
            border-radius:12px;
            font-size:13px;
            color:white;
            display:inline-block;
        }

        .blue { background:#3b82f6; }
        .info { background:#3b82f6; }
        .secondary { background:#6b7280; }

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
     * Rendu des scripts
     */
    private function renderScripts(): void
    {
        ?>
        <script>
        function editItem(id){
            if (window.evenements) {
                window.evenements.edit(id);
            } else {
                location.href = "<?= base_url('admin/evenements/evenements') ?>";
            }
        }

        function deleteItem(id){
            if (window.evenements) {
                window.evenements.delete(id);
            } else {
                alert("Erreur: Handler non initialisé");
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            console.log('Page détail événement chargée');
            console.log('Handler evenements:', window.evenements);
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