<?php
/**
 * Page profil membre - Affichage et édition du profil
 */

require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/ViewComponents.php';

ViewComponents::renderHeader([
    'title' => 'Mon Profil - Espace Membre',
    'role' => 'membre',
    'showLogout' => true,
    'additionalJs' => [base_url('assets/js/member/profil-handler.js')]
]);

// Récupérer les informations utilisateur depuis la session
$user = [
    'username' => session('username') ?? '',
    'email' => session('email') ?? '',
    'role' => session('role') ?? 'membre'
];
?>

<div class="admin-container">
    <div class="container profil-container">
        <!-- En-tête -->
        <div class="page-header">
            <h1>Mon Profil</h1>
            <p class="subtitle">Gérez vos informations personnelles et professionnelles</p>
        </div>

        <!-- Messages flash -->
        <?php if (has_flash('success')): ?>
        <div class="alert alert-success">
            <?= flash('success') ?>
        </div>
        <?php endif; ?>

        <?php if (has_flash('error')): ?>
        <div class="alert alert-error">
            <?= flash('error') ?>
        </div>
        <?php endif; ?>

        <div class="profil-layout">
            <!-- Colonne gauche - Informations principales -->
            <div class="profil-main">
                <!-- Carte profil -->
                <div class="profil-card">
                    <div class="profil-header">
                        <div class="profil-avatar-section">
                            <div class="profil-avatar-large">
                                <?php if (!empty($membre['photo'])): ?>
                                    <img src="<?= base_url('uploads/photos/' . $membre['photo']) ?>" 
                                         alt="Photo de profil"
                                         id="avatar-preview">
                                <?php else: ?>
                                    <div class="avatar-placeholder-large" id="avatar-placeholder">
                                        <?= strtoupper(substr($user['username'], 0, 2)) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <button type="button" class="btn-change-photo" onclick="document.getElementById('photo-input').click();">
                                    Changer la photo
                                </button>
                                <input type="file" 
                                       id="photo-input" 
                                       accept="image/*" 
                                       style="display: none;"
                                       onchange="handlePhotoChange(event)">
                            </div>
                            
                            <div class="profil-info-header">
                                <h2><?= e($user['username']) ?></h2>
                                <?php if (!empty($membre['grade'])): ?>
                                <div class="profil-grade"><?= e($membre['grade']) ?></div>
                                <?php endif; ?>
                                <div class="profil-role-badge">
                                    <?= e(ucfirst($user['role'])) ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Formulaire d'édition -->
                    <form id="profil-form" method="POST" action="<?= base_url('membre/profil/update') ?>" enctype="multipart/form-data">
                        <div class="form-section">
                            <h3>Informations personnelles</h3>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="nom">Nom <span class="required">*</span></label>
                                    <input type="text" 
                                           id="nom" 
                                           name="nom" 
                                           class="form-control"
                                           value="<?= e($membre['nom'] ?? '') ?>"
                                           required>
                                </div>

                                <div class="form-group">
                                    <label for="prenom">Prénom <span class="required">*</span></label>
                                    <input type="text" 
                                           id="prenom" 
                                           name="prenom" 
                                           class="form-control"
                                           value="<?= e($membre['prenom'] ?? '') ?>"
                                           required>
                                </div>

                                <div class="form-group">
                                    <label for="email">Email <span class="required">*</span></label>
                                    <input type="email" 
                                           id="email" 
                                           name="email" 
                                           class="form-control"
                                           value="<?= e($user['email']) ?>"
                                           required>
                                </div>

                                <div class="form-group">
                                    <label for="telephone">Téléphone</label>
                                    <input type="tel" 
                                           id="telephone" 
                                           name="telephone" 
                                           class="form-control"
                                           value="<?= e($membre['telephone'] ?? '') ?>"
                                           placeholder="+213 XX XX XX XX">
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3>Informations professionnelles</h3>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="poste">Poste</label>
                                    <select id="poste" name="poste" class="form-control">
                                        <option value="enseignant" <?= ($membre['poste'] ?? '') === 'enseignant' ? 'selected' : '' ?>>Enseignant</option>
                                        <option value="doctorant" <?= ($membre['poste'] ?? '') === 'doctorant' ? 'selected' : '' ?>>Doctorant</option>
                                        <option value="etudiant" <?= ($membre['poste'] ?? '') === 'etudiant' ? 'selected' : '' ?>>Étudiant</option>
                                        <option value="invite" <?= ($membre['poste'] ?? '') === 'invite' ? 'selected' : '' ?>>Invité</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="grade">Grade</label>
                                    <select id="grade" name="grade" class="form-control">
                                        <option value="">Sélectionner...</option>
                                        <option value="Professeur" <?= ($membre['grade'] ?? '') === 'Professeur' ? 'selected' : '' ?>>Professeur</option>
                                        <option value="Maître de conférences A" <?= ($membre['grade'] ?? '') === 'Maître de conférences A' ? 'selected' : '' ?>>Maître de conférences A</option>
                                        <option value="Maître de conférences B" <?= ($membre['grade'] ?? '') === 'Maître de conférences B' ? 'selected' : '' ?>>Maître de conférences B</option>
                                        <option value="Doctorant" <?= ($membre['grade'] ?? '') === 'Doctorant' ? 'selected' : '' ?>>Doctorant</option>
                                        <option value="Étudiant" <?= ($membre['grade'] ?? '') === 'Étudiant' ? 'selected' : '' ?>>Étudiant</option>
                                    </select>
                                </div>

                                <div class="form-group full-width">
                                    <label for="specialite">Spécialité / Domaine de recherche</label>
                                    <input type="text" 
                                           id="specialite" 
                                           name="specialite" 
                                           class="form-control"
                                           value="<?= e($membre['specialite'] ?? '') ?>"
                                           placeholder="Intelligence Artificielle, Cybersécurité...">
                                </div>

                                <div class="form-group full-width">
                                    <label for="adresse">Adresse</label>
                                    <textarea id="adresse" 
                                              name="adresse" 
                                              class="form-control"
                                              rows="2"><?= e($membre['adresse'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3>Biographie</h3>
                            
                            <div class="form-group">
                                <label for="biographie">À propos de vous</label>
                                <textarea id="biographie" 
                                          name="biographie" 
                                          class="form-control"
                                          rows="6"
                                          placeholder="Présentez votre parcours, vos recherches et vos centres d'intérêt..."><?= e($membre['biographie'] ?? '') ?></textarea>
                                <small class="form-text">Cette information sera visible publiquement sur votre profil</small>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn-secondary" onclick="resetForm()">
                                Annuler les modifications
                            </button>
                            <button type="submit" class="btn-primary" id="btn-save">
                                Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Section changement de mot de passe -->
                <div class="profil-card">
                    <h3>Sécurité</h3>
                    
                    <form id="password-form" method="POST" action="<?= base_url('membre/profil/change-password') ?>">
                        <div class="form-group">
                            <label for="current_password">Mot de passe actuel</label>
                            <input type="password" 
                                   id="current_password" 
                                   name="current_password" 
                                   class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="new_password">Nouveau mot de passe</label>
                            <input type="password" 
                                   id="new_password" 
                                   name="new_password" 
                                   class="form-control"
                                   minlength="6">
                            <small class="form-text">Minimum 6 caractères</small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirmer le mot de passe</label>
                            <input type="password" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   class="form-control">
                        </div>

                        <button type="submit" class="btn-primary">
                            Modifier le mot de passe
                        </button>
                    </form>
                </div>
            </div>

            <!-- Colonne droite - Statistiques -->
            <div class="profil-sidebar">
                <!-- Statistiques -->
                <div class="profil-card">
                    <h3>Mes statistiques</h3>
                    
                    <div class="stats-list">
                        <div class="stat-item">
                            <div class="stat-value"><?= $stats['total_projets'] ?? 0 ?></div>
                            <div class="stat-label">Projets</div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-value"><?= $stats['total_publications'] ?? 0 ?></div>
                            <div class="stat-label">Publications</div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-value"><?= $stats['projets_en_cours'] ?? 0 ?></div>
                            <div class="stat-label">Projets actifs</div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-value"><?= $stats['publications_validees'] ?? 0 ?></div>
                            <div class="stat-label">Publications validées</div>
                        </div>
                    </div>
                </div>

                <!-- Informations du compte -->
                <div class="profil-card">
                    <h3>Informations du compte</h3>
                    
                    <div class="info-list">
                        <div class="info-item">
                            <strong>Nom d'utilisateur</strong>
                            <span><?= e($user['username']) ?></span>
                        </div>
                        
                        <div class="info-item">
                            <strong>Rôle</strong>
                            <span><?= e(ucfirst($user['role'])) ?></span>
                        </div>
                        
                        <?php if (!empty($membre['equipe_nom'])): ?>
                        <div class="info-item">
                            <strong>Équipe</strong>
                            <span><?= e($membre['equipe_nom']) ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($membre['date_adhesion'])): ?>
                        <div class="info-item">
                            <strong>Membre depuis</strong>
                            <span><?= format_date($membre['date_adhesion'], 'd/m/Y') ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
.profil-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 40px 20px;
}

