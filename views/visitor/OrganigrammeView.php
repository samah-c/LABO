<?php
/**
 * Vue de l'organigramme du laboratoire
 */

require_once __DIR__ . '/../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../lib/components/NavigationComponent.php';
require_once __DIR__ . '/../../lib/components/FooterComponent.php';
require_once __DIR__ . '/../../lib/LabHelpers.php';

class OrganigrammeView
{
    private ?array $directeur;
    private array $membres;
    private array $equipes;
    private $equipeModel;

    public function __construct(?array $directeur, array $membres, array $equipes, $equipeModel = null)
    {
        $this->directeur = $directeur;
        $this->membres = $membres;
        $this->equipes = $equipes;
        $this->equipeModel = $equipeModel;
    }

    /**
     * Rendu complet de la vue
     */
    public function render(): void
    {
        $this->renderHeader();
        $this->renderNavigation();
        echo '<div class="visitor-container">';
        $this->renderPageBanner();
        echo '<div class="container">';
        $this->renderPresentation();
        $this->renderOrganigramme();
        $this->renderEquipes();
        echo '</div>';
        echo '</div>';
        $this->renderFooter();
    }

    /**
     * Rendu de l'en-tête
     */
    private function renderHeader(): void
    {
        HeaderComponent::render([
            'title' => 'Organigramme - Laboratoire TDW',
            'role' => 'visiteur',
            'showLogout' => false,
            'showLoginButton' => true,
            'additionalJs' => [base_url('assets/js/visitor/organigramme-handler.js')]
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
     * Rendu de la bannière
     */
    private function renderPageBanner(): void
    {
        ?>
        <section class="page-banner">
            <div class="banner-content">
                <h1>Organigramme du Laboratoire</h1>
                <p>Structure organisationnelle et équipes de recherche</p>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu de la présentation
     */
    private function renderPresentation(): void
    {
        ?>
        <section class="detail-card presentation-section">
            <h2>Présentation du Laboratoire TDW</h2>
            <div class="presentation-content">
                <p>
                    Le Laboratoire TDW (Technologies du Développement Web) est un centre de recherche de pointe 
                    spécialisé dans les domaines des technologies web modernes, de l'intelligence artificielle 
                    et de la cybersécurité. Notre mission est de contribuer à l'avancement des connaissances 
                    scientifiques et de former la prochaine génération de chercheurs et d'ingénieurs.
                </p>
                
                <h3>Thèmes de Recherche</h3>
                <div class="themes-grid">
                    <?php $this->renderThemeCard(
                        'Intelligence Artificielle',
                        'Machine Learning, Deep Learning, Vision par Ordinateur, Traitement du Langage Naturel'
                    ); ?>
                    
                    <?php $this->renderThemeCard(
                        'Développement Web',
                        'Architectures Cloud, Progressive Web Apps, Frameworks Modernes, Performance Web'
                    ); ?>
                    
                    <?php $this->renderThemeCard(
                        'Cybersécurité',
                        'Sécurité des Réseaux, Cryptographie, Détection d\'Intrusions, Sécurité Applicative'
                    ); ?>
                    
                    <?php $this->renderThemeCard(
                        'IoT & Systèmes Embarqués',
                        'Internet des Objets, Systèmes Temps Réel, Edge Computing, Capteurs Intelligents'
                    ); ?>
                </div>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu d'une carte de thème
     */
    private function renderThemeCard(string $title, string $description): void
    {
        ?>
        <div class="theme-card">
            <h4><?= e($title) ?></h4>
            <p><?= e($description) ?></p>
        </div>
        <?php
    }

    /**
     * Rendu de l'organigramme
     */
    private function renderOrganigramme(): void
    {
        ?>
        <section class="detail-card organigramme-section">
            <h2>Organigramme du Laboratoire</h2>
            
            <?php if ($this->directeur): ?>
                <?php $this->renderDirecteur(); ?>
            <?php endif; ?>
            
            <?php $this->renderMembresParPoste(); ?>
        </section>
        <?php
    }

    /**
     * Rendu du directeur
     */
    private function renderDirecteur(): void
    {
        $d = $this->directeur;
        ?>
        <div class="directeur-section">
            <h3>Direction du Laboratoire</h3>
            <div class="membre-card-horizontal directeur-card">
                <div class="membre-avatar-large">
                    <?php if (!empty($d['photo'])): ?>
                        <img src="<?= base_url('uploads/photos/' . $d['photo']) ?>" 
                             alt="<?= e($d['nom']) ?>">
                    <?php else: ?>
                        <div class="avatar-placeholder-large">
                            <?= strtoupper(substr($d['nom'] ?? 'D', 0, 2)) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="membre-info-horizontal">
                    <div class="membre-header">
                        <h4>
                            <a href="<?= base_url('membres/' . $d['id']) ?>">
                                <?= e($d['nom']) ?>
                            </a>
                        </h4>
                        <span class="badge-directeur">Directeur</span>
                    </div>
                    <?php if (!empty($d['grade'])): ?>
                        <div class="membre-grade"><?= e($d['grade']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($d['biographie'])): ?>
                        <p class="membre-bio"><?= truncate($d['biographie'], 200) ?></p>
                    <?php endif; ?>
                    <div class="membre-actions">
                        <?php if (!empty($d['email'])): ?>
                            <a href="mailto:<?= e($d['email']) ?>" class="btn-link">Contact</a>
                        <?php endif; ?>
                        <a href="<?= base_url('membres/' . $d['id']) ?>" class="btn-primary">Voir profil</a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu des membres par poste
     */
    private function renderMembresParPoste(): void
    {
        ?>
        <div class="postes-section">
            <h3>Membres du Laboratoire par Poste</h3>
            
            <div class="filters-inline">
                <select id="filter-poste-org" class="filter-select">
                    <option value="">Tous les postes</option>
                    <option value="enseignant">Enseignants</option>
                    <option value="doctorant">Doctorants</option>
                    <option value="etudiant">Étudiants</option>
                    <option value="invite">Invités</option>
                </select>
                
                <select id="filter-grade-org" class="filter-select">
                    <option value="">Tous les grades</option>
                    <option value="Professeur">Professeur</option>
                    <option value="Maître de conférences A">Maître de conférences A</option>
                    <option value="Maître de conférences B">Maître de conférences B</option>
                    <option value="Doctorant">Doctorant</option>
                </select>
            </div>

            <div id="membres-by-poste" class="membres-compact-grid">
                <?php 
                $directeurId = $this->directeur['id'] ?? null;
                
                if (!empty($this->membres)): 
                    foreach ($this->membres as $membre): 
                        if ($directeurId && $membre['id'] == $directeurId) {
                            continue;
                        }
                        $this->renderMembreCard($membre);
                    endforeach;
                else:
                ?>
                    <div class="empty-state">
                        <p>Aucun membre à afficher.</p>
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
        $posteNormalized = $membre['poste_normalized'] ?? '';
        $posteColors = [
            'enseignant' => '#3B82F6',
            'doctorant' => '#8B5CF6',
            'etudiant' => '#10B981',
            'invite' => '#F59E0B'
        ];
        $badgeColor = $posteColors[$posteNormalized] ?? '#6B7280';
        $initiales = strtoupper(substr($membre['username'] ?? 'U', 0, 2));
        ?>
        <div class="membre-card-compact" 
             data-poste="<?= e($posteNormalized) ?>"
             data-grade="<?= e($membre['grade'] ?? '') ?>">
            <div class="membre-avatar-compact">
                <?php if (!empty($membre['photo'])): ?>
                    <img src="<?= base_url('uploads/photos/' . $membre['photo']) ?>" 
                         alt="<?= e($membre['username']) ?>">
                <?php else: ?>
                    <div class="avatar-placeholder-compact">
                        <?= $initiales ?>
                    </div>
                <?php endif; ?>
                <span class="badge-compact" style="background: <?= $badgeColor ?>;">
                    <?= e(ucfirst($membre['poste'] ?? '')) ?>
                </span>
            </div>
            <div class="membre-info-compact">
                <h5>
                    <a href="<?= base_url('membres/' . $membre['id']) ?>">
                        <?= e($membre['username']) ?>
                    </a>
                </h5>
                <?php if (!empty($membre['grade'])): ?>
                    <div class="grade-compact"><?= e($membre['grade']) ?></div>
                <?php endif; ?>
                <?php if (!empty($membre['equipe_nom'])): ?>
                    <div class="equipe-compact"><?= e($membre['equipe_nom']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu des équipes
     */
    private function renderEquipes(): void
    {
        ?>
        <section class="detail-card equipes-section">
            <h2>Équipes de Recherche</h2>
            
            <div class="equipes-filters">
                <input type="text" 
                       id="search-equipe" 
                       placeholder="Rechercher une équipe..."
                       class="search-input">
                
                <select id="filter-domaine" class="filter-select">
                    <option value="">Tous les domaines</option>
                    <?php
                    $domaines = array_unique(array_filter(array_column($this->equipes, 'domaine')));
                    sort($domaines);
                    foreach ($domaines as $domaine):
                    ?>
                        <option value="<?= e($domaine) ?>"><?= e($domaine) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="equipes-container" class="equipes-list">
                <?php if (!empty($this->equipes)): ?>
                    <?php foreach ($this->equipes as $equipe): ?>
                        <?php $this->renderEquipeCard($equipe); ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <p>Aucune équipe n'est actuellement disponible.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div id="no-equipes-results" class="empty-message" style="display: none;">
                Aucune équipe ne correspond à vos critères de recherche.
                <button onclick="resetEquipesFilters()" class="btn-secondary mt-md">
                    Réinitialiser les filtres
                </button>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu d'une carte équipe
     */
 private function renderEquipeCard(array $equipe): void
    {
        // Récupérer les membres de l'équipe
        $membresEquipe = [];
        if ($this->equipeModel && isset($equipe['id'])) {
            $membresEquipe = $this->equipeModel->getMembres($equipe['id']);
        }
        
        // Trouver le chef d'équipe
        $chef = null;
        foreach ($membresEquipe as $m) {
            if (isset($m['chef_equipe']) && $m['chef_equipe'] == 1) {
                $chef = $m;
                break;
            }
        }
        ?>
        <div class="equipe-card" 
             data-nom="<?= e(strtolower($equipe['nom'])) ?>"
             data-domaine="<?= e($equipe['domaine'] ?? '') ?>">
            <div class="equipe-header">
                <div>
                    <h3><?= e($equipe['nom']) ?></h3>
                    <?php if (!empty($equipe['domaine'])): ?>
                        <span class="badge-domaine"><?= e($equipe['domaine']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="equipe-stats">
                    <span class="stat-badge"><?= count($membresEquipe) ?> membres</span>
                </div>
            </div>

            <?php if (!empty($equipe['description'])): ?>
                <p class="equipe-description"><?= nl2br(e($equipe['description'])) ?></p>
            <?php endif; ?>

            <?php if ($chef): ?>
                <?php $this->renderChefEquipe($chef); ?>
            <?php endif; ?>

            <?php if (!empty($membresEquipe)): ?>
                <?php $this->renderMembresEquipe($membresEquipe, $chef); ?>
            <?php endif; ?>

            <div class="equipe-actions">
                <a href="<?= base_url('publications?equipe=' . $equipe['id']) ?>" 
                   class="btn-secondary">
                    Publications de l'équipe
                </a>
            </div>
        </div>
        <?php
    }


    /**
     * Rendu du chef d'équipe
     */
    private function renderChefEquipe(array $chef): void
    {
        ?>
        <div class="chef-section">
            <h4>Chef d'équipe</h4>
            <div class="membre-card-inline">
                <div class="membre-avatar-small">
                    <?php if (!empty($chef['photo'])): ?>
                        <img src="<?= base_url('uploads/photos/' . $chef['photo']) ?>" 
                             alt="<?= e($chef['username']) ?>">
                    <?php else: ?>
                        <div class="avatar-placeholder-small">
                            <?= strtoupper(substr($chef['username'] ?? 'C', 0, 2)) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="membre-info-inline">
                    <strong>
                        <a href="<?= base_url('membres/' . $chef['id']) ?>">
                            <?= e($chef['username']) ?>
                        </a>
                    </strong>
                    <?php if (!empty($chef['grade'])): ?>
                        <span class="grade-inline"><?= e($chef['grade']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu des membres de l'équipe
     */
    private function renderMembresEquipe(array $membres, ?array $chef): void
    {
        ?>
        <div class="membres-equipe-section">
            <h4>Membres de l'équipe (<?= count($membres) ?>)</h4>
            <div class="membres-grid-inline">
                <?php foreach ($membres as $membre): ?>
                    <?php if ($membre['id'] == ($chef['id'] ?? null)) continue; ?>
                    <div class="membre-card-inline">
                        <div class="membre-avatar-small">
                            <?php if (!empty($membre['photo'])): ?>
                                <img src="<?= base_url('uploads/photos/' . $membre['photo']) ?>" 
                                     alt="<?= e($membre['username']) ?>">
                            <?php else: ?>
                                <div class="avatar-placeholder-small">
                                    <?= strtoupper(substr($membre['username'] ?? 'M', 0, 2)) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="membre-info-inline">
                            <a href="<?= base_url('membres/' . $membre['id']) ?>">
                                <?= e($membre['username']) ?>
                            </a>
                            <?php if (!empty($membre['grade'])): ?>
                                <span class="grade-inline"><?= e($membre['grade']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu du pied de page
     */
    private function renderFooter(): void
    {
        FooterComponent::render(['role' => 'visiteur']);
        $this->renderStyles();
    }

    /**
     * Styles de la page
     */
    private function renderStyles(): void
    {
        ?>
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

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .detail-card {
            background: white;
            padding: 40px;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: 32px;
        }

        .detail-card h2 {
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 24px 0;
            color: var(--gray-900);
            border-bottom: 3px solid var(--primary);
            padding-bottom: 12px;
        }

        .detail-card h3 {
            font-size: 22px;
            font-weight: 600;
            margin: 32px 0 20px 0;
            color: var(--gray-800);
        }

        .detail-card h4 {
            font-size: 18px;
            font-weight: 600;
            margin: 20px 0 12px 0;
            color: var(--gray-700);
        }

        .presentation-content {
            line-height: 1.8;
            color: var(--gray-700);
        }

        .presentation-content p {
            margin-bottom: 20px;
            font-size: 16px;
        }

        .themes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 24px;
        }

        .theme-card {
            padding: 24px;
            background: var(--gray-50);
            border-radius: 12px;
            border: 2px solid var(--border-color);
            transition: var(--transition);
        }

        .theme-card:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow-sm);
        }

        .theme-card h4 {
            margin: 0 0 12px 0;
            color: var(--primary);
            font-size: 16px;
        }

        .theme-card p {
            margin: 0;
            font-size: 14px;
            line-height: 1.6;
            color: var(--gray-600);
        }

        .directeur-section {
            margin-bottom: 40px;
        }

        .membre-card-horizontal {
            display: flex;
            gap: 24px;
            padding: 24px;
            background: var(--gray-50);
            border-radius: 12px;
            border: 2px solid var(--primary);
        }

        .directeur-card {
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
        }

        .membre-avatar-large {
            width: 120px;
            height: 120px;
            flex-shrink: 0;
        }

        .membre-avatar-large img,
        .avatar-placeholder-large {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
        }

        .avatar-placeholder-large {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: 700;
        }

        .membre-info-horizontal {
            flex: 1;
        }

        .membre-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }

        .membre-header h4 {
            margin: 0;
            font-size: 24px;
        }

        .membre-header h4 a {
            color: var(--gray-900);
            text-decoration: none;
            transition: var(--transition);
        }

        .membre-header h4 a:hover {
            color: var(--primary);
        }

        .badge-directeur {
            padding: 6px 16px;
            background: var(--primary);
            color: white;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .membre-grade {
            color: var(--primary);
            font-weight: 500;
            margin-bottom: 12px;
        }

        .membre-bio {
            color: var(--gray-600);
            line-height: 1.6;
            margin-bottom: 16px;
        }

        .membre-actions {
            display: flex;
            gap: 12px;
        }

        .filters-inline {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }

        .filter-select {
            padding: 10px 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            background: white;
            cursor: pointer;
            transition: var(--transition);
        }

        .filter-select:hover,
        .filter-select:focus {
            border-color: var(--primary);
            outline: none;
        }

        .membres-compact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
        }

        .membre-card-compact {
            padding: 16px;
            background: var(--gray-50);
            border-radius: 10px;
            border: 1px solid var(--border-color);
            text-align: center;
            transition: var(--transition);
        }

        .membre-card-compact:hover {
            box-shadow: var(--shadow-sm);
            border-color: var(--primary);
        }

        .membre-avatar-compact {
            position: relative;
            width: 80px;
            height: 80px;
            margin: 0 auto 12px;
        }

        .membre-avatar-compact img,
        .avatar-placeholder-compact {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
        }

        .avatar-placeholder-compact {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
        }

        .badge-compact {
            position: absolute;
            bottom: -4px;
            left: 50%;
            transform: translateX(-50%);
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .membre-info-compact h5 {
            margin: 0 0 6px 0;
            font-size: 14px;
            font-weight: 600;
        }

        .membre-info-compact h5 a {
            color: var(--gray-900);
            text-decoration: none;
            transition: var(--transition);
        }

        .membre-info-compact h5 a:hover {
            color: var(--primary);
        }

        .grade-compact,
        .equipe-compact {
            font-size: 12px;
            color: var(--gray-600);
            margin-top: 4px;
        }

        .equipes-filters {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }

        .search-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .equipes-list {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .equipe-card {
            padding: 32px;
            background: var(--gray-50);
            border-radius: 12px;
            border: 2px solid var(--border-color);
            transition: var(--transition);
        }

        .equipe-card:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow-md);
        }

        .equipe-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .equipe-header h3 {
            margin: 0 0 8px 0;
            font-size: 24px;
            color: var(--gray-900);
        }

        .badge-domaine {
            display: inline-block;
            padding: 6px 14px;
            background: var(--primary);
            color: white;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
        }

        .stat-badge {
            padding: 6px 14px;
            background: var(--gray-200);
            color: var(--gray-700);
            border-radius: 16px;
            font-size: 13px;
            font-weight: 600;
        }

        .equipe-description {
            color: var(--gray-600);
            line-height: 1.6;
            margin-bottom: 24px;
            font-size: 15px;
        }

        .chef-section {
            margin-bottom: 24px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            border: 1px solid var(--border-color);
        }

        .membre-card-inline {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .membre-avatar-small {
            width: 50px;
            height: 50px;
            flex-shrink: 0;
        }

        .membre-avatar-small img,
        .avatar-placeholder-small {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--border-color);
        }

        .avatar-placeholder-small {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 700;
        }

        .membre-info-inline {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .membre-info-inline strong a {
            color: var(--gray-900);
            text-decoration: none;
            font-size: 15px;
            transition: var(--transition);
        }

        .membre-info-inline strong a:hover {
            color: var(--primary);
        }

        .grade-inline {
            font-size: 12px;
            color: var(--gray-600);
        }       
        .membres-grid-inline {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
        }
        .equipe-actions {
            margin-top: 24px;
        }
        </style>
        <?php }
}