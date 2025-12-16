<?php
/**
 * Dashboard Membre
 */

ViewComponents::renderHeader([
    'title' => 'Mon Espace Membre',
    'username' => session('username'),
    'role' => 'membre',
    'showLogout' => true,
    'additionalJs' => [
        base_url('assets/js/membre-dashboard.js')
    ]
]);
?>

<div class="container">
    <?php ViewComponents::renderBreadcrumbs([
        ['label' => 'Accueil', 'url' => base_url('membre/dashboard')],
        ['label' => 'Tableau de bord']
    ]); ?>
    
    <!-- Bannière de bienvenue -->
    <div class="welcome-banner">
        <div class="welcome-content">
            <div class="welcome-text">
                <h2>Bienvenue, <?= e(session('username')) ?></h2>
                <p>Gérez vos activités de recherche et vos contributions</p>
            </div>
            <div class="welcome-actions">
                <a href="<?= base_url('membre/profil') ?>" class="btn-secondary">
                    Modifier mon profil
                </a>
            </div>
        </div>
    </div>
    
    <!-- Statistiques personnelles -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Mes Projets</h3>
            <div class="number"><?= $stats['mes_projets'] ?? 0 ?></div>
            <a href="<?= base_url('membre/projets') ?>" class="stat-link">Voir tous</a>
        </div>
        
        <div class="stat-card">
            <h3>Mes Publications</h3>
            <div class="number"><?= $stats['mes_publications'] ?? 0 ?></div>
            <a href="<?= base_url('membre/publications') ?>" class="stat-link">Voir toutes</a>
        </div>
        
        <div class="stat-card">
            <h3>Réservations actives</h3>
            <div class="number"><?= $stats['reservations_actives'] ?? 0 ?></div>
            <a href="<?= base_url('membre/reservations') ?>" class="stat-link">Gérer</a>
        </div>
        
        <div class="stat-card">
            <h3>Événements à venir</h3>
            <div class="number"><?= $stats['evenements_a_venir'] ?? 0 ?></div>
            <a href="<?= base_url('membre/evenements') ?>" class="stat-link">Découvrir</a>
        </div>
    </div>
    
    <!-- Actions rapides -->
    <div class="quick-actions-section">
        <h2 class="section-title">Actions rapides</h2>
        <div class="actions-grid">
            <a href="<?= base_url('membre/publications/nouveau') ?>" class="action-card">
                <div class="action-content">
                    <h3>Nouvelle publication</h3>
                    <p>Soumettre une nouvelle publication</p>
                </div>
            </a>
            
            <a href="<?= base_url('membre/reservations') ?>" class="action-card">
                <div class="action-content">
                    <h3>Réserver équipement</h3>
                    <p>Réserver du matériel de recherche</p>
                </div>
            </a>
            
            <a href="<?= base_url('membre/profil') ?>" class="action-card">
                <div class="action-content">
                    <h3>Modifier profil</h3>
                    <p>Mettre à jour mes informations</p>
                </div>
            </a>
            
            <a href="<?= base_url('membre/projets') ?>" class="action-card">
                <div class="action-content">
                    <h3>Mes projets</h3>
                    <p>Gérer mes projets de recherche</p>
                </div>
            </a>
        </div>
    </div>
    
    <!-- Contenu principal en deux colonnes -->
    <div class="dashboard-grid">
        <!-- Colonne gauche -->
        <div class="dashboard-col-main">
            <!-- Mes projets récents -->
            <section class="dashboard-section">
                <div class="section-header">
                    <h2>Mes projets de recherche</h2>
                    <a href="<?= base_url('membre/projets') ?>" class="see-all">Voir tous</a>
                </div>
                
                <?php if (empty($mesProjets)): ?>
                    <div class="empty-state">
                        <p>Aucun projet pour le moment</p>
                        <small>Vous n'êtes associé à aucun projet actuellement</small>
                    </div>
                <?php else: ?>
                    <div class="items-list">
                        <?php foreach ($mesProjets as $projet): ?>
                            <div class="item-card">
                                <div class="item-header">
                                    <div class="item-title-group">
                                        <h3>
                                            <a href="<?= base_url('membre/projets/' . $projet['id']) ?>">
                                                <?= e($projet['titre']) ?>
                                            </a>
                                        </h3>
                                        <span class="item-role"><?= e($projet['mon_role'] ?? 'Membre') ?></span>
                                    </div>
                                    <?= LabHelpers::getStatusBadge($projet['status']) ?>
                                </div>
                                <p class="item-description"><?= truncate($projet['description'], 150) ?></p>
                                <div class="item-meta">
                                    <span>Début: <?= date('d/m/Y', strtotime($projet['date_debut'])) ?></span>
                                    <span><?= $projet['nb_membres'] ?? 0 ?> membres</span>
                                    <span><?= e($projet['domaine_recherche'] ?? 'N/A') ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
            
            <!-- Mes publications récentes -->
            <section class="dashboard-section">
                <div class="section-header">
                    <h2>Mes publications</h2>
                    <a href="<?= base_url('membre/publications') ?>" class="see-all">Voir toutes</a>
                </div>
                
                <?php if (empty($mesPublications)): ?>
                    <div class="empty-state">
                        <p>Aucune publication pour le moment</p>
                        <a href="<?= base_url('membre/publications/nouveau') ?>" class="btn-primary">
                            Soumettre une publication
                        </a>
                    </div>
                <?php else: ?>
                    <div class="items-list">
                        <?php foreach ($mesPublications as $pub): ?>
                            <div class="item-card">
                                <div class="item-header">
                                    <h3>
                                        <a href="<?= base_url('membre/publications/' . $pub['id']) ?>">
                                            <?= e($pub['titre']) ?>
                                        </a>
                                    </h3>
                                    <?= LabHelpers::getStatusBadge($pub['statut_validation']) ?>
                                </div>
                                <div class="item-meta">
                                    <?= LabHelpers::getPublicationTypeBadge($pub['type_publication']) ?>
                                    <span><?= date('d/m/Y', strtotime($pub['date_publication'])) ?></span>
                                    <?php if (!empty($pub['conference'])): ?>
                                        <span><?= e($pub['conference']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
        
        <!-- Colonne droite (Sidebar) -->
        <div class="dashboard-col-sidebar">
            <!-- Informations du profil -->
            <section class="dashboard-section profile-section">
                <h2>Mon profil</h2>
                <div class="profile-info">
                    <?php if (!empty($membre['photo'])): ?>
                        <img src="<?= base_url('uploads/' . $membre['photo']) ?>" 
                             alt="Photo de profil" 
                             class="profile-photo">
                    <?php else: ?>
                        <div class="profile-photo-placeholder">
                            <?= strtoupper(substr(session('username'), 0, 2)) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="profile-details">
                        <h3><?= e($membre['nom'] . ' ' . $membre['prenom']) ?></h3>
                        <p class="profile-grade"><?= e($membre['grade'] ?? 'Membre') ?></p>
                        <?php if (!empty($membre['domaine_recherche'])): ?>
                            <p class="profile-domain"><?= e($membre['domaine_recherche']) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <a href="<?= base_url('membre/profil') ?>" class="btn-secondary btn-block">
                        Modifier mon profil
                    </a>
                </div>
            </section>
            
            <!-- Réservations d'équipements -->
            <section class="dashboard-section">
                <div class="section-header">
                    <h2>Équipements réservés</h2>
                    <a href="<?= base_url('membre/reservations') ?>" class="see-all-small">Tout voir</a>
                </div>
                
                <?php if (empty($mesReservations)): ?>
                    <div class="empty-state-small">
                        <p>Aucune réservation active</p>
                        <a href="<?= base_url('membre/reservations') ?>" class="btn-secondary btn-block">
                            Réserver du matériel
                        </a>
                    </div>
                <?php else: ?>
                    <div class="reservations-list">
                        <?php foreach (array_slice($mesReservations, 0, 3) as $resa): ?>
                            <div class="reservation-item">
                                <div class="reservation-header">
                                    <strong><?= e($resa['equipement_nom']) ?></strong>
                                    <?= LabHelpers::getStatusBadge($resa['statut']) ?>
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
                    
                    <a href="<?= base_url('membre/reservations') ?>" class="btn-secondary btn-block">
                        Gérer mes réservations
                    </a>
                <?php endif; ?>
            </section>
            
            <!-- Événements à venir -->
            <section class="dashboard-section">
                <div class="section-header">
                    <h2>Événements à venir</h2>
                    <a href="<?= base_url('membre/evenements') ?>" class="see-all-small">Tout voir</a>
                </div>
                
                <?php if (empty($evenements)): ?>
                    <div class="empty-state-small">
                        <p>Aucun événement prévu</p>
                    </div>
                <?php else: ?>
                    <div class="events-list">
                        <?php foreach (array_slice($evenements, 0, 3) as $event): ?>
                            <div class="event-item">
                                <div class="event-date">
                                    <span class="day"><?= date('d', strtotime($event['date_evenement'])) ?></span>
                                    <span class="month"><?= date('M', strtotime($event['date_evenement'])) ?></span>
                                </div>
                                <div class="event-info">
                                    <h4><?= e($event['titre']) ?></h4>
                                    <small class="event-type"><?= e($event['type_evenement']) ?></small>
                                    <small class="event-lieu"><?= e($event['lieu']) ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <a href="<?= base_url('membre/evenements') ?>" class="btn-secondary btn-block">
                        Voir tous les événements
                    </a>
                <?php endif; ?>
            </section>
            
            <!-- Notifications ou Alertes -->
            <?php if (!empty($notifications)): ?>
            <section class="dashboard-section notifications-section">
                <h2>Notifications</h2>
                <div class="notifications-list">
                    <?php foreach (array_slice($notifications, 0, 3) as $notif): ?>
                        <div class="notification-item">
                            <span class="notification-type <?= e($notif['type']) ?>"></span>
                            <div class="notification-content">
                                <p><?= e($notif['message']) ?></p>
                                <small><?= time_ago($notif['date']) ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Welcome Banner amélioré */
.welcome-banner {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    padding: 32px;
    border-radius: var(--border-radius-xl);
    margin-bottom: 32px;
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
    font-size: 28px;
    font-weight: 700;
    margin: 0 0 8px 0;
    letter-spacing: -0.5px;
}

.welcome-text p {
    font-size: 15px;
    margin: 0;
    opacity: 0.95;
}

.welcome-actions .btn-secondary {
    background: white;
    color: var(--primary);
    border: none;
}

.welcome-actions .btn-secondary:hover {
    background: var(--gray-50);
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.stat-card {
    background: var(--bg-card);
    padding: 24px;
    border-radius: var(--border-radius-lg);
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
}

.stat-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
    border-color: var(--primary);
}

.stat-card h3 {
    color: var(--gray-600);
    font-size: 13px;
    font-weight: 600;
    margin: 0 0 12px 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-card .number {
    font-size: 42px;
    font-weight: 700;
    color: var(--primary);
    line-height: 1;
    margin-bottom: 12px;
}

.stat-link {
    color: var(--primary);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: var(--transition);
}

.stat-link:hover {
    color: var(--primary-dark);
}

/* Actions Grid */
.quick-actions-section {
    margin-bottom: 32px;
}

.section-title {
    font-size: 20px;
    font-weight: 600;
    color: var(--gray-900);
    margin: 0 0 20px 0;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
}

.action-card {
    background: var(--bg-card);
    padding: 20px;
    border-radius: var(--border-radius-lg);
    border: 2px solid var(--border-color);
    text-decoration: none;
    transition: var(--transition);
    box-shadow: var(--shadow-xs);
}

.action-card:hover {
    border-color: var(--primary);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.action-content h3 {
    font-size: 16px;
    font-weight: 600;
    color: var(--gray-900);
    margin: 0 0 6px 0;
}

.action-content p {
    font-size: 13px;
    color: var(--gray-600);
    margin: 0;
}

/* Dashboard Grid */
.dashboard-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 24px;
}

.dashboard-section {
    background: var(--bg-card);
    padding: 24px;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-sm);
    margin-bottom: 24px;
    border: 1px solid var(--border-color);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.section-header h2 {
    font-size: 18px;
    font-weight: 600;
    color: var(--gray-900);
    margin: 0;
}

.see-all,
.see-all-small {
    color: var(--primary);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: var(--transition);
}

.see-all:hover,
.see-all-small:hover {
    color: var(--primary-dark);
}

/* Items List */
.items-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.item-card {
    padding: 16px;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-sm);
    transition: var(--transition);
    background: var(--gray-50);
}

.item-card:hover {
    box-shadow: var(--shadow-sm);
    border-color: var(--primary);
    background: white;
}

.item-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 8px;
    gap: 12px;
}

