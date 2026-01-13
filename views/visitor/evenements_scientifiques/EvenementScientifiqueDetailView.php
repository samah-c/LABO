<?php
/**
 * EvenementScientifiqueDetailView.php - Vue détail d'un événement scientifique
 * À placer dans : /TDW_project/app/views/public/evenements/EvenementScientifiqueDetailView.php
 */

require_once __DIR__ . '/../../../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../../../lib/components/NavigationComponent.php';
require_once __DIR__ . '/../../../../lib/components/FooterComponent.php';

class EvenementScientifiqueDetailView
{
    private array $evenement;
    private array $evenementsLies;

    public function __construct(array $evenement, array $evenementsLies = [])
    {
        $this->evenement = $evenement;
        $this->evenementsLies = $evenementsLies;
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
        $this->renderEventHeader();
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
            'title' => $this->evenement['titre'] . ' - Laboratoire TDW',
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
        NavigationComponent::renderHorizontalMenu('evenements');
    }

    /**
     * Rendu du fil d'Ariane
     */
    private function renderBreadcrumbs(): void
    {
        NavigationComponent::renderBreadcrumbs([
            ['label' => 'Accueil', 'url' => base_url()],
            ['label' => 'Événements Scientifiques', 'url' => base_url('evenements/scientifiques')],
            ['label' => $this->evenement['titre']]
        ]);
    }

