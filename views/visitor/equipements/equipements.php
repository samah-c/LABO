<?php
/**
 * Page publique des équipements
 */

require_once __DIR__ . '/../../../lib/helpers.php';
require_once __DIR__ . '/../../../lib/ViewComponents.php';

ViewComponents::renderHeader([
    'title' => 'Équipements du Laboratoire - TDW',
    'role' => 'visiteur',
    'showLogout' => false,
    'showLoginButton' => true,
    'additionalCss' => [],
    'additionalJs' => [base_url('assets/js/visitor/equipement-handler.js')]
]);
?>

<div class="visitor-container">
    <!-- En-tête de la page -->
    <section class="page-banner">
        <div class="banner-content">
            <h1>Équipements du Laboratoire</h1>
            <p>Découvrez nos équipements de recherche et infrastructures</p>
        </div>
    </section>

    <div class="container">
        <!-- Filtres et recherche -->
        <div class="filters-bar">
            <div class="search-box">
                <input type="text" 
                       id="search-input" 
                       placeholder="Rechercher un équipement..."
                       value="<?= e(get('search', '')) ?>">
            </div>
            
            <div class="filters">
                <div class="filter-item">
                    <label for="filter-type">Type</label>
                    <select id="filter-type" class="filter-select">
                        <option value="">Tous</option>
                        <option value="Ordinateur">Ordinateur</option>
                        <option value="serveur">Serveur</option>
                        <option value="Imprimante">Imprimante</option>
                        <option value="Reseau">Équipement réseau</option>
                        <option value="Laboratoire">Équipement de labo</option>
                        <option value="Robot">Robot</option>
                        <option value="Salle">Salle</option>
                    </select>
                </div>
                
                <div class="filter-item">
                    <label for="filter-etat">État</label>
                    <select id="filter-etat" class="filter-select">
                        <option value="">Tous</option>
                        <option value="libre">Disponible</option>
                        <option value="reserve">Réservé</option>
                        <option value="en_maintenance">En maintenance</option>
                    </select>
                </div>
                
                <div class="filter-item">
                    <label for="filter-localisation">Localisation</label>
                    <select id="filter-localisation" class="filter-select">
                        <option value="">Toutes</option>
                        <?php
                        $localisations = array_unique(array_filter(array_column($equipements, 'localisation')));
                        foreach ($localisations as $loc):
                        ?>
                            <option value="<?= e($loc) ?>"><?= e($loc) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-item">
                    <label for="sort-by">Trier par</label>
                    <select id="sort-by" class="filter-select">
                        <option value="nom">Nom (A-Z)</option>
                        <option value="type">Type</option>
                        <option value="etat">État</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Statistiques rapides -->
        <div class="stats-grid" style="margin-bottom: 32px;">
            <div class="stat-card">
                <h3>Équipements Total</h3>
                <div class="number"><?= count($equipements) ?></div>
            </div>
            <div class="stat-card">
                <h3>Disponibles</h3>
                <div class="number" id="available-count"><?= count(array_filter($equipements, fn($e) => ($e['etat_normalized'] ?? '') === 'libre')) ?></div>
            </div>
            <div class="stat-card">
                <h3>Laboratoires</h3>
                <div class="number"><?= count(array_filter($equipements, fn($e) => ($e['type_normalized'] ?? '') === 'Laboratoire')) ?></div>
            </div>
            <div class="stat-card">
                <h3>Serveurs</h3>
                <div class="number"><?= count(array_filter($equipements, fn($e) => ($e['type_normalized'] ?? '') === 'serveur')) ?></div>
            </div>
        </div>

        <!-- Liste des équipements -->
        <div id="equipements-container" class="equipements-grid">
            <?php if (!empty($equipements)): ?>
                <?php foreach ($equipements as $eq): ?>
                    <?php
                    // Normalisation des valeurs
                    $typeNormalized = $eq['type_normalized'] ?? 'Autre';
                    $etatNormalized = $eq['etat_normalized'] ?? 'libre';
                    $localisation = $eq['localisation'] ?? 'Non spécifié';
                    
                    // Badges d'état
                    $etatBadges = [
                        'libre' => '<span class="badge badge-success">Disponible</span>',
                        'reserve' => '<span class="badge badge-info">Réservé</span>',
                        'en_maintenance' => '<span class="badge badge-warning">Maintenance</span>',
                        'hors_service' => '<span class="badge badge-danger">Hors service</span>'
                    ];
                    $badge = $etatBadges[$etatNormalized] ?? '';
                    ?>
                    <article class="equipement-card" 
                             data-type="<?= e($typeNormalized) ?>"
                             data-etat="<?= e($etatNormalized) ?>"
                             data-localisation="<?= e($localisation) ?>"
                             data-nom="<?= e(strtolower($eq['nom'] ?? '')) ?>">
                        
                        <div class="equipement-header">
                            <span class="equipement-type-label"><?= e($eq['type_original'] ?? $typeNormalized) ?></span>
                            <?= $badge ?>
                        </div>
                        
                        <h3 class="equipement-title">
                            <a href="<?= base_url('equipements/' . $eq['id']) ?>">
                                <?= e($eq['nom']) ?>
                            </a>
                        </h3>
                        
                        <?php if (!empty($eq['description'])): ?>
                        <p class="equipement-description">
                            <?= truncate($eq['description'], 120) ?>
                        </p>
                        <?php endif; ?>
                        
                        <div class="equipement-meta">
                            <?php if (!empty($localisation)): ?>
                            <span class="meta-item">
                                <strong>Localisation:</strong> <?= e($localisation) ?>
                            </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($eq['equipe_nom'])): ?>
                            <span class="meta-item">
                                <strong>Équipe:</strong> <?= e($eq['equipe_nom']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="equipement-footer">
                            <?php if (!empty($eq['numero_serie'])): ?>
                            <span class="serial-number">
                                N° <?= e($eq['numero_serie']) ?>
                            </span>
                            <?php endif; ?>
                            
                            <a href="<?= base_url('equipements/' . $eq['id']) ?>" 
                               class="btn-primary">
                                Voir détails
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>Aucun équipement disponible</h3>
                    <p class="text-muted">Aucun équipement n'est actuellement listé.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Message si aucun résultat après filtrage -->
        <div id="no-results" class="empty-message" style="display: none;">
            Aucun équipement ne correspond à vos critères de recherche.
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
/* Styles pour la page des équipements */

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

/* Grille des équipements */
.equipements-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
}

