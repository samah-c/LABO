<?php
/**
 * Page publique des publications
 * À placer dans : views/visitor/publications/publications.php
 */

require_once __DIR__ . '/../../../lib/helpers.php';
require_once __DIR__ . '/../../../lib/ViewComponents.php';

ViewComponents::renderHeader([
    'title' => 'Publications Scientifiques - Laboratoire TDW',
    'role' => 'visiteur',
    'showLogout' => false,
    'showLoginButton' => true,
    'additionalCss' => [],
    'additionalJs' => [base_url('assets/js/visitor/publications-handler.js')]
]);
?>

<div class="visitor-container">
    <!-- En-tête de la page -->
    <section class="page-banner">
        <div class="banner-content">
            <h1> Publications Scientifiques</h1>
            <p>Découvrez nos travaux de recherche et publications académiques</p>
        </div>
    </section>

    <div class="container">
        <!-- Filtres et recherche -->
        <div class="filters-bar">
            <div class="search-box">
                <input type="text" 
                       id="search-input" 
                       placeholder="Rechercher une publication..."
                       value="<?= e(get('search', '')) ?>">
            </div>
            
            <div class="filters">
                <div class="filter-item">
                    <label for="filter-type">Type</label>
                    <select id="filter-type" class="filter-select">
                        <option value="">Tous</option>
                        <option value="Article">Article</option>
                        <option value="Conference">Conférence</option>
                        <option value="These">Thèse</option>
                        <option value="Rapport">Rapport</option>
                        <option value="Livre">Livre</option>
                        <option value="Chapitre">Chapitre</option>
                    </select>
                </div>
                
                <div class="filter-item">
                    <label for="filter-domaine">Domaine</label>
                    <select id="filter-domaine" class="filter-select">
                        <option value="">Tous</option>
                        <option value="IA">Intelligence Artificielle</option>
                        <option value="Securite">Sécurité</option>
                        <option value="Reseaux">Réseaux</option>
                        <option value="Blockchain">Blockchain</option>
                        <option value="IoT">IoT</option>
                        <option value="BigData">Big Data</option>
                    </select>
                </div>
                
                <div class="filter-item">
                    <label for="filter-annee">Année</label>
                    <select id="filter-annee" class="filter-select">
                        <option value="">Toutes</option>
                        <?php for($year = date('Y'); $year >= 2020; $year--): ?>
                            <option value="<?= $year ?>"><?= $year ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="filter-item">
                    <label for="sort-by">Trier par</label>
                    <select id="sort-by" class="filter-select">
                        <option value="recent">Plus récentes</option>
                        <option value="title">Titre (A-Z)</option>
                        <option value="type">Type</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Statistiques rapides -->
        <div class="stats-grid" style="margin-bottom: 32px;">
            <div class="stat-card">
                <h3>Publications Total</h3>
                <div class="number"><?= count($publications) ?></div>
            </div>
            <div class="stat-card">
                <h3>Publications Affichées</h3>
                <div class="number" id="filtered-count"><?= count($publications) ?></div>
            </div>
            <div class="stat-card">
                <h3>Articles</h3>
                <div class="number"><?= count(array_filter($publications, fn($p) => ($p['type_normalized'] ?? '') === 'Article')) ?></div>
            </div>
            <div class="stat-card">
                <h3>Conférences</h3>
                <div class="number"><?= count(array_filter($publications, fn($p) => ($p['type_normalized'] ?? '') === 'Conference')) ?></div>
            </div>
        </div>

        <!-- Liste des publications -->
        <div id="publications-container" class="publications-grid">
            <?php if (!empty($publications)): ?>
                <?php foreach ($publications as $pub): ?>
                    <?php
                    // S'assurer que les valeurs normalisées existent
                    $typeNormalized = $pub['type_normalized'] ?? 'Article';
                    $domaineNormalized = $pub['domaine_normalized'] ?? '';
                    $annee = $pub['annee_publication'] ?? date('Y');
                    
                    // Couleurs par type
                    $typeColors = [
                        'Article' => '#3B82F6',
                        'Conference' => '#8B5CF6',
                        'These' => '#EC4899',
                        'Rapport' => '#F59E0B',
                        'Livre' => '#10B981',
                        'Chapitre' => '#6366F1'
                    ];
                    $badgeColor = $typeColors[$typeNormalized] ?? '#6B7280';
                    ?>
                    <article class="publication-card" 
                             data-type="<?= e($typeNormalized) ?>"
                             data-domaine="<?= e($domaineNormalized) ?>"
                             data-annee="<?= e($annee) ?>"
                             data-title="<?= e(strtolower($pub['titre'] ?? '')) ?>"
                             data-date="<?= strtotime($pub['date_publication'] ?? 'now') ?>">
                        
                        <div class="publication-header">
                            <span class="publication-type-badge" style="background: <?= $badgeColor ?>;">
                                <?= e($pub['type_original'] ?? $typeNormalized) ?>
                            </span>
                            <span class="publication-year"><?= e($annee) ?></span>
                        </div>
                        
                        <h3 class="publication-title">
                            <a href="<?= base_url('publications/' . $pub['id']) ?>">
                                <?= e($pub['titre']) ?>
                            </a>
                        </h3>
                        
                        <?php if (!empty($pub['auteurs_noms'])): ?>
                        <div class="publication-authors">
                            <?= e($pub['auteurs_noms']) ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($pub['resume'])): ?>
                        <p class="publication-resume">
                            <?= truncate($pub['resume'], 150) ?>
                        </p>
                        <?php endif; ?>
                        
                        <div class="publication-meta">
                            <?php if (!empty($pub['domaine_original'])): ?>
                            <span class="meta-badge">
                                <?= e($pub['domaine_original']) ?>
                            </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($pub['projet_titre'])): ?>
                            <span class="meta-info">
                                 <?= e(truncate($pub['projet_titre'], 30)) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="publication-footer">
                            <?php if (!empty($pub['doi'])): ?>
                            <a href="https://doi.org/<?= e($pub['doi']) ?>" 
                               target="_blank" 
                               class="btn-link"
                               title="Voir sur DOI">
                                 DOI
                            </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($pub['lien'])): ?>
                            <a href="<?= e($pub['lien']) ?>" 
                               target="_blank" 
                               class="btn-link"
                               title="Télécharger">
                                 PDF
                            </a>
                            <?php endif; ?>
                            
                            <a href="<?= base_url('publications/' . $pub['id']) ?>" 
                               class="btn-primary">
                                Voir détails
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>Aucune publication disponible</h3>
                    <p class="text-muted">Aucune publication n'est actuellement publiée.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Message si aucun résultat après filtrage -->
        <div id="no-results" class="empty-message" style="display: none;">
            Aucune publication ne correspond à vos critères de recherche.
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
/* Styles spécifiques pour la page des publications */

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

