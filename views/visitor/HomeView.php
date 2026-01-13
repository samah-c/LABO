<?php
/**
 * HomeView.php - Vue de la page d'accueil publique
 */

require_once __DIR__ . '/../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../lib/components/NavigationComponent.php';
require_once __DIR__ . '/../../lib/components/FooterComponent.php';
require_once __DIR__ . '/../../lib/components/TableComponent.php';
require_once __DIR__ . '/../../lib/LabHelpers.php';
class HomeView
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Rendu complet de la vue
     */
    public function render(): void
    {
        $this->renderHeader();
        $this->renderNavigation();
        echo '<div class="visitor-container">';
        $this->renderSlideshow();
        echo '<div class="content-wrapper">';
        $this->renderStats();
        $this->renderActualitesScientifiques();
        $this->renderPresentation();
        $this->renderEvenements();
        $this->renderEvenementsScientifiques(); 
       $this->renderOffresOpportunites();
        $this->renderPartenaires();
        $this->renderProjets();
        $this->renderPublications();
        $this->renderActualites();
        echo '</div>'; // content-wrapper
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
            'title' => 'Laboratoire TDW - Accueil',
            'role' => 'visiteur',
            'showLogout' => false,
            'showLoginButton' => true,
            'additionalJs' => [base_url('assets/js/diaporama.js')]
        ]);
    }

    /**
     * Rendu de la navigation horizontale
     */
    private function renderNavigation(): void
    {
        NavigationComponent::renderHorizontalMenu('');
    }

    /**
     * Rendu du diaporama
     */
    private function renderSlideshow(): void
    {
        $actualites = $this->data['actualites'] ?? [];
        ?>
        <section class="slideshow-section">
            <div class="slideshow-container">
                <?php if (!empty($actualites)): ?>
                    <?php foreach ($actualites as $index => $actu): ?>
                        <div class="slide <?= $index === 0 ? 'active' : '' ?>">
                            <div class="slide-content">
                                <div class="slide-text">
                                    <span class="slide-category"><?= e($actu['categorie']) ?></span>
                                    <h2><?= e($actu['titre']) ?></h2>
                                    <p><?= e($actu['description']) ?></p>
                                    <a href="<?= base_url('actualites/' . $actu['id']) ?>" class="slide-link">
                                        En savoir plus
                                    </a>
                                </div>
                                <?php if (!empty($actu['image'])): ?>
                                    <div class="slide-image">
                                        <img src="<?= base_url('uploads/' . $actu['image']) ?>" alt="<?= e($actu['titre']) ?>">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <button class="slide-prev" onclick="changeSlide(-1)">&#10094;</button>
                    <button class="slide-next" onclick="changeSlide(1)">&#10095;</button>
                    
                    <div class="slide-indicators">
                        <?php foreach ($actualites as $index => $actu): ?>
                            <span class="indicator <?= $index === 0 ? 'active' : '' ?>" onclick="goToSlide(<?= $index ?>)"></span>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="slide active">
                        <div class="slide-content">
                            <div class="slide-text">
                                <h2>Bienvenue au Laboratoire TDW</h2>
                                <p>Centre de recherche de pointe en technologies du web et développement</p>
                                <a href="<?= base_url('projets') ?>" class="slide-link">Découvrir nos projets</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu des statistiques
     */
    private function renderStats(): void
    {
        $stats = [
            ['label' => 'Projets de recherche', 'value' => $this->data['stats']['total_projets'] ?? 0],
            ['label' => 'Publications', 'value' => $this->data['stats']['total_publications'] ?? 0],
            ['label' => 'Chercheurs', 'value' => $this->data['stats']['total_membres'] ?? 0],
            ['label' => 'Équipes', 'value' => $this->data['stats']['total_equipes'] ?? 0]
        ];
        
        TableComponent::renderStatsCards($stats);
    }

    /**
     * Rendu des actualités scientifiques
     */
    private function renderActualitesScientifiques(): void
    {
        $actualites = $this->data['actualitesScientifiques'] ?? [];
        ?>
        <section class="content-section">
            <h2 class="section-title">Actualités scientifiques</h2>
            <?php if (!empty($actualites)): ?>
                <div class="actualites-grid">
                    <?php foreach ($actualites as $actu): ?>
                        <article class="actualite-scientifique-card">
                            <h3><?= e($actu['titre']) ?></h3>
                            <p><?= truncate($actu['description'] ?? '', 150) ?></p>
                            <div class="actualite-meta">
                                <span><?= date('d/m/Y', strtotime($actu['date_publication'])) ?></span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty-message">Aucune actualité disponible</p>
            <?php endif; ?>
        </section>
        <?php
    }

    /**
     * Rendu de la présentation du laboratoire
     */
    private function renderPresentation(): void
    {
        $presentation = $this->data['presentation'] ?? [];
        $directeur = $this->data['directeur'] ?? null;
        ?>
        <section class="content-section presentation-section">
            <div class="presentation-grid">
                <div class="presentation-text">
                    <h2 class="section-title">À propos du laboratoire</h2>
                    <p><?= nl2br(e($presentation['description'] ?? 'Le Laboratoire TDW est un centre de recherche spécialisé dans les technologies du web et le développement logiciel.')) ?></p>
                    <a href="<?= base_url('organigramme') ?>" class="btn-secondary">Découvrir l'organigramme</a>
                </div>
                <div class="presentation-organigramme">
                    <h3>Organigramme</h3>
                    <?php if (!empty($directeur)): ?>
                        <div class="organigramme-item">
                            <strong>Directeur :</strong> <?= e($directeur['nom'] . ' ' . $directeur['prenom']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu des événements
     */
    private function renderEvenements(): void
    {
        $evenements = $this->data['evenements'] ?? [];
        ?>
        <section class="content-section">
            <div class="section-header">
                <h2 class="section-title">Événements à venir</h2>
                <a href="<?= base_url('evenements') ?>" class="see-all">Voir tous les événements</a>
            </div>
            
            <?php if (!empty($evenements)): ?>
                <div class="events-grid">
                    <?php foreach (array_slice($evenements, 0, 6) as $event): ?>
                        <article class="event-card">
                            <div class="event-date">
                                <div class="date-day"><?= date('d', strtotime($event['date_evenement'])) ?></div>
                                <div class="date-month"><?= date('M', strtotime($event['date_evenement'])) ?></div>
                            </div>
                            <div class="event-content">
                                <span class="event-type"><?= e($event['type_evenement']) ?></span>
                                <h3><?= e($event['titre']) ?></h3>
                                <p class="event-location"><?= e($event['lieu']) ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty-message">Aucun événement prévu</p>
            <?php endif; ?>
        </section>
        <?php
    }

private function renderEvenementsScientifiques(): void
{
    $evenementsScientifiques = $this->data['evenementsScientifiques'] ?? [];
    ?>
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">Événements scientifiques</h2>
        </div>
        
        <?php if (!empty($evenementsScientifiques)): ?>
            <div class="events-grid">
                <?php foreach ($evenementsScientifiques as $event): ?>
                    <article class="event-card">
                        <div class="event-date">
                            <div class="date-day"><?= date('d', strtotime($event['date_evenement'])) ?></div>
                            <div class="date-month"><?= date('M', strtotime($event['date_evenement'])) ?></div>
                        </div>
                        <div class="event-content">
                            <span class="event-type">
                                <?php
                                $typeValue = $event['type_scientifique'] ?? $event['type_evenement'] ?? 'autre';
                                $typeLabels = [
                                    'atelier' => 'Atelier',
                                    'seminaire' => 'Séminaire',
                                    'conference' => 'Conférence',
                                    'colloque' => 'Colloque'
                                ];
                                echo $typeLabels[$typeValue] ?? ucfirst($typeValue);
                                ?>
                            </span>
                            <h3><?= e($event['titre']) ?></h3>
                            
                            <?php if (!empty($event['theme_scientifique'])): ?>
                                <p class="event-theme" style="margin: 8px 0; color: var(--gray-700); font-size: 13px;">
                                    <strong>Thème :</strong> <?= e($event['theme_scientifique']) ?>
                                </p>
                            <?php endif; ?>
                            
                            <?php if (!empty($event['intervenant_principal'])): ?>
                                <p class="event-intervenant" style="margin: 8px 0; color: var(--gray-700); font-size: 13px;">
                                    <strong>Intervenant :</strong> <?= e($event['intervenant_principal']) ?>
                                </p>
                            <?php endif; ?>
                            
                            <p class="event-location"><?= e($event['lieu'] ?? 'À définir') ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="empty-message">Aucun événement scientifique prévu pour le moment</p>
        <?php endif; ?>
    </section>
    <?php
}

/**
 * Rendu des offres et opportunités
 */
private function renderOffresOpportunites(): void
{
    $offres = $this->data['offres'] ?? [];
    ?>
    <section class="content-section offres-section">
        <div class="section-header">
            <h2 class="section-title">Offres et opportunités</h2>
            <a href="<?= base_url('offres') ?>" class="see-all">Voir toutes les offres</a>
        </div>
        
        <?php if (!empty($offres)): ?>
            <div class="offres-grid">
                <?php foreach ($offres as $offre): ?>
                    <article class="offre-card">
                        <div class="offre-header">
                            <span class="offre-type-badge badge-<?= e(strtolower($offre['type_offre'])) ?>">
                                <?php
                                $typeLabels = [
                                    'stage' => 'Stage',
                                    'these' => 'Thèse',
                                    'bourse' => 'Bourse',
                                    'collaboration' => 'Collaboration',
                                    'emploi' => 'Emploi',
                                    'postdoc' => 'Post-Doc'
                                ];
                                echo $typeLabels[$offre['type_offre']] ?? ucfirst($offre['type_offre']);
                                ?>
                            </span>
                            
                            <?php if (!empty($offre['date_expiration'])): ?>
                                <div class="offre-expiration">
                                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                                        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                                    </svg>
                                    Expire le <?= date('d/m/Y', strtotime($offre['date_expiration'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="offre-content">
                            <h3><?= e($offre['titre']) ?></h3>
                            
                            <?php if (!empty($offre['description'])): ?>
                                <p class="offre-description">
                                    <?= truncate($offre['description'], 100) ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="offre-meta">
                                <?php if (!empty($offre['lieu'])): ?>
                                    <span class="offre-meta-item">
                                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M8 0a5 5 0 0 0-5 5c0 3.5 5 11 5 11s5-7.5 5-11a5 5 0 0 0-5-5z"/>
                                        </svg>
                                        <?= e($offre['lieu']) ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($offre['duree'])): ?>
                                    <span class="offre-meta-item">
                                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M11 6.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1z"/>
                                            <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5z"/>
                                        </svg>
                                        <?= e($offre['duree']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="empty-message">Aucune offre disponible actuellement</p>
        <?php endif; ?>
    </section>
    <?php
}


    /**
     * Rendu des partenaires
     */
    private function renderPartenaires(): void
    {
        $partenaires = $this->data['partenaires'] ?? [];
        ?>
        <section class="content-section">
            <h2 class="section-title">Nos partenaires</h2>
            <?php if (!empty($partenaires)): ?>
                <div class="partenaires-grid">
                    <?php foreach ($partenaires as $partenaire): ?>
                        <div class="partenaire-card">
                            <?php if (!empty($partenaire['logo'])): ?>
                                <img src="<?= base_url('uploads/' . $partenaire['logo']) ?>" alt="<?= e($partenaire['nom']) ?>">
                            <?php endif; ?>
                            <h4><?= e($partenaire['nom']) ?></h4>
                            <p class="partenaire-type"><?= e($partenaire['type']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty-message">Aucun partenaire enregistré</p>
            <?php endif; ?>
        </section>
        <?php
    }

    /**
     * Rendu des projets récents
     */
    private function renderProjets(): void
    {
        $projets = $this->data['projetsRecents'] ?? [];
        ?>
        <section class="content-section">
            <div class="section-header">
                <h2 class="section-title">Projets récents</h2>
                <a href="<?= base_url('projets') ?>" class="see-all">Voir tous les projets</a>
            </div>
            
            <?php if (!empty($projets)): ?>
                <div class="projects-grid">
                    <?php foreach ($projets as $projet): ?>
                        <div class="project-card">
                            <div class="project-header">
                                <h3>
                                    <a href="<?= base_url('projets/projets/' . $projet['id']) ?>">
                                        <?= e($projet['titre']) ?>
                                    </a>
                                </h3>
                                <?= LabHelpers::getStatusBadge($projet['statut']) ?>
                            </div>
                            <p class="project-description">
                                <?= truncate($projet['description'], 150) ?>
                            </p>
                            <div class="project-meta">
                                <span><?= date('Y', strtotime($projet['date_debut'])) ?></span>
                                <span><?= e($projet['domaine_recherche']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty-message">Aucun projet disponible</p>
            <?php endif; ?>
        </section>
        <?php
    }

    /**
     * Rendu des publications récentes
     */
    private function renderPublications(): void
    {
        $publications = $this->data['publicationsRecentes'] ?? [];
        ?>
        <section class="content-section">
            <div class="section-header">
                <h2 class="section-title">Publications récentes</h2>
                <a href="<?= base_url('publications') ?>" class="see-all">Voir toutes les publications</a>
            </div>
            
            <?php if (!empty($publications)): ?>
                <div class="publications-list">
                    <?php foreach ($publications as $pub): ?>
                        <div class="publication-item">
                            <div class="publication-type">
                                <?= LabHelpers::getPublicationTypeBadge($pub['type_publication']) ?>
                            </div>
                            <div class="publication-content">
                                <h3>
                                    <a href="<?= base_url('publications/' . $pub['id']) ?>">
                                        <?= e($pub['titre']) ?>
                                    </a>
                                </h3>
                                <p class="publication-authors">
                                    <?= e($pub['auteurs'] ?? 'Auteur inconnu') ?>
                                </p>
                                <div class="publication-meta">
                                    <span><?= $pub['date_publication'] ?></span>
                                    <?php if (!empty($pub['conference'])): ?>
                                        <span><?= e($pub['conference']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty-message">Aucune publication disponible</p>
            <?php endif; ?>
        </section>
        <?php
    }
    /**
     * Rendu des actualités
     */
    private function renderActualites(): void
    {
        $actualites = $this->data['actualites'] ?? [];
        
        // Couleurs par type
        $typeColors = [
            'publication' => '#3B82F6',
            'evenement' => '#8B5CF6',
            'scientifique' => '#10B981',
            'laboratoire' => '#F59E0B'
        ];
        
        // Labels
        $typeLabels = [
            'publication' => 'Publication',
            'evenement' => 'Événement',
            'scientifique' => 'Actualité Scientifique',
            'laboratoire' => 'Actualité Laboratoire'
        ];
        
        if (empty($actualites)): ?>
            <p class="empty-message">Aucune actualité disponible</p>
        <?php return; endif; ?>
        
        <section class="content-section">
            <div class="section-header">
            <h2 class="section-title">Actualités</h2>
            <a href="<?= base_url('actualites') ?>" class="see-all">Voir toutes les actualités</a>
            </div>
            <div class="actualites-list">
                <?php foreach ($actualites as $actualite): ?>
                    <?php
                    $type = $actualite['type'] ?? 'laboratoire';
                    $source = $actualite['source'] ?? 'laboratoire';
                    $badgeColor = $typeColors[$type] ?? $typeColors[$source] ?? '#6B7280';
                    $typeLabel = $typeLabels[$type] ?? $typeLabels[$source] ?? 'Actualité';
                    $date = $actualite['date'] ?? $actualite['date_publication'] ?? date('Y-m-d');
                    $hasImage = !empty($actualite['image'] ?? null);
                    $imagePath = $hasImage ? base_url('uploads/' . $actualite['image']) : null;
                    $detailUrl = base_url('actualites/' . $actualite['id']);
                    ?>
                    
                    <article class="actualite-card">
                        <?php if ($imagePath): ?>
                        <div class="actualite-image">
                            <img src="<?= htmlspecialchars($imagePath) ?>" 
                                 alt="<?= htmlspecialchars($actualite['titre'] ?? 'Actualité') ?>">
                            <span class="actualite-badge" style="background: <?= $badgeColor ?>;">
                                <?= htmlspecialchars($typeLabel) ?>
                            </span>
                        </div>
                        <?php else: ?>
                        <div class="actualite-image-placeholder">
                            <span class="actualite-badge" style="background: <?= $badgeColor ?>;">
                                <?= htmlspecialchars($typeLabel) ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="actualite-content">
                            <div class="actualite-meta">
                                <span class="actualite-date"><?= htmlspecialchars(date('d/m/Y', strtotime($date))) ?></span>
                                <?php if (!empty($actualite['auteur_nom'] ?? null)): ?>
                                <span class="actualite-author">
                                    Par <?= htmlspecialchars($actualite['auteur_nom']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            
                            <h3 class="actualite-title">
                                <a href="<?= htmlspecialchars($detailUrl) ?>">
                                    <?= htmlspecialchars($actualite['titre'] ?? 'Sans titre') ?>
                                </a>
                            </h3>
                            
                            <?php if (!empty($actualite['description'] ?? null)): ?>
                            <p class="actualite-description">
                                <?= truncate($actualite['description'], 150) ?>
                            </p>
                            <?php endif; ?>
                            
                            <a href="<?= htmlspecialchars($detailUrl) ?>" class="btn-primary btn-small">
                                Lire la suite
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }



    /**
     * Rendu des styles
     */
    private function renderStyles(): void
    {
        ?>
        <style>
/* ============================================
   DIAPORAMA
   ============================================ */
.slideshow-section {
    background: var(--gray-100);
    padding: 0;
}

.slideshow-container {
    max-width: 100%;
    margin: 0 auto;
    position: relative;
    height: 500px;
    overflow: hidden;
    background: var(--gray-900);
}

.slide {
    display: none;
    width: 100%;
    height: 100%;
    position: absolute;
    top: 0;
    left: 0;
    opacity: 0;
    transition: opacity 0.6s ease-in-out;
}

.slide.active {
    display: block;
    opacity: 1;
    z-index: 1;
    animation: fadeSlide 0.8s ease-in-out;
}

@keyframes fadeSlide {
    0% { opacity: 0; transform: scale(1.05); }
    100% { opacity: 1; transform: scale(1); }
}

.slide-content {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 60px 80px;
    gap: 60px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
}

.slide-text {
    flex: 1;
    color: white;
}

.slide-category {
    display: inline-block;
    padding: 6px 16px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 16px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.slide-text h2 {
    font-size: 42px;
    font-weight: 700;
    margin: 0 0 20px 0;
    line-height: 1.2;
    letter-spacing: -0.5px;
}

.slide-text p {
    font-size: 18px;
    line-height: 1.6;
    margin: 0 0 30px 0;
    opacity: 0.95;
}

.slide-link {
    display: inline-block;
    padding: 12px 28px;
    background: white;
    color: var(--primary);
    text-decoration: none;
    border-radius: var(--border-radius-sm);
    font-weight: 600;
    font-size: 14px;
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
}

.slide-link:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.slide-image {
    flex: 1;
    max-width: 500px;
}

.slide-image img {
    width: 100%;
    height: auto;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-xl);
}

.slide-prev, .slide-next {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 2px solid rgba(255, 255, 255, 0.3);
    padding: 16px 20px;
    cursor: pointer;
    font-size: 24px;
    font-weight: bold;
    transition: all 0.3s ease;
    border-radius: var(--border-radius-sm);
    backdrop-filter: blur(10px);
    z-index: 10;
    user-select: none;
}

.slide-prev:hover, .slide-next:hover {
    background: rgba(255, 255, 255, 0.4);
    border-color: rgba(255, 255, 255, 0.5);
    transform: translateY(-50%) scale(1.1);
}

.slide-prev { left: 20px; }
.slide-next { right: 20px; }

.slide-indicators {
    position: absolute;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 12px;
    z-index: 10;
}

.indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.4);
    cursor: pointer;
    transition: all 0.3s ease;
}

.indicator:hover {
    background: rgba(255, 255, 255, 0.7);
    transform: scale(1.2);
}

.indicator.active {
    background: white;
    transform: scale(1.3);
    box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
}

/* ============================================
   ZONE DE CONTENU
   ============================================ */
.content-wrapper {
    max-width: 1400px;
    margin: 0 auto;
    padding: 40px 32px;
}

.content-section {
    margin-bottom: 48px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.section-title {
    font-size: 28px;
    font-weight: 700;
    color: var(--gray-900);
    margin: 0 0 24px 0;
    letter-spacing: -0.5px;
}

.see-all {
    color: var(--primary);
    text-decoration: none;
    font-weight: 500;
    font-size: 14px;
    transition: var(--transition);
}

.see-all:hover {
    color: var(--primary-dark);
}

/* ============================================
   ACTUALITÉS
   ============================================ */

   .actualite-scientifique-card {
    background: white;
    border-radius: var(--border-radius-lg);
    border: 1px solid var(--border-color);
    padding: 24px;
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
}

.actualite-scientifique-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-4px);
    border-color: var(--primary);
}

.actualite-scientifique-card h3 {
    margin: 0 0 12px 0;
    font-size: 18px;
    font-weight: 600;
    color: var(--gray-900);
    line-height: 1.4;
}

.actualite-scientifique-card p {
    color: var(--gray-600);
    line-height: 1.6;
    margin: 0 0 16px 0;
    font-size: 14px;
}

.actualite-scientifique-card .actualite-meta {
    display: flex;
    gap: 12px;
    align-items: center;
    font-size: 13px;
    color: var(--gray-500);
    font-weight: 500;
    padding-top: 12px;
    border-top: 1px solid var(--border-color);
}
.actualites-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
}

.actualites-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 28px;
}

.actualite-card {
    background: white;
    border-radius: var(--border-radius-lg);
    border: 1px solid var(--border-color);
    overflow: hidden;
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
    display: flex;
    flex-direction: column;
}

.actualite-card:hover {
    box-shadow: var(--shadow-xl);
    transform: translateY(-6px);
    border-color: var(--primary);
}

.actualite-image {
    position: relative;
    width: 100%;
    height: 200px;
    overflow: hidden;
    background: var(--gray-100);
}

.actualite-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.actualite-card:hover .actualite-image img {
    transform: scale(1.08);
}

.actualite-image-placeholder {
    position: relative;
    width: 100%;
    height: 200px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
}

.actualite-badge {
    position: absolute;
    top: 16px;
    left: 16px;
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    color: white;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: var(--shadow-sm);
    z-index: 2;
}

.actualite-content {
    padding: 24px;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.actualite-meta {
    display: flex;
    gap: 12px;
    align-items: center;
    margin-bottom: 12px;
    font-size: 13px;
    color: var(--gray-500);
    font-weight: 500;
}

.actualite-date {
    display: flex;
    align-items: center;
    gap: 4px;
}

.actualite-author {
    display: flex;
    align-items: center;
    gap: 4px;
}

.actualite-author::before {
    content: '•';
    margin-right: 4px;
}

.actualite-title {
    margin: 0 0 12px 0;
    font-size: 18px;
    font-weight: 600;
    line-height: 1.4;
}

.actualite-title a {
    color: var(--gray-900);
    text-decoration: none;
    transition: var(--transition);
}

.actualite-title a:hover {
    color: var(--primary);
}

.actualite-description {
    color: var(--gray-600);
    line-height: 1.6;
    margin: 0 0 20px 0;
    font-size: 14px;
    flex: 1;
}

.btn-small {
    padding: 10px 20px;
    font-size: 13px;
    align-self: flex-start;
}
/* ============================================
   PRÉSENTATION
   ============================================ */
.presentation-section {
    background: var(--gray-50);
    padding: 40px;
    border-radius: var(--border-radius-xl);
}

.presentation-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 40px;
}

.presentation-text p {
    color: var(--gray-600);
    line-height: 1.8;
    margin: 0 0 24px 0;
    font-size: 15px;
}

.presentation-organigramme {
    background: white;
    padding: 24px;
    border-radius: var(--border-radius-lg);
    border: 1px solid var(--border-color);
}

.presentation-organigramme h3 {
    margin: 0 0 16px 0;
    font-size: 18px;
    font-weight: 600;
    color: var(--gray-900);
}

.organigramme-item {
    padding: 12px;
    background: var(--gray-50);
    border-radius: var(--border-radius-sm);
    font-size: 14px;
}

/* ============================================
   ÉVÉNEMENTS
   ============================================ */
.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}

.event-card {
    display: flex;
    gap: 16px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-lg);
    padding: 20px;
    transition: var(--transition);
    box-shadow: var(--shadow-xs);
}

.event-card:hover {
    box-shadow: var(--shadow-sm);
    border-color: var(--primary);
    transform: translateY(-2px);
}

.event-date {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-width: 70px;
    padding: 12px;
    background: var(--primary);
    color: white;
    border-radius: var(--border-radius-sm);
    flex-shrink: 0;
}

.date-day {
    font-size: 28px;
    font-weight: 700;
    line-height: 1;
}

.date-month {
    font-size: 13px;
    text-transform: uppercase;
    font-weight: 600;
    margin-top: 4px;
}

.event-type {
    display: inline-block;
    background: rgba(91, 127, 255, 0.1);
    color: var(--primary);
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 8px;
}

.event-content h3 {
    margin: 8px 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--gray-900);
}

.event-location {
    color: var(--gray-600);
    font-size: 13px;
    margin: 0;
}

/* ============================================
   PARTENAIRES
   ============================================ */
.partenaires-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
}

.partenaire-card {
    background: var(--bg-card);
    padding: 24px;
    border-radius: var(--border-radius-lg);
    border: 1px solid var(--border-color);
    text-align: center;
    transition: var(--transition);
    box-shadow: var(--shadow-xs);
}

.partenaire-card:hover {
    box-shadow: var(--shadow-sm);
    transform: translateY(-2px);
    border-color: var(--primary);
}

.partenaire-card img {
    width: 100%;
    height: 80px;
    object-fit: contain;
    margin-bottom: 12px;
}

.partenaire-card h4 {
    margin: 0 0 4px 0;
    font-size: 15px;
    font-weight: 600;
    color: var(--gray-900);
}

.partenaire-type {
    font-size: 13px;
    color: var(--gray-500);
    margin: 0;
}

/* ============================================
   PROJETS
   ============================================ */
.projects-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}

.project-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-lg);
    padding: 24px;
    transition: var(--transition);
    box-shadow: var(--shadow-xs);
}

