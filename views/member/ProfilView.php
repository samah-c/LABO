<?php
/**
 * Vue du profil membre - FINAL VERSION
 */
require_once __DIR__ . '/../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../lib/components/NavigationComponent.php';
require_once __DIR__ . '/../../lib/components/PageHeaderComponent.php';
require_once __DIR__ . '/../../lib/components/FormComponent.php';
require_once __DIR__ . '/../../lib/components/FooterComponent.php';

class ProfilView {
    private $user;
    private $membre;
    private $stats;

    public function __construct($user, $membre, $stats) {
        $this->user = $user;
        $this->membre = $membre;
        $this->stats = $stats;
    }

    public function render() {
        $this->renderHeader();
        $this->renderNavigation();
        echo '<div class="container">';
        $this->renderBreadcrumbs();
        $this->renderFlashMessages();
        $this->renderProfileContent();
        echo '</div>';
        $this->renderStyles();
        $this->renderScripts();
        FooterComponent::render(['role' => 'membre']);
    }

    private function renderFlashMessages() {
        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-error">' . htmlspecialchars($_SESSION['error']) . '</div>';
            unset($_SESSION['error']);
        }
    }

    private function renderHeader() {
        HeaderComponent::render([
            'title' => 'Mon Profil - Espace Membre',
            'username' => session('username'),
            'role' => 'membre',
            'showLogout' => true
        ]);
    }

    private function renderNavigation() {
        NavigationComponent::renderSidebar('membre');
    }

    private function renderBreadcrumbs() {
        NavigationComponent::renderBreadcrumbs([
            ['label' => 'Tableau de bord', 'url' => base_url('membre/dashboard')],
            ['label' => 'Mon Profil']
        ]);
    }

    private function renderProfileContent() {
        ?>
        <div class="profil-container">
            <!-- Profile Header with Stats -->
            <div class="profil-header-card">
                <div class="header-content">
                    <div class="avatar-section">
                        <div class="avatar-wrapper">
                            <?php if (!empty($this->membre['photo'])): ?>
                                <img id="profile-avatar" 
                                     src="<?= base_url('uploads/photos/' . $this->membre['photo']) ?>" 
                                     alt="Photo de profil" 
                                     class="profile-avatar">
                            <?php else: ?>
                                <div id="profile-avatar" class="profile-avatar-placeholder">
                                    <?= strtoupper(substr($this->membre['prenom'] ?? 'U', 0, 1) . substr($this->membre['nom'] ?? 'S', 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <div class="avatar-overlay" onclick="document.getElementById('photo-input').click()">
                                <span class="camera-icon">📷</span>
                                <span class="upload-text">Changer</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="profile-info">
                        <h1><?= e($this->membre['prenom'] ?? '') ?> <?= e($this->membre['nom'] ?? '') ?></h1>
                        <p class="profile-grade"><?= e($this->membre['grade'] ?? 'Membre') ?></p>
                        <p class="profile-email"><?= e($this->user['email'] ?? '') ?></p>
                    </div>

                    <div class="profile-stats-inline">
                        <div class="stat-item">
                            <span class="stat-value"><?= $this->stats['total_projets'] ?? 0 ?></span>
                            <span class="stat-label">Projets</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?= $this->stats['total_publications'] ?? 0 ?></span>
                            <span class="stat-label">Publications</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?= $this->stats['projets_en_cours'] ?? 0 ?></span>
                            <span class="stat-label">En cours</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Forms Layout -->
            <div class="forms-layout">
                <!-- Left Column - Profile Form -->
                <div class="form-column">
                    <div class="form-card">
                        <div class="card-header">
                            <h2>Informations personnelles</h2>
                            <p class="card-subtitle">Gérez vos informations de profil</p>
                        </div>
                        
                        <form id="profil-form" method="POST" action="<?= base_url('membre/profil/update') ?>" enctype="multipart/form-data">
                            <!-- Hidden photo input -->
                            <input type="file" 
                                   id="photo-input" 
                                   name="photo" 
                                   accept="image/jpeg,image/png,image/gif,image/jpg"
                                   style="display: none;">

                            <div class="form-section">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="nom">Nom *</label>
                                        <input type="text" id="nom" name="nom" 
                                               value="<?= e($this->membre['nom'] ?? '') ?>" 
                                               placeholder="Votre nom" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="prenom">Prénom *</label>
                                        <input type="text" id="prenom" name="prenom" 
                                               value="<?= e($this->membre['prenom'] ?? '') ?>" 
                                               placeholder="Votre prénom" required>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="email">Email *</label>
                                        <input type="email" id="email" name="email" 
                                               value="<?= e($this->user['email'] ?? '') ?>" 
                                               placeholder="email@example.com" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="telephone">Téléphone</label>
                                        <input type="tel" id="telephone" name="telephone" 
                                               value="<?= e($this->membre['telephone'] ?? '') ?>" 
                                               placeholder="+213 XX XX XX XX">
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h3 class="section-title">Informations professionnelles</h3>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="poste">Poste</label>
                                        <select id="poste" name="poste">
                                            <option value="enseignant" <?= ($this->membre['poste'] ?? '') === 'enseignant' ? 'selected' : '' ?>>Enseignant</option>
                                            <option value="doctorant" <?= ($this->membre['poste'] ?? '') === 'doctorant' ? 'selected' : '' ?>>Doctorant</option>
                                            <option value="etudiant" <?= ($this->membre['poste'] ?? '') === 'etudiant' ? 'selected' : '' ?>>Étudiant</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="grade">Grade</label>
                                        <input type="text" id="grade" name="grade" 
                                               value="<?= e($this->membre['grade'] ?? '') ?>" 
                                               placeholder="Ex: Professeur, Docteur">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="specialite">Spécialité / Domaine de recherche</label>
                                    <input type="text" id="specialite" name="specialite" 
                                           value="<?= e($this->membre['specialite'] ?? '') ?>" 
                                           placeholder="Votre domaine d'expertise">
                                </div>

                                <div class="form-group">
                                    <label for="adresse">Adresse</label>
                                    <input type="text" id="adresse" name="adresse" 
                                           value="<?= e($this->membre['adresse'] ?? '') ?>" 
                                           placeholder="Adresse complète">
                                </div>
                            </div>

                            <div class="form-section">
                                <h3 class="section-title">Biographie</h3>
                                <div class="form-group">
                                    <label for="biographie">À propos de vous</label>
                                    <textarea id="biographie" name="biographie" rows="4" 
                                              placeholder="Présentez-vous en quelques mots..."><?= e($this->membre['biographie'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary" onclick="window.location.reload()">
                                    Annuler
                                </button>
                                <button type="submit" id="btn-save" class="btn btn-primary">
                                     Enregistrer les modifications
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column - Password Form -->
                <div class="form-column-sidebar">
                    <div class="form-card">
                        <div class="card-header">
                            <h2>Sécurité</h2>
                            <p class="card-subtitle">Changez votre mot de passe</p>
                        </div>
                        
                        <form id="password-form" method="POST" action="<?= base_url('membre/profil/password') ?>">
                            <div class="form-group">
                                <label for="current_password">Mot de passe actuel *</label>
                                <input type="password" id="current_password" name="current_password" 
                                       placeholder="••••••••" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">Nouveau mot de passe *</label>
                                <input type="password" id="new_password" name="new_password" 
                                       placeholder="••••••••" required minlength="6">
                                <small class="form-hint">Minimum 6 caractères</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirmer le mot de passe *</label>
                                <input type="password" id="confirm_password" name="confirm_password" 
                                       placeholder="••••••••" required>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary btn-block">
                                     Changer le mot de passe
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Info Card -->
                    <div class="info-card">
                        <h3> Conseils de sécurité</h3>
                        <ul class="tips-list">
                            <li>Utilisez au moins 8 caractères</li>
                            <li>Combinez lettres et chiffres</li>
                            <li>Évitez les mots courants</li>
                            <li>Ne partagez jamais votre mot de passe</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function renderStyles() {
        ?>
        <style>
        /* Alerts */
        .alert {
            padding: 16px 20px;
            margin-bottom: 24px;
            border-radius: var(--border-radius-lg);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert::before {
            font-size: 20px;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        .alert-success::before {
            content: '✓';
        }
        
        .alert-error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        
        .alert-error::before {
            content: '⚠';
        }

        /* Container */
        .profil-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Profile Header Card */
        .profil-header-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: var(--border-radius-xl);
            padding: 32px;
            margin-bottom: 28px;
            box-shadow: var(--shadow-lg);
            color: white;
        }

        .header-content {
            display: flex;
            align-items: center;
            gap: 28px;
        }

        /* Avatar Section with Hover Effect */
        .avatar-section {
            flex-shrink: 0;
        }

        .avatar-wrapper {
            position: relative;
            width: 120px;
            height: 120px;
            cursor: pointer;
            transition: var(--transition);
        }

        .avatar-wrapper:hover {
            transform: scale(1.05);
        }

        .profile-avatar,
        .profile-avatar-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            transition: var(--transition);
        }

        .profile-avatar {
            object-fit: cover;
        }

        .profile-avatar-placeholder {
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: 700;
            color: white;
        }

        /* Avatar Overlay */
        .avatar-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            cursor: pointer;
        }

        .avatar-wrapper:hover .avatar-overlay {
            opacity: 1;
        }

        .camera-icon {
            font-size: 32px;
            margin-bottom: 4px;
        }

        .upload-text {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Profile Info */
        .profile-info {
            flex: 1;
        }

        .profile-info h1 {
            margin: 0 0 8px 0;
            font-size: 28px;
            font-weight: 700;
        }

        .profile-grade {
            font-size: 16px;
            margin: 0 0 4px 0;
            opacity: 0.95;
            font-weight: 500;
        }

        .profile-email {
            font-size: 14px;
            margin: 0;
            opacity: 0.85;
        }

        /* Inline Stats */
        .profile-stats-inline {
            display: flex;
            gap: 24px;
        }

        .stat-item {
            text-align: center;
            background: rgba(255, 255, 255, 0.15);
            padding: 16px 24px;
            border-radius: var(--border-radius-lg);
            backdrop-filter: blur(10px);
            min-width: 90px;
        }

        .stat-value {
            display: block;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 4px;
            line-height: 1;
        }

        .stat-label {
            display: block;
            font-size: 12px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Forms Layout */
        .forms-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }

        /* Form Cards */
        .form-card,
        .info-card {
            background: var(--bg-card);
            border-radius: var(--border-radius-xl);
            padding: 28px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            margin-bottom: 20px;
        }

        .card-header {
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--border-color);
        }

        .card-header h2 {
            margin: 0 0 4px 0;
            font-size: 20px;
            font-weight: 600;
            color: var(--gray-900);
        }

        .card-subtitle {
            margin: 0;
            font-size: 13px;
            color: var(--gray-600);
        }

        /* Form Sections */
        .form-section {
            margin-bottom: 28px;
        }

        .section-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--gray-700);
            margin: 0 0 16px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Form Elements */
        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            font-size: 13px;
            color: var(--gray-700);
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-sm);
            font-size: 14px;
            transition: all 0.2s ease;
            background: white;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(91, 127, 255, 0.1);
        }

        .form-hint {
            font-size: 11px;
            color: var(--gray-500);
            margin-top: 4px;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        .btn-block {
            width: 100%;
        }

        /* Info Card */
        .info-card {
            padding: 20px;
        }

        .info-card h3 {
            font-size: 15px;
            font-weight: 600;
            margin: 0 0 12px 0;
            color: var(--gray-900);
        }

        .tips-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .tips-list li {
            padding: 8px 0;
            font-size: 13px;
            color: var(--gray-600);
            position: relative;
            padding-left: 20px;
        }

        .tips-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--primary);
            font-weight: 700;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .forms-layout {
                grid-template-columns: 1fr;
            }

            .form-column-sidebar {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .profil-header-card {
                padding: 20px;
            }

            .header-content {
                flex-direction: column;
                text-align: center;
            }

            .profile-stats-inline {
                width: 100%;
                justify-content: center;
            }

            .form-card,
            .info-card {
                padding: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .form-column-sidebar {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column-reverse;
            }

            .form-actions button {
                width: 100%;
            }
        }
        </style>
        <?php
    }

    private function renderScripts() {
        ?>
        <script>
        // Photo preview with instant update
        document.getElementById('photo-input').addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (!file) return;
            
            // Validate file
            if (!file.type.startsWith('image/')) {
                alert('Veuillez sélectionner une image');
                this.value = '';
                return;
            }
            
            if (file.size > 5 * 1024 * 1024) {
                alert(' L\'image ne doit pas dépasser 5 MB');
                this.value = '';
                return;
            }
            
            // Preview the image INSTANTLY
            const reader = new FileReader();
            reader.onload = function(event) {
                const avatar = document.getElementById('profile-avatar');
                
                if (avatar.tagName === 'IMG') {
                    // Already an image, just update src
                    avatar.src = event.target.result;
                } else {
                    // It's a placeholder div, replace with img
                    const img = document.createElement('img');
                    img.id = 'profile-avatar';
                    img.src = event.target.result;
                    img.alt = 'Photo de profil';
                    img.className = 'profile-avatar';
                    
                    avatar.parentNode.replaceChild(img, avatar);
                }
                
                // Show success message
                showNotification('✓ Photo sélectionnée! Cliquez sur "Enregistrer" pour sauvegarder.', 'success');
            };
            
            reader.readAsDataURL(file);
        });

        // Show notification
        function showNotification(message, type) {
            // Remove existing notifications
            const existing = document.querySelectorAll('.notification');
            existing.forEach(n => n.remove());
            
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 16px 24px;
                background: ${type === 'success' ? 'linear-gradient(135deg, #d1fae5, #a7f3d0)' : 'linear-gradient(135deg, #fee2e2, #fecaca)'};
                color: ${type === 'success' ? '#065f46' : '#991b1b'};
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                font-weight: 500;
                font-size: 14px;
                z-index: 10000;
                animation: slideIn 0.3s ease;
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Add animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);

        // Form submission prevention for double-click
        let isSubmitting = false;
        document.getElementById('profil-form').addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            isSubmitting = true;
            document.getElementById('btn-save').textContent = '⏳ Enregistrement...';
            document.getElementById('btn-save').disabled = true;
        });
        </script>
        <?php
    }
}