.item-title-group {
    flex: 1;
}

.item-title-group h3 {
    font-size: 15px;
    font-weight: 600;
    margin: 0 0 4px 0;
}

.item-title-group h3 a {
    color: var(--gray-900);
    text-decoration: none;
    transition: var(--transition);
}

.item-title-group h3 a:hover {
    color: var(--primary);
}

.item-role {
    font-size: 12px;
    color: var(--gray-500);
    font-weight: 500;
}

.item-description {
    color: var(--gray-600);
    font-size: 14px;
    margin: 8px 0;
    line-height: 1.5;
}

.item-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    font-size: 13px;
    color: var(--gray-500);
    font-weight: 500;
}

/* Profile Section */
.profile-section {
    text-align: center;
}

.profile-info {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
}

.profile-photo {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--primary);
}

.profile-photo-placeholder {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: var(--primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
    font-weight: 700;
}

.profile-details h3 {
    font-size: 16px;
    font-weight: 600;
    color: var(--gray-900);
    margin: 0 0 4px 0;
}

.profile-grade {
    font-size: 13px;
    color: var(--primary);
    font-weight: 600;
    margin: 0 0 4px 0;
}

.profile-domain {
    font-size: 13px;
    color: var(--gray-600);
    margin: 0;
}

/* Reservations List */
.reservations-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 16px;
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
    align-items: center;
    margin-bottom: 6px;
    gap: 8px;
}

