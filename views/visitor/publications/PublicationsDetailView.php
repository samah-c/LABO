<?php
/**
 * Vue détail d'une publication (visiteur)
 */
require_once __DIR__ . '/../../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/NavigationComponent.php';
require_once __DIR__ . '/../../../lib/components/FooterComponent.php';

class PublicationsDetailView
{
    private array $publication;
    private array $auteurs;
    private ?array $projet;

    public function __construct(array $publication, array $auteurs, ?array $projet = null)
    {
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
        echo '<div class="visitor-container">';
        echo '<div class="container detail-container">';
        $this->renderBreadcrumbs();
        $this->renderPublicationHeader();
        $this->renderDetailLayout();
        echo '</div>';
        echo '</div>';
        $this->renderFooter();
        $this->renderStyles();
    }

    /**
     * Rendu de l'en-tête
     */
    private function renderHeader(): void
    {
        HeaderComponent::render([
            'title' => $this->publication['titre'] . ' - Laboratoire TDW',
            'role' => 'visiteur',
            'showLogout' => false,
            'showLoginButton' => true
        ]);
    }

    /**
     * Rendu de la navigation
     */
    private function renderNavigation(): void
    {
        NavigationComponent::renderHorizontalMenu();
    }

    /**
     * Rendu du fil d'Ariane
     */
    private function renderBreadcrumbs(): void
    {
        NavigationComponent::renderBreadcrumbs([
            ['label' => 'Accueil', 'url' => base_url()],
            ['label' => 'Publications', 'url' => base_url('publications')],
            ['label' => 'Détail']
        ]);
    }

