<?php
require_once __DIR__ . '/Model.php';
// ========================================
// PublicationModel.php - CORRIGÉ SELON SCHÉMA SQL
// ========================================
class PublicationModel extends Model {
    protected $table = 'Publication';
    
    /**
     * Récupérer toutes les publications avec auteurs
     */
    public function getAllWithAuteurs() {
        $stmt = $this->db->query("
            SELECT p.*, 
                   u.username as auteur_nom,
                   pr.titre as projet_titre,
                   (SELECT COUNT(*) FROM Publication_Auteur WHERE publication_id = p.id) as nb_auteurs
            FROM Publication p
            LEFT JOIN Projet pr ON p.projet_id = pr.id
            LEFT JOIN Publication_Auteur pa ON p.id = pa.publication_id
            LEFT JOIN Membre m ON pa.membre_id = m.id
            LEFT JOIN User u ON m.user_id = u.id
            GROUP BY p.id
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Récupérer les publications par équipe (via les auteurs)
     */
    public function getByEquipe($equipeId) {
        $stmt = $this->db->prepare("
            SELECT p.*, 
                   u.username as auteur_nom,
                   e.nom as equipe_nom,
                   pr.titre as projet_titre,
                   (SELECT COUNT(*) FROM Publication_Auteur WHERE publication_id = p.id) as nb_auteurs
            FROM Publication p
            LEFT JOIN Projet pr ON p.projet_id = pr.id
            LEFT JOIN Publication_Auteur pa ON p.id = pa.publication_id
            LEFT JOIN Membre m ON pa.membre_id = m.id
            LEFT JOIN User u ON m.user_id = u.id
            LEFT JOIN Equipe e ON m.equipe_id = e.id
            WHERE m.equipe_id = ?
            GROUP BY p.id
            ORDER BY p.date_publication DESC
        ");
        $stmt->execute([$equipeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer les publications filtrées - VERSION CORRIGÉE
     */
    public function getAllFiltered($filters = []) {
        $sql = "SELECT p.*, 
                       u.username as auteur_nom,
                       pr.titre as projet_titre,
                       (SELECT COUNT(*) FROM Publication_Auteur WHERE publication_id = p.id) as nb_auteurs
                FROM Publication p
                LEFT JOIN Projet pr ON p.projet_id = pr.id
                LEFT JOIN Publication_Auteur pa ON p.id = pa.publication_id
                LEFT JOIN Membre m ON pa.membre_id = m.id
                LEFT JOIN User u ON m.user_id = u.id
                WHERE 1";
        
        $params = [];
        
        // Filtre par type de publication
        if (!empty($filters['type_publication'])) {
            $sql .= " AND p.type_publication = :type_publication";
            $params['type_publication'] = $filters['type_publication'];
        }
        
        // Filtre par domaine
        if (!empty($filters['domaine'])) {
            $sql .= " AND p.domaine = :domaine";
            $params['domaine'] = $filters['domaine'];
        }
        
        // Filtre par année
        if (!empty($filters['annee'])) {
            $sql .= " AND YEAR(p.date_publication) = :annee";
            $params['annee'] = $filters['annee'];
        }
        
        // Filtre par projet
        if (!empty($filters['projet_id'])) {
            $sql .= " AND p.projet_id = :projet_id";
            $params['projet_id'] = $filters['projet_id'];
        }
        
        // Filtre de recherche
        if (!empty($filters['search'])) {
            $sql .= " AND (p.titre LIKE :search 
                           OR p.doi LIKE :search
                           OR u.username LIKE :search
                           OR p.resume LIKE :search
                           OR pr.titre LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        // Grouper par publication
        $sql .= " GROUP BY p.id";
        
        // Tri par défaut (les plus récentes en premier)
        $sql .= " ORDER BY p.date_publication DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer par type
     */
    public function getByType($type) {
        $stmt = $this->db->prepare("
            SELECT p.*, 
                   u.username as auteur_nom,
                   pr.titre as projet_titre
            FROM Publication p
            LEFT JOIN Projet pr ON p.projet_id = pr.id
            LEFT JOIN Publication_Auteur pa ON p.id = pa.publication_id
            LEFT JOIN Membre m ON pa.membre_id = m.id
            LEFT JOIN User u ON m.user_id = u.id
            WHERE p.type_publication = ?
            GROUP BY p.id
        ");
        $stmt->execute([$type]);
        return $stmt->fetchAll();
    }
    
    /**
     * Récupérer les auteurs d'une publication
     */
    public function getAuteurs($publicationId) {
        $stmt = $this->db->prepare("
            SELECT m.*, 
                   u.username,
                   u.email,
                   e.nom as equipe_nom,
                   pa.ordre_auteur
            FROM Publication_Auteur pa
            JOIN Membre m ON pa.membre_id = m.id
            JOIN User u ON m.user_id = u.id
            LEFT JOIN Equipe e ON m.equipe_id = e.id
            WHERE pa.publication_id = ?
            ORDER BY pa.ordre_auteur
        ");
        $stmt->execute([$publicationId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Récupérer les publications par projet
     */
    public function getByProjet($projetId) {
        $stmt = $this->db->prepare("
            SELECT p.*, 
                   (SELECT COUNT(*) FROM Publication_Auteur WHERE publication_id = p.id) as nb_auteurs
            FROM Publication p
            WHERE p.projet_id = ?
            ORDER BY p.date_publication DESC
        ");
        $stmt->execute([$projetId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Récupérer les publications d'un auteur (membre)
     */
    public function getByAuteur($membreId) {
        $stmt = $this->db->prepare("
            SELECT p.*, 
                   pa.ordre_auteur,
                   (SELECT COUNT(*) FROM Publication_Auteur WHERE publication_id = p.id) as nb_auteurs
            FROM Publication p
            JOIN Publication_Auteur pa ON p.id = pa.publication_id
            WHERE pa.membre_id = ?
            ORDER BY p.date_publication DESC
        ");
        $stmt->execute([$membreId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Récupérer les types de publications disponibles
     */
    public function getTypes() {
        $stmt = $this->db->query("SELECT DISTINCT type_publication FROM Publication ORDER BY type_publication");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Récupérer les domaines disponibles
     */
    public function getDomaines() {
        $stmt = $this->db->query("SELECT DISTINCT domaine FROM Publication WHERE domaine IS NOT NULL ORDER BY domaine");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Récupérer les années de publication disponibles
     */
    public function getAnnees() {
        $stmt = $this->db->query("
            SELECT DISTINCT YEAR(date_publication) as annee 
            FROM Publication 
            WHERE date_publication IS NOT NULL 
            ORDER BY annee DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Récupérer les projets disponibles (pour les filtres)
     */
    public function getProjets() {
        $stmt = $this->db->query("
            SELECT DISTINCT pr.id, pr.titre 
            FROM Publication p
            JOIN Projet pr ON p.projet_id = pr.id
            WHERE p.projet_id IS NOT NULL
            ORDER BY pr.titre
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Ajouter un auteur à une publication
     */
    public function addAuteur($publicationId, $membreId, $ordre = 1) {
        $stmt = $this->db->prepare("
            INSERT INTO Publication_Auteur (publication_id, membre_id, ordre_auteur)
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$publicationId, $membreId, $ordre]);
    }
    
    /**
     * Supprimer un auteur d'une publication
     */
    public function removeAuteur($publicationId, $membreId) {
        $stmt = $this->db->prepare("
            DELETE FROM Publication_Auteur 
            WHERE publication_id = ? AND membre_id = ?
        ");
        return $stmt->execute([$publicationId, $membreId]);
    }
    
    /**
     * Statistiques des publications
     */
    public function getStats() {
        $stats = [];
        
        // Nombre total de publications
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM Publication");
        $stats['total'] = $stmt->fetch()['total'];
        
        // Nombre par type
        $stmt = $this->db->query("SELECT type_publication, COUNT(*) as count FROM Publication GROUP BY type_publication");
        $stats['par_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Nombre par année
        $stmt = $this->db->query("
            SELECT YEAR(date_publication) as annee, COUNT(*) as count 
            FROM Publication 
            WHERE date_publication IS NOT NULL
            GROUP BY YEAR(date_publication)
            ORDER BY annee DESC
        ");
        $stats['par_annee'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Nombre par domaine
        $stmt = $this->db->query("
            SELECT domaine, COUNT(*) as count 
            FROM Publication 
            WHERE domaine IS NOT NULL
            GROUP BY domaine
            ORDER BY count DESC
        ");
        $stats['par_domaine'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
}
?>