/* Grille des publications */
.publications-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
}

.publication-card {
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

.publication-card:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-4px);
    border-color: var(--primary);
}

.publication-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.publication-type-badge {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 16px;
    font-size: 12px;
    font-weight: 600;
    color: white;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.publication-year {
    font-size: 14px;
    font-weight: 600;
    color: var(--gray-600);
}

.publication-title {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    line-height: 1.4;
}

.publication-title a {
    color: var(--gray-900);
    text-decoration: none;
    transition: var(--transition);
}

.publication-title a:hover {
    color: var(--primary);
}

.publication-authors {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: var(--gray-700);
}

.publication-authors .icon {
    font-size: 16px;
}

.publication-resume {
    line-height: 1.6;
    margin: 0;
    font-size: 14px;
    color: var(--gray-600);
}

.publication-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}

.meta-badge {
    display: inline-block;
    padding: 4px 10px;
    background: var(--gray-100);
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    color: var(--gray-700);
}

.meta-info {
    font-size: 13px;
    color: var(--gray-600);
}

.publication-footer {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-top: auto;
    padding-top: 16px;
    border-top: 1px solid var(--border-color);
}

.btn-link {
    padding: 6px 12px;
    font-size: 13px;
    color: var(--primary);
    text-decoration: none;
    border: 1px solid var(--primary);
    border-radius: 6px;
    transition: var(--transition);
}

.btn-link:hover {
    background: var(--primary);
    color: white;
}

.publication-footer .btn-primary {
    margin-left: auto;
    padding: 8px 16px;
    font-size: 14px;
}