.equipement-card {
    background: var(--bg-card);
    border-radius: var(--border-radius-lg);
    border: 1px solid var(--border-color);
    padding: 24px;
    transition: var(--transition);
    box-shadow: var(--shadow-xs);
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.equipement-card:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-4px);
    border-color: var(--primary);
}

.equipement-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.equipement-type-label {
    display: inline-block;
    padding: 6px 14px;
    background: var(--gray-100);
    border-radius: 16px;
    font-size: 13px;
    font-weight: 600;
    color: var(--gray-700);
}

.equipement-title {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    line-height: 1.4;
}

.equipement-title a {
    color: var(--gray-900);
    text-decoration: none;
    transition: var(--transition);
}

.equipement-title a:hover {
    color: var(--primary);
}

.equipement-description {
    line-height: 1.6;
    margin: 0;
    font-size: 14px;
    color: var(--gray-600);
}

.equipement-meta {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 12px;
    background: var(--gray-50);
    border-radius: 8px;
}

.meta-item {
    font-size: 13px;
    color: var(--gray-700);
}

.meta-item strong {
    color: var(--gray-900);
}

.equipement-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: auto;
    padding-top: 16px;
    border-top: 1px solid var(--border-color);
}

.serial-number {
    font-size: 12px;
    color: var(--gray-600);
    font-family: monospace;
    background: var(--gray-100);
    padding: 4px 8px;
    border-radius: 4px;
}

.equipement-footer .btn-primary {
    padding: 8px 16px;
    font-size: 14px;
}

/* Responsive */
@media (max-width: 1024px) {
    .equipements-grid {
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
    
    .equipements-grid {
        grid-template-columns: 1fr;
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
