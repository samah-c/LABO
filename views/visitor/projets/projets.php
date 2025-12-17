<?php
/**
 * Page publique des projets de recherche - VERSION CORRIGÉE
 * À placer dans : views/visitor/projets/projets.php
 */

require_once __DIR__ . '/../../../lib/helpers.php';
require_once __DIR__ . '/../../../lib/ViewComponents.php';

ViewComponents::renderHeader([
    'title' => 'Projets de Recherche - Laboratoire TDW',
    'role' => 'visiteur',
    'showLogout' => false,
    'showLoginButton' => true,
    'additionalCss' => [],
    'additionalJs' => [base_url('assets/js/visitor/projets-handler.js')]
]);
?>

<div class="visitor-container">
    <!-- En-tête de la page -->
    <section class="page-banner">
        <div class="banner-content">
            <h1>Projets de Recherche</h1>
            <p>Découvrez nos projets innovants dans différents domaines de recherche</p>
        </div>
    </section>

    <div class="container">
        <!-- Filtres et recherche -->
        <div class="filters-bar">
            <div class="search-box">
                <input type="text" 
                       id="search-input" 
                       placeholder="Rechercher un projet..."
                       value="<?= e(get('search', '')) ?>">
            </div>
            
            <div class="filters">
                <div class="filter-item">
                    <label for="filter-thematique">Thématique</label>
                    <select id="filter-thematique" class="filter-select">
                        <option value="">Toutes</option>
                        <option value="IA">Intelligence Artificielle</option>
                        <option value="Securite">Sécurité Informatique</option>
                        <option value="Cloud">Cloud Computing</option>
                        <option value="Reseaux">Réseaux</option>
                        <option value="Systemes">Systèmes Embarqués</option>
                        <option value="Big Data">Big Data</option>
                        <option value="IoT">Internet des Objets</option>
                    </select>
                </div>
                
                <div class="filter-item">
                    <label for="filter-status">Statut</label>
                    <select id="filter-status" class="filter-select">
                        <option value="">Tous</option>
                        <option value="en_cours">En cours</option>
                        <option value="termine">Terminé</option>
                        <option value="soumis">Soumis</option>
                        <option value="approuve">Approuvé</option>
                        <option value="rejete">Rejeté</option>
                    </select>
                </div>
                
                <div class="filter-item">
                    <label for="sort-by">Trier par</label>
                    <select id="sort-by" class="filter-select">
                        <option value="recent">Plus récents</option>
                        <option value="title">Titre (A-Z)</option>
                        <option value="thematique">Thématique</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Statistiques rapides -->
        <div class="stats-grid" style="margin-bottom: 32px;">
            <div class="stat-card">
                <h3>Projets Total</h3>
                <div class="number"><?= count($projets) ?></div>
            </div>
            <div class="stat-card">
                <h3>Projets Affichés</h3>
                <div class="number" id="filtered-count"><?= count($projets) ?></div>
            </div>
            <div class="stat-card">
                <h3>En Cours</h3>
                <div class="number"><?= count(array_filter($projets, fn($p) => ($p['status_normalized'] ?? '') === 'en_cours')) ?></div>
            </div>
        </div>

        <!-- Liste des projets -->
        <div id="projects-container" class="projects-grid">
            <?php if (!empty($projets)): ?>
                <?php foreach ($projets as $projet): ?>
                    <?php
                    // S'assurer que le statut normalisé existe
                    $statusNormalized = $projet['status_normalized'] ?? 'en_cours';
                    $thematiqueCode = $projet['thematique_code'] ?? $projet['thematique'] ?? '';
                    ?>
                    <article class="project-card" 
                             data-thematique="<?= e($thematiqueCode) ?>"
                             data-status="<?= e($statusNormalized) ?>"
                             data-title="<?= e(strtolower($projet['titre'] ?? '')) ?>"
                             data-date="<?= strtotime($projet['date_debut'] ?? 'now') ?>">
                        
                        <div class="project-theme-badge badge badge-primary">
                            <?= e($projet['thematique'] ?? 'Non défini') ?>
                        </div>
                        
                        <div class="project-header">
                            <h3 class="project-title">
                                <a href="<?= base_url('projets/' . $projet['id']) ?>">
                                    <?= e($projet['titre']) ?>
                                </a>
                            </h3>
                            <?php 
                            // Utiliser le statut original pour l'affichage du badge
                            echo LabHelpers::getProjetStatusBadge($projet['status_original'] ?? $projet['status'] ?? 'en_cours'); 
                            ?>
                        </div>
                        
                        <p class="project-description text-gray">
                            <?= truncate($projet['descriptif'] ?? $projet['description'] ?? '', 150) ?>
                        </p>
                        
                        <div class="project-info">
                            <div class="info-row d-flex items-center gap-sm">
                                <span class="info-text text-sm">
                                    <strong>Responsable:</strong> 
                                    <?= e($projet['responsable_username'] ?? $projet['responsable_nom'] ?? 'Non défini') ?>
                                </span>
                            </div>
                            
                            <?php if (!empty($projet['nb_membres'])): ?>
                            <div class="info-row d-flex items-center gap-sm">
                                <span class="info-text text-sm">
                                    <?= $projet['nb_membres'] ?> membre<?= $projet['nb_membres'] > 1 ? 's' : '' ?>
                                </span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="info-row d-flex items-center gap-sm">
                                <span class="info-text text-sm">
                                    <?= date('Y', strtotime($projet['date_debut'])) ?>
                                    <?php if (!empty($projet['date_fin'])): ?>
                                        - <?= date('Y', strtotime($projet['date_fin'])) ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <?php if (!empty($projet['source_financement'])): ?>
                            <div class="info-row d-flex items-center gap-sm">
                                <span class="info-text text-sm">
                                    <?= e($projet['source_financement']) ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php 
                        $progression = LabHelpers::calculateProjectProgress(
                            $projet['date_debut'], 
                            $projet['date_fin'] ?? null
                        );
                        ?>
                        <div class="project-progress mt-md">
                            <div class="progress">
                                <div class="progress-bar" style="width: <?= $progression ?>%"></div>
                            </div>
                            <span class="progress-text text-xs text-muted mt-xs d-block"><?= $progression ?>% complété</span>
                        </div>
                        
                        <div class="project-footer mt-md pt-md border-top">
                            <a href="<?= base_url('projets/' . $projet['id']) ?>" class="btn-primary">
                                Voir les détails
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>Aucun projet disponible</h3>
                    <p class="text-muted">Aucun projet de recherche n'est actuellement publié.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Message si aucun résultat après filtrage -->
        <div id="no-results" class="empty-message" style="display: none;">
            Aucun projet ne correspond à vos critères de recherche.
            <button onclick="resetFilters()" class="btn-secondary mt-md">
                Réinitialiser les filtres
            </button>
        </div>
        
        <!-- Pagination -->
        <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
            <div class="pagination">
                <?php if ($pagination['current_page'] > 1): ?>
                    <a href="?page=<?= $pagination['current_page'] - 1 ?>" class="page-link">
                        Précédent
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                    <a href="?page=<?= $i ?>" 
                       class="page-link <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                    <a href="?page=<?= $pagination['current_page'] + 1 ?>" class="page-link">
                        Suivant
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Styles spécifiques pour la page des projets uniquement */

