<?php
/**
 * MembreDetailView.php - Vue détail d'un membre
 * À placer dans : /TDW_project/app/views/public/MembreDetailView.php
 */

require_once __DIR__ . '/../../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/NavigationComponent.php';
require_once __DIR__ . '/../../../lib/components/FooterComponent.php';

class MembreDetailView
{
    private array $membre;
    private array $projets;
    private array $publications;

    public function __construct(array $membre, array $projets = [], array $publications = [])
    {
        $this->membre = $membre;
        $this->projets = $projets;
        $this->publications = $publications;
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
        $this->renderProfileHeader();
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
            'title' => $this->membre['username'] . ' - Laboratoire TDW',
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
        NavigationComponent::renderHorizontalMenu('membres');
    }

    /**
     * Rendu du fil d'Ariane
     */
    private function renderBreadcrumbs(): void
    {
        NavigationComponent::renderBreadcrumbs([
            ['label' => 'Accueil', 'url' => base_url()],
            ['label' => 'Membres', 'url' => base_url('membres')],
            ['label' => $this->membre['username']]
        ]);
    }

    /**
     * Rendu de l'en-tête du profil
     */
    private function renderProfileHeader(): void
    {
        $posteNormalized = $this->membre['poste_normalized'] ?? '';
        
        // Couleurs par poste
        $posteColors = [
            'enseignant' => '#3B82F6',
            'doctorant' => '#8B5CF6',
            'etudiant' => '#10B981',
            'invite' => '#F59E0B'
        ];
        $badgeColor = $posteColors[$posteNormalized] ?? '#6B7280';
        
        // Initiales pour l'avatar
        $initiales = strtoupper(substr($this->membre['username'] ?? 'U', 0, 2));
        ?>
        
        <div class="membre-profile-header">
            <div class="profile-avatar-large">
                <?php if (!empty($this->membre['photo'])): ?>
                    <img src="<?= base_url('uploads/photos/' . $this->membre['photo']) ?>" 
                         alt="<?= htmlspecialchars($this->membre['username']) ?>">
                <?php else: ?>
                    <div class="avatar-placeholder-large">
                        <?= $initiales ?>
                    </div>
                <?php endif; ?>
                
                <span class="profile-badge" style="background: <?= $badgeColor ?>;">
                    <?= htmlspecialchars(ucfirst($this->membre['poste'])) ?>
                </span>
            </div>
            
            <div class="profile-info">
                <h1><?= htmlspecialchars($this->membre['username']) ?></h1>
                
                <?php if (!empty($this->membre['grade'])): ?>
                <div class="profile-grade"><?= htmlspecialchars($this->membre['grade']) ?></div>
                <?php endif; ?>
                
                <?php if (!empty($this->membre['equipe_nom'])): ?>
                <div class="profile-equipe">
                    Membre de l'équipe <strong><?= htmlspecialchars($this->membre['equipe_nom']) ?></strong>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($this->membre['email'])): ?>
                <div class="profile-contact">
                    <a href="mailto:<?= htmlspecialchars($this->membre['email']) ?>" class="contact-email">
                        <?= htmlspecialchars($this->membre['email']) ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="profile-stats">
                <div class="stat-box">
                    <div class="stat-number"><?= count($this->projets) ?></div>
                    <div class="stat-label">Projets</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?= count($this->publications) ?></div>
                    <div class="stat-label">Publications</div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu du contenu principal
     */
    private function renderMainContent(): void
    {
        ?>
        <div class="main-content">
            <?php 
            $this->renderBiographie();
            $this->renderProjets();
            $this->renderPublications();
            $this->renderEmptyState();
            ?>
        </div>
        <?php
    }

    /**
     * Rendu de la biographie
     */
    private function renderBiographie(): void
    {
        if (empty($this->membre['biographie'])) {
            return;
        }
        ?>
        
        <section class="detail-card">
            <h2>Biographie</h2>
            <div class="bio-content">
                <?= nl2br(htmlspecialchars($this->membre['biographie'])) ?>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu des projets
     */
    private function renderProjets(): void
    {
        if (empty($this->projets)) {
            return;
        }
        ?>
        
        <section class="detail-card">
            <h2>Projets (<?= count($this->projets) ?>)</h2>
            <div class="items-list">
                <?php foreach ($this->projets as $projet): ?>
                    <?php $this->renderProjetCard($projet); ?>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu d'une carte projet
     */
    private function renderProjetCard(array $projet): void
    {
        ?>
        <div class="item-card">
            <div class="item-header">
                <h3>
                    <a href="<?= base_url('projets/' . $projet['id']) ?>">
                        <?= htmlspecialchars($projet['titre']) ?>
                    </a>
                </h3>
                <span class="badge" style="background: var(--primary);">
                    <?= htmlspecialchars($projet['thematique']) ?>
                </span>
            </div>
            
            <?php if (!empty($projet['description'])): ?>
            <p class="item-description">
                <?= truncate($projet['description'], 150) ?>
            </p>
            <?php endif; ?>
            
            <div class="item-meta">
                <span class="status-badge status-<?= htmlspecialchars($projet['status']) ?>">
                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $projet['status']))) ?>
                </span>
                <?php if (!empty($projet['role_projet'])): ?>
                <span class="role-badge">
                    <?= htmlspecialchars($projet['role_projet']) ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu des publications
     */
    private function renderPublications(): void
    {
        if (empty($this->publications)) {
            return;
        }
        ?>
        
        <section class="detail-card">
            <h2>Publications (<?= count($this->publications) ?>)</h2>
            <div class="items-list">
                <?php foreach ($this->publications as $pub): ?>
                    <?php $this->renderPublicationCard($pub); ?>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu d'une carte publication
     */
    private function renderPublicationCard(array $pub): void
    {
        $typeColors = [
            'Article' => '#3B82F6',
            'Conference' => '#8B5CF6',
            'These' => '#EC4899',
            'Rapport' => '#F59E0B',
            'Livre' => '#10B981',
            'Chapitre' => '#6366F1'
        ];
        $typeColor = $typeColors[$pub['type_publication']] ?? '#6B7280';
        ?>
        
        <div class="item-card publication-item">
            <div class="item-header">
                <span class="pub-type-badge" style="background: <?= $typeColor ?>;">
                    <?= htmlspecialchars($pub['type_publication']) ?>
                </span>
                <span class="pub-year">
                    <?= date('Y', strtotime($pub['date_publication'])) ?>
                </span>
            </div>
            
            <h3>
                <a href="<?= base_url('publications/' . $pub['id']) ?>">
                    <?= htmlspecialchars($pub['titre']) ?>
                </a>
            </h3>
            
            <?php if (!empty($pub['resume'])): ?>
            <p class="item-description">
                <?= truncate($pub['resume'], 120) ?>
            </p>
            <?php endif; ?>
            
            <div class="item-meta">
                <?php if (!empty($pub['domaine'])): ?>
                <span class="meta-badge">
                    <?= htmlspecialchars($pub['domaine']) ?>
                </span>
                <?php endif; ?>
                
                <?php if (!empty($pub['doi'])): ?>
                <a href="https://doi.org/<?= htmlspecialchars($pub['doi']) ?>" 
                   target="_blank" 
                   class="doi-link">
                    DOI
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu de l'état vide
     */
    private function renderEmptyState(): void
    {
        if (!empty($this->projets) || !empty($this->publications)) {
            return;
        }
        ?>
        
        <section class="detail-card">
            <div class="empty-state">
                <p>Aucune activité publique disponible pour ce membre.</p>
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
        ?>
        <section class="detail-card">
            <h2>Informations</h2>
            <div class="info-list">
                <div class="info-item">
                    <strong>Poste</strong>
                    <span><?= htmlspecialchars(ucfirst($this->membre['poste'])) ?></span>
                </div>
                
                <?php if (!empty($this->membre['grade'])): ?>
                <div class="info-item">
                    <strong>Grade</strong>
                    <span><?= htmlspecialchars($this->membre['grade']) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($this->membre['equipe_nom'])): ?>
                <div class="info-item">
                    <strong>Équipe</strong>
                    <span><?= htmlspecialchars($this->membre['equipe_nom']) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($this->membre['date_adhesion'])): ?>
                <div class="info-item">
                    <strong>Membre depuis</strong>
                    <span><?= format_date($this->membre['date_adhesion'], 'F Y') ?></span>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu des actions
     */
    private function renderActions(): void
    {
        ?>
        <?php if (!empty($this->membre['email'])): ?>
        <a href="mailto:<?= htmlspecialchars($this->membre['email']) ?>" 
           class="btn-primary btn-block">
            Envoyer un email
        </a>
        <?php endif; ?>

        <a href="<?= base_url('membres') ?>" class="btn-secondary btn-block">
            Retour aux membres
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

        .membre-profile-header {
            background: white;
            padding: 40px;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 30px;
            align-items: center;
        }

        .profile-avatar-large {
            position: relative;
            width: 160px;
            height: 160px;
        }

        .profile-avatar-large img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid var(--border-color);
        }

        .avatar-placeholder-large {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: 700;
            border: 5px solid var(--border-color);
        }

        .profile-badge {
            position: absolute;
            bottom: 5px;
            right: 5px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 4px solid white;
        }

        .profile-info h1 {
            font-size: 32px;
            font-weight: 700;
            margin: 0 0 8px 0;
            color: var(--gray-900);
        }

        .profile-grade {
            font-size: 18px;
            font-weight: 500;
            color: var(--primary);
            margin-bottom: 8px;
        }

        .profile-equipe {
            font-size: 15px;
            color: var(--gray-600);
            margin-bottom: 12px;
        }

        .profile-contact {
            margin-top: 12px;
        }

        .contact-email {
            color: var(--primary);
            text-decoration: none;
            font-size: 15px;
            transition: var(--transition);
        }

        .contact-email:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .profile-stats {
            display: flex;
            gap: 20px;
        }

        .stat-box {
            text-align: center;
            padding: 20px;
            background: var(--gray-50);
            border-radius: 12px;
            min-width: 100px;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 13px;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.5px;
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

        .bio-content {
            line-height: 1.8;
            color: var(--gray-700);
            font-size: 16px;
        }

        .items-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .item-card {
            padding: 20px;
            background: var(--gray-50);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .item-card:hover {
            background: white;
            box-shadow: var(--shadow-sm);
            border-color: var(--primary);
            transform: translateX(4px);
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
        }

        .item-card h3 {
            font-size: 16px;
            font-weight: 600;
            margin: 0;
            flex: 1;
        }

        .item-card h3 a {
            color: var(--gray-900);
            text-decoration: none;
            transition: var(--transition);
        }

        .item-card h3 a:hover {
            color: var(--primary);
        }

        .item-description {
            color: var(--gray-600);
            font-size: 14px;
            line-height: 1.6;
            margin: 12px 0;
        }

        .item-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-top: 12px;
        }

        .badge, .meta-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }

        .meta-badge {
            background: var(--gray-600);
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-en_cours {
            background: #3B82F6;
            color: white;
        }

        .status-termine {
            background: #10B981;
            color: white;
        }

        .status-soumis {
            background: #F59E0B;
            color: white;
        }

        .role-badge {
            padding: 4px 12px;
            background: var(--primary);
            color: white;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .pub-type-badge {
            padding: 6px 14px;
            border-radius: 16px;
            font-size: 11px;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
        }

        .pub-year {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-600);
        }

        .doi-link {
            color: var(--primary);
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            transition: var(--transition);
        }

        .doi-link:hover {
            text-decoration: underline;
            color: var(--primary-dark);
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

        .btn-block {
            display: block;
            width: 100%;
            text-align: center;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-primary.btn-block {
            background: var(--primary);
            color: white;
        }

        .btn-primary.btn-block:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-secondary.btn-block {
            background: var(--gray-200);
            color: var(--gray-700);
        }

        .btn-secondary.btn-block:hover {
            background: var(--gray-300);
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray-600);
        }

        @media (max-width: 1024px) {
            .detail-layout {
                grid-template-columns: 1fr;
            }
            
            .sidebar-content {
                order: -1;
            }
            
            .membre-profile-header {
                grid-template-columns: 1fr;
                text-align: center;
                justify-items: center;
            }
            
            .profile-stats {
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .membre-profile-header {
                padding: 24px 20px;
            }
            
            .profile-info h1 {
                font-size: 24px;
            }
            
            .detail-card {
                padding: 20px;
            }
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