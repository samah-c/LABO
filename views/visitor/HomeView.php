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
        $this->renderPartenaires();
        $this->renderProjets();
        $this->renderPublications();
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
                        <article class="actualite-card">
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
.actualites-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}

.actualite-card {
    background: var(--bg-card);
    padding: 24px;
    border-radius: var(--border-radius-lg);
    border: 1px solid var(--border-color);
    transition: var(--transition);
    box-shadow: var(--shadow-xs);
}

.actualite-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-4px);
    border-color: var(--primary);
}

.actualite-card h3 {
    margin: 0 0 12px 0;
    font-size: 18px;
    font-weight: 600;
    color: var(--gray-900);
}

.actualite-card p {
    color: var(--gray-600);
    line-height: 1.6;
    margin: 0 0 12px 0;
    font-size: 14px;
}

.actualite-meta {
    font-size: 13px;
    color: var(--gray-500);
    font-weight: 500;
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