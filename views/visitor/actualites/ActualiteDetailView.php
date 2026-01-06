<?php
/**
 * ActualiteDetailView.php - Vue détail d'une actualité
 * À placer dans : /TDW_project/app/views/public/actualites/ActualiteDetailView.php
 */

require_once __DIR__ . '/../../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/NavigationComponent.php';
require_once __DIR__ . '/../../../lib/components/FooterComponent.php';

class ActualiteDetailView
{
    private array $actualite;
    private array $actualitesLiees;

    public function __construct(array $actualite, array $actualitesLiees = [])
    {
        $this->actualite = $actualite;
        $this->actualitesLiees = $actualitesLiees;
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
        echo '<div class="detail-layout">';
        $this->renderMainContent();
        $this->renderSidebar();
        echo '</div>'; // detail-layout
        echo '</div>'; // container
        echo '</div>'; // visitor-container
        $this->renderStyles();
        $this->renderFooter();
    }

    /**
     * Rendu de l'en-tête
     */
    private function renderHeader(): void
    {
        HeaderComponent::render([
            'title' => ($this->actualite['titre'] ?? 'Actualité') . ' - Laboratoire TDW',
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
        NavigationComponent::renderHorizontalMenu('actualites');
    }

    /**
     * Rendu du fil d'Ariane
     */
    private function renderBreadcrumbs(): void
    {
        NavigationComponent::renderBreadcrumbs([
            ['label' => 'Accueil', 'url' => base_url()],
            ['label' => 'Actualités', 'url' => base_url('actualites')],
            ['label' => $this->actualite['titre'] ?? 'Actualité']
        ]);
    }

    /**
     * Rendu du contenu principal
     */
    private function renderMainContent(): void
    {
        ?>
        <div class="main-content">
            <?php $this->renderActualiteHeader(); ?>
            <?php $this->renderActualiteContent(); ?>
        </div>
        <?php
    }

    /**
     * Rendu de l'en-tête de l'actualité
     */
    private function renderActualiteHeader(): void
    {
        $source = $this->actualite['source'] ?? 'laboratoire';
        
        // Couleurs et labels par source
        $sourceConfig = [
            'scientifique' => ['color' => '#10B981', 'label' => 'Actualité Scientifique'],
            'laboratoire' => ['color' => '#F59E0B', 'label' => 'Actualité Laboratoire']
        ];
        
        $config = $sourceConfig[$source] ?? $sourceConfig['laboratoire'];
        $badgeColor = $config['color'];
        $badgeLabel = $config['label'];
        
        // Date formatée
        $date = $this->actualite['date_publication'] ?? date('Y-m-d');
        $dateFormatted = format_date($date, 'd F Y');
        
        // Image
        $hasImage = !empty($this->actualite['image']);
        $imagePath = $hasImage ? base_url('uploads/actualites/' . $this->actualite['image']) : null;
        ?>
        
        <article class="actualite-detail-header">
            <div class="actualite-meta-top">
                <span class="actualite-badge-large" style="background: <?= $badgeColor ?>;">
                    <?= htmlspecialchars($badgeLabel) ?>
                </span>
                <span class="actualite-date-large"><?= htmlspecialchars($dateFormatted) ?></span>
            </div>
            
            <h1><?= htmlspecialchars($this->actualite['titre']) ?></h1>
            
            <?php if (!empty($this->actualite['auteur_nom'])): ?>
            <div class="actualite-author-info">
                Par <strong><?= htmlspecialchars($this->actualite['auteur_nom']) ?></strong>
            </div>
            <?php endif; ?>
            
            <?php if ($imagePath): ?>
            <div class="actualite-featured-image">
                <img src="<?= htmlspecialchars($imagePath) ?>" 
                     alt="<?= htmlspecialchars($this->actualite['titre']) ?>">
            </div>
            <?php endif; ?>
        </article>
        <?php
    }

    /**
     * Rendu du contenu de l'actualité
     */
    private function renderActualiteContent(): void
    {
        ?>
        <section class="detail-card">
            <div class="actualite-body">
                <?php if (!empty($this->actualite['description'])): ?>
                    <?= nl2br(htmlspecialchars($this->actualite['description'])) ?>
                <?php elseif (!empty($this->actualite['contenu'])): ?>
                    <?= nl2br(htmlspecialchars($this->actualite['contenu'])) ?>
                <?php elseif (!empty($this->actualite['descriptif'])): ?>
                    <?= nl2br(htmlspecialchars($this->actualite['descriptif'])) ?>
                <?php else: ?>
                    <p class="text-muted">Aucun contenu disponible pour cette actualité.</p>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($this->actualite['lien_detail'])): ?>
            <div class="actualite-actions">
                <a href="<?= htmlspecialchars($this->actualite['lien_detail']) ?>" 
                   target="_blank"
                   rel="noopener noreferrer"
                   class="btn-primary">
                    En savoir plus
                </a>
            </div>
            <?php endif; ?>
        </section>
        
        <?php $this->renderShareSection(); ?>
        <?php
    }

    /**
     * Rendu de la section de partage
     */
    private function renderShareSection(): void
    {
        $currentUrl = urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        $titre = urlencode($this->actualite['titre'] ?? 'Actualité');
        ?>
        
        <section class="detail-card share-section">
            <h2>Partager cette actualité</h2>
            <div class="share-buttons">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $currentUrl ?>" 
                   target="_blank"
                   class="share-btn facebook">
                    Facebook
                </a>
                <a href="https://twitter.com/intent/tweet?url=<?= $currentUrl ?>&text=<?= $titre ?>" 
                   target="_blank"
                   class="share-btn twitter">
                    Twitter
                </a>
                <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?= $currentUrl ?>&title=<?= $titre ?>" 
                   target="_blank"
                   class="share-btn linkedin">
                    LinkedIn
                </a>
                <button onclick="copyToClipboard('<?= $currentUrl ?>')" 
                        class="share-btn copy">
                    Copier le lien
                </button>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu de la sidebar
     */
    private function renderSidebar(): void
    {
        ?>
        <aside class="sidebar-content">
            <?php 
            $this->renderInformations();
            $this->renderActualitesLiees();
            $this->renderActions();
            ?>
        </aside>
        <?php
    }

    /**
     * Rendu des informations
     */
    private function renderInformations(): void
    {
        $source = $this->actualite['source'] ?? 'laboratoire';
        $date = $this->actualite['date_publication'] ?? date('Y-m-d');
        ?>
        
        <section class="detail-card">
            <h2>Informations</h2>
            <div class="info-list">
                <div class="info-item">
                    <strong>Type</strong>
                    <span><?= htmlspecialchars($source === 'scientifique' ? 'Scientifique' : 'Laboratoire') ?></span>
                </div>
                
                <div class="info-item">
                    <strong>Date de publication</strong>
                    <span><?= format_date($date, 'd F Y') ?></span>
                </div>
                
                <?php if (!empty($this->actualite['auteur_nom'])): ?>
                <div class="info-item">
                    <strong>Auteur</strong>
                    <span><?= htmlspecialchars($this->actualite['auteur_nom']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu des actualités liées
     */
    private function renderActualitesLiees(): void
    {
        if (empty($this->actualitesLiees)) {
            return;
        }
        ?>
        
        <section class="detail-card">
            <h2>Actualités similaires</h2>
            <div class="actualites-liees">
                <?php foreach (array_slice($this->actualitesLiees, 0, 3) as $actualite): ?>
                    <?php $this->renderActualiteLieeCard($actualite); ?>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu d'une carte actualité liée
     */
    private function renderActualiteLieeCard(array $actualite): void
    {
        $titre = $actualite['titre'] ?? 'Sans titre';
        $date = format_date($actualite['date_publication'] ?? date('Y-m-d'), 'd M Y');
        $url = base_url('actualites/' . ($actualite['id'] ?? 0));
        ?>
        
        <a href="<?= htmlspecialchars($url) ?>" class="actualite-liee-item">
            <div class="actualite-liee-date"><?= htmlspecialchars($date) ?></div>
            <div class="actualite-liee-title"><?= htmlspecialchars($titre) ?></div>
        </a>
        <?php
    }

    /**
     * Rendu des actions
     */
    private function renderActions(): void
    {
        ?>
        <a href="<?= base_url('actualites') ?>" class="btn-secondary btn-block">
            Retour aux actualités
        </a>
        <?php
    }

    /**
     * Rendu des styles CSS
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

        .detail-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
        }

        .actualite-detail-header {
            background: white;
            padding: 40px;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
        }

        .actualite-meta-top {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
        }

        .actualite-badge-large {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .actualite-date-large {
            color: var(--gray-600);
            font-size: 15px;
            font-weight: 500;
        }

        .actualite-detail-header h1 {
            font-size: 36px;
            font-weight: 700;
            line-height: 1.3;
            color: var(--gray-900);
            margin: 0 0 16px 0;
        }

        .actualite-author-info {
            color: var(--gray-600);
            font-size: 16px;
            margin-bottom: 24px;
        }

        .actualite-author-info strong {
            color: var(--primary);
        }

        .actualite-featured-image {
            width: 100%;
            max-height: 500px;
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            margin-top: 24px;
        }

        .actualite-featured-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .detail-card {
            background: white;
            padding: 30px;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
            transition: var(--transition);
        }

        .detail-card:hover {
            box-shadow: var(--shadow-md);
        }

        .detail-card h2 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--gray-900);
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 12px;
        }

        .actualite-body {
            line-height: 1.8;
            color: var(--gray-700);
            font-size: 16px;
        }

        .actualite-body p {
            margin-bottom: 16px;
        }

        .actualite-actions {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid var(--border-color);
        }

        .share-section {
            background: var(--gray-50);
        }

        .share-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .share-btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            color: white;
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }

        .share-btn.facebook {
            background: #1877F2;
        }

        .share-btn.twitter {
            background: #1DA1F2;
        }

        .share-btn.linkedin {
            background: #0A66C2;
        }

        .share-btn.copy {
            background: var(--gray-700);
        }

        .share-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            opacity: 0.9;
        }

        .info-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 12px;
            background: var(--gray-50);
            border-radius: 8px;
            transition: var(--transition);
        }

        .info-item:hover {
            background: var(--gray-100);
        }

        .info-item strong {
            color: var(--gray-600);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-item span {
            color: var(--gray-900);
            font-size: 15px;
        }

        .actualites-liees {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .actualite-liee-item {
            display: block;
            padding: 16px;
            background: var(--gray-50);
            border-radius: 8px;
            text-decoration: none;
            transition: var(--transition);
            border-left: 3px solid var(--primary);
        }

        .actualite-liee-item:hover {
            background: white;
            box-shadow: var(--shadow-sm);
            transform: translateX(4px);
        }

        .actualite-liee-date {
            font-size: 12px;
            color: var(--gray-600);
            margin-bottom: 4px;
            font-weight: 500;
        }

        .actualite-liee-title {
            font-size: 14px;
            color: var(--gray-900);
            font-weight: 600;
            line-height: 1.4;
        }

        .btn-block {
            display: block;
            width: 100%;
            text-align: center;
            padding: 12px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-secondary.btn-block {
            background: var(--gray-200);
            color: var(--gray-700);
        }

        .btn-secondary.btn-block:hover {
            background: var(--gray-300);
            transform: translateY(-2px);
        }

        .text-muted {
            color: var(--gray-600);
            font-style: italic;
        }

        @media (max-width: 1024px) {
            .detail-layout {
                grid-template-columns: 1fr;
            }
            
            .sidebar-content {
                order: -1;
            }
        }

        @media (max-width: 768px) {
            .actualite-detail-header {
                padding: 24px 20px;
            }
            
            .actualite-detail-header h1 {
                font-size: 28px;
            }
            
            .detail-card {
                padding: 20px;
            }
            
            .share-buttons {
                flex-direction: column;
            }
            
            .share-btn {
                width: 100%;
                text-align: center;
            }
        }
        </style>
        
        <script>
        function copyToClipboard(url) {
            const decodedUrl = decodeURIComponent(url);
            navigator.clipboard.writeText(decodedUrl).then(() => {
                alert('Lien copié dans le presse-papiers !');
            }).catch(err => {
                console.error('Erreur lors de la copie:', err);
            });
        }
        </script>
        <?php
    }

    /**
     * Rendu du pied de page
     */
    private function renderFooter(): void
    {
        FooterComponent::render(['role' => 'visiteur']);
    }
}
