<?php
/**
 * Page publique des membres du laboratoire
 */

require_once __DIR__ . '/../../../lib/helpers.php';
require_once __DIR__ . '/../../../lib/ViewComponents.php';

ViewComponents::renderHeader([
    'title' => 'Membres du Laboratoire - Laboratoire TDW',
    'role' => 'visiteur',
    'showLogout' => false,
    'showLoginButton' => true,
    'additionalCss' => [],
    'additionalJs' => [base_url('assets/js/visitor/membres-handler.js')]
]);
?>

<div class="visitor-container">
    <!-- En-tête de la page -->
    <section class="page-banner">
        <div class="banner-content">
            <h1>Membres du Laboratoire</h1>
            <p>Découvrez notre équipe de chercheurs et enseignants</p>
        </div>
    </section>

    <div class="container">
        <!-- Filtres et recherche -->
        <div class="filters-bar">
            <div class="search-box">
                <input type="text" 
                       id="search-input" 
                       placeholder="Rechercher un membre..."
                       value="<?= e(get('search', '')) ?>">
            </div>
            
            <div class="filters">
                <div class="filter-item">
                    <label for="filter-poste">Poste</label>
                    <select id="filter-poste" class="filter-select">
                        <option value="">Tous</option>
                        <option value="enseignant">Enseignant</option>
                        <option value="doctorant">Doctorant</option>
                        <option value="etudiant">Étudiant</option>
                        <option value="invite">Invité</option>
                    </select>
                </div>
                
                <div class="filter-item">
                    <label for="filter-equipe">Équipe</label>
                    <select id="filter-equipe" class="filter-select">
                        <option value="">Toutes</option>
                        <?php 
                        // Extraire les équipes uniques
                        $equipes = array_unique(array_filter(array_column($membres, 'equipe_nom')));
                        sort($equipes);
                        foreach ($equipes as $equipe): 
                        ?>
                            <option value="<?= e($equipe) ?>"><?= e($equipe) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-item">
                    <label for="filter-grade">Grade</label>
                    <select id="filter-grade" class="filter-select">
                        <option value="">Tous</option>
                        <option value="Professeur">Professeur</option>
                        <option value="Maître de conférences A">Maître de conférences A</option>
                        <option value="Maître de conférences B">Maître de conférences B</option>
                        <option value="Doctorant">Doctorant</option>
                    </select>
                </div>
                
                <div class="filter-item">
                    <label for="sort-by">Trier par</label>
                    <select id="sort-by" class="filter-select">
                        <option value="name">Nom (A-Z)</option>
                        <option value="poste">Poste</option>
                        <option value="equipe">Équipe</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Statistiques rapides -->
        <div class="stats-grid" style="margin-bottom: 32px;">
            <div class="stat-card">
                <h3>Membres Total</h3>
                <div class="number"><?= count($membres) ?></div>
            </div>
            <div class="stat-card">
                <h3>Membres Affichés</h3>
                <div class="number" id="filtered-count"><?= count($membres) ?></div>
            </div>
            <div class="stat-card">
                <h3>Enseignants</h3>
                <div class="number"><?= count(array_filter($membres, fn($m) => ($m['poste_normalized'] ?? '') === 'enseignant')) ?></div>
            </div>
            <div class="stat-card">
                <h3>Doctorants</h3>
                <div class="number"><?= count(array_filter($membres, fn($m) => ($m['poste_normalized'] ?? '') === 'doctorant')) ?></div>
            </div>
        </div>

        <!-- Liste des membres -->
        <div id="membres-container" class="membres-grid">
            <?php if (!empty($membres)): ?>
                <?php foreach ($membres as $membre): ?>
                    <?php
                    // Normalisation du poste
                    $posteNormalized = $membre['poste_normalized'] ?? '';
                    $equipeNom = $membre['equipe_nom'] ?? '';
                    $grade = $membre['grade'] ?? '';
                    
                    // Couleurs par poste
                    $posteColors = [
                        'enseignant' => '#3B82F6',
                        'doctorant' => '#8B5CF6',
                        'etudiant' => '#10B981',
                        'invite' => '#F59E0B'
                    ];
                    $badgeColor = $posteColors[$posteNormalized] ?? '#6B7280';
                    
                    // Initiales pour l'avatar
                    $initiales = strtoupper(substr($membre['username'] ?? 'U', 0, 2));
                    ?>
                    <article class="membre-card" 
                             data-poste="<?= e($posteNormalized) ?>"
                             data-equipe="<?= e($equipeNom) ?>"
                             data-grade="<?= e($grade) ?>"
                             data-name="<?= e(strtolower($membre['username'] ?? '')) ?>">
                        
                        <div class="membre-avatar">
                            <?php if (!empty($membre['photo'])): ?>
                                <img src="<?= base_url('uploads/' . $membre['photo']) ?>" 
                                     alt="<?= e($membre['username']) ?>">
                            <?php else: ?>
                                <div class="avatar-placeholder">
                                    <?= $initiales ?>
                                </div>
                            <?php endif; ?>
                            
                            <span class="membre-status-badge" style="background: <?= $badgeColor ?>;">
                                <?= e(ucfirst($membre['poste'] ?? '')) ?>
                            </span>
                        </div>
                        
                        <div class="membre-info">
                            <h3 class="membre-name">
                                <a href="<?= base_url('membres/' . $membre['id']) ?>">
                                    <?= e($membre['username']) ?>
                                </a>
                            </h3>
                            
                            <?php if (!empty($grade)): ?>
                            <div class="membre-grade">
                                <?= e($grade) ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($equipeNom)): ?>
                            <div class="membre-equipe">
                                <?= e($equipeNom) ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($membre['biographie'])): ?>
                            <p class="membre-bio">
                                <?= truncate($membre['biographie'], 120) ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="membre-stats">
                            <div class="stat-item">
                                <span class="stat-value"><?= $membre['nb_projets'] ?? 0 ?></span>
                                <span class="stat-label">Projets</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value"><?= $membre['nb_publications'] ?? 0 ?></span>
                                <span class="stat-label">Publications</span>
                            </div>
                        </div>
                        
                        <div class="membre-footer">
                            <?php if (!empty($membre['email'])): ?>
                            <a href="mailto:<?= e($membre['email']) ?>" 
                               class="btn-link"
                               title="Envoyer un email">
                                Email
                            </a>
                            <?php endif; ?>
                            
                            <a href="<?= base_url('membres/' . $membre['id']) ?>" 
                               class="btn-primary">
                                Voir profil
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>Aucun membre disponible</h3>
                    <p class="text-muted">Aucun membre n'est actuellement affiché.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Message si aucun résultat après filtrage -->
        <div id="no-results" class="empty-message" style="display: none;">
            Aucun membre ne correspond à vos critères de recherche.
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

