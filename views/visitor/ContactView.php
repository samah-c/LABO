<?php
/**
 * ContactView.php - Vue de la page de contact
 * À placer dans : /TDW_project/app/views/public/ContactView.php
 */

require_once __DIR__ . '/../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../lib/components/NavigationComponent.php';
require_once __DIR__ . '/../../lib/components/PageHeaderComponent.php';
require_once __DIR__ . '/../../lib/components/FormComponent.php';
require_once __DIR__ . '/../../lib/components/FooterComponent.php';

class ContactView
{
    private array $formData;
    private ?string $successMessage;
    private ?string $errorMessage;

    public function __construct(
        array $formData = [],
        ?string $successMessage = null,
        ?string $errorMessage = null
    ) {
        $this->formData = $formData;
        $this->successMessage = $successMessage;
        $this->errorMessage = $errorMessage;
    }

    /**
     * Rendu complet de la vue
     */
    public function render(): void
    {
        $this->renderHeader();
        $this->renderNavigation();
        echo '<div class="visitor-container">';
        $this->renderBanner();
        echo '<div class="container contact-container">';
        $this->renderAlerts();
        echo '<div class="contact-layout">';
        $this->renderContactForm();
        $this->renderContactInfo();
        echo '</div>'; // contact-layout
        echo '</div>'; // container
        echo '</div>'; // visitor-container
        $this->renderStyles();
        $this->renderScripts();
        $this->renderFooter();
    }