.project-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-4px);
    border-color: var(--primary);
}

.project-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 12px;
    gap: 12px;
}

.project-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    flex: 1;
}

.project-header a {
    color: var(--gray-900);
    text-decoration: none;
    transition: var(--transition);
}

.project-header a:hover {
    color: var(--primary);
}

.project-description {
    color: var(--gray-600);
    margin: 12px 0;
    line-height: 1.6;
    font-size: 14px;
}

.project-meta {
    display: flex;
    gap: 16px;
    font-size: 13px;
    color: var(--gray-500);
    font-weight: 500;
}

/* ============================================
   PUBLICATIONS
   ============================================ */
.publications-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.publication-item {
    display: flex;
    gap: 16px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-lg);
    padding: 20px;
    transition: var(--transition);
    box-shadow: var(--shadow-xs);
}

.publication-item:hover {
    box-shadow: var(--shadow-sm);
    border-color: var(--primary);
}

.publication-type {
    flex-shrink: 0;
}

.publication-content {
    flex: 1;
}

.publication-content h3 {
    margin: 0 0 8px 0;
    font-size: 16px;
    font-weight: 600;
}

.publication-content h3 a {
    color: var(--gray-900);
    text-decoration: none;
    transition: var(--transition);
}

.publication-content h3 a:hover {
    color: var(--primary);
}

