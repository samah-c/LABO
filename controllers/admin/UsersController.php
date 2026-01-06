<?php
/**
 * UsersController.php - Contrôleur complet pour la gestion des utilisateurs
 * Gère toutes les opérations CRUD + gestion des rôles et permissions
 */

require_once __DIR__ . '/../../models/UserModel.php';
require_once __DIR__ . '/../../models/MembreModel.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../auth/AuthController.php';

require_once __DIR__ . '/../../views/admin/users/UsersListView.php';
require_once __DIR__ . '/../../views/admin/users/UserDetailView.php';

class UsersController {
    private $userModel;
    private $membreModel;
    
    public function __construct() {
        AuthController::requireAdmin();
        $this->userModel = new UserModel();
        $this->membreModel = new MembreModel();
    }
    
    /**
     * Liste des utilisateurs avec filtres et recherche
     */
    public function index() {
        // Récupérer les filtres
        $filters = [
            'role' => get('role'),
            'statut' => get('statut'),
            'search' => get('search')
        ];
        
        // Récupérer les utilisateurs filtrés
        $users = $this->userModel->getAllFiltered($filters);
        
        // Pagination
        $page = (int) get('page', 1);
        $perPage = 10;
        $pagination = Utils::paginate(count($users), $perPage, $page);
        $users = array_slice($users, $pagination['offset'], $perPage);
        
        // Charger la vue
        $view = new UsersListView($users, $pagination);
        $view->render();
    }
    
    /**
     * Vue détaillée d'un utilisateur
     */
    public function view($id) {
        // Récupérer l'utilisateur
        $user = $this->userModel->getById($id);
        
        if (!$user) {
            $_SESSION['error'] = 'Utilisateur non trouvé';
            redirect(base_url('admin/users/users'));
        }
        
        // Récupérer les informations du membre si c'est un membre
        $membre = null;
        if ($user['role'] === 'membre') {
            $membre = $this->membreModel->getByUserId($id);
        }
        
        // Statistiques de l'utilisateur
        $stats = [
            'publications' => 0,
            'projets' => 0,
            'connexions' => 0
        ];
        
        // Si membre, récupérer ses statistiques
        if ($membre) {
            require_once __DIR__ . '/../../models/PublicationModel.php';
            require_once __DIR__ . '/../../models/ProjetModel.php';
            
            $publicationModel = new PublicationModel();
            $projetModel = new ProjetModel();
            
            $stats['publications'] = count($publicationModel->getByAuteur($membre['id']));
            $stats['projets'] = count($projetModel->getByMembre($membre['id']));
        }
        
        // Charger la vue détaillée
        $view = new UserDetailView($user, $membre, $stats);
        $view->render();
    }
    