    /**
     * Rendu de l'en-tête
     */
    private function renderHeader(): void
    {
        HeaderComponent::render([
            'title' => 'Contact - Laboratoire TDW',
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
        NavigationComponent::renderHorizontalMenu('contact');
    }

    /**
     * Rendu de la bannière
     */
    private function renderBanner(): void
    {
        ?>
        <section class="page-banner">
            <div class="banner-content">
                <h1>Contactez-nous</h1>
                <p>Nous sommes à votre écoute pour toute collaboration ou question</p>
            </div>
        </section>
        <?php
    }

    /**
     * Rendu des alertes
     */
    private function renderAlerts(): void
    {
        if ($this->successMessage) {
            ?>
            <div class="alert alert-success">
                <strong>✓</strong> <?= htmlspecialchars($this->successMessage) ?>
            </div>
            <?php
        }

        if ($this->errorMessage) {
            ?>
            <div class="alert alert-error">
                <strong>✗</strong> <?= htmlspecialchars($this->errorMessage) ?>
            </div>
            <?php
        }
    }

    /**
     * Rendu du formulaire de contact
     */
    private function renderContactForm(): void
    {
        ?>
        <div class="contact-form-section">
            <div class="detail-card">
                <h2>Envoyez-nous un message</h2>
                <p class="form-description">
                    Remplissez le formulaire ci-dessous et nous vous répondrons dans les plus brefs délais.
                </p>

                <?php
                FormComponent::render([
                    'action' => base_url('contact/envoyer'),
                    'method' => 'POST',
                    'formClass' => 'contact-form',
                    'submitText' => 'Envoyer le message',
                    'fields' => [
                        [
                            'type' => 'text',
                            'name' => 'nom',
                            'label' => 'Nom complet',
                            'placeholder' => 'Entrez votre nom complet',
                            'required' => true,
                            'value' => $this->formData['nom'] ?? '',
                            'attributes' => [
                                'minlength' => '3',
                                'maxlength' => '100',
                                'id' => 'nom'
                            ]
                        ],
                        [
                            'type' => 'email',
                            'name' => 'email',
                            'label' => 'Adresse email',
                            'placeholder' => 'votre.email@exemple.com',
                            'required' => true,
                            'value' => $this->formData['email'] ?? '',
                            'attributes' => ['id' => 'email']
                        ],
                        [
                            'type' => 'text',
                            'name' => 'organisation',
                            'label' => 'Organisation / Université',
                            'placeholder' => 'Nom de votre organisation (optionnel)',
                            'value' => $this->formData['organisation'] ?? '',
                            'attributes' => [
                                'maxlength' => '150',
                                'id' => 'organisation'
                            ]
                        ],
                        [
                            'type' => 'select',
                            'name' => 'sujet',
                            'label' => 'Sujet',
                            'required' => true,
                            'value' => $this->formData['sujet'] ?? '',
                            'placeholder' => '-- Sélectionnez un sujet --',
                            'options' => [
                                'collaboration' => 'Proposition de collaboration',
                                'stage' => 'Demande de stage',
                                'these' => 'Information sur les thèses',
                                'partenariat' => 'Partenariat institutionnel',
                                'publication' => 'Question sur une publication',
                                'equipement' => 'Accès aux équipements',
                                'autre' => 'Autre demande'
                            ],
                            'attributes' => ['id' => 'sujet']
                        ],
                        [
                            'type' => 'textarea',
                            'name' => 'message',
                            'label' => 'Message',
                            'placeholder' => 'Décrivez votre demande en détail...',
                            'required' => true,
                            'value' => $this->formData['message'] ?? '',
                            'attributes' => [
                                'rows' => '8',
                                'minlength' => '20',
                                'maxlength' => '2000',
                                'id' => 'message'
                            ]
                        ]
                    ]
                ]);
                ?>

                <div class="character-count">
                    <span id="char-count">0</span> / 2000 caractères
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu des informations de contact
     */
    private function renderContactInfo(): void
    {
        ?>
        <aside class="contact-info-section">
            <!-- Coordonnées -->
            <div class="detail-card">
                <h2>Coordonnées</h2>
                <div class="contact-info-list">
                    <?php
                    $contactInfos = [
                        [
                            'icon' => '📍',
                            'title' => 'Adresse',
                            'content' => "École Supérieure d'Informatique<br>Oued Smar, Alger, Algérie"
                        ],
                        [
                            'icon' => '📧',
                            'title' => 'Email',
                            'content' => '<a href="mailto:contact@laboratoire-tdw.dz">contact@laboratoire-tdw.dz</a>'
                        ],
                        [
                            'icon' => '📞',
                            'title' => 'Téléphone',
                            'content' => '+213 (0)23 54 16 89'
                        ],
                        [
                            'icon' => '🕐',
                            'title' => 'Horaires',
                            'content' => 'Dimanche - Jeudi<br>8h00 - 17h00'
                        ]
                    ];

                    foreach ($contactInfos as $info) {
                        $this->renderContactInfoItem($info);
                    }
                    ?>
                </div>
            </div>

            <!-- Réseaux sociaux -->
            <div class="detail-card">
                <h2>Suivez-nous</h2>
                <div class="social-links">
                    <?php
                    $socialLinks = [
                        ['icon' => 'facebook.png', 'name' => 'Facebook', 'url' => 'https://facebook.com'],
                        ['icon' => 'twitter.png', 'name' => 'Twitter', 'url' => 'https://twitter.com'],
                        ['icon' => 'esi.png', 'name' => 'Site de l\'ESI', 'url' => 'https://www.esi.dz'],
                        ['icon' => 'linkedin.png', 'name' => 'LinkedIn', 'url' => 'https://linkedin.com']
                    ];

                    foreach ($socialLinks as $link) {
                        $this->renderSocialLink($link);
                    }
                    ?>
                </div>
            </div>

            <!-- FAQ -->
            <div class="detail-card">
                <h2>Questions fréquentes</h2>
                <div class="faq-list">
                    <?php
                    $faqs = [
                        [
                            'question' => 'Délai de réponse ?',
                            'answer' => 'Nous répondons généralement sous 48h ouvrées.'
                        ],
                        [
                            'question' => 'Demande de stage ?',
                            'answer' => 'Envoyez votre CV et lettre de motivation via ce formulaire.'
                        ],
                        [
                            'question' => 'Collaboration scientifique ?',
                            'answer' => 'Décrivez votre projet et vos objectifs de collaboration.'
                        ]
                    ];

                    foreach ($faqs as $faq) {
                        $this->renderFaqItem($faq);
                    }
                    ?>
                </div>
            </div>

            <!-- Liens rapides -->
            <div class="quick-links">
                <?php
                $quickLinks = [
                    ['url' => 'projets', 'label' => 'Nos projets'],
                    ['url' => 'publications', 'label' => 'Publications'],
                    ['url' => 'membres', 'label' => 'Notre équipe']
                ];

                foreach ($quickLinks as $link) {
                    ?>
                    <a href="<?= base_url($link['url']) ?>" class="quick-link-btn">
                        <?= htmlspecialchars($link['label']) ?>
                    </a>
                    <?php
                }
                ?>
            </div>
        </aside>
        <?php
    }

    /**
     * Rendu d'un item d'information de contact
     */
    private function renderContactInfoItem(array $info): void
    {
        ?>
        <div class="contact-info-item">
            <div class="info-icon"><?= $info['icon'] ?></div>
            <div class="info-content">
                <strong><?= htmlspecialchars($info['title']) ?></strong>
                <p><?= $info['content'] ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu d'un lien social
     */
    private function renderSocialLink(array $link): void
    {
        ?>
        <a href="<?= htmlspecialchars($link['url']) ?>" 
           class="social-link" 
           target="_blank" 
           rel="noopener">
            <img src="<?= base_url('assets/images/icons/' . $link['icon']) ?>" 
                 alt="<?= htmlspecialchars($link['name']) ?>"
                 width="24" height="24">
            <?= htmlspecialchars($link['name']) ?>
        </a>
        <?php
    }

    /**
     * Rendu d'un item FAQ
     */
    private function renderFaqItem(array $faq): void
    {
        ?>
        <div class="faq-item">
            <strong><?= htmlspecialchars($faq['question']) ?></strong>
            <p><?= htmlspecialchars($faq['answer']) ?></p>
        </div>
        <?php
    }

    /**
     * Rendu des styles CSS
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

        .contact-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .alert {
            padding: 16px 20px;
            border-radius: var(--border-radius-lg);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 15px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: #D1FAE5;
            color: #065F46;
            border: 1px solid #6EE7B7;
        }

        .alert-error {
            background: #FEE2E2;
            color: #991B1B;
            border: 1px solid #FCA5A5;
        }

        .contact-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
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

        .form-description {
            color: var(--gray-600);
            margin-bottom: 24px;
            line-height: 1.6;
        }

        .contact-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .character-count {
            font-size: 13px;
            color: var(--gray-500);
            text-align: right;
            margin-top: -12px;
            margin-bottom: 12px;
        }

        .contact-info-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .contact-info-item {
            display: flex;
            gap: 16px;
            align-items: flex-start;
            padding: 12px;
            border-radius: var(--border-radius-sm);
            transition: var(--transition);
        }

        .contact-info-item:hover {
            background: var(--gray-50);
        }

        .info-icon {
            font-size: 24px;
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--gray-100);
            border-radius: 50%;
        }

        .info-content {
            flex: 1;
        }

        .info-content strong {
            display: block;
            font-size: 14px;
            color: var(--gray-900);
            margin-bottom: 4px;
        }

        .info-content p {
            font-size: 14px;
            color: var(--gray-600);
            line-height: 1.6;
            margin: 0;
        }

        .info-content a {
            color: var(--primary);
            text-decoration: none;
            transition: var(--transition);
        }

        .info-content a:hover {
            text-decoration: underline;
            color: var(--primary-dark);
        }

        .social-links {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .social-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: var(--gray-50);
            border-radius: var(--border-radius-sm);
            color: var(--gray-700);
            text-decoration: none;
            transition: var(--transition);
            font-weight: 500;
        }

        .social-link:hover {
            background: var(--primary);
            color: white;
            transform: translateX(4px);
        }

        .social-link img {
            width: 24px;
            height: 24px;
            transition: var(--transition);
        }

        .faq-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .faq-item {
            padding: 16px;
            background: var(--gray-50);
            border-radius: var(--border-radius-sm);
            border-left: 3px solid var(--primary);
            transition: var(--transition);
        }

        .faq-item:hover {
            background: var(--gray-100);
            transform: translateX(4px);
        }

        .faq-item strong {
            display: block;
            color: var(--gray-900);
            margin-bottom: 6px;
            font-size: 14px;
        }

        .faq-item p {
            font-size: 13px;
            color: var(--gray-600);
            margin: 0;
            line-height: 1.5;
        }

        .quick-links {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .quick-link-btn {
            display: block;
            padding: 12px 16px;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-sm);
            color: var(--gray-700);
            text-decoration: none;
            text-align: center;
            transition: var(--transition);
            font-size: 14px;
            font-weight: 500;
        }

        .quick-link-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        @media (max-width: 1024px) {
            .contact-layout {
                grid-template-columns: 1fr;
            }
            
            .contact-info-section {
                order: -1;
            }
        }

        @media (max-width: 768px) {
            .banner-content h1 {
                font-size: 32px;
            }
            
            .banner-content p {
                font-size: 16px;
            }
            
            .contact-container {
                padding: 20px;
            }
            
            .detail-card {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .page-banner {
                padding: 40px 20px;
            }
            
            .banner-content h1 {
                font-size: 28px;
            }
        }
        </style>
        <?php
    }

    /**
     * Rendu des scripts JavaScript
     */
    private function renderScripts(): void
    {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const messageField = document.getElementById('message');
            const charCount = document.getElementById('char-count');

            // Compteur de caractères
            if (messageField && charCount) {
                messageField.addEventListener('input', function() {
                    const count = this.value.length;
                    charCount.textContent = count;
                    
                    if (count > 2000) {
                        charCount.style.color = '#EF4444';
                    } else if (count > 1800) {
                        charCount.style.color = '#F59E0B';
                    } else {
                        charCount.style.color = 'var(--gray-500)';
                    }
                });
                
                // Initialiser le compteur
                const initialCount = messageField.value.length;
                charCount.textContent = initialCount;
            }

            // Animation de soumission
            const form = document.querySelector('.contact-form');
            if (form) {
                form.addEventListener('submit', function() {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span>Envoi en cours...</span>';
                    }
                });
            }

            // Auto-hide alerts après 5 secondes
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });
        </script>
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