    /**
     * Rendu de l'en-tête de la publication
     */
    private function renderPublicationHeader(): void
    {
        $typeColors = [
            'Article' => '#3B82F6',
            'Conférence' => '#8B5CF6',
            'Thèse' => '#EC4899',
            'Rapport' => '#F59E0B',
            'Livre' => '#10B981',
            'Chapitre' => '#6366F1'
        ];
        $badgeColor = $typeColors[$this->publication['type_publication']] ?? '#6B7280';
        ?>
        <div class="publication-detail-header">
            <div class="publication-type-badge" style="background: <?= $badgeColor ?>;">
                <?= e($this->publication['type_publication']) ?>
            </div>
            
            <h1><?= e($this->publication['titre']) ?></h1>
            
            <div class="publication-meta-header">
                <span class="meta-item">
                     <?= date('d/m/Y', strtotime($this->publication['date_publication'])) ?>
                </span>
                
                <?php if (!empty($this->publication['domaine'])): ?>
                <span class="meta-item">
                     <?= e($this->publication['domaine']) ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu du layout détail (colonnes principale + sidebar)
     */
    private function renderDetailLayout(): void
    {
        ?>
        <div class="detail-layout">
            <!-- Colonne principale -->
            <div class="main-content">
                <?php 
                $this->renderResumeSection();
                $this->renderInfoSection();
                $this->renderActionsSection();
                ?>
            </div>

            <!-- Sidebar -->
            <aside class="sidebar-content">
                <?php 
                $this->renderAuteursSection();
                $this->renderProjetSection();
                $this->renderBackButton();
                ?>
            </aside>
        </div>
        <?php
    }

    /**
     * Rendu de la section résumé
     */
    private function renderResumeSection(): void
    {
        ?>
        <section class="detail-card">
            <h2> Résumé</h2>
            <div class="resume-content">
                <?= nl2br(e($this->publication['resume'])) ?>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu de la section informations
     */
    private function renderInfoSection(): void
    {
        ?>
        <section class="detail-card">
            <h2> Informations</h2>
            <div class="info-grid">
                <?php if (!empty($this->publication['doi'])): ?>
                <div class="info-item">
                    <strong>DOI</strong>
                    <a href="https://doi.org/<?= e($this->publication['doi']) ?>" 
                       target="_blank"
                       class="doi-link">
                        <?= e($this->publication['doi']) ?>
                    </a>
                </div>
                <?php endif; ?>
                
                <div class="info-item">
                    <strong>Date de publication</strong>
                    <span><?= format_date($this->publication['date_publication'], 'd F Y') ?></span>
                </div>
                
                <div class="info-item">
                    <strong>Type</strong>
                    <span><?= e($this->publication['type_publication']) ?></span>
                </div>
                
                <?php if (!empty($this->publication['domaine'])): ?>
                <div class="info-item">
                    <strong>Domaine</strong>
                    <span><?= e($this->publication['domaine']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu de la section actions
     */
    private function renderActionsSection(): void
    {
        if (empty($this->publication['lien']) && empty($this->publication['doi'])) {
            return;
        }
        ?>
        <section class="detail-card actions-card">
            <h2> Accès à la publication</h2>
            <div class="actions-buttons">
                <?php if (!empty($this->publication['lien'])): ?>
                <a href="<?= e($this->publication['lien']) ?>" 
                   target="_blank"
                   class="btn-action btn-download">
                     Télécharger le PDF
                </a>
                <?php endif; ?>
                
                <?php if (!empty($this->publication['doi'])): ?>
                <a href="https://doi.org/<?= e($this->publication['doi']) ?>" 
                   target="_blank"
                   class="btn-action btn-doi">
                    Voir sur DOI
                </a>
                <?php endif; ?>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu de la section auteurs
     */
    private function renderAuteursSection(): void
    {
        ?>
        <section class="detail-card">
            <h2> Auteurs</h2>
            <div class="authors-list">
                <?php foreach ($this->auteurs as $auteur): ?>
                <div class="author-item">
                    <div class="author-avatar">
                        <?= strtoupper(substr($auteur['username'], 0, 1)) ?>
                    </div>
                    <div class="author-info">
                        <div class="author-name"><?= e($auteur['username']) ?></div>
                        <?php if (!empty($auteur['equipe_nom'])): ?>
                        <div class="author-team"><?= e($auteur['equipe_nom']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu de la section projet
     */
    private function renderProjetSection(): void
    {
        if (!$this->projet) {
            return;
        }
        ?>
        <section class="detail-card">
            <h2> Projet associé</h2>
            <div class="projet-card">
                <h3><?= e($this->projet['titre']) ?></h3>
                <span class="badge" style="background: var(--primary);">
                    <?= e($this->projet['thematique']) ?>
                </span>
                <a href="<?= base_url('projets/' . $this->projet['id']) ?>" 
                   class="btn-link mt-md">
                    Voir le projet →
                </a>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu du bouton retour
     */
    private function renderBackButton(): void
    {
        ?>
        <a href="<?= base_url('publications') ?>" class="btn-secondary btn-block">
            ← Retour aux publications
        </a>
        <?php
    }

    /**
     * Rendu du footer
     */
    private function renderFooter(): void
    {
        FooterComponent::render(['role' => 'visiteur']);
    }

    /**
     * Rendu des styles
     */
    private function renderStyles(): void
    {
        ?>
        <style>
        .detail-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            color: var(--gray-600);
        }

        .breadcrumb a {
            color: var(--primary);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumb a:hover {
            color: var(--primary-dark);
        }

        .breadcrumb span:not(.breadcrumb > a) {
            color: var(--gray-400);
        }

        .publication-detail-header {
            background: white;
            padding: 40px;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: 30px;
            text-align: center;
        }

        .publication-detail-header h1 {
            font-size: 32px;
            font-weight: 700;
            color: var(--gray-900);
            margin: 20px 0;
            line-height: 1.3;
        }

        .publication-type-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .publication-meta-header {
            display: flex;
            justify-content: center;
            gap: 24px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .meta-item {
            font-size: 15px;
            color: var(--gray-600);
        }

        .detail-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
        }

        .detail-card {
            background: white;
            padding: 30px;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
        }

        .detail-card h2 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--gray-900);
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 12px;
        }

        .resume-content {
            line-height: 1.8;
            color: var(--gray-700);
            font-size: 16px;
        }

        .info-grid {
            display: grid;
            gap: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .info-item strong {
            color: var(--gray-600);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-item span, .info-item a {
            color: var(--gray-900);
            font-size: 15px;
        }

        .doi-link {
            color: var(--primary);
            text-decoration: none;
            transition: var(--transition);
        }

        .doi-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .actions-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .actions-card h2 {
            color: white;
            border-bottom-color: rgba(255,255,255,0.3);
        }

        .actions-buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .btn-action {
            display: block;
            padding: 14px 24px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            transition: var(--transition);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .authors-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .author-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: var(--gray-50);
            border-radius: 8px;
            transition: var(--transition);
        }

        .author-item:hover {
            background: var(--gray-100);
        }

        .author-avatar {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            flex-shrink: 0;
        }

        .author-info {
            flex: 1;
        }

        .author-name {
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 2px;
        }

        .author-team {
            font-size: 13px;
            color: var(--gray-600);
        }

        .projet-card {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .projet-card h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            color: white;
            width: fit-content;
        }

        .btn-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: var(--transition);
        }

        .btn-link:hover {
            color: var(--primary-dark);
        }

        .btn-block {
            display: block;
            width: 100%;
            text-align: center;
            padding: 12px;
            border-radius: 8px;
        }

        .mt-md {
            margin-top: 12px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .detail-layout {
                grid-template-columns: 1fr;
            }
            
            .sidebar-content {
                order: -1;
            }
        }

        @media (max-width: 768px) {
            .publication-detail-header {
                padding: 24px 20px;
            }
            
            .publication-detail-header h1 {
                font-size: 24px;
            }
            
            .publication-meta-header {
                flex-direction: column;
                gap: 12px;
            }
            
            .detail-card {
                padding: 20px;
            }
        }
        </style>
        <?php
    }
}