/* Responsive */
@media (max-width: 1024px) {
    .publications-grid {
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
    
    .publications-grid {
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
<script>
    /**
 * Script de debug à ajouter temporairement dans publications.php
 * Collez ce code juste avant la balise </body>
 */

console.log('🔍 === DIAGNOSTIC DES LIENS PUBLICATIONS ===');

// Attendre que le DOM soit chargé
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Vérifier tous les liens "Voir détails"
    const detailLinks = document.querySelectorAll('.publication-footer .btn-primary');
    console.log(`📊 Nombre de liens "Voir détails" trouvés: ${detailLinks.length}`);
    
    detailLinks.forEach((link, index) => {
        const card = link.closest('.publication-card');
        const titre = card ? card.querySelector('.publication-title a')?.textContent?.trim() : 'Inconnu';
        
        console.log(`\n🔗 Lien ${index + 1}:`);
        console.log(`  📝 Titre: ${titre}`);
        console.log(`  🌐 href: ${link.href}`);
        console.log(`  📍 href relatif: ${link.getAttribute('href')}`);
        
        // Ajouter un listener pour intercepter le clic
        link.addEventListener('click', function(e) {
            console.log(`\n🖱️ CLIC DÉTECTÉ sur publication ${index + 1}`);
            console.log(`  📝 Titre: ${titre}`);
            console.log(`  🎯 URL cible: ${this.href}`);
            console.log(`  📂 Pathname: ${window.location.pathname}`);
            console.log(`  🔗 Lien cliqué: ${this.getAttribute('href')}`);
            
            // Ne pas bloquer la navigation
            // e.preventDefault(); 
            
            return true;
        });
    });
    
    // 2. Vérifier la structure des cartes
    const cards = document.querySelectorAll('.publication-card');
    console.log(`\n📦 Nombre de cartes de publication: ${cards.length}`);
    
    cards.forEach((card, index) => {
        const titre = card.querySelector('.publication-title a')?.textContent?.trim();
        const titreLink = card.querySelector('.publication-title a')?.href;
        const detailLink = card.querySelector('.publication-footer .btn-primary')?.href;
        
        console.log(`\nCarte ${index + 1}:`);
        console.log(`  📝 ${titre}`);
        console.log(`  🔗 Lien titre: ${titreLink || 'N/A'}`);
        console.log(`  🔗 Lien "Voir détails": ${detailLink || 'N/A'}`);
    });
    
    // 3. Test: essayer de cliquer programmatiquement
    console.log('\n🧪 Pour tester un clic programmatique, tapez dans la console:');
    console.log('   testPublicationClick(0)  // Pour tester le premier lien');
});

// Fonction de test exposée globalement
window.testPublicationClick = function(index) {
    const links = document.querySelectorAll('.publication-footer .btn-primary');
    if (links[index]) {
        console.log(`🧪 Test du lien ${index}`);
        console.log(`   URL: ${links[index].href}`);
        links[index].click();
    } else {
        console.error(`❌ Pas de lien à l'index ${index}`);
    }
};

// Fonction pour vérifier la configuration de base_url
window.checkBaseUrl = function() {
    console.log('\n🔧 VÉRIFICATION BASE URL:');
    console.log(`  🌐 window.location.origin: ${window.location.origin}`);
    console.log(`  📂 window.location.pathname: ${window.location.pathname}`);
    console.log(`  🔗 Liens complets attendus: ${window.location.origin}/TDW_project/publications/[ID]`);
    
    const firstLink = document.querySelector('.publication-footer .btn-primary');
    if (firstLink) {
        const url = new URL(firstLink.href);
        console.log(`  ✅ Exemple de lien généré: ${url.href}`);
        console.log(`  📍 Path: ${url.pathname}`);
    }
};

console.log('\n💡 Commandes disponibles:');
console.log('  • diagnosticPublications() - Diagnostic complet');
console.log('  • testPublicationClick(0) - Tester le premier lien');
console.log('  • checkBaseUrl() - Vérifier les URLs');
console.log('\n👉 Maintenant, cliquez sur "Voir détails" et observez la console!');
</script>

<?php ViewComponents::renderFooter(['role' => 'visiteur']); ?>