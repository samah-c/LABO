<?php
require_once __DIR__ . '/Model.php';

/**
 * UserModel.php - Modèle complet pour la gestion des utilisateurs
 */
class UserModel extends Model {
    protected $table = 'User';
    
    /**
     * Connexion utilisateur
     */
    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT * FROM User WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $this->db->prepare("UPDATE User SET derniere_connexion = NOW() WHERE id = ?")->execute([$user['id']]);
            return $user;
        }
        return false;
    }
    
    /**
     * Créer un utilisateur
     */
    public function createUser($username, $email, $password, $role = 'visiteur') {
        $data = [
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'statut' => 'actif'
        ];
        return $this->create($data);
    }
    
    /**
     * Vérifier si le username existe
     */
    public function usernameExists($username) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM User WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Vérifier si l'email existe
     */
    public function emailExists($email) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM User WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Récupérer un utilisateur par username
     */
    public function getByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM User WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }
    
    /**
     * Récupérer un utilisateur par email
     */
    public function getByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM User WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    /**
     * Récupérer les utilisateurs par rôle
     */
    public function getByRole($role) {
        $stmt = $this->db->prepare("SELECT * FROM User WHERE role = ? ORDER BY username");
        $stmt->execute([$role]);
        return $stmt->fetchAll();
    }
    
    /**
     * Récupérer tous les utilisateurs avec filtres
     */
    public function getAllFiltered($filters = []) {
        $sql = "SELECT u.*, 
                       (SELECT COUNT(*) FROM Membre m WHERE m.user_id = u.id) as is_membre
                FROM User u
                WHERE 1";
        $params = [];
        
        // Filtre par rôle
        if (!empty($filters['role'])) {
            $sql .= " AND u.role = :role";
            $params['role'] = $filters['role'];
        }
        
        // Filtre par statut
        if (!empty($filters['statut'])) {
            $sql .= " AND u.statut = :statut";
            $params['statut'] = $filters['statut'];
        } else {
            // Par défaut, si pas de filtre statut, on considère tous comme actifs
            $sql .= " AND (u.statut IS NULL OR u.statut = 'actif' OR u.statut = 'suspendu' OR u.statut = 'inactif')";
        }
        
        // Recherche
        if (!empty($filters['search'])) {
            $sql .= " AND (u.username LIKE :search 
                           OR u.email LIKE :search
                           OR u.role LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        $sql .= " ORDER BY u.username";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer les statistiques d'un utilisateur
     */
    public function getUserStats($userId) {
        $stats = [
            'publications' => 0,
            'projets' => 0,
            'connexions' => 0
        ];
        
        // Vérifier si c'est un membre
        $stmt = $this->db->prepare("SELECT id FROM Membre WHERE user_id = ?");
        $stmt->execute([$userId]);
        $membre = $stmt->fetch();
        
        if ($membre) {
            // Compter les publications
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM Publication p
                JOIN Auteur a ON p.id = a.publication_id
                WHERE a.membre_id = ?
            ");
            $stmt->execute([$membre['id']]);
            $stats['publications'] = $stmt->fetchColumn();
            
            // Compter les projets
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM Projet p
                WHERE p.responsable_id = ?
            ");
            $stmt->execute([$membre['id']]);
            $stats['projets'] = $stmt->fetchColumn();
        }
        
        return $stats;
    }
    
    /**
     * Changer le mot de passe
     */
    public function changePassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        return $this->update($userId, ['password' => $hashedPassword]);
    }
    
    /**
     * Activer/Désactiver un utilisateur
     */
    public function toggleStatus($userId, $status) {
        return $this->update($userId, ['statut' => $status]);
    }
    
    /**
     * Changer le rôle
     */
    public function changeRole($userId, $role) {
        if (!in_array($role, ['admin', 'membre', 'visiteur'])) {
            return false;
        }
        return $this->update($userId, ['role' => $role]);
    }
    
    /**
     * Obtenir les utilisateurs récents
     */
    public function getRecentUsers($limit = 5) {
        $stmt = $this->db->prepare("
            SELECT * FROM User 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtenir les utilisateurs actifs
     */
    public function getActiveUsers() {
        $stmt = $this->db->query("
            SELECT * FROM User 
            WHERE statut = 'actif' OR statut IS NULL
            ORDER BY derniere_connexion DESC
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Compter les utilisateurs par rôle
     */
    public function countByRole() {
        $stmt = $this->db->query("
            SELECT role, COUNT(*) as count 
            FROM User 
            GROUP BY role
        ");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    
    /**
     * Compter les utilisateurs par statut
     */
    public function countByStatus() {
        $stmt = $this->db->query("
            SELECT 
                COALESCE(statut, 'actif') as statut, 
                COUNT(*) as count 
            FROM User 
            GROUP BY statut
        ");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}
?>