<?php
/**
 * Page détail d'un équipement (visiteur)
 */

require_once __DIR__ . '/../../../lib/helpers.php';
require_once __DIR__ . '/../../../lib/ViewComponents.php';

ViewComponents::renderHeader([
    'title' => $equipement['nom'] . ' - Laboratoire TDW',
    'role' => 'visiteur',
    'showLogout' => false,
    'showLoginButton' => true
]);
?>

<div class="visitor-container">
    <div class="container detail-container">
        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="<?= base_url() ?>">Accueil</a>
            <span>›</span>
            <a href="<?= base_url('equipements') ?>">Équipements</a>
            <span>›</span>
            <span>Détail</span>
        </nav>

        <!-- Header de l'équipement -->
        <div class="equipement-detail-header">
            <div class="equipement-type-badge">
                <?= e($equipement['type_equipement']) ?>
            </div>
            
            <h1><?= e($equipement['nom']) ?></h1>
            
            <div class="equipement-meta-header">
                <?php
                $etatBadges = [
                    'libre' => '<span class="badge badge-success">Disponible</span>',
                    'reserve' => '<span class="badge badge-info">Réservé</span>',
                    'en_maintenance' => '<span class="badge badge-warning">En maintenance</span>',
                    'hors_service' => '<span class="badge badge-danger">Hors service</span>'
                ];
                echo $etatBadges[$equipement['etat']] ?? '';
                ?>
                
                <?php if (!empty($equipement['numero_serie'])): ?>
                <span class="meta-item">
                    N° série: <code><?= e($equipement['numero_serie']) ?></code>
                </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="detail-layout">
            <!-- Colonne principale -->
            <div class="main-content">
                <!-- Description -->
                <?php if (!empty($equipement['description'])): ?>
                <section class="detail-card">
                    <h2>Description</h2>
                    <div class="description-content">
                        <?= nl2br(e($equipement['description'])) ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Informations techniques -->
                <section class="detail-card">
                    <h2>Informations techniques</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <strong>Type d'équipement</strong>
                            <span><?= e($equipement['type_equipement']) ?></span>
                        </div>
                        
                        <?php if (!empty($equipement['numero_serie'])): ?>
                        <div class="info-item">
                            <strong>Numéro de série</strong>
                            <span><code><?= e($equipement['numero_serie']) ?></code></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="info-item">
                            <strong>État actuel</strong>
                            <span>
                                <?php
                                $etats = [
                                    'libre' => 'Disponible',
                                    'reserve' => 'Réservé',
                                    'en_maintenance' => 'En maintenance',
                                    'hors_service' => 'Hors service'
                                ];
                                echo $etats[$equipement['etat']] ?? e($equipement['etat']);
                                ?>
                            </span>
                        </div>
                        
                        <?php if (!empty($equipement['localisation'])): ?>
                        <div class="info-item">
                            <strong>Localisation</strong>
                            <span><?= e($equipement['localisation']) ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($equipement['date_acquisition'])): ?>
                        <div class="info-item">
                            <strong>Date d'acquisition</strong>
                            <span><?= format_date($equipement['date_acquisition'], 'd F Y') ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Statistiques d'utilisation -->
                <?php if (isset($stats)): ?>
                <section class="detail-card">
                    <h2>Statistiques d'utilisation</h2>
                    <div class="stats-list">
                        <div class="stat-item">
                            <span class="stat-label">Réservations totales</span>
                            <span class="stat-value"><?= $stats['nb_reservations_total'] ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Réservations actives</span>
                            <span class="stat-value"><?= $stats['nb_reservations_actives'] ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Taux d'utilisation</span>
                            <span class="stat-value"><?= $stats['taux_utilisation'] ?>%</span>
                        </div>
                    </div>
                </section>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <aside class="sidebar-content">
                <!-- Équipe assignée -->
                <?php if (!empty($equipement['equipe_nom'])): ?>
                <section class="detail-card">
                    <h2>Équipe assignée</h2>
                    <div class="equipe-card">
                        <h3><?= e($equipement['equipe_nom']) ?></h3>
                        <?php if (!empty($equipement['equipe_id'])): ?>
                        <a href="<?= base_url('equipes/' . $equipement['equipe_id']) ?>" 
                           class="btn-link mt-md">
                            Voir l'équipe
                        </a>
                        <?php endif; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Retour -->
                <a href="<?= base_url('equipements') ?>" class="btn-secondary btn-block">
                    Retour aux équipements
                </a>
            </aside>
        </div>
    </div>
</div>

<style>
.detail-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 24px;
    font-size: 14px;
    color: var(--gray-600);
}

.breadcrumb a {
    color: var(--primary);
    text-decoration: none;
    transition: var(--transition);
}

.breadcrumb a:hover {
    color: var(--primary-dark);
}

.equipement-detail-header {
    background: white;
    padding: 40px;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
    margin-bottom: 30px;
    text-align: center;
}

.equipement-detail-header h1 {
    font-size: 32px;
    font-weight: 700;
    color: var(--gray-900);
    margin: 20px 0;
    line-height: 1.3;
}

.equipement-type-badge {
    display: inline-block;
    padding: 8px 16px;
    background: var(--gray-100);
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    color: var(--gray-700);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.equipement-meta-header {
    display: flex;
    justify-content: center;
    gap: 24px;
    flex-wrap: wrap;
    margin-top: 20px;
}

.meta-item {
    font-size: 15px;
    color: var(--gray-600);
}

.meta-item code {
    background: var(--gray-100);
    padding: 2px 8px;
    border-radius: 4px;
    font-family: monospace;
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
}

.detail-card h2 {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 20px;
    color: var(--gray-900);
    border-bottom: 2px solid var(--border-color);
    padding-bottom: 12px;
}

.description-content {
    line-height: 1.8;
    color: var(--gray-700);
    font-size: 16px;
}

.info-grid {
    display: grid;
    gap: 20px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 12px;
    background: var(--gray-50);
    border-radius: 8px;
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

.stats-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    background: var(--gray-50);
    border-radius: 8px;
}

.stat-label {
    color: var(--gray-600);
    font-weight: 500;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: var(--primary);
}

.equipe-card {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.equipe-card h3 {
    font-size: 16px;
    font-weight: 600;
    color: var(--gray-900);
    margin: 0;
}

.btn-link {
    color: var(--primary);
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: var(--transition);
}

.btn-link:hover {
    color: var(--primary-dark);
}

.btn-block {
    display: block;
    width: 100%;
    text-align: center;
    padding: 12px;
    border-radius: 8px;
}

.mt-md {
    margin-top: 12px;
}

/* Responsive */
@media (max-width: 1024px) {
    .detail-layout {
        grid-template-columns: 1fr;
    }
    
    .sidebar-content {
        order: -1;
    }
}

@media (max-width: 768px) {
    .equipement-detail-header {
        padding: 24px 20px;
    }
    
    .equipement-detail-header h1 {
        font-size: 24px;
    }
    
    .equipement-meta-header {
        flex-direction: column;
        gap: 12px;
    }
    
    .detail-card {
        padding: 20px;
    }
}
</style>

<?php ViewComponents::renderFooter(['role' => 'visiteur']); ?>
