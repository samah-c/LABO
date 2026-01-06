<?php
/**
 * Vue du Dashboard Membre
 */
require_once __DIR__ . '/../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../lib/components/NavigationComponent.php';
require_once __DIR__ . '/../../lib/components/PageHeaderComponent.php';
require_once __DIR__ . '/../../lib/components/TableComponent.php';
require_once __DIR__ . '/../../lib/components/FooterComponent.php';

class MembreDashboardView
{
    private array $stats;
    private array $membre;
    private array $mesProjets;
    private array $mesPublications;
    private array $mesReservations;
    private array $evenements;
    private array $notifications;
    private string $username;

    public function __construct(
        array $stats = [],
        array $membre = [],
        array $mesProjets = [],
        array $mesPublications = [],
        array $mesReservations = [],
        array $evenements = [],
        string $username = ''
    ) {
        $this->stats = $stats;
        $this->membre = $membre;
        $this->mesProjets = $mesProjets;
        $this->mesPublications = $mesPublications;
        $this->mesReservations = $mesReservations;
        $this->evenements = $evenements;
        $this->username = $username;
    }

    /**
     * Rendu complet de la vue
     */
    public function render(): void
    {
        $this->renderHeader();
        $this->renderNavigation();
        echo '<div class="container">';
        $this->renderBreadcrumbs();
        $this->renderWelcomeBanner();
        $this->renderStats();
        $this->renderQuickActions();
        $this->renderMainContent();
        echo '</div>';
        $this->renderStyles();
        $this->renderFooter();
    }

    /**
     * Rendu de l'en-tête
     */
    private function renderHeader(): void
    {
        HeaderComponent::render([
            'title' => 'Mon Espace Membre',
            'username' => $this->username,
            'role' => 'membre',
            'showLogout' => true,
            'additionalJs' => [
                base_url('assets/js/membre-dashboard.js')
            ]
        ]);
    }

    /**
     * Rendu de la navigation
     */
    private function renderNavigation(): void
    {
        NavigationComponent::renderSidebar('membre');
    }

    /**
     * Rendu du fil d'Ariane
     */
    private function renderBreadcrumbs(): void
    {
        NavigationComponent::renderBreadcrumbs([
            ['label' => 'Accueil', 'url' => base_url('membre/dashboard')],
            ['label' => 'Tableau de bord']
        ]);
    }

