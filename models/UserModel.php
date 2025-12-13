<?php
require_once __DIR__ . '/Model.php';
// ========================================
// UserModel.php
// ========================================
class UserModel extends Model {
    protected $table = 'User';
    
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
    
    public function createUser($username, $email, $password, $role = 'visiteur') {
        $data = [
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role
        ];
        return $this->create($data);
    }
    
    public function usernameExists($username) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM User WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetchColumn() > 0;
    }
    
    public function getByRole($role) {
        $stmt = $this->db->prepare("SELECT * FROM User WHERE role = ?");
        $stmt->execute([$role]);
        return $stmt->fetchAll();
    }
}