.publication-authors {
    color: var(--gray-600);
    font-size: 14px;
    margin: 0 0 8px 0;
}

.publication-meta {
    font-size: 13px;
    color: var(--gray-500);
    font-weight: 500;
}

.publication-meta span + span::before {
    content: '•';
    margin: 0 8px;
}

/* ============================================
   MESSAGE VIDE
   ============================================ */
.empty-message {
    text-align: center;
    padding: 60px 24px;
    color: var(--gray-500);
    font-size: 15px;
    background: var(--gray-50);
    border-radius: var(--border-radius-lg);
}

/* ============================================
   RESPONSIVE
   ============================================ */
@media (max-width: 1024px) {
    .content-wrapper { padding: 32px 24px; }
    .slide-content { padding: 40px; }
    .presentation-grid { grid-template-columns: 1fr; }
    .slideshow-container { height: 450px; }
}

@media (max-width: 768px) {
    .slideshow-container { height: auto; min-height: 400px; }
    .slide-content { flex-direction: column; padding: 32px 24px; gap: 32px; }
    .slide-text h2 { font-size: 28px; }
    .slide-text p { font-size: 16px; }
    .slide-image { max-width: 100%; }
    .content-wrapper { padding: 24px 20px; }
    .stats-grid, .actualites-grid, .events-grid, 
    .partenaires-grid, .projects-grid { grid-template-columns: 1fr; }
    .publication-item { flex-direction: column; }
    .section-header { flex-direction: column; align-items: flex-start; gap: 12px; }
}

