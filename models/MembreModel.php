<?php
require_once __DIR__ . '/Model.php';

/**
 * MembreModel.php - Modèle pour la gestion des membres
 */
class MembreModel extends Model {
    protected $table = 'Membre';
    
    /**
     * Récupérer tous les membres (alias pour cohérence)
     */
    public function all() {
        return $this->getAllMembresWithUser();
    }
    
    /**
     * Récupérer tous les membres avec leurs informations utilisateur
     */
    public function getAllMembresWithUser() {
        $query = "
            SELECT 
                m.id, m.user_id, m.nom, m.prenom, m.poste, m.grade, 
                m.photo, m.biographie, m.chef_equipe, m.equipe_id, m.date_adhesion,
                m.telephone, m.specialite, m.adresse,
                u.username, u.email, u.role,
                e.nom as equipe_nom
            FROM Membre m
            INNER JOIN User u ON m.user_id = u.id
            LEFT JOIN Equipe e ON m.equipe_id = e.id
            ORDER BY u.username ASC
        ";
        
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les membres sans équipe (disponibles)
     */
    public function getMembresDisponibles() {
        $stmt = $this->db->prepare("
            SELECT m.*, u.username, u.email 
            FROM Membre m
            JOIN User u ON m.user_id = u.id
            WHERE m.equipe_id IS NULL
            ORDER BY u.username
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer les membres d'une équipe spécifique
     */
    public function getByEquipe($equipeId) {
        $stmt = $this->db->prepare("
            SELECT m.*, u.username, u.email
            FROM Membre m
            JOIN User u ON m.user_id = u.id
            WHERE m.equipe_id = ?
        ");
        $stmt->execute([$equipeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer un membre par son user_id
     */
    public function getByUserId($userId) {
        $stmt = $this->db->prepare("
            SELECT 
                m.*, 
                u.username, 
                u.email,
                e.nom as equipe_nom
            FROM Membre m
            JOIN User u ON m.user_id = u.id
            LEFT JOIN Equipe e ON m.equipe_id = e.id
            WHERE m.user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer un membre avec ses informations complètes
     */
    public function getWithDetails($membreId) {
        $stmt = $this->db->prepare("
            SELECT 
                m.id, m.user_id, m.nom, m.prenom, m.poste, m.grade,
                m.photo, m.biographie, m.telephone, m.specialite, m.adresse,
                m.chef_equipe, m.equipe_id, m.date_adhesion,
                u.username, u.email, u.role,
                e.nom as equipe_nom
            FROM Membre m
            JOIN User u ON m.user_id = u.id
            LEFT JOIN Equipe e ON m.equipe_id = e.id
            WHERE m.id = ?
        ");
        $stmt->execute([$membreId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Mettre à jour les informations d'un membre
     */
    public function updateProfil($membreId, $data) {
        $allowedFields = [
            'nom', 'prenom', 'poste', 'grade', 
            'specialite', 'telephone', 'adresse', 
            'biographie', 'photo'
        ];
        
        $fields = [];
        $values = [];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $values[] = $membreId;
        
        $sql = "UPDATE Membre SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($values);
    }

    /**
     * Créer un nouveau membre
     */
    public function createMembre($data) {
        $stmt = $this->db->prepare("
            INSERT INTO Membre (
                user_id, nom, prenom, poste, grade,
                specialite, telephone, adresse, biographie,
                photo, equipe_id, date_adhesion, chef_equipe
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['user_id'],
            $data['nom'] ?? '',
            $data['prenom'] ?? '',
            $data['poste'] ?? 'enseignant',
            $data['grade'] ?? '',
            $data['specialite'] ?? '',
            $data['telephone'] ?? '',
            $data['adresse'] ?? '',
            $data['biographie'] ?? '',
            $data['photo'] ?? '',
            $data['equipe_id'] ?? null,
            $data['date_adhesion'] ?? date('Y-m-d'),
            $data['chef_equipe'] ?? 0
        ]);
    }

    /**
     * Compter le nombre total de membres
     */
    public function count() {
        $stmt = $this->db->query("
            SELECT COUNT(*) AS total
            FROM Membre
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    /**
     * Récupérer tous les projets publics (en cours)
     * NOTE: Cette méthode semble mal placée ici - devrait être dans ProjetModel
     */
    public function getAllPublic() {
        $stmt = $this->db->query("
            SELECT *
            FROM Projet
            WHERE status = 'en_cours'
            ORDER BY date_debut DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer l'utilisateur associé à un membre
     */
    public function getUserByMembreId($membreId) {
        $stmt = $this->db->prepare("
            SELECT u.* 
            FROM User u
            INNER JOIN Membre m ON u.id = m.user_id
            WHERE m.id = ?
        ");
        $stmt->execute([$membreId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Vérifier si un membre existe par user_id
     */
    public function existsByUserId($userId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM Membre 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    /**
     * Assigner un membre à une équipe
     */
    public function assignToEquipe($membreId, $equipeId) {
        $stmt = $this->db->prepare("
            UPDATE Membre 
            SET equipe_id = ? 
            WHERE id = ?
        ");
        return $stmt->execute([$equipeId, $membreId]);
    }

    /**
     * Retirer un membre d'une équipe
     */
    public function removeFromEquipe($membreId) {
        $stmt = $this->db->prepare("
            UPDATE Membre 
            SET equipe_id = NULL, chef_equipe = 0 
            WHERE id = ?
        ");
        return $stmt->execute([$membreId]);
    }

    /**
     * Définir ou retirer le statut de chef d'équipe
     */
    public function setChefEquipe($membreId, $isChef = true) {
        $stmt = $this->db->prepare("
            UPDATE Membre 
            SET chef_equipe = ? 
            WHERE id = ?
        ");
        return $stmt->execute([$isChef ? 1 : 0, $membreId]);
    }
}