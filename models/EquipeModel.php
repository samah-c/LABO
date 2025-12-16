<?php
require_once __DIR__ . '/Model.php';
// ========================================
// EquipeModel.php - VERSION CORRIGÉE
// ========================================
class EquipeModel extends Model {
    protected $table = 'Equipe';
    
    /**
     * Récupérer toutes les équipes avec leurs chefs
     */
    public function getAllWithChefs() {
        $stmt = $this->db->query("
            SELECT e.*, 
                   u.username as chef_nom,
                   (SELECT COUNT(*) FROM Membre WHERE equipe_id = e.id) as nb_membres,
                   (SELECT COUNT(*) FROM Membre WHERE equipe_id = e.id AND chef_equipe = 1) as nb_chefs
            FROM Equipe e
            LEFT JOIN Membre m_chef ON e.chef_id = m_chef.id
            LEFT JOIN User u ON m_chef.user_id = u.id
            ORDER BY e.nom
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Alias pour cohérence
     */
    public function getAllWithChef() {
        return $this->getAllWithChefs();
    }
    
    /**
     * Récupérer une équipe complète avec ses membres
     */
    public function getEquipeComplete($equipeId) {
        $equipe = $this->getById($equipeId);
        if ($equipe) {
            $stmt = $this->db->prepare("
                SELECT m.*, u.username, u.email
                FROM Membre m
                JOIN User u ON m.user_id = u.id
                WHERE m.equipe_id = ?
                ORDER BY m.chef_equipe DESC, u.username
            ");
            $stmt->execute([$equipeId]);
            $equipe['membres'] = $stmt->fetchAll();
        }
        return $equipe;
    }

    /**
     * Récupérer les équipes avec filtres
     */
    public function getAllFiltered($filters = []) {
        $sql = "SELECT e.*, 
                       u.username as chef_nom,
                       (SELECT COUNT(*) FROM Membre WHERE equipe_id = e.id) as nb_membres
                FROM Equipe e
                LEFT JOIN Membre m_chef ON e.chef_id = m_chef.id
                LEFT JOIN User u ON m_chef.user_id = u.id
                WHERE 1";
        $params = [];

        // Filtre par domaine
        if (!empty($filters['domaine'])) {
            $sql .= " AND e.domaine = :domaine";
            $params['domaine'] = $filters['domaine'];
        }

        // Filtre de recherche
        if (!empty($filters['search'])) {
            $sql .= " AND (e.nom LIKE :search 
                           OR u.username LIKE :search
                           OR e.domaine LIKE :search
                           OR e.description LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY e.nom";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer les membres d'une équipe
     */
    public function getMembres($equipeId) {
        $stmt = $this->db->prepare("
            SELECT m.*, 
                   u.username, 
                   u.email,
                   m.chef_equipe
            FROM Membre m
            JOIN User u ON m.user_id = u.id
            WHERE m.equipe_id = ?
            ORDER BY m.chef_equipe DESC, u.username
        ");
        $stmt->execute([$equipeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer les domaines disponibles
     */
    public function getDomaines() {
        $stmt = $this->db->query("
            SELECT DISTINCT domaine 
            FROM Equipe 
            WHERE domaine IS NOT NULL 
            ORDER BY domaine
        ");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Ajouter un membre à une équipe
     */
    public function addMembre($equipeId, $membreId) {
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
    public function removeMembre($membreId) {
        $stmt = $this->db->prepare("
            UPDATE Membre 
            SET equipe_id = NULL, chef_equipe = 0 
            WHERE id = ?
        ");
        return $stmt->execute([$membreId]);
    }
    
    /**
     * Définir le chef d'équipe
     */
    public function setChef($equipeId, $membreId) {
        // D'abord retirer le statut chef de tous les membres
        $stmt = $this->db->prepare("
            UPDATE Membre 
            SET chef_equipe = 0 
            WHERE equipe_id = ?
        ");
        $stmt->execute([$equipeId]);
        
        // Puis définir le nouveau chef
        $stmt = $this->db->prepare("
            UPDATE Membre 
            SET chef_equipe = 1 
            WHERE id = ? AND equipe_id = ?
        ");
        $stmt->execute([$membreId, $equipeId]);
        
        // Mettre à jour la table Equipe
        $stmt = $this->db->prepare("
            UPDATE Equipe 
            SET chef_id = ? 
            WHERE id = ?
        ");
        return $stmt->execute([$membreId, $equipeId]);
    }
    
    /**
     * Récupérer les équipes d'un membre
     */
    public function getByMembre($membreId) {
        $stmt = $this->db->prepare("
            SELECT e.*, 
                   u.username as chef_nom,
                   m.chef_equipe
            FROM Equipe e
            JOIN Membre m ON m.equipe_id = e.id
            LEFT JOIN Membre m_chef ON e.chef_id = m_chef.id
            LEFT JOIN User u ON m_chef.user_id = u.id
            WHERE m.id = ?
        ");
        $stmt->execute([$membreId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Statistiques d'une équipe
     */
    public function getStats($equipeId) {
        $stats = [];
        
        // Nombre de membres
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM Membre 
            WHERE equipe_id = ?
        ");
        $stmt->execute([$equipeId]);
        $stats['nb_membres'] = $stmt->fetch()['total'];
        
        // Nombre de projets
        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT p.id) as total
            FROM Projet p
            JOIN Projet_Membre pm ON p.id = pm.projet_id
            JOIN Membre m ON pm.membre_id = m.id
            WHERE m.equipe_id = ?
        ");
        $stmt->execute([$equipeId]);
        $stats['nb_projets'] = $stmt->fetch()['total'];
        
        // Nombre de publications
        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT pub.id) as total
            FROM Publication pub
            JOIN Publication_Auteur pa ON pub.id = pa.publication_id
            JOIN Membre m ON pa.membre_id = m.id
            WHERE m.equipe_id = ?
        ");
        $stmt->execute([$equipeId]);
        $stats['nb_publications'] = $stmt->fetch()['total'];
        
        // Nombre d'équipements
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM Equipement 
            WHERE equipe_id = ?
        ");
        $stmt->execute([$equipeId]);
        $stats['nb_equipements'] = $stmt->fetch()['total'];
        
        return $stats;
    }
    
    /**
     * Compter toutes les équipes
     */
    public function count() {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM Equipe");
        return $stmt->fetch()['total'];
    }
}
?>