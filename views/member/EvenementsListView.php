<?php
/**
 * Vue de la liste des événements (membre)
 */
require_once __DIR__ . '/../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../lib/components/NavigationComponent.php';
require_once __DIR__ . '/../../lib/components/PageHeaderComponent.php';
require_once __DIR__ . '/../../lib/components/FilterComponent.php';
require_once __DIR__ . '/../../lib/components/FooterComponent.php';
class EvenementsListView
{
    private array $evenements;

    public function __construct(array $evenements)
    {
        $this->evenements = $evenements;
    }

    public function render(): void
    {
        $this->renderHeader();
        $this->renderNavigation();
        echo '<div class="container">';
        $this->renderBreadcrumbs();
        $this->renderPageHeader();
        $this->renderFilters();
        $this->renderTimeline();
        echo '</div>';
        $this->renderStyles();
        $this->renderFooter();
    }

    private function renderHeader(): void
    {
        HeaderComponent::render([
            'title' => 'Événements - Espace Membre',
            'username' => session('username'),
            'role' => 'membre',
            'showLogout' => true
        ]);
    }

    private function renderNavigation(): void
    {
        NavigationComponent::renderSidebar('membre');
    }

    private function renderBreadcrumbs(): void
    {
        NavigationComponent::renderBreadcrumbs([
            ['label' => 'Tableau de bord', 'url' => base_url('membre/dashboard')],
            ['label' => 'Événements']
        ]);
    }

    private function renderPageHeader(): void
    {
        PageHeaderComponent::render([
            'title' => 'Événements',
            'subtitle' => 'Découvrez les prochains événements du laboratoire',
            'actions' => []
        ]);
    }

    private function renderFilters(): void
    {
        FilterComponent::render([
            'action' => base_url('membre/evenements'),
            'showSearch' => false,
            'filters' => [
                [
                    'type' => 'select',
                    'name' => 'type',
                    'label' => '',
                    'options' => [
                        'conference' => 'Conférence',
                        'atelier' => 'Atelier',
                        'soutenance' => 'Soutenance',
                        'seminaire' => 'Séminaire',
                        'autre' => 'Autre'
                    ],
                    'defaultLabel' => 'Tous les types'
                ]
            ]
        ]);
    }

    private function renderTimeline(): void
    {
        if (empty($this->evenements)) {
            $this->renderEmptyState();
            return;
        }
        ?>
        <div class="timeline">
            <?php 
            $currentMonth = null;
            foreach ($this->evenements as $evenement): 
                $eventDate = strtotime($evenement['date_evenement']);
                $eventMonth = date('F Y', $eventDate);
                $isUpcoming = $eventDate >= time();
                
                if ($eventMonth !== $currentMonth):
                    $currentMonth = $eventMonth;
                    $this->renderMonthSeparator($evenement['date_evenement']);
                endif;
                
                $this->renderEventCard($evenement, $isUpcoming);
            endforeach;
            ?>
        </div>
        <?php
    }

    private function renderMonthSeparator(string $date): void
    {
        ?>
        <div class="month-separator">
            <h3><?= format_date($date, 'F Y') ?></h3>
        </div>
        <?php
    }

    private function renderEventCard(array $evenement, bool $isUpcoming): void
    {
        ?>
        <div class="event-card <?= !$isUpcoming ? 'past-event' : '' ?>">
            <div class="event-date">
                <div class="date-day"><?= format_date($evenement['date_evenement'], 'd') ?></div>
                <div class="date-month"><?= format_date($evenement['date_evenement'], 'M') ?></div>
                <?php if (!$isUpcoming): ?>
                <div class="past-label">Passé</div>
                <?php endif; ?>
            </div>

            <div class="event-content">
                <div class="event-header">
                    <div>
                        <h3><?= e($evenement['titre']) ?></h3>
                        <span class="badge badge-<?= e($evenement['type_evenement']) ?>">
                            <?= e(ucfirst($evenement['type_evenement'])) ?>
                        </span>
                    </div>
                </div>

                <?php if (!empty($evenement['description'])): ?>
                <p class="event-description">
                    <?= e($evenement['description']) ?>
                </p>
                <?php endif; ?>

                <div class="event-meta">
                    <?php if (!empty($evenement['lieu'])): ?>
                    <div class="meta-item">
                        <span class="meta-label">Lieu</span>
                        <span><?= e($evenement['lieu']) ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="meta-item">
                        <span class="meta-label">Heure</span>
                        <span><?= format_date($evenement['date_evenement'], 'H:i') ?></span>
                    </div>

                    <?php if (!empty($evenement['organisateur_nom'])): ?>
                    <div class="meta-item">
                        <span class="meta-label">Organisateur</span>
                        <span><?= e($evenement['organisateur_nom']) ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($evenement['lien_inscription'])): ?>
                    <div class="meta-item">
                        <a href="<?= e($evenement['lien_inscription']) ?>" 
                           target="_blank" 
                           class="inscription-link">
                            S'inscrire
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    private function renderEmptyState(): void
    {
        ?>
        <div class="empty-state">
            <h3>Aucun événement trouvé</h3>
            <p>Il n'y a pas d'événements prévus pour le moment.</p>
        </div>
        <?php
    }

