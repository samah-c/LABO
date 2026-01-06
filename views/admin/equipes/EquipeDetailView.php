<?php
/**
 * Vue détaillée d'une équipe
 */
require_once __DIR__ . '/../../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/NavigationComponent.php';
require_once __DIR__ . '/../../../lib/components/PageHeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/FooterComponent.php';
require_once __DIR__ . '/../../../lib/components/ModalComponent.php';

class EquipeDetailView
{
    private array $equipe;
    private array $publications;
    private array $ressources;

    public function __construct(array $equipe, array $publications = [], array $ressources = [])
    {
        $this->equipe = $equipe;
        $this->publications = $publications;
        $this->ressources = $ressources;
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
        $this->renderDescription();
        $this->renderInfoCards();
        $this->renderMembres();
        $this->renderPublications();
        $this->renderRessources();
        echo '</div>';
        $this->renderModal();
        $this->renderStyles();
        $this->renderFooter();
    }

    /**
     * Rendu de l'en-tête
     */
    private function renderHeader(): void
    {
        HeaderComponent::render([
            'title' => 'Détails de l\'équipe',
            'username' => session('username'),
            'role' => 'admin',
            'additionalJs' => [
                base_url('assets/js/ui.js'),
                base_url('assets/js/admin/equipes-handler.js')
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
            ['label' => 'Équipes', 'url' => base_url('admin/equipes/equipes')],
            ['label' => htmlspecialchars($this->equipe['nom'] ?? 'Détails')]
        ]);
    }

    /**
     * Rendu de l'en-tête de page
     */
    private function renderPageHeader(): void
    {
        $titleHtml = '<h1>' . htmlspecialchars($this->equipe['nom']) . '</h1>
                      <p style="color: #6B7280; margin-top: 8px;">
                          ' . htmlspecialchars($this->equipe['domaine']) . ' • Créée le ' . format_date($this->equipe['date_creation']) . '
                      </p>';

        PageHeaderComponent::render([
            'titleHtml' => $titleHtml,
            'actions' => [
                [
                    'type' => 'button',
                    'label' => 'Modifier',
                    'onclick' => 'editItem(' . $this->equipe['id'] . ')',
                    'class' => 'btn-secondary'
                ],
                [
                    'type' => 'button',
                    'label' => ' Supprimer',
                    'onclick' => 'deleteItem(' . $this->equipe['id'] . ')',
                    'class' => 'btn-secondary'
                ]
            ]
        ]);
    }

    /**
     * Rendu de la description
     */
    private function renderDescription(): void
    {
        ?>
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h2> Description</h2>
            </div>
            <div class="card-body">
                <p style="line-height: 1.6; color: #374151;">
                    <?= nl2br(htmlspecialchars($this->equipe['description'])) ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu des cartes d'informations (Chef + Statistiques)
     */
    private function renderInfoCards(): void
    {
        echo '<div class="grid-2-cols">';
        $this->renderChefCard();
        $this->renderStatsCard();
        echo '</div>';
    }

    /**
     * Rendu de la carte Chef d'équipe
     */
    private function renderChefCard(): void
    {
        ?>
        <div class="card">
            <div class="card-header">
                <h2> Chef d'équipe</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($this->equipe['chef_id'])): ?>
                    <?php
                    $chef = null;
                    foreach ($this->equipe['membres'] as $membre) {
                        if ($membre['id'] == $this->equipe['chef_id']) {
                            $chef = $membre;
                            break;
                        }
                    }
                    ?>
                    
                    <?php if ($chef): ?>
                        <div class="membre-card">
                            <div class="membre-info">
                                <strong><?= htmlspecialchars($chef['username']) ?></strong>
                                <?php if (!empty($chef['grade'])): ?>
                                    <br><?= LabHelpers::getGradeBadge($chef['grade']) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <p style="color: #9CA3AF;">Chef d'équipe non trouvé</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p style="color: #9CA3AF;">Aucun chef d'équipe assigné</p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu de la carte Statistiques
     */
    private function renderStatsCard(): void
    {
        ?>
        <div class="card">
            <div class="card-header">
                <h2> Statistiques</h2>
            </div>
            <div class="card-body">
                <div class="stats-list">
                    <div class="stat-item">
                        <span class="stat-label">Membres</span>
                        <span class="stat-value"><?= count($this->equipe['membres']) ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Publications</span>
                        <span class="stat-value"><?= count($this->publications) ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Ressources</span>
                        <span class="stat-value"><?= count($this->ressources) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu de la section Membres
     */
    private function renderMembres(): void
    {
        ?>
        <div class="card" style="margin-top: 24px;">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h2> Membres de l'équipe (<?= count($this->equipe['membres']) ?>)</h2>
                <button class="btn-primary btn-sm" style="width: fit-content;"
                        onclick="equipes.openAddMembreModal(<?= $this->equipe['id'] ?>)">
                     Ajouter un membre
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($this->equipe['membres'])): ?>
                    <p style="color: #9CA3AF; text-align: center; padding: 20px;">
                        Aucun membre dans cette équipe
                    </p>
                <?php else: ?>
                    <div class="membres-grid">
                        <?php foreach ($this->equipe['membres'] as $membre): ?>
                            <?php $this->renderMembreCard($membre); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu d'une carte membre
     */
    private function renderMembreCard(array $membre): void
    {
        ?>
        <div class="membre-card">
            <div class="membre-avatar">
                <?= Utils::getInitials($membre['username']) ?>
            </div>
            <div class="membre-info">
                <strong><?= htmlspecialchars($membre['username']) ?></strong>
                <?php if (!empty($membre['grade'])): ?>
                    <br><small><?= htmlspecialchars($membre['grade']) ?></small>
                <?php endif; ?>
                <?php if ($membre['id'] == $this->equipe['chef_id']): ?>
                    <br><span class="badge badge-purple">Chef d'équipe</span>
                <?php endif; ?>
            </div>
            <div class="membre-actions">
                <button class="btn-icon" 
                        onclick="equipes.removeMembre(<?= $membre['id'] ?>, '<?= htmlspecialchars($membre['username']) ?>')"
                        title="Retirer de l'équipe">
                     Retirer
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu de la section Publications
     */
    private function renderPublications(): void
    {
        ?>
        <div class="card" style="margin-top: 24px;">
            <div class="card-header">
                <h2> Publications de l'équipe (<?= count($this->publications) ?>)</h2>
            </div>
            <div class="card-body">
                <?php if (empty($this->publications)): ?>
                    <p style="color: #9CA3AF; text-align: center; padding: 20px;">
                        Aucune publication pour cette équipe
                    </p>
                <?php else: ?>
                    <div class="publications-list">
                        <?php foreach ($this->publications as $pub): ?>
                            <?php $this->renderPublicationItem($pub); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu d'un item de publication
     */
    private function renderPublicationItem(array $pub): void
    {
        ?>
        <div class="publication-item">
            <div>
                <strong><?= htmlspecialchars($pub['titre']) ?></strong>
                <br>
                <small style="color: #6B7280;">
                    <?= LabHelpers::getPublicationTypeBadge($pub['type_publication']) ?>
                    • <?= format_date($pub['date_publication']) ?>
                </small>
            </div>
            <a href="<?= base_url('admin/publications/publications/view/' . $pub['id']) ?>" 
               class="btn-secondary btn-sm">
                 Voir
            </a>
        </div>
        <?php
    }

    /**
     * Rendu de la section Ressources
     */
    private function renderRessources(): void
    {
        if (empty($this->ressources)) {
            return;
        }
        ?>
        <div class="card" style="margin-top: 24px;">
            <div class="card-header">
                <h2> Ressources allouées (<?= count($this->ressources) ?>)</h2>
            </div>
            <div class="card-body">
                <div class="ressources-list">
                    <?php foreach ($this->ressources as $ressource): ?>
                        <?php $this->renderRessourceItem($ressource); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu d'un item de ressource
     */
    private function renderRessourceItem(array $ressource): void
    {
        ?>
        <div class="ressource-item">
            <div>
                <strong><?= htmlspecialchars($ressource['nom']) ?></strong>
                <br>
                <small style="color: #6B7280;">
                    <?= htmlspecialchars($ressource['type_equipement']) ?>
                    • <?= LabHelpers::getEquipementEtatBadge($ressource['etat']) ?>
                </small>
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
            'id' => 'equipe-modal',
            'title' => 'Ajouter un membre',
            'content' => '<div id="modal-form-container"></div>',
            'size' => 'medium'
        ]);
    }

    /**
     * Rendu des styles CSS
     */
    private function renderStyles(): void
    {
        ?>
        <style>
        .grid-2-cols {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }

        .card {
            background: white;
            border-radius: 12px;
            border: 1px solid #E5E7EB;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #E5E7EB;
        }

        .card-header h2 {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
            margin: 0;
        }

        .card-body {
            padding: 24px;
        }

        .stats-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: #F9FAFB;
            border-radius: 8px;
        }

        .stat-label {
            color: #6B7280;
            font-weight: 500;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #5B7FFF;
        }

        .membres-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
        }

        .membre-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: #F9FAFB;
            border-radius: 8px;
            border: 1px solid #E5E7EB;
        }

        .membre-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #5B7FFF;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            flex-shrink: 0;
        }

        .membre-info {
            flex: 1;
        }

        .membre-info strong {
            color: #111827;
        }

        .membre-info small {
            color: #6B7280;
        }

        .membre-actions {
            display: flex;
            gap: 8px;
        }

        .btn-icon {
            background: none;
            border: 1px solid #E5E7EB;
            cursor: pointer;
            padding: 6px 12px;
            font-size: 13px;
            border-radius: 6px;
            transition: all 0.2s;
            color: #EF4444;
        }

        .btn-icon:hover {
            background: #FEE2E2;
            border-color: #EF4444;
        }

        .btn-sm {
            padding: 6px 14px;
            font-size: 13px;
        }

        .publications-list,
        .ressources-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .publication-item,
        .ressource-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: #F9FAFB;
            border-radius: 8px;
            border: 1px solid #E5E7EB;
        }
        </style>
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