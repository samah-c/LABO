<?php
require_once __DIR__ . '/../../models/NotificationModel.php';
require_once __DIR__ . '/../../models/MembreModel.php';
require_once __DIR__ . '/../../models/EquipeModel.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../auth/AuthController.php';

class NotificationsController {
    private $notificationModel;
    private $membreModel;
    private $equipeModel;
    
    public function __construct() {
        AuthController::requireAdmin();
        $this->notificationModel = new NotificationModel();
        $this->membreModel = new MembreModel();
        $this->equipeModel = new EquipeModel();
    }
    
    public function index() {
        $notifications = $this->notificationModel->getAll();
        
        $page = (int) get('page', 1);
        $perPage = 15;
        $pagination = Utils::paginate(count($notifications), $perPage, $page);
        $notifications = array_slice($notifications, $pagination['offset'], $perPage);
        
        require_once __DIR__ . '/../../views/admin/notifications/NotificationsListView.php';
        $view = new NotificationsListView($notifications, $pagination);
        $view->render();
    }
    
    public function form($id = null) {
        $notification = $id ? $this->notificationModel->getById($id) : null;
        $equipes = $this->equipeModel->getAll();
        $membres = $this->membreModel->getAllMembresWithUser();
        
        $this->renderForm($notification, $equipes, $membres);
    }
    