    /**
     * Rendu de la bannière de bienvenue
     */
    private function renderWelcomeBanner(): void
    {
        ?>
        <div class="welcome-banner">
            <div class="welcome-content">
                <div class="welcome-text">
                    <h2>Bienvenue, <?= htmlspecialchars($this->username) ?></h2>
                    <p>Gérez vos activités de recherche et vos contributions</p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu des statistiques
     */
    private function renderStats(): void
    {
        $statsData = [
            [
                'label' => 'Mes Projets',
                'value' => $this->stats['mes_projets'] ?? 0
            ],
            [
                'label' => 'Mes Publications',
                'value' => $this->stats['mes_publications'] ?? 0
            ],
            [
                'label' => 'Réservations actives',
                'value' => $this->stats['reservations_actives'] ?? 0
            ],
            [
                'label' => 'Événements à venir',
                'value' => $this->stats['evenements_a_venir'] ?? 0
            ]
        ];

        echo '<div class="stats-grid">';
        foreach ($statsData as $stat) {
            $this->renderStatCard($stat);
        }
        echo '</div>';
    }

    /**
     * Rendu d'une carte de statistique
     */
    private function renderStatCard(array $stat): void
    {
        $links = [
            'Mes Projets' => base_url('membre/projets'),
            'Mes Publications' => base_url('membre/publications'),
            'Réservations actives' => base_url('membre/reservations'),
            'Événements à venir' => base_url('membre/evenements')
        ];
        ?>
        <div class="stat-card">
            <h3><?= htmlspecialchars($stat['label']) ?></h3>
            <div class="number"><?= htmlspecialchars($stat['value']) ?></div>
            <a href="<?= $links[$stat['label']] ?? '#' ?>" class="stat-link">Voir tous</a>
        </div>
        <?php
    }

    /**
     * Rendu des actions rapides
     */
    private function renderQuickActions(): void
    {
        $actions = [
            [
                'url' => base_url('membre/publications/nouveau'),
                'titre' => 'Nouvelle publication',
                'description' => 'Soumettre une nouvelle publication'
            ],
            [
                'url' => base_url('membre/reservations'),
                'titre' => 'Réserver équipement',
                'description' => 'Réserver du matériel de recherche'
            ],
            [
                'url' => base_url('membre/profil'),
                'titre' => 'Modifier profil',
                'description' => 'Mettre à jour mes informations'
            ],
            [
                'url' => base_url('membre/projets'),
                'titre' => 'Mes projets',
                'description' => 'Gérer mes projets de recherche'
            ]
        ];
        ?>
        <div class="quick-actions-section">
            <h2 class="section-title">Actions rapides</h2>
            <div class="actions-grid">
                <?php foreach ($actions as $action): ?>
                    <a href="<?= htmlspecialchars($action['url']) ?>" class="action-card">
                        <div class="action-content">
                            <h3><?= htmlspecialchars($action['titre']) ?></h3>
                            <p><?= htmlspecialchars($action['description']) ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu du contenu principal (grid 3 colonnes)
     */
    private function renderMainContent(): void
    {
        ?>
        <div class="dashboard-grid">
            <!-- Colonne gauche - Événements à venir -->
            <div class="dashboard-col-left">
                <?php $this->renderEvenementsSection(); ?>
            </div>

            <!-- Colonne centrale - Projets et Publications -->
            <div class="dashboard-col-main">
                <?php $this->renderProjetsSection(); ?>
                <?php $this->renderPublicationsSection(); ?>
            </div>

            <!-- Colonne droite - Profil et Réservations -->
            <div class="dashboard-col-sidebar">
                <?php $this->renderProfileSection(); ?>
                <?php $this->renderReservationsSection(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Section Événements à venir
     */
    private function renderEvenementsSection(): void
    {
        ?>
        <section class="dashboard-section">
            <div class="section-header">
                <h2>Événements à venir</h2>
                <a href="<?= base_url('membre/evenements') ?>" class="see-all">Tout voir</a>
            </div>

            <?php if (empty($this->evenements)): ?>
                <div class="empty-state-small">
                    <p>Aucun événement prévu</p>
                </div>
            <?php else: ?>
                <div class="events-list">
                    <?php foreach (array_slice($this->evenements, 0, 4) as $event): ?>
                        <div class="event-item">
                            <div class="event-date">
                                <span class="day"><?= date('d', strtotime($event['date_evenement'])) ?></span>
                                <span class="month"><?= strtoupper(date('M', strtotime($event['date_evenement']))) ?></span>
                            </div>
                            <div class="event-info">
                                <h4><?= htmlspecialchars($event['titre']) ?></h4>
                                <small class="event-type"><?= htmlspecialchars($event['type_evenement']) ?></small>
                                <small class="event-lieu"><?= htmlspecialchars($event['lieu']) ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php
    }

    /**
     * Section Réservations
     */
    private function renderReservationsSection(): void
    {
        ?>
        <section class="dashboard-section">
            <div class="section-header">
                <h2>Équipements réservés</h2>
                <a href="<?= base_url('membre/reservations') ?>" class="see-all">Tout voir</a>
            </div>

            <?php if (empty($this->mesReservations)): ?>
                <div class="empty-state-small">
                    <p>Aucune réservation active</p>
                    <a href="<?= base_url('membre/reservations') ?>" class="btn-secondary btn-block">
                        Réserver du matériel
                    </a>
                </div>
            <?php else: ?>
                <div class="reservations-list">
                    <?php foreach (array_slice($this->mesReservations, 0, 3) as $resa): ?>
                        <div class="reservation-item">
                            <div class="reservation-header">
                                <strong><?= htmlspecialchars($resa['equipement_nom']) ?></strong>
                                <?= $this->getStatusBadge($resa['statut']) ?>
                            </div>
                            <div class="reservation-dates">
                                <small>
                                    Du <?= date('d/m/Y', strtotime($resa['date_debut'])) ?>
                                    au <?= date('d/m/Y', strtotime($resa['date_fin'])) ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php
    }

    /**
     * Section Projets
     */
    private function renderProjetsSection(): void
    {
        ?>
        <section class="dashboard-section">
            <div class="section-header">
                <h2>Mes projets de recherche</h2>
                <a href="<?= base_url('membre/projets') ?>" class="see-all">Voir tous</a>
            </div>

            <?php if (empty($this->mesProjets)): ?>
                <div class="empty-state">
                    <p>Aucun projet pour le moment</p>
                    <small>Vous n'êtes associé à aucun projet actuellement</small>
                </div>
            <?php else: ?>
                <div class="items-list">
                    <?php foreach ($this->mesProjets as $projet): ?>
                        <div class="item-card">
                            <div class="item-header">
                                <div class="item-title-group">
                                    <h3>
                                        <a href="<?= base_url('membre/projets/' . $projet['id']) ?>">
                                            <?= htmlspecialchars($projet['titre']) ?>
                                        </a>
                                    </h3>
                                    <span class="item-role"><?= htmlspecialchars($projet['mon_role'] ?? 'Membre') ?></span>
                                </div>
                                <?= $this->getStatusBadge($projet['status']) ?>
                            </div>
                            <p class="item-description"><?= htmlspecialchars(substr($projet['description'], 0, 150)) ?>...</p>
                            <div class="item-meta">
                                <span>Début: <?= date('d/m/Y', strtotime($projet['date_debut'])) ?></span>
                                <span><?= $projet['nb_membres'] ?? 0 ?> membres</span>
                                <span><?= htmlspecialchars($projet['domaine_recherche'] ?? 'N/A') ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php
    }

    /**
     * Section Publications
     */
    private function renderPublicationsSection(): void
    {
        ?>
        <section class="dashboard-section">
            <div class="section-header">
                <h2>Mes publications</h2>
                <a href="<?= base_url('membre/publications') ?>" class="see-all">Voir toutes</a>
            </div>

            <?php if (empty($this->mesPublications)): ?>
                <div class="empty-state">
                    <p>Aucune publication pour le moment</p>
                    <a href="<?= base_url('membre/publications/nouveau') ?>" class="btn-primary">
                        Soumettre une publication
                    </a>
                </div>
            <?php else: ?>
                <div class="items-list">
                    <?php foreach ($this->mesPublications as $pub): ?>
                        <div class="item-card">
                            <div class="item-header">
                                <h3>
                                    <a href="<?= base_url('membre/publications/' . $pub['id']) ?>">
                                        <?= htmlspecialchars($pub['titre']) ?>
                                    </a>
                                </h3>
                                <?= $this->getStatusBadge($pub['statut_validation']) ?>
                            </div>
                            <div class="item-meta">
                                <?= $this->getPublicationTypeBadge($pub['type_publication']) ?>
                                <span><?= date('d/m/Y', strtotime($pub['date_publication'])) ?></span>
                                <?php if (!empty($pub['conference'])): ?>
                                    <span><?= htmlspecialchars($pub['conference']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php
    }

    /**
     * Section Profil
     */
    private function renderProfileSection(): void
    {
        ?>
        <section class="dashboard-section profile-section">
            <h2>Mon profil</h2>
            <div class="profile-info">
                <?php if (!empty($this->membre['photo'])): ?>
                    <img src="<?= base_url('uploads/photos/' . $this->membre['photo']) ?>" 
                         alt="Photo de profil"
                         class="profile-photo">
                <?php else: ?>
                    <div class="profile-photo-placeholder">
                        <?= strtoupper(substr($this->username, 0, 2)) ?>
                    </div>
                <?php endif; ?>

                <div class="profile-details">
                    <h3><?= htmlspecialchars($this->membre['nom'] . ' ' . $this->membre['prenom']) ?></h3>
                    <p class="profile-grade"><?= htmlspecialchars($this->membre['grade'] ?? 'Membre') ?></p>
                    <?php if (!empty($this->membre['domaine_recherche'])): ?>
                        <p class="profile-domain"><?= htmlspecialchars($this->membre['domaine_recherche']) ?></p>
                    <?php endif; ?>
                </div>

                <a href="<?= base_url('membre/profil') ?>" class="btn-secondary btn-block">
                    Modifier mon profil
                </a>
            </div>
        </section>
        <?php
    }
    /**
     * Rendu du pied de page
     */
    private function renderFooter(): void
    {
        FooterComponent::render(['role' => 'membre']);
    }

    /**
     * Helper: Badge de statut
     */
    private function getStatusBadge(string $status): string
    {
        $badges = [
            'en_cours' => '<span class="badge badge-info">En cours</span>',
            'termine' => '<span class="badge badge-success">Terminé</span>',
            'en_attente' => '<span class="badge badge-warning">En attente</span>',
            'valide' => '<span class="badge badge-success">Validé</span>',
            'refuse' => '<span class="badge badge-danger">Refusé</span>',
            'libre' => '<span class="badge badge-success">Libre</span>',
            'reserve' => '<span class="badge badge-info">Réservé</span>'
        ];
        return $badges[$status] ?? '<span class="badge badge-secondary">' . htmlspecialchars($status) . '</span>';
    }

    /**
     * Helper: Badge de type de publication
     */
    private function getPublicationTypeBadge(string $type): string
    {
        $badges = [
            'article' => '<span class="badge badge-primary">Article</span>',
            'conference' => '<span class="badge badge-info">Conférence</span>',
            'these' => '<span class="badge badge-warning">Thèse</span>',
            'rapport' => '<span class="badge badge-secondary">Rapport</span>'
        ];
        return $badges[$type] ?? '<span class="badge badge-secondary">' . htmlspecialchars($type) . '</span>';
    }

    /**
     * Helper: Time ago
     */
    private function timeAgo(string $date): string
    {
        $timestamp = strtotime($date);
        $diff = time() - $timestamp;

        if ($diff < 60) return 'À l\'instant';
        if ($diff < 3600) return floor($diff / 60) . ' min';
        if ($diff < 86400) return floor($diff / 3600) . ' h';
        if ($diff < 604800) return floor($diff / 86400) . ' j';
        return date('d/m/Y', $timestamp);
    }

    /**
     * Rendu des styles CSS
     */
    private function renderStyles(): void
    {
        ?>
        <style>
        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 28px 32px;
            border-radius: var(--border-radius-xl);
            margin-bottom: 28px;
            color: white;
            box-shadow: var(--shadow-md);
        }

        .welcome-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
        }

        .welcome-text h2 {
            font-size: 26px;
            font-weight: 700;
            margin: 0 0 6px 0;
        }

        .welcome-text p {
            font-size: 14px;
            margin: 0;
            opacity: 0.95;
        }

        .welcome-actions .btn-secondary {
            background: white;
            color: var(--primary);
            border: none;
        }

        /* Stats Grid - 4 colonnes compactes */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--bg-card);
            padding: 20px;
            border-radius: var(--border-radius-lg);
            border: 1px solid var(--border-color);
            text-align: center;
            transition: var(--transition);
        }

        .stat-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
            border-color: var(--primary);
        }

        .stat-card h3 {
            color: var(--gray-600);
            font-size: 12px;
            font-weight: 600;
            margin: 0 0 12px 0;
            text-transform: uppercase;
        }

        .stat-card .number {
            font-size: 36px;
            font-weight: 700;
            color: var(--primary);
            line-height: 1;
            margin-bottom: 8px;
        }

        .stat-link {
            color: var(--primary);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
        }

        /* Quick Actions - 4 colonnes */
        .quick-actions-section {
            margin-bottom: 28px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 16px 0;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
        }

        .action-card {
            background: var(--bg-card);
            padding: 18px;
            border-radius: var(--border-radius-lg);
            border: 2px solid var(--border-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .action-card:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .action-content h3 {
            font-size: 15px;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0 0 4px 0;
        }

        .action-content p {
            font-size: 12px;
            color: var(--gray-600);
            margin: 0;
        }

        /* Dashboard Grid - 3 colonnes: 1fr 2fr 1fr */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 2fr 1fr;
            gap: 20px;
        }

        .dashboard-section {
            background: var(--bg-card);
            padding: 20px;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm);
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .section-header h2 {
            font-size: 16px;
            font-weight: 600;
            margin: 0;
        }

        .see-all {
            color: var(--primary);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
        }

        /* Items List */
        .items-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .item-card {
            padding: 14px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-sm);
            background: var(--gray-50);
            transition: var(--transition);
        }

        .item-card:hover {
            box-shadow: var(--shadow-sm);
            border-color: var(--primary);
            background: white;
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            gap: 10px;
        }

        .item-title-group h3 {
            font-size: 14px;
            font-weight: 600;
            margin: 0 0 4px 0;
        }

        .item-title-group h3 a {
            color: var(--gray-900);
            text-decoration: none;
        }

        .item-title-group h3 a:hover {
            color: var(--primary);
        }

        .item-role {
            font-size: 11px;
            color: var(--gray-500);
            font-weight: 500;
        }

        .item-description {
            color: var(--gray-600);
            font-size: 13px;
            margin: 8px 0;
            line-height: 1.5;
        }

        .item-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 12px;
            color: var(--gray-500);
        }

