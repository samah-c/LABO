<?php
/**
 * Page de contact pour les visiteurs
 * À placer dans : views/visitor/contact.php
 */

require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/ViewComponents.php';

ViewComponents::renderHeader([
    'title' => 'Contact - Laboratoire TDW',
    'role' => 'visiteur',
    'showLogout' => false,
    'showLoginButton' => true,
    'additionalCss' => [],
    'additionalJs' => []
]);
?>

<div class="visitor-container">
    <!-- En-tête de la page -->
    <section class="page-banner">
        <div class="banner-content">
            <h1>Contactez-nous</h1>
            <p>Nous sommes à votre écoute pour toute collaboration ou question</p>
        </div>
    </section>

    <div class="container contact-container">
        <!-- Messages de feedback -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <strong>✓</strong> <?= e($_SESSION['success']) ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <strong>✗</strong> <?= e($_SESSION['error']) ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="contact-layout">
            <!-- Formulaire de contact -->
            <div class="contact-form-section">
                <div class="detail-card">
                    <h2>Envoyez-nous un message</h2>
                    <p class="form-description">
                        Remplissez le formulaire ci-dessous et nous vous répondrons dans les plus brefs délais.
                    </p>

                    <form id="contact-form" method="POST" action="<?= base_url('contact/envoyer') ?>" class="contact-form">
                        <!-- Nom complet -->
                        <div class="form-group">
                            <label for="nom" class="form-label required">Nom complet</label>
                            <input 
                                type="text" 
                                id="nom" 
                                name="nom" 
                                class="form-control"
                                placeholder="Entrez votre nom complet"
                                required
                                minlength="3"
                                maxlength="100"
                                value="<?= e($_POST['nom'] ?? '') ?>"
                            >
                            <span class="form-error" id="nom-error"></span>
                        </div>

                        <!-- Email -->
                        <div class="form-group">
                            <label for="email" class="form-label required">Adresse email</label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-control"
                                placeholder="votre.email@exemple.com"
                                required
                                value="<?= e($_POST['email'] ?? '') ?>"
                            >
                            <span class="form-error" id="email-error"></span>
                        </div>

                        <!-- Organisation -->
                        <div class="form-group">
                            <label for="organisation" class="form-label">Organisation / Université</label>
                            <input 
                                type="text" 
                                id="organisation" 
                                name="organisation" 
                                class="form-control"
                                placeholder="Nom de votre organisation (optionnel)"
                                maxlength="150"
                                value="<?= e($_POST['organisation'] ?? '') ?>"
                            >
                        </div>

                        <!-- Sujet -->
                        <div class="form-group">
                            <label for="sujet" class="form-label required">Sujet</label>
                            <select id="sujet" name="sujet" class="form-control" required>
                                <option value="">-- Sélectionnez un sujet --</option>
                                <option value="collaboration" <?= ($_POST['sujet'] ?? '') === 'collaboration' ? 'selected' : '' ?>>
                                    Proposition de collaboration
                                </option>
                                <option value="stage" <?= ($_POST['sujet'] ?? '') === 'stage' ? 'selected' : '' ?>>
                                    Demande de stage
                                </option>
                                <option value="these" <?= ($_POST['sujet'] ?? '') === 'these' ? 'selected' : '' ?>>
                                    Information sur les thèses
                                </option>
                                <option value="partenariat" <?= ($_POST['sujet'] ?? '') === 'partenariat' ? 'selected' : '' ?>>
                                    Partenariat institutionnel
                                </option>
                                <option value="publication" <?= ($_POST['sujet'] ?? '') === 'publication' ? 'selected' : '' ?>>
                                    Question sur une publication
                                </option>
                                <option value="equipement" <?= ($_POST['sujet'] ?? '') === 'equipement' ? 'selected' : '' ?>>
                                    Accès aux équipements
                                </option>
                                <option value="autre" <?= ($_POST['sujet'] ?? '') === 'autre' ? 'selected' : '' ?>>
                                    Autre demande
                                </option>
                            </select>
                            <span class="form-error" id="sujet-error"></span>
                        </div>

                        <!-- Message -->
                        <div class="form-group">
                            <label for="message" class="form-label required">Message</label>
                            <textarea 
                                id="message" 
                                name="message" 
                                class="form-control"
                                rows="8"
                                placeholder="Décrivez votre demande en détail..."
                                required
                                minlength="20"
                                maxlength="2000"
                            ><?= e($_POST['message'] ?? '') ?></textarea>
                            <div class="character-count">
                                <span id="char-count">0</span> / 2000 caractères
                            </div>
                            <span class="form-error" id="message-error"></span>
                        </div>

                        <!-- Boutons -->
                        <div class="form-actions">
                            <button type="reset" class="btn-secondary">
                                Réinitialiser
                            </button>
                            <button type="submit" class="btn-primary" id="submit-btn">
                                <span class="btn-text">Envoyer le message</span>
                                <span class="btn-loading" style="display: none;">
                                    Envoi en cours...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Informations de contact -->
            <aside class="contact-info-section">
                <!-- Coordonnées -->
                <div class="detail-card">
                    <h2>Coordonnées</h2>
                    <div class="contact-info-list">
                        <div class="contact-info-item">
                            <div class="info-icon">📍</div>
                            <div class="info-content">
                                <strong>Adresse</strong>
                                <p>École Supérieure d'Informatique<br>
                                Oued Smar, Alger, Algérie</p>
                            </div>
                        </div>

                        <div class="contact-info-item">
                            <div class="info-icon">📧</div>
                            <div class="info-content">
                                <strong>Email</strong>
                                <p><a href="mailto:contact@laboratoire-tdw.dz">contact@laboratoire-tdw.dz</a></p>
                            </div>
                        </div>

                        <div class="contact-info-item">
                            <div class="info-icon">📞</div>
                            <div class="info-content">
                                <strong>Téléphone</strong>
                                <p>+213 (0)23 54 16 89</p>
                            </div>
                        </div>

                        <div class="contact-info-item">
                            <div class="info-icon">🕐</div>
                            <div class="info-content">
                                <strong>Horaires</strong>
                                <p>Dimanche - Jeudi<br>
                                8h00 - 17h00</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Réseaux sociaux -->
                <div class="detail-card">
                    <h2>Suivez-nous</h2>
                    <div class="social-links">
                        <a href="#" class="social-link" target="_blank" rel="noopener">
                            <img src="<?= base_url('assets/images/icons/facebook.png') ?>" alt="Facebook">
                            Facebook
                        </a>
                        <a href="#" class="social-link" target="_blank" rel="noopener">
                            <img src="<?= base_url('assets/images/icons/twitter.png') ?>" alt="Twitter">
                            Twitter
                        </a>
                        <a href="#" class="social-link" target="_blank" rel="noopener">
                            <img src="<?= base_url('assets/images/icons/linkedin.png') ?>" alt="LinkedIn">
                            LinkedIn
                        </a>
                    </div>
                </div>

                <!-- FAQ rapide -->
                <div class="detail-card">
                    <h2>Questions fréquentes</h2>
                    <div class="faq-list">
                        <div class="faq-item">
                            <strong>Délai de réponse ?</strong>
                            <p>Nous répondons généralement sous 48h ouvrées.</p>
                        </div>
                        <div class="faq-item">
                            <strong>Demande de stage ?</strong>
                            <p>Envoyez votre CV et lettre de motivation via ce formulaire.</p>
                        </div>
                        <div class="faq-item">
                            <strong>Collaboration scientifique ?</strong>
                            <p>Décrivez votre projet et vos objectifs de collaboration.</p>
                        </div>
                    </div>
                </div>

                <!-- Liens utiles -->
                <div class="quick-links">
                    <a href="<?= base_url('projets') ?>" class="quick-link-btn">
                        Nos projets
                    </a>
                    <a href="<?= base_url('publications') ?>" class="quick-link-btn">
                        Publications
                    </a>
                    <a href="<?= base_url('membres') ?>" class="quick-link-btn">
                        Notre équipe
                    </a>
                </div>
            </aside>
        </div>
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