    /**
     * Formulaire d'ajout/édition d'utilisateur (AJAX)
     */
    public function form($id = null) {
        // Récupérer l'utilisateur si édition
        $user = $id ? $this->userModel->getById($id) : null;
        
        // Générer le formulaire
        ?>
        <form id="user-form" method="POST" action="<?= base_url('admin/users/users/save') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $user['id'] ?? '' ?>">
            
            <div class="form-group">
                <label for="username">Nom d'utilisateur *</label>
                <input type="text" 
                       name="username" 
                       id="username" 
                       value="<?= e($user['username'] ?? '') ?>" 
                       required 
                       placeholder="Ex: john.doe">
            </div>
            
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" 
                       name="email" 
                       id="email" 
                       value="<?= e($user['email'] ?? '') ?>" 
                       required 
                       placeholder="exemple@esi.dz">
            </div>
            
            <?php if (!$id): ?>
            <div class="form-group">
                <label for="password">Mot de passe *</label>
                <input type="password" 
                       name="password" 
                       id="password" 
                       required 
                       placeholder="Minimum 8 caractères">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirmer le mot de passe *</label>
                <input type="password" 
                       name="confirm_password" 
                       id="confirm_password" 
                       required 
                       placeholder="Retapez le mot de passe">
            </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="role">Rôle *</label>
                <select name="role" id="role" required>
                    <option value="">-- Sélectionner un rôle --</option>
                    <option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrateur</option>
                    <option value="membre" <?= ($user['role'] ?? '') === 'membre' ? 'selected' : '' ?>>Membre</option>
                    <option value="visiteur" <?= ($user['role'] ?? '') === 'visiteur' ? 'selected' : '' ?>>Visiteur</option>
                </select>
                <small class="form-hint">
                    <strong>Admin:</strong> Accès total à la plateforme<br>
                    <strong>Membre:</strong> Peut gérer ses projets et publications<br>
                    <strong>Visiteur:</strong> Accès en lecture seule
                </small>
            </div>
            
            <div class="form-group">
                <label for="statut">Statut</label>
                <select name="statut" id="statut">
                    <option value="actif" <?= ($user['statut'] ?? 'actif') === 'actif' ? 'selected' : '' ?>>Actif</option>
                    <option value="suspendu" <?= ($user['statut'] ?? '') === 'suspendu' ? 'selected' : '' ?>>Suspendu</option>
                    <option value="inactif" <?= ($user['statut'] ?? '') === 'inactif' ? 'selected' : '' ?>>Inactif</option>
                </select>
            </div>
            
            <?php if ($id): ?>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="reset_password" id="reset_password" value="1">
                    Réinitialiser le mot de passe
                </label>
            </div>
            
            <div id="password-fields" style="display: none;">
                <div class="form-group">
                    <label for="new_password">Nouveau mot de passe</label>
                    <input type="password" 
                           name="new_password" 
                           id="new_password" 
                           placeholder="Minimum 8 caractères">
                </div>
                
                <div class="form-group">
                    <label for="confirm_new_password">Confirmer le nouveau mot de passe</label>
                    <input type="password" 
                           name="confirm_new_password" 
                           id="confirm_new_password" 
                           placeholder="Retapez le mot de passe">
                </div>
            </div>
            <?php endif; ?>
            
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal()">
                    Annuler
                </button>
                <button type="submit" class="btn-primary">
                    <?= $id ? 'Mettre à jour' : 'Créer l\'utilisateur' ?>
                </button>
            </div>
        </form>
        
        <script>
        // Afficher/masquer les champs de mot de passe
        const resetCheckbox = document.getElementById('reset_password');
        if (resetCheckbox) {
            resetCheckbox.addEventListener('change', function() {
                const passwordFields = document.getElementById('password-fields');
                passwordFields.style.display = this.checked ? 'block' : 'none';
                
                const newPassword = document.getElementById('new_password');
                const confirmPassword = document.getElementById('confirm_new_password');
                
                if (this.checked) {
                    newPassword.required = true;
                    confirmPassword.required = true;
                } else {
                    newPassword.required = false;
                    confirmPassword.required = false;
                    newPassword.value = '';
                    confirmPassword.value = '';
                }
            });
        }
        </script>
        <?php
    }
    