        /* Profile Section */
        .profile-section {
            text-align: center;
        }

        .profile-photo,
        .profile-photo-placeholder {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            margin: 0 auto 12px;
        }

        .profile-photo {
            object-fit: cover;
            border: 3px solid var(--primary);
        }

        .profile-photo-placeholder {
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 700;
        }

        .profile-details h3 {
            font-size: 15px;
            font-weight: 600;
            margin: 0 0 4px 0;
        }

        .profile-grade {
            font-size: 12px;
            color: var(--primary);
            font-weight: 600;
            margin: 0 0 4px 0;
        }

        .profile-domain {
            font-size: 12px;
            color: var(--gray-600);
            margin: 0 0 16px 0;
        }

        /* Events List */
        .events-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .event-item {
            display: flex;
            gap: 12px;
            padding: 12px;
            background: var(--gray-50);
            border-radius: var(--border-radius-sm);
            border: 1px solid var(--border-color);
        }

        .event-date {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 48px;
            padding: 8px;
            background: var(--primary);
            color: white;
            border-radius: var(--border-radius-sm);
        }

        .event-date .day {
            font-size: 18px;
            font-weight: 700;
            line-height: 1;
        }

        .event-date .month {
            font-size: 10px;
            font-weight: 600;
            margin-top: 2px;
        }