    private function renderForm($notification, $equipes, $membres) {
        ?>
        <form id="notification-form" method="POST" action="<?= base_url('admin/notifications/save') ?>">
            <?= csrf_field() ?>
            
            <div class="form-group">
                <label for="titre">Titre *</label>
                <input type="text" 
                       name="titre" 
                       id="titre" 
                       value="<?= e($notification['titre'] ?? '') ?>" 
                       required 
                       placeholder="Ex: Nouvelle publication disponible">
            </div>
            
            <div class="form-group">
                <label for="message">Message *</label>
                <textarea name="message" 
                          id="message" 
                          rows="4" 
                          required 
                          placeholder="Contenu de la notification"><?= e($notification['message'] ?? '') ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="type_notification">Type</label>
                    <select name="type_notification" id="type_notification">
                        <option value="generale" <?= ($notification['type_notification'] ?? 'generale') === 'generale' ? 'selected' : '' ?>>Générale</option>
                        <option value="evenement" <?= ($notification['type_notification'] ?? '') === 'evenement' ? 'selected' : '' ?>>Événement</option>
                        <option value="projet" <?= ($notification['type_notification'] ?? '') === 'projet' ? 'selected' : '' ?>>Projet</option>
                        <option value="equipement" <?= ($notification['type_notification'] ?? '') === 'equipement' ? 'selected' : '' ?>>Équipement</option>
                        <option value="publication" <?= ($notification['type_notification'] ?? '') === 'publication' ? 'selected' : '' ?>>Publication</option>
                        <option value="systeme" <?= ($notification['type_notification'] ?? '') === 'systeme' ? 'selected' : '' ?>>Système</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="priorite">Priorité</label>
                    <select name="priorite" id="priorite">
                        <option value="normale" <?= ($notification['priorite'] ?? 'normale') === 'normale' ? 'selected' : '' ?>>Normale</option>
                        <option value="importante" <?= ($notification['priorite'] ?? '') === 'importante' ? 'selected' : '' ?>>Importante</option>
                        <option value="urgente" <?= ($notification['priorite'] ?? '') === 'urgente' ? 'selected' : '' ?>>Urgente</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="destinataire_type">Destinataires *</label>
                <select name="destinataire_type" id="destinataire_type" required onchange="toggleDestinataire()">
                    <option value="tous" <?= ($notification['destinataire_type'] ?? 'tous') === 'tous' ? 'selected' : '' ?>>Tous les membres</option>
                    <option value="role" <?= ($notification['destinataire_type'] ?? '') === 'role' ? 'selected' : '' ?>>Par rôle</option>
                    <option value="equipe" <?= ($notification['destinataire_type'] ?? '') === 'equipe' ? 'selected' : '' ?>>Par équipe</option>
                    <option value="individuel" <?= ($notification['destinataire_type'] ?? '') === 'individuel' ? 'selected' : '' ?>>Membre spécifique</option>
                </select>
            </div>
            
            <div id="role-select" class="form-group" style="display: none;">
                <label for="role">Rôle</label>
                <select name="role" id="role">
                    <option value="admin">Administrateurs</option>
                    <option value="membre">Membres</option>
                    <option value="visiteur">Visiteurs</option>
                </select>
            </div>
            
            <div id="equipe-select" class="form-group" style="display: none;">
                <label for="equipe_id">Équipe</label>
                <select name="equipe_id" id="equipe_id">
                    <option value="">-- Sélectionner --</option>
                    <?php foreach ($equipes as $equipe): ?>
                        <option value="<?= $equipe['id'] ?>"><?= e($equipe['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div id="membre-select" class="form-group" style="display: none;">
                <label for="membre_id">Membre</label>
                <select name="membre_id" id="membre_id">
                    <option value="">-- Sélectionner --</option>
                    <?php foreach ($membres as $membre): ?>
                        <option value="<?= $membre['user_id'] ?>"><?= e($membre['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="lien">Lien (optionnel)</label>
                <input type="text" 
                       name="lien" 
                       id="lien" 
                       value="<?= e($notification['lien'] ?? '') ?>" 
                       placeholder="/admin/projets/view/123">
                <small>Lien vers la ressource concernée</small>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal()">Annuler</button>
                <button type="submit" class="btn-primary">Envoyer la notification</button>
            </div>
        </form>
        
        <script>
        function toggleDestinataire() {
            const type = document.getElementById('destinataire_type').value;
            document.getElementById('role-select').style.display = type === 'role' ? 'block' : 'none';
            document.getElementById('equipe-select').style.display = type === 'equipe' ? 'block' : 'none';
            document.getElementById('membre-select').style.display = type === 'individuel' ? 'block' : 'none';
        }
        toggleDestinataire();
        </script>
        
        <style>
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        </style>
        <?php
    }
    
    public function save() {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']);
        
        if (!isset($_POST['csrf_token']) || !Utils::verifyCsrfToken($_POST['csrf_token'])) {
            if ($isAjax) {
                json(['success' => false, 'message' => 'Token CSRF invalide']);
            } else {
                $_SESSION['error'] = 'Token CSRF invalide';
                redirect(base_url('admin/notifications'));
            }
        }
        
        try {
            $destinataireType = $_POST['destinataire_type'];
            $destinataireId = null;
            
            if ($destinataireType === 'equipe') {
                $destinataireId = (int)$_POST['equipe_id'];
            } elseif ($destinataireType === 'individuel') {
                $destinataireId = (int)$_POST['membre_id'];
            }
            
            $data = [
                'titre' => Utils::sanitize($_POST['titre']),
                'message' => Utils::sanitize($_POST['message']),
                'type_notification' => $_POST['type_notification'] ?? 'generale',
                'priorite' => $_POST['priorite'] ?? 'normale',
                'destinataire_type' => $destinataireType,
                'destinataire_id' => $destinataireId,
                'createur_id' => session('user_id'),
                'lien' => Utils::sanitize($_POST['lien'] ?? '')
            ];
            
            $id = $this->notificationModel->create($data);
            
            if ($id) {
                Utils::log("Notification créée par " . session('username'));
                
                if ($isAjax) {
                    json(['success' => true, 'message' => 'Notification envoyée avec succès']);
                } else {
                    $_SESSION['success'] = 'Notification envoyée avec succès';
                    redirect(base_url('admin/notifications'));
                }
            }
            
        } catch (Exception $e) {
            Utils::log("Erreur création notification: " . $e->getMessage(), 'ERROR');
            
            if ($isAjax) {
                json(['success' => false, 'message' => $e->getMessage()]);
            } else {
                $_SESSION['error'] = $e->getMessage();
                redirect(base_url('admin/notifications'));
            }
        }
    }
    
    public function delete($id) {
        try {
            $success = $this->notificationModel->delete($id);
            
            if ($success) {
                json(['success' => true, 'message' => 'Notification supprimée']);
            } else {
                json(['success' => false, 'message' => 'Erreur lors de la suppression']);
            }
        } catch (Exception $e) {
            json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // API pour les utilisateurs
    public function getUserNotifications() {
        $userId = session('user_id');
        $notifications = $this->notificationModel->getForUser($userId);
        json(['success' => true, 'notifications' => $notifications]);
    }
    
    public function getUnreadCount() {
        $userId = session('user_id');
        $count = $this->notificationModel->countUnread($userId);
        json(['success' => true, 'count' => $count]);
    }
    
    public function markAsRead($id) {
        $userId = session('user_id');
        $success = $this->notificationModel->markAsRead($id, $userId);
        json(['success' => $success]);
    }
    
    public function markAllAsRead() {
        $userId = session('user_id');
        $success = $this->notificationModel->markAllAsRead($userId);
        json(['success' => $success]);
    }
}