    /**
     * Sauvegarder un utilisateur (création ou mise à jour)
     */
    public function save() {
        // Vérifier si c'est une requête AJAX
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        // Vérifier le token CSRF
        if (!isset($_POST['csrf_token']) || !Utils::verifyCsrfToken($_POST['csrf_token'])) {
            if ($isAjax) {
                json(['success' => false, 'message' => 'Token CSRF invalide']);
            } else {
                $_SESSION['error'] = 'Token CSRF invalide';
                redirect(base_url('admin/users/users'));
            }
        }
        
        try {
            $data = [
                'username' => Utils::sanitize($_POST['username']),
                'email' => Utils::sanitize($_POST['email']),
                'role' => Utils::sanitize($_POST['role']),
                'statut' => Utils::sanitize($_POST['statut'] ?? 'actif')
            ];
            
            // Validation
            $errors = [];
            
            if (empty($data['username'])) {
                $errors['username'] = 'Le nom d\'utilisateur est requis';
            } elseif (strlen($data['username']) < 3) {
                $errors['username'] = 'Le nom d\'utilisateur doit contenir au moins 3 caractères';
            }
            
            if (empty($data['email'])) {
                $errors['email'] = 'L\'email est requis';
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Email invalide';
            }
            
            if (empty($data['role'])) {
                $errors['role'] = 'Le rôle est requis';
            } elseif (!in_array($data['role'], ['admin', 'membre', 'visiteur'])) {
                $errors['role'] = 'Rôle invalide';
            }
            
            // Validation du mot de passe pour création
            if (empty($_POST['id'])) {
                if (empty($_POST['password'])) {
                    $errors['password'] = 'Le mot de passe est requis';
                } elseif (strlen($_POST['password']) < 8) {
                    $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères';
                } elseif ($_POST['password'] !== $_POST['confirm_password']) {
                    $errors['confirm_password'] = 'Les mots de passe ne correspondent pas';
                } else {
                    $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                }
            }
            
            // Validation du mot de passe pour modification
            if (!empty($_POST['id']) && !empty($_POST['reset_password'])) {
                if (empty($_POST['new_password'])) {
                    $errors['new_password'] = 'Le nouveau mot de passe est requis';
                } elseif (strlen($_POST['new_password']) < 8) {
                    $errors['new_password'] = 'Le mot de passe doit contenir au moins 8 caractères';
                } elseif ($_POST['new_password'] !== $_POST['confirm_new_password']) {
                    $errors['confirm_new_password'] = 'Les mots de passe ne correspondent pas';
                } else {
                    $data['password'] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                }
            }
            
            if (!empty($errors)) {
                if ($isAjax) {
                    json(['success' => false, 'message' => 'Erreurs de validation', 'errors' => $errors]);
                } else {
                    $_SESSION['validation_errors'] = $errors;
                    $_SESSION['old_input'] = $_POST;
                    redirect(base_url('admin/users/users'));
                }
            }
            
            // Créer ou mettre à jour
            if (!empty($_POST['id'])) {
                // Mise à jour
                $id = (int)$_POST['id'];
                
                // Vérifier si le username ou email existe déjà (pour un autre utilisateur)
                $existing = $this->userModel->getByUsername($data['username']);
                if ($existing && $existing['id'] != $id) {
                    if ($isAjax) {
                        json(['success' => false, 'message' => 'Ce nom d\'utilisateur existe déjà']);
                    } else {
                        $_SESSION['error'] = 'Ce nom d\'utilisateur existe déjà';
                        redirect(base_url('admin/users/users'));
                    }
                }
                
                $success = $this->userModel->update($id, $data);
                $message = 'Utilisateur mis à jour avec succès';
            } else {
                // Création
                
                // Vérifier si le username existe déjà
                if ($this->userModel->usernameExists($data['username'])) {
                    if ($isAjax) {
                        json(['success' => false, 'message' => 'Ce nom d\'utilisateur existe déjà']);
                    } else {
                        $_SESSION['error'] = 'Ce nom d\'utilisateur existe déjà';
                        redirect(base_url('admin/users/users'));
                    }
                }
                
                // Vérifier si l'email existe déjà
                if ($this->userModel->emailExists($data['email'])) {
                    if ($isAjax) {
                        json(['success' => false, 'message' => 'Cet email existe déjà']);
                    } else {
                        $_SESSION['error'] = 'Cet email existe déjà';
                        redirect(base_url('admin/users/users'));
                    }
                }
                
                $id = $this->userModel->create($data);
                $success = $id > 0;
                $message = 'Utilisateur créé avec succès';
            }
            
            if ($success) {
                Utils::log("Utilisateur sauvegardé par " . session('username'));
                
                if ($isAjax) {
                    json(['success' => true, 'message' => $message, 'id' => $id]);
                } else {
                    $_SESSION['success'] = $message;
                    redirect(base_url('admin/users/users'));
                }
            } else {
                if ($isAjax) {
                    json(['success' => false, 'message' => 'Erreur lors de l\'enregistrement']);
                } else {
                    $_SESSION['error'] = 'Erreur lors de l\'enregistrement';
                    redirect(base_url('admin/users/users'));
                }
            }
            
        } catch (Exception $e) {
            Utils::log("Erreur sauvegarde utilisateur: " . $e->getMessage(), 'ERROR');
            
            if ($isAjax) {
                json(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
            } else {
                $_SESSION['error'] = 'Erreur serveur: ' . $e->getMessage();
                redirect(base_url('admin/users/users'));
            }
        }
    }
    
    /**
     * Supprimer un utilisateur
     */
    public function delete($id) {
        try {
            // Ne pas permettre la suppression de l'admin actuel
            $currentUserId = session('user_id');
            if ($id == $currentUserId) {
                json([
                    'success' => false, 
                    'message' => 'Vous ne pouvez pas supprimer votre propre compte'
                ]);
            }
            
            // Vérifier si l'utilisateur est un membre avec des données associées
            $user = $this->userModel->getById($id);
            if ($user && $user['role'] === 'membre') {
                $membre = $this->membreModel->getByUserId($id);
                if ($membre) {
                    // Vérifier s'il a des publications ou projets
                    // Pour l'instant, on empêche la suppression
                    json([
                        'success' => false, 
                        'message' => 'Impossible de supprimer un membre avec des données associées. Veuillez d\'abord désactiver le compte.'
                    ]);
                }
            }
            
            // Supprimer l'utilisateur
            $success = $this->userModel->delete($id);
            
            if ($success) {
                Utils::log("Utilisateur #$id supprimé par " . session('username'));
                json(['success' => true, 'message' => 'Utilisateur supprimé avec succès']);
            } else {
                json(['success' => false, 'message' => 'Erreur lors de la suppression']);
            }
            
        } catch (Exception $e) {
            Utils::log("Erreur suppression utilisateur: " . $e->getMessage(), 'ERROR');
            json(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Changer le rôle d'un utilisateur
     */
    public function changeRole() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $userId = (int)($input['user_id'] ?? 0);
            $newRole = $input['role'] ?? '';
            
            if (!$userId || !$newRole) {
                json(['success' => false, 'message' => 'Données invalides']);
            }
            
            if (!in_array($newRole, ['admin', 'membre', 'visiteur'])) {
                json(['success' => false, 'message' => 'Rôle invalide']);
            }
            
            // Ne pas permettre de changer son propre rôle
            if ($userId == session('user_id')) {
                json(['success' => false, 'message' => 'Vous ne pouvez pas changer votre propre rôle']);
            }
            
            $success = $this->userModel->update($userId, ['role' => $newRole]);
            
            if ($success) {
                Utils::log("Rôle de l'utilisateur #$userId changé en $newRole par " . session('username'));
                json(['success' => true, 'message' => 'Rôle modifié avec succès']);
            } else {
                json(['success' => false, 'message' => 'Erreur lors de la modification']);
            }
            
        } catch (Exception $e) {
            Utils::log("Erreur changement de rôle: " . $e->getMessage(), 'ERROR');
            json(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Changer le statut d'un utilisateur
     */
    public function changeStatus() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $userId = (int)($input['user_id'] ?? 0);
            $newStatus = $input['statut'] ?? '';
            
            if (!$userId || !$newStatus) {
                json(['success' => false, 'message' => 'Données invalides']);
            }
            
            if (!in_array($newStatus, ['actif', 'suspendu', 'inactif'])) {
                json(['success' => false, 'message' => 'Statut invalide']);
            }
            
            // Ne pas permettre de suspendre son propre compte
            if ($userId == session('user_id') && $newStatus === 'suspendu') {
                json(['success' => false, 'message' => 'Vous ne pouvez pas suspendre votre propre compte']);
            }
            
            $success = $this->userModel->update($userId, ['statut' => $newStatus]);
            
            if ($success) {
                Utils::log("Statut de l'utilisateur #$userId changé en $newStatus par " . session('username'));
                json(['success' => true, 'message' => 'Statut modifié avec succès']);
            } else {
                json(['success' => false, 'message' => 'Erreur lors de la modification']);
            }
            
        } catch (Exception $e) {
            Utils::log("Erreur changement de statut: " . $e->getMessage(), 'ERROR');
            json(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Exporter les utilisateurs en CSV
     */
    public function export() {
        $users = $this->userModel->getAll();
        
        $data = [];
        $data[] = ['Username', 'Email', 'Rôle', 'Statut', 'Dernière connexion', 'Date création'];
        
        foreach ($users as $user) {
            $data[] = [
                $user['username'],
                $user['email'],
                $user['role'],
                $user['statut'] ?? 'actif',
                $user['derniere_connexion'] ? format_date($user['derniere_connexion']) : 'Jamais',
                format_date($user['created_at'] ?? date('Y-m-d'))
            ];
        }
        
        LabHelpers::exportToCsv($data, 'utilisateurs_' . date('Y-m-d') . '.csv');
    }
    
    /**
     * API - Récupérer un utilisateur par ID
     */
    public function get($id) {
        $user = $this->userModel->getById($id);
        
        if ($user) {
            // Ne pas retourner le mot de passe
            unset($user['password']);
            json(['success' => true, 'user' => $user]);
        } else {
            json(['success' => false, 'message' => 'Utilisateur non trouvé']);
        }
    }
}
?>