        .event-info h4 {
            margin: 0 0 4px 0;
            font-size: 13px;
            font-weight: 600;
        }

        .event-type,
        .event-lieu {
            display: block;
            font-size: 11px;
            color: var(--gray-600);
            margin-top: 2px;
        }

        .event-type {
            color: var(--primary);
            font-weight: 600;
        }

        /* Reservations List */
        .reservations-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .reservation-item {
            padding: 12px;
            background: var(--gray-50);
            border-radius: var(--border-radius-sm);
            border: 1px solid var(--border-color);
        }

        .reservation-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            gap: 8px;
        }

        .reservation-header strong {
            font-size: 13px;
        }

        .reservation-dates small {
            font-size: 11px;
            color: var(--gray-600);
        }


        /* Empty States */
        .empty-state,
        .empty-state-small {
            text-align: center;
            padding: 32px 16px;
            color: var(--gray-500);
        }

        .empty-state-small {
            padding: 24px 12px;
        }

        .empty-state p {
            margin: 0 0 6px 0;
            font-size: 13px;
            font-weight: 500;
        }

        .empty-state small {
            font-size: 12px;
            color: var(--gray-400);
        }

        /* Buttons */
        .btn-block {
            display: block;
            width: 100%;
            text-align: center;
            padding: 10px 16px;
            border-radius: var(--border-radius-sm);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
        }

        .btn-secondary.btn-block {
            background: var(--gray-100);
            color: var(--gray-700);
            border: 1px solid var(--border-color);
        }

        .btn-secondary.btn-block:hover {
            background: var(--gray-200);
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-primary {
            background: #ddd6fe;
            color: #5b21b6;
        }

        .badge-secondary {
            background: #f3f4f6;
            color: #4b5563;
        }

        /* Responsive */
        @media (max-width: 1400px) {
            .dashboard-grid {
                grid-template-columns: 1fr 1.5fr 1fr;
            }
        }

        @media (max-width: 1200px) {
            .stats-grid,
            .actions-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-col-left,
            .dashboard-col-sidebar {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }

            .dashboard-col-left .dashboard-section,
            .dashboard-col-sidebar .dashboard-section {
                margin-bottom: 0;
            }
        }

        @media (max-width: 768px) {
            .welcome-content {
                flex-direction: column;
                text-align: center;
            }

            .stats-grid,
            .actions-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-col-left,
            .dashboard-col-sidebar {
                grid-template-columns: 1fr;
            }

            .dashboard-section {
                padding: 16px;
            }
        }
        </style>
        <?php
    }
}