/* Bannière de page */
.page-banner {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    padding: 60px 32px;
    text-align: center;
    color: white;
}

.banner-content h1 {
    font-size: 42px;
    font-weight: 700;
    margin: 0 0 12px 0;
    letter-spacing: -0.5px;
}

.banner-content p {
    font-size: 18px;
    opacity: 0.95;
    margin: 0;
}

/* Grille des projets */
.projects-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
}

.project-card {
    background: var(--bg-card);
    border-radius: var(--border-radius-lg);
    border: 1px solid var(--border-color);
    padding: 24px;
    transition: var(--transition);
    box-shadow: var(--shadow-xs);
    position: relative;
    display: flex;
    flex-direction: column;
}

.project-card:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-4px);
    border-color: var(--primary);
}

.project-theme-badge {
    position: absolute;
    top: 16px;
    right: 16px;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.project-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 16px;
    padding-right: 120px;
}

.project-title {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    line-height: 1.4;
    flex: 1;
}

.project-title a {
    color: var(--gray-900);
    text-decoration: none;
    transition: var(--transition);
}

.project-title a:hover {
    color: var(--primary);
}

.project-description {
    line-height: 1.6;
    margin: 0 0 20px 0;
    font-size: 14px;
}

.project-info {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 20px;
    padding: 16px;
    background: var(--gray-50);
    border-radius: var(--border-radius-sm);
}

.info-icon {
    font-size: 16px;
    flex-shrink: 0;
}

.project-footer {
    margin-top: auto;
}

.project-footer .btn-primary {
    width: 100%;
    padding: 10px 20px;
    font-size: 14px;
}

.progress-text {
    text-align: center;
}

/* Responsive */
@media (max-width: 1024px) {
    .projects-grid {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    }
}

@media (max-width: 768px) {
    .banner-content h1 {
        font-size: 32px;
    }
    
    .banner-content p {
        font-size: 16px;
    }
    
    .projects-grid {
        grid-template-columns: 1fr;
    }
    
    .project-header {
        flex-direction: column;
        padding-right: 0;
    }
    
    .project-theme-badge {
        position: static;
        display: inline-block;
        margin-bottom: 12px;
    }
    
    .filters-bar {
        padding: 20px;
    }
    
    .filters {
        flex-direction: column;
        width: 100%;
    }
}
</style>

<?php ViewComponents::renderFooter(['role' => 'visiteur']); ?>