.event-header-banner {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}


/* ============================================
   OFFRES ET OPPORTUNITÉS
   ============================================ */
.offres-section {
    background: linear-gradient(135deg, #f6f9fc 0%, #f0f4f8 100%);
    padding: 48px 40px;
    border-radius: var(--border-radius-xl);
    margin-bottom: 48px;
}

.offres-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
}

.offre-card {
    background: white;
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
}

.offre-card:hover {
    box-shadow: var(--shadow-xl);
    transform: translateY(-6px);
    border-color: var(--primary);
}

.offre-header {
    background: var(--gray-50);
    padding: 16px 20px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
}

.offre-type-badge {
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.badge-stage { 
    background: rgba(33, 150, 243, 0.1); 
    color: #1976d2; 
}

.badge-these { 
    background: rgba(156, 39, 176, 0.1); 
    color: #7b1fa2; 
}

.badge-bourse { 
    background: rgba(255, 193, 7, 0.1); 
    color: #f57c00; 
}

.badge-collaboration { 
    background: rgba(76, 175, 80, 0.1); 
    color: #388e3c; 
}

.badge-emploi { 
    background: rgba(244, 67, 54, 0.1); 
    color: #d32f2f; 
}

.badge-postdoc { 
    background: rgba(0, 188, 212, 0.1); 
    color: #0097a7; 
}

.offre-expiration {
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--gray-600);
    font-size: 12px;
    font-weight: 500;
}

