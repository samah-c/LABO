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
        return $stmt->fetchAll();
    }
    
    /**
     * Récupérer un membre par son user_id
     */
    public function getByUserId($userId) {
        $stmt = $this->db->prepare("
            SELECT m.*, u.username, u.email 
            FROM Membre m
            JOIN User u ON m.user_id = u.id
            WHERE m.user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    /**
     * Récupérer un membre avec ses informations complètes
     */
    public function getWithDetails($membreId) {
        $stmt = $this->db->prepare("
            SELECT m.*, u.username, u.email, e.nom as equipe_nom
            FROM Membre m
            JOIN User u ON m.user_id = u.id
            LEFT JOIN Equipe e ON m.equipe_id = e.id
            WHERE m.id = ?
        ");
        $stmt->execute([$membreId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Compter le nombre total de membres
     */
    public function count() {
        $stmt = $this->db->query("
            SELECT COUNT(*) AS total
            FROM Membre
        ");
        return $stmt->fetch()['total'];
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
        return $stmt->fetchAll();
    }

    /**
     * Récupérer l'utilisateur associé à un membre
     * CORRECTION: Utilisation de prepare() au lieu de query() avec paramètres
     */
    public function getUserByMembreId($membreId) {
        $stmt = $this->db->prepare("
            SELECT u.* 
            FROM User u
            INNER JOIN Membre m ON u.id = m.user_id
            WHERE m.id = :membre_id
        ");
        $stmt->execute(['membre_id' => $membreId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}