<?php
/**
 * Vue du détail d'un événement (visiteur)
 */

require_once __DIR__ . '/../../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/NavigationComponent.php';
require_once __DIR__ . '/../../../lib/components/FooterComponent.php';
require_once __DIR__ . '/../../../lib/LabHelpers.php';

class EvenementsDetailView
{
    private array $evenement;

    public function __construct(array $evenement)
    {
        $this->evenement = $evenement;
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
        $this->renderBreadcrumb();
        $this->renderEvenementHeader();
        $this->renderDetailLayout();
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
        NavigationComponent::renderHorizontalMenu();
    }

    /**
     * Rendu du fil d'Ariane
     */
    private function renderBreadcrumb(): void
    {
        NavigationComponent::renderBreadcrumbs([
            ['url' => base_url(), 'label' => 'Accueil'],
            ['url' => base_url('evenements'), 'label' => 'Événements'],
            ['label' => 'Détail']
        ]);
    }

    /**
     * Rendu de l'en-tête de l'événement
     */
    private function renderEvenementHeader(): void
    {
        $event = $this->evenement;
        $type = $event['type_evenement'] ?? 'conference';
        $dateEvenement = $event['date_evenement'] ?? '';
        
        // Déterminer le statut
        $aujourd_hui = date('Y-m-d');
        $estPasse = $dateEvenement < $aujourd_hui;
        $estAujourdhui = $dateEvenement === $aujourd_hui;
        
        $typeBadges = [
            'conference' => '<span class="badge badge-primary">Conférence</span>',
            'seminaire' => '<span class="badge badge-info">Séminaire</span>',
            'workshop' => '<span class="badge badge-success">Workshop</span>',
            'soutenance' => '<span class="badge badge-warning">Soutenance</span>',
            'colloque' => '<span class="badge badge-purple">Colloque</span>',
            'reunion' => '<span class="badge badge-secondary">Réunion</span>'
        ];
        $typeBadge = $typeBadges[$type] ?? '<span class="badge badge-secondary">' . ucfirst($type) . '</span>';
        
        $statusBadge = '';
        if ($estAujourdhui) {
            $statusBadge = '<span class="badge badge-danger pulse">Aujourd\'hui</span>';
        } elseif (!$estPasse) {
            $statusBadge = '<span class="badge badge-success">À venir</span>';
        } else {
            $statusBadge = '<span class="badge badge-muted">Terminé</span>';
        }
        
        ?>
        <div class="evenement-detail-header <?= $estPasse ? 'event-past' : '' ?>">
            <div class="evenement-badges-header">
                <?= $typeBadge ?>
                <?= $statusBadge ?>
            </div>
            
            <h1><?= e($event['titre']) ?></h1>
            
            <div class="evenement-meta-header">
                <div class="meta-group">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <span><?= format_date($dateEvenement, 'd F Y') ?></span>
                </div>
                
                <?php if (!empty($event['heure_debut'])): ?>
                <div class="meta-group">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <span>
                        <?= date('H:i', strtotime($event['heure_debut'])) ?>
                        <?php if (!empty($event['heure_fin'])): ?>
                            - <?= date('H:i', strtotime($event['heure_fin'])) ?>
                        <?php endif; ?>
                    </span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($event['lieu'])): ?>
                <div class="meta-group">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    <span><?= e($event['lieu']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu du layout principal
     */
    private function renderDetailLayout(): void
    {
        ?>
        <div class="detail-layout">
            <div class="main-content">
                <?php 
                $this->renderDescription();
                $this->renderProgramme();
                $this->renderInformationsPratiques();
                ?>
            </div>
            
            <aside class="sidebar-content">
                <?php 
                $this->renderDateCard();
                $this->renderOrganisateur();
                $this->renderContactInfo();
                $this->renderBackButton();
                ?>
            </aside>
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
            <h2>Description de l'événement</h2>
            <div class="description-content">
                <?= nl2br(e($this->evenement['description'])) ?>
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
                <?= nl2br(e($this->evenement['programme'])) ?>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu des informations pratiques
     */
    private function renderInformationsPratiques(): void
    {
        $event = $this->evenement;
        ?>
        <section class="detail-card">
            <h2>Informations pratiques</h2>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Type d'événement</strong>
                    <span><?= e(ucfirst($event['type_evenement'] ?? 'Non spécifié')) ?></span>
                </div>
                
                <div class="info-item">
                    <strong>Date</strong>
                    <span><?= format_date($event['date_evenement'] ?? '', 'd F Y') ?></span>
                </div>
                
                <?php if (!empty($event['heure_debut'])): ?>
                <div class="info-item">
                    <strong>Horaires</strong>
                    <span>
                        De <?= date('H:i', strtotime($event['heure_debut'])) ?>
                        <?php if (!empty($event['heure_fin'])): ?>
                            à <?= date('H:i', strtotime($event['heure_fin'])) ?>
                        <?php endif; ?>
                    </span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($event['lieu'])): ?>
                <div class="info-item">
                    <strong>Lieu</strong>
                    <span><?= e($event['lieu']) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($event['nb_participants_max'])): ?>
                <div class="info-item">
                    <strong>Places disponibles</strong>
                    <span><?= e($event['nb_participants_max']) ?> personnes</span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($event['lien_inscription'])): ?>
                <div class="info-item full-width">
                    <strong>Inscription</strong>
                    <a href="<?= e($event['lien_inscription']) ?>" 
                       target="_blank" 
                       class="btn-primary">
                        S'inscrire à l'événement
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu de la carte de date
     */
    private function renderDateCard(): void
    {
        $dateEvenement = $this->evenement['date_evenement'] ?? '';
        ?>
        <section class="detail-card date-highlight-card">
            <div class="date-highlight">
                <div class="date-jour"><?= date('d', strtotime($dateEvenement)) ?></div>
                <div class="date-mois"><?= date('F', strtotime($dateEvenement)) ?></div>
                <div class="date-annee"><?= date('Y', strtotime($dateEvenement)) ?></div>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu des informations organisateur
     */
    private function renderOrganisateur(): void
    {
        $organisateur = $this->evenement['organisateur_nom'] ?? null;
        
        if (!$organisateur) {
            return;
        }
        ?>
        <section class="detail-card">
            <h2>Organisateur</h2>
            <div class="organisateur-card">
                <div class="organisateur-avatar">
                    <?= strtoupper(substr($organisateur, 0, 2)) ?>
                </div>
                <h3><?= e($organisateur) ?></h3>
                <p class="organisateur-role">Responsable de l'événement</p>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu des informations de contact
     */
    private function renderContactInfo(): void
    {
        if (empty($this->evenement['contact_email']) && empty($this->evenement['contact_telephone'])) {
            return;
        }
        ?>
        <section class="detail-card">
            <h2>Contact</h2>
            <div class="contact-info">
                <?php if (!empty($this->evenement['contact_email'])): ?>
                <a href="mailto:<?= e($this->evenement['contact_email']) ?>" class="contact-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                    <span><?= e($this->evenement['contact_email']) ?></span>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($this->evenement['contact_telephone'])): ?>
                <a href="tel:<?= e($this->evenement['contact_telephone']) ?>" class="contact-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                    <span><?= e($this->evenement['contact_telephone']) ?></span>
                </a>
                <?php endif; ?>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu du bouton retour
     */
    private function renderBackButton(): void
    {
        ?>
        <a href="<?= base_url('evenements') ?>" class="btn-secondary btn-block">
            Retour aux événements
        </a>
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
        .detail-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .breadcrumbs {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            color: var(--gray-600);
            padding: 12px;
            background: white;
            border-radius: 8px;
            box-shadow: var(--shadow-xs);
        }

        .breadcrumbs a {
            color: var(--primary);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumbs a:hover {
            color: var(--primary-dark);
        }

        .breadcrumbs .separator {
            color: var(--gray-400);
            user-select: none;
        }

        .breadcrumbs .current {
            color: var(--gray-900);
            font-weight: 500;
        }

        .evenement-detail-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 40px;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: 30px;
            color: white;
        }

        .event-past {
            opacity: 0.8;
        }

        .evenement-badges-header {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
        }

        .evenement-detail-header h1 {
            font-size: 36px;
            font-weight: 700;
            margin: 0 0 20px 0;
            line-height: 1.3;
            color: white;
        }

        .evenement-meta-header {
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
        }

        .meta-group {
            display: flex;
            align-items: center;
            gap: 8px;
            color: white;
            font-size: 15px;
        }

        .meta-group svg {
            opacity: 0.9;
        }

        .badge-purple {
            background: #9333ea;
            color: white;
        }

        .badge-muted {
            background: var(--gray-400);
            color: white;
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
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

        .description-content,
        .programme-content {
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

        .info-item.full-width {
            grid-column: 1 / -1;
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

        .date-highlight-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            text-align: center;
            padding: 32px;
        }

        .date-highlight {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .date-jour {
            font-size: 64px;
            font-weight: 700;
            line-height: 1;
        }

        .date-mois {
            font-size: 24px;
            text-transform: capitalize;
            font-weight: 600;
        }

        .date-annee {
            font-size: 18px;
            opacity: 0.9;
        }

        .organisateur-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 12px;
        }

        .organisateur-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 700;
        }

        .organisateur-card h3 {
            font-size: 18px;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0;
        }

        .organisateur-role {
            font-size: 14px;
            color: var(--gray-600);
            margin: 0;
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: var(--gray-50);
            border-radius: 8px;
            text-decoration: none;
            color: var(--gray-700);
            transition: var(--transition);
        }

        .contact-item:hover {
            background: var(--gray-100);
            color: var(--primary);
        }

        .contact-item svg {
            color: var(--primary);
            flex-shrink: 0;
        }

        .btn-block {
            display: block;
            width: 100%;
            text-align: center;
            padding: 12px;
            border-radius: 8px;
        }

        @media (max-width: 1024px) {
            .detail-layout {
                grid-template-columns: 1fr;
            }
            
            .sidebar-content {
                order: -1;
            }
        }

        @media (max-width: 768px) {
            .evenement-detail-header {
                padding: 24px 20px;
            }
            
            .evenement-detail-header h1 {
                font-size: 24px;
            }
            
            .evenement-meta-header {
                flex-direction: column;
                gap: 12px;
            }
            
            .detail-card {
                padding: 20px;
            }

            .breadcrumbs {
                font-size: 12px;
            }

            .date-jour {
                font-size: 48px;
            }

            .date-mois {
                font-size: 20px;
            }
        }
        </style>
        <?php
    }
}