.page-header {
    margin-bottom: 32px;
}

.page-header h1 {
    font-size: 32px;
    font-weight: 700;
    margin: 0 0 8px 0;
    color: var(--gray-900);
}

.subtitle {
    color: var(--gray-600);
    font-size: 16px;
    margin: 0;
}

.alert {
    padding: 16px 20px;
    border-radius: 8px;
    margin-bottom: 24px;
    font-size: 14px;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #6ee7b7;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

.profil-layout {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 32px;
}

.profil-card {
    background: white;
    padding: 32px;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
    margin-bottom: 24px;
}

.profil-card h3 {
    font-size: 20px;
    font-weight: 600;
    margin: 0 0 24px 0;
    color: var(--gray-900);
    border-bottom: 2px solid var(--border-color);
    padding-bottom: 12px;
}

/* En-tête profil */
.profil-header {
    margin-bottom: 32px;
}

.profil-avatar-section {
    display: flex;
    align-items: center;
    gap: 24px;
}

.profil-avatar-large {
    position: relative;
    text-align: center;
}

.profil-avatar-large img,
.avatar-placeholder-large {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid var(--border-color);
}

.avatar-placeholder-large {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    font-weight: 700;
    margin: 0 auto 12px;
}

.btn-change-photo {
    margin-top: 12px;
    padding: 8px 16px;
    background: var(--gray-100);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 13px;
    cursor: pointer;
    transition: var(--transition);
}

.btn-change-photo:hover {
    background: var(--gray-200);
}

.profil-info-header h2 {
    font-size: 28px;
    font-weight: 700;
    margin: 0 0 8px 0;
    color: var(--gray-900);
}

.profil-grade {
    font-size: 16px;
    color: var(--primary);
    font-weight: 500;
    margin-bottom: 8px;
}

.profil-role-badge {
    display: inline-block;
    padding: 6px 16px;
    background: var(--primary);
    color: white;
    border-radius: 16px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

/* Formulaire */
.form-section {
    margin-bottom: 32px;
}

.form-section h3 {
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 20px 0;
    color: var(--gray-800);
    border-bottom: none;
    padding-bottom: 0;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    font-weight: 500;
    margin-bottom: 8px;
    color: var(--gray-700);
    font-size: 14px;
}

.required {
    color: #ef4444;
}

.form-control {
    padding: 12px 16px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    font-size: 14px;
    transition: var(--transition);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

textarea.form-control {
    resize: vertical;
    font-family: inherit;
}

.form-text {
    margin-top: 6px;
    font-size: 13px;
    color: var(--gray-500);
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    padding-top: 24px;
    border-top: 1px solid var(--border-color);
}

/* Statistiques */
.stats-list {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.stat-item {
    text-align: center;
    padding: 20px;
    background: var(--gray-50);
    border-radius: 12px;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 6px;
}

.stat-label {
    font-size: 13px;
    color: var(--gray-600);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Informations */
.info-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.info-item strong {
    font-size: 13px;
    color: var(--gray-600);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-item span {
    font-size: 15px;
    color: var(--gray-900);
}

/* Actions rapides */
.quick-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.btn-block {
    display: block;
    width: 100%;
    text-align: center;
}

/* Boutons */
.btn-primary,
.btn-secondary {
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    border: none;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-dark);
}

.btn-secondary {
    background: var(--gray-200);
    color: var(--gray-700);
}

.btn-secondary:hover {
    background: var(--gray-300);
}

/* Responsive */
@media (max-width: 1024px) {
    .profil-layout {
        grid-template-columns: 1fr;
    }
    
    .profil-sidebar {
        order: -1;
    }
}

@media (max-width: 768px) {
    .profil-avatar-section {
        flex-direction: column;
        text-align: center;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-list {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .profil-card {
        padding: 20px;
    }
}
</style>

<?php ViewComponents::renderFooter(['role' => 'membre']); ?>