.contact-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

/* Alerts */
.alert {
    padding: 16px 20px;
    border-radius: var(--border-radius-lg);
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 15px;
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

/* Layout */
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

/* Formulaire */
.contact-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-label {
    font-weight: 600;
    color: var(--gray-700);
    font-size: 14px;
}

.form-label.required::after {
    content: ' *';
    color: #EF4444;
}

.form-control {
    padding: 12px 16px;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-sm);
    font-size: 15px;
    transition: var(--transition);
    font-family: inherit;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(91, 127, 255, 0.1);
}

textarea.form-control {
    resize: vertical;
    min-height: 120px;
}

.character-count {
    font-size: 13px;
    color: var(--gray-500);
    text-align: right;
    margin-top: 4px;
}

.form-error {
    font-size: 13px;
    color: #EF4444;
    display: none;
}

.form-error.active {
    display: block;
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 8px;
}

.btn-primary,
.btn-secondary {
    padding: 12px 28px;
    border-radius: var(--border-radius-sm);
    font-weight: 600;
    font-size: 15px;
    border: none;
    cursor: pointer;
    transition: var(--transition);
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-primary:disabled {
    background: var(--gray-400);
    cursor: not-allowed;
    transform: none;
}

.btn-secondary {
    background: var(--gray-200);
    color: var(--gray-700);
}

.btn-secondary:hover {
    background: var(--gray-300);
}

/* Informations de contact */
.contact-info-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.contact-info-item {
    display: flex;
    gap: 16px;
    align-items: flex-start;
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
}

.info-content a:hover {
    text-decoration: underline;
}

/* Réseaux sociaux */
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
}