    /**
     * Rendu de l'en-tête de l'événement
     */
    private function renderEventHeader(): void
    {
        $typeScientifique = $this->evenement['type_scientifique'] ?? 'autre';
        
        // Couleurs par type
        $typeColors = [
            'atelier' => '#3B82F6',
            'seminaire' => '#8B5CF6',
            'conference' => '#EC4899',
            'colloque' => '#F59E0B'
        ];
        $badgeColor = $typeColors[$typeScientifique] ?? '#6B7280';
        
        // Labels des types
        $typeLabels = [
            'atelier' => 'Atelier',
            'seminaire' => 'Séminaire',
            'conference' => 'Conférence',
            'colloque' => 'Colloque'
        ];
        $typeLabel = $typeLabels[$typeScientifique] ?? ucfirst($typeScientifique);
        
        $dateEvenement = $this->evenement['date_evenement'] ?? '';
        ?>
        
        <div class="event-profile-header">
            <div class="event-date-large">
                <div class="date-day"><?= date('d', strtotime($dateEvenement)) ?></div>
                <div class="date-month"><?= date('M', strtotime($dateEvenement)) ?></div>
                <div class="date-year"><?= date('Y', strtotime($dateEvenement)) ?></div>
                
                <span class="event-badge" style="background: <?= $badgeColor ?>;">
                    <?= htmlspecialchars($typeLabel) ?>
                </span>
            </div>
            
            <div class="event-info">
                <h1><?= htmlspecialchars($this->evenement['titre']) ?></h1>
                
                <?php if (!empty($this->evenement['theme_scientifique'])): ?>
                <div class="event-theme"><?= htmlspecialchars($this->evenement['theme_scientifique']) ?></div>
                <?php endif; ?>
                
                <?php if (!empty($this->evenement['intervenant_principal'])): ?>
                <div class="event-intervenant">
                    Intervenant principal: <strong><?= htmlspecialchars($this->evenement['intervenant_principal']) ?></strong>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($this->evenement['lieu'])): ?>
                <div class="event-lieu">
                    Lieu: <?= htmlspecialchars($this->evenement['lieu']) ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="event-stats">
                <?php if (!empty($this->evenement['nombre_participants'])): ?>
                <div class="stat-box">
                    <div class="stat-number"><?= htmlspecialchars($this->evenement['nombre_participants']) ?></div>
                    <div class="stat-label">Participants</div>
                </div>
                <?php endif; ?>
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
            $this->renderDescription();
            $this->renderProgramme();
            $this->renderIntervenants();
            $this->renderEmptyState();
            ?>
        </div>
        <?php
    }

    /**
     * Rendu de la description
     */
    private function renderDescription(): void
    {
        if (empty($this->evenement['description'])) {
            return;
        }
        ?>
        
        <section class="detail-card">
            <h2>Description</h2>
            <div class="description-content">
                <?= nl2br(htmlspecialchars($this->evenement['description'])) ?>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu du programme
     */
    private function renderProgramme(): void
    {
        if (empty($this->evenement['programme'])) {
            return;
        }
        ?>
        
        <section class="detail-card">
            <h2>Programme</h2>
            <div class="programme-content">
                <?= nl2br(htmlspecialchars($this->evenement['programme'])) ?>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu des intervenants
     */
    private function renderIntervenants(): void
    {
        $intervenants = [];
        
        if (!empty($this->evenement['intervenant_principal'])) {
            $intervenants[] = [
                'nom' => $this->evenement['intervenant_principal'],
                'role' => 'Intervenant principal'
            ];
        }
        
        if (!empty($this->evenement['autres_intervenants'])) {
            $autresIntervenants = explode(',', $this->evenement['autres_intervenants']);
            foreach ($autresIntervenants as $intervenant) {
                $intervenants[] = [
                    'nom' => trim($intervenant),
                    'role' => 'Intervenant'
                ];
            }
        }
        
        if (empty($intervenants)) {
            return;
        }
        ?>
        
        <section class="detail-card">
            <h2>Intervenants</h2>
            <div class="intervenants-list">
                <?php foreach ($intervenants as $intervenant): ?>
                    <div class="intervenant-item">
                        <div class="intervenant-avatar">
                            <div class="avatar-placeholder">
                                <?= strtoupper(substr($intervenant['nom'], 0, 2)) ?>
                            </div>
                        </div>
                        <div class="intervenant-info">
                            <h4><?= htmlspecialchars($intervenant['nom']) ?></h4>
                            <span class="intervenant-role"><?= htmlspecialchars($intervenant['role']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu de l'état vide
     */
    private function renderEmptyState(): void
    {
        if (!empty($this->evenement['description']) || 
            !empty($this->evenement['programme'])) {
            return;
        }
        ?>
        
        <section class="detail-card">
            <div class="empty-state">
                <p>Aucun détail supplémentaire disponible pour cet événement.</p>
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
            $this->renderEvenementsLies();
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
                    <strong>Date</strong>
                    <span><?= format_date($this->evenement['date_evenement'], 'l d F Y') ?></span>
                </div>
                
                <?php if (!empty($this->evenement['heure_debut'])): ?>
                <div class="info-item">
                    <strong>Heure</strong>
                    <span>
                        <?= date('H:i', strtotime($this->evenement['heure_debut'])) ?>
                        <?php if (!empty($this->evenement['heure_fin'])): ?>
                            - <?= date('H:i', strtotime($this->evenement['heure_fin'])) ?>
                        <?php endif; ?>
                    </span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($this->evenement['lieu'])): ?>
                <div class="info-item">
                    <strong>Lieu</strong>
                    <span><?= htmlspecialchars($this->evenement['lieu']) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($this->evenement['organisateur_nom'])): ?>
                <div class="info-item">
                    <strong>Organisateur</strong>
                    <span><?= htmlspecialchars($this->evenement['organisateur_nom']) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($this->evenement['nombre_participants'])): ?>
                <div class="info-item">
                    <strong>Participants attendus</strong>
                    <span><?= htmlspecialchars($this->evenement['nombre_participants']) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($this->evenement['public_cible'])): ?>
                <div class="info-item">
                    <strong>Public cible</strong>
                    <span><?= htmlspecialchars($this->evenement['public_cible']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu des événements liés
     */
    private function renderEvenementsLies(): void
    {
        if (empty($this->evenementsLies)) {
            return;
        }
        ?>
        
        <section class="detail-card">
            <h2>Événements similaires</h2>
            <div class="related-events">
                <?php foreach ($this->evenementsLies as $event): ?>
                    <a href="<?= base_url('evenements/scientifiques/' . $event['id']) ?>" 
                       class="related-event-item">
                        <div class="related-event-date">
                            <?= date('d M', strtotime($event['date_evenement'])) ?>
                        </div>
                        <div class="related-event-info">
                            <h4><?= htmlspecialchars($event['titre']) ?></h4>
                            <span class="related-event-type">
                                <?= htmlspecialchars(ucfirst($event['type_scientifique'] ?? '')) ?>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
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
        <a href="<?= base_url('evenements/scientifiques') ?>" class="btn-secondary btn-block">
            Retour aux événements
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

        .event-profile-header {
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

        .event-date-large {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 120px;
            padding: 20px;
            background: var(--primary);
            color: white;
            border-radius: var(--border-radius-lg);
        }

        .event-date-large .date-day {
            font-size: 48px;
            font-weight: 700;
            line-height: 1;
        }

        .event-date-large .date-month {
            font-size: 16px;
            text-transform: uppercase;
            font-weight: 600;
            margin-top: 8px;
        }

        .event-date-large .date-year {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 4px;
        }

        .event-badge {
            position: absolute;
            bottom: -12px;
            left: 50%;
            transform: translateX(-50%);
            padding: 6px 16px;
            border-radius: 16px;
            font-size: 11px;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 3px solid white;
            white-space: nowrap;
        }

        .event-info h1 {
            font-size: 32px;
            font-weight: 700;
            margin: 0 0 12px 0;
            color: var(--gray-900);
        }

        .event-theme {
            font-size: 18px;
            font-weight: 500;
            color: var(--primary);
            margin-bottom: 12px;
        }

        .event-intervenant {
            font-size: 15px;
            color: var(--gray-600);
            margin-bottom: 8px;
        }

        .event-lieu {
            font-size: 15px;
            color: var(--gray-600);
            margin-top: 8px;
        }

        .event-stats {
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

        .description-content,
        .programme-content {
            line-height: 1.8;
            color: var(--gray-700);
            font-size: 16px;
        }

        .intervenants-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .intervenant-item {
            display: flex;
            gap: 16px;
            padding: 16px;
            background: var(--gray-50);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .intervenant-item:hover {
            background: white;
            box-shadow: var(--shadow-sm);
        }

        .intervenant-avatar {
            flex-shrink: 0;
        }

        .avatar-placeholder {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 700;
        }

        .intervenant-info h4 {
            margin: 0 0 4px 0;
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-900);
        }

        .intervenant-role {
            font-size: 13px;
            color: var(--gray-600);
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

        .related-events {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .related-event-item {
            display: flex;
            gap: 12px;
            padding: 12px;
            background: var(--gray-50);
            border-radius: 8px;
            text-decoration: none;
            transition: var(--transition);
        }

        .related-event-item:hover {
            background: var(--gray-100);
            transform: translateX(4px);
        }

        .related-event-date {
            flex-shrink: 0;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--primary);
            color: white;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
        }

        .related-event-info h4 {
            margin: 0 0 4px 0;
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-900);
        }

        .related-event-type {
            font-size: 12px;
            color: var(--gray-600);
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
            
            .event-profile-header {
                grid-template-columns: 1fr;
                text-align: center;
                justify-items: center;
            }
            
            .event-stats {
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .event-profile-header {
                padding: 24px 20px;
            }
            
            .event-info h1 {
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