.offre-content {
    padding: 24px;
}

.offre-content h3 {
    margin: 0 0 12px 0;
    font-size: 17px;
    font-weight: 600;
    color: var(--gray-900);
    line-height: 1.4;
}

.offre-description {
    color: var(--gray-600);
    font-size: 14px;
    line-height: 1.6;
    margin: 0 0 16px 0;
}

.offre-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 20px;
    padding-top: 16px;
    border-top: 1px solid var(--border-color);
}

.offre-meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--gray-500);
    font-size: 13px;
}

.offre-meta-item svg {
    flex-shrink: 0;
}

.btn-offre-details {
    display: inline-block;
    width: 100%;
    padding: 12px 20px;
    background: var(--primary);
    color: white;
    text-align: center;
    text-decoration: none;
    border-radius: var(--border-radius-sm);
    font-weight: 600;
    font-size: 14px;
    transition: var(--transition);
}

.btn-offre-details:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}


@media (max-width: 768px) {
    .scientific-events-grid,
    .offres-grid {
        grid-template-columns: 1fr;
    }
    
    .offres-section {
        padding: 32px 24px;
    }
    
    .event-header-banner {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .offre-header {
        flex-direction: column;
        align-items: flex-start;
    }
}
@media (max-width: 480px) {
    .slide-text h2 { font-size: 24px; }
    .stat-card .number { font-size: 36px; }
}
        </style>
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