.reservation-header strong {
    font-size: 14px;
    color: var(--gray-900);
}

.reservation-dates small {
    font-size: 12px;
    color: var(--gray-600);
    line-height: 1.4;
}

/* Events List */
.events-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 16px;
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
    min-width: 50px;
    padding: 8px;
    background: var(--primary);
    color: white;
    border-radius: var(--border-radius-sm);
    flex-shrink: 0;
}

.event-date .day {
    font-size: 20px;
    font-weight: 700;
    line-height: 1;
}

.event-date .month {
    font-size: 11px;
    text-transform: uppercase;
    font-weight: 600;
    margin-top: 2px;
}

.event-info {
    flex: 1;
}

.event-info h4 {
    margin: 0 0 4px 0;
    font-size: 14px;
    font-weight: 600;
    color: var(--gray-900);
}

.event-type,
.event-lieu {
    display: block;
    font-size: 12px;
    color: var(--gray-600);
}

.event-type {
    color: var(--primary);
    font-weight: 600;
}

/* Empty States */
.empty-state,
.empty-state-small {
    text-align: center;
    padding: 40px 20px;
    color: var(--gray-500);
}

.empty-state-small {
    padding: 24px 16px;
}

.empty-state p,
.empty-state-small p {
    margin: 0 0 8px 0;
    font-size: 14px;
    font-weight: 500;
}

.empty-state small {
    font-size: 13px;
    color: var(--gray-400);
}

.empty-state .btn-primary {
    margin-top: 16px;
    width: auto;
    display: inline-block;
}

/* Notifications */
.notifications-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.notification-item {
    display: flex;
    gap: 12px;
    padding: 12px;
    background: var(--gray-50);
    border-radius: var(--border-radius-sm);
    border-left: 3px solid var(--primary);
}

.notification-type {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--primary);
    margin-top: 6px;
}

.notification-content p {
    margin: 0 0 4px 0;
    font-size: 13px;
    color: var(--gray-900);
}

.notification-content small {
    font-size: 12px;
    color: var(--gray-500);
}

/* Buttons */
.btn-block {
    display: block;
    width: 100%;
    text-align: center;
    padding: 10px 16px;
    border-radius: var(--border-radius-sm);
    font-size: 14px;
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

/* Responsive */
@media (max-width: 1024px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .welcome-content {
        flex-direction: column;
        text-align: center;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .actions-grid {
        grid-template-columns: 1fr;
    }
    
    .dashboard-section {
        padding: 20px;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php ViewComponents::renderFooter(['role' => 'membre']); ?>