.membres-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
}

.membre-card {
    background: var(--bg-card);
    border-radius: var(--border-radius-lg);
    border: 1px solid var(--border-color);
    padding: 24px;
    transition: var(--transition);
    box-shadow: var(--shadow-xs);
    display: flex;
    flex-direction: column;
    gap: 16px;
    text-align: center;
}

.membre-card:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-4px);
    border-color: var(--primary);
}

.membre-avatar {
    position: relative;
    width: 120px;
    height: 120px;
    margin: 0 auto 12px;
}

.membre-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid var(--border-color);
}

.avatar-placeholder {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
    font-weight: 700;
    border: 4px solid var(--border-color);
}

.membre-status-badge {
    position: absolute;
    bottom: 0;
    right: 0;
    padding: 6px 12px;
    border-radius: 16px;
    font-size: 11px;
    font-weight: 600;
    color: white;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: 3px solid white;
}

.membre-info {
    flex: 1;
}

.membre-name {
    margin: 0 0 8px 0;
    font-size: 20px;
    font-weight: 600;
}

.membre-name a {
    color: var(--gray-900);
    text-decoration: none;
    transition: var(--transition);
}

.membre-name a:hover {
    color: var(--primary);
}

.membre-grade {
    font-size: 14px;
    font-weight: 500;
    color: var(--primary);
    margin-bottom: 6px;
}

.membre-equipe {
    font-size: 14px;
    color: var(--gray-600);
    margin-bottom: 12px;
    padding: 4px 12px;
    background: var(--gray-100);
    border-radius: 12px;
    display: inline-block;
}

.membre-bio {
    line-height: 1.6;
    margin: 12px 0 0 0;
    font-size: 14px;
    color: var(--gray-600);
    text-align: left;
}

.membre-stats {
    display: flex;
    justify-content: space-around;
    padding: 16px 0;
    border-top: 1px solid var(--border-color);
    border-bottom: 1px solid var(--border-color);
}

.stat-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: var(--primary);
}

.stat-label {
    font-size: 12px;
    color: var(--gray-600);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.membre-footer {
    display: flex;
    gap: 10px;
    align-items: center;
    justify-content: center;
}

.btn-link {
    padding: 8px 16px;
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

.membre-footer .btn-primary {
    padding: 8px 20px;
    font-size: 14px;
}

@media (max-width: 1024px) {
    .membres-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    }
}

@media (max-width: 768px) {
    .banner-content h1 {
        font-size: 32px;
    }
    
    .banner-content p {
        font-size: 16px;
    }
    
    .membres-grid {
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