    private function renderStyles(): void
    {
        ?>
        <style>
        /* Timeline */
        .timeline {
            position: relative;
            padding-left: 32px;
            margin-top: 24px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 12px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, var(--primary), var(--primary-light));
        }

        .month-separator {
            margin: 32px 0 20px 0;
            padding-left: 12px;
        }

        .month-separator h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
            text-transform: capitalize;
            margin: 0;
        }

        /* Event card */
        .event-card {
            position: relative;
            display: flex;
            gap: 20px;
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .event-card::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 28px;
            width: 10px;
            height: 10px;
            background: var(--primary);
            border: 3px solid white;
            border-radius: 50%;
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        .event-card:hover {
            transform: translateX(4px);
            box-shadow: var(--shadow-md);
        }

        .event-card.past-event {
            opacity: 0.65;
        }

        .event-card.past-event::before {
            background: var(--gray-400);
            box-shadow: 0 0 0 3px var(--gray-200);
        }

        /* Event date */
        .event-date {
            flex-shrink: 0;
            width: 75px;
            text-align: center;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: var(--border-radius-sm);
            color: white;
        }

        .date-day {
            font-size: 28px;
            font-weight: 700;
            line-height: 1;
        }

        .date-month {
            font-size: 13px;
            text-transform: uppercase;
            margin-top: 4px;
            opacity: 0.9;
        }

        .past-label {
            margin-top: 8px;
            padding: 3px 6px;
            background: rgba(255,255,255,0.2);
            border-radius: 4px;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 600;
        }

        /* Event content */
        .event-content {
            flex: 1;
            min-width: 0;
        }

        .event-header {
            margin-bottom: 12px;
        }

        .event-header h3 {
            margin: 0 0 8px 0;
            font-size: 18px;
            font-weight: 600;
            color: var(--gray-900);
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .badge-conference {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-atelier {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-soutenance {
            background: #e0e7ff;
            color: #4338ca;
        }

        .badge-seminaire {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-autre {
            background: var(--gray-100);
            color: var(--gray-700);
        }

        .event-description {
            color: var(--gray-700);
            line-height: 1.6;
            margin: 0 0 16px 0;
            font-size: 14px;
        }

        /* Event meta */
        .event-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            padding-top: 16px;
            border-top: 1px solid var(--border-color);
        }

        .meta-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
            font-size: 13px;
        }

        .meta-label {
            font-weight: 600;
            color: var(--gray-600);
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
        }

        .meta-item > span:not(.meta-label) {
            color: var(--gray-900);
            font-weight: 500;
        }

        .inscription-link {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            padding: 6px 12px;
            background: var(--primary-light);
            border-radius: var(--border-radius-sm);
            display: inline-block;
            transition: var(--transition);
        }

        .inscription-link:hover {
            background: var(--primary);
            color: white;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm);
            margin-top: 24px;
        }

        .empty-state h3 {
            margin: 0 0 8px 0;
            color: var(--gray-900);
            font-size: 18px;
        }

        .empty-state p {
            color: var(--gray-600);
            margin: 0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .timeline {
                padding-left: 20px;
            }
            
            .timeline::before {
                left: 4px;
            }
            
            .event-card {
                flex-direction: column;
                padding: 16px;
            }
            
            .event-card::before {
                left: -16px;
                top: 20px;
            }
            
            .event-date {
                width: 100%;
                max-width: 90px;
            }
            
            .event-meta {
                flex-direction: column;
                gap: 12px;
            }

            .month-separator {
                margin: 24px 0 16px 0;
            }
        }
        </style>
        <?php
    }

    private function renderFooter(): void
    {
        FooterComponent::render(['role' => 'membre']);
    }
}