.social-link:hover {
    background: var(--primary);
    color: white;
    transform: translateX(4px);
}

.social-link img {
    width: 24px;
    height: 24px;
}

/* FAQ */
.faq-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.faq-item {
    padding: 12px;
    background: var(--gray-50);
    border-radius: var(--border-radius-sm);
}

.faq-item strong {
    display: block;
    color: var(--gray-900);
    margin-bottom: 4px;
    font-size: 14px;
}

.faq-item p {
    font-size: 13px;
    color: var(--gray-600);
    margin: 0;
    line-height: 1.5;
}

/* Liens rapides */
.quick-links {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.quick-link-btn {
    display: block;
    padding: 10px 16px;
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
}

/* Responsive */
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
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn-primary,
    .btn-secondary {
        width: 100%;
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('contact-form');
    const messageField = document.getElementById('message');
    const charCount = document.getElementById('char-count');
    const submitBtn = document.getElementById('submit-btn');

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
        charCount.textContent = messageField.value.length;
    }

    // Validation du formulaire
    if (form) {
        form.addEventListener('submit', function(e) {
            let isValid = true;

            // Validation nom
            const nom = document.getElementById('nom');
            if (nom.value.trim().length < 3) {
                showError('nom', 'Le nom doit contenir au moins 3 caractères');
                isValid = false;
            } else {
                hideError('nom');
            }

            // Validation email
            const email = document.getElementById('email');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email.value)) {
                showError('email', 'Veuillez entrer une adresse email valide');
                isValid = false;
            } else {
                hideError('email');
            }

            // Validation sujet
            const sujet = document.getElementById('sujet');
            if (!sujet.value) {
                showError('sujet', 'Veuillez sélectionner un sujet');
                isValid = false;
            } else {
                hideError('sujet');
            }

            // Validation message
            const message = document.getElementById('message');
            if (message.value.trim().length < 20) {
                showError('message', 'Le message doit contenir au moins 20 caractères');
                isValid = false;
            } else if (message.value.length > 2000) {
                showError('message', 'Le message ne peut pas dépasser 2000 caractères');
                isValid = false;
            } else {
                hideError('message');
            }

            if (!isValid) {
                e.preventDefault();
                return false;
            }

            // Afficher l'état de chargement
            submitBtn.disabled = true;
            document.querySelector('.btn-text').style.display = 'none';
            document.querySelector('.btn-loading').style.display = 'inline';
        });
    }

    function showError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const error = document.getElementById(fieldId + '-error');
        
        if (field) {
            field.style.borderColor = '#EF4444';
        }
        
        if (error) {
            error.textContent = message;
            error.classList.add('active');
        }
    }

    function hideError(fieldId) {
        const field = document.getElementById(fieldId);
        const error = document.getElementById(fieldId + '-error');
        
        if (field) {
            field.style.borderColor = '';
        }
        
        if (error) {
            error.classList.remove('active');
        }
    }

    // Validation en temps réel
    const inputs = form.querySelectorAll('.form-control');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value.trim()) {
                hideError(this.id);
            }
        });
    });
});
</script>

<?php ViewComponents::renderFooter(['role' => 'visiteur']); ?>