<?php
require_once __DIR__ . '/Model.php';
// ========================================
// PublicationModel.php - VERSION CORRIGÉE
// ========================================
class PublicationModel extends Model {
    protected $table = 'Publication';
    
    /**
     * Récupérer toutes les publications avec auteurs
     */
    public function getAllWithAuteurs() {
        $stmt = $this->db->query("
            SELECT p.*, 
                   GROUP_CONCAT(u.username SEPARATOR ', ') as auteur_nom,
                   pr.titre as projet_titre,
                   (SELECT COUNT(*) FROM Publication_Auteur WHERE publication_id = p.id) as nb_auteurs
            FROM Publication p
            LEFT JOIN Projet pr ON p.projet_id = pr.id
            LEFT JOIN Publication_Auteur pa ON p.id = pa.publication_id
            LEFT JOIN Membre m ON pa.membre_id = m.id
            LEFT JOIN User u ON m.user_id = u.id
            GROUP BY p.id
            ORDER BY p.date_publication DESC
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Récupérer les publications par équipe
     */
    public function getByEquipe($equipeId) {
        $stmt = $this->db->prepare("
            SELECT p.*, 
                   GROUP_CONCAT(u.username SEPARATOR ', ') as auteur_nom,
                   e.nom as equipe_nom,
                   pr.titre as projet_titre,
                   (SELECT COUNT(*) FROM Publication_Auteur WHERE publication_id = p.id) as nb_auteurs
            FROM Publication p
            LEFT JOIN Projet pr ON p.projet_id = pr.id
            JOIN Publication_Auteur pa ON p.id = pa.publication_id
            JOIN Membre m ON pa.membre_id = m.id
            JOIN User u ON m.user_id = u.id
            LEFT JOIN Equipe e ON m.equipe_id = e.id
            WHERE m.equipe_id = ?
            GROUP BY p.id
            ORDER BY p.date_publication DESC
        ");
        $stmt->execute([$equipeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer les publications filtrées
     */
    public function getAllFiltered($filters = []) {
        $sql = "SELECT p.*, 
                       GROUP_CONCAT(DISTINCT u.username SEPARATOR ', ') as auteur_nom,
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
        
        // Filtre par statut de validation
        if (!empty($filters['statut_validation'])) {
            $sql .= " AND p.statut_validation = :statut_validation";
            $params['statut_validation'] = $filters['statut_validation'];
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
        
        // Tri par défaut
        $sql .= " ORDER BY p.date_publication DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer les publications par statut de validation
     */
    public function getByStatutValidation($statutValidation) {
        $stmt = $this->db->prepare("
            SELECT p.*, 
                   GROUP_CONCAT(DISTINCT u.username SEPARATOR ', ') as auteur_nom,
                   pr.titre as projet_titre,
                   (SELECT COUNT(*) FROM Publication_Auteur WHERE publication_id = p.id) as nb_auteurs
            FROM Publication p
            LEFT JOIN Projet pr ON p.projet_id = pr.id
            LEFT JOIN Publication_Auteur pa ON p.id = pa.publication_id
            LEFT JOIN Membre m ON pa.membre_id = m.id
            LEFT JOIN User u ON m.user_id = u.id
            WHERE p.statut_validation = ?
            GROUP BY p.id
            ORDER BY p.date_publication DESC
        ");
        $stmt->execute([$statutValidation]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer par type
     */
    public function getByType($type) {
        $stmt = $this->db->prepare("
            SELECT p.*, 
                   GROUP_CONCAT(DISTINCT u.username SEPARATOR ', ') as auteur_nom,
                   pr.titre as projet_titre
            FROM Publication p
            LEFT JOIN Projet pr ON p.projet_id = pr.id
            LEFT JOIN Publication_Auteur pa ON p.id = pa.publication_id
            LEFT JOIN Membre m ON pa.membre_id = m.id
            LEFT JOIN User u ON m.user_id = u.id
            WHERE p.type_publication = ?
            GROUP BY p.id
            ORDER BY p.date_publication DESC
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
     * Récupérer les publications d'un membre
     */
    public function getByMembre($membreId, $limit = null) {
        $sql = "SELECT p.*, 
                       pa.ordre_auteur,
                       (SELECT COUNT(*) FROM Publication_Auteur WHERE publication_id = p.id) as nb_auteurs
                FROM Publication p
                JOIN Publication_Auteur pa ON p.id = pa.publication_id
                WHERE pa.membre_id = ?
                ORDER BY p.date_publication DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $this->db->prepare($sql);
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
     * Récupérer les statuts de validation disponibles
     */
    public function getStatuts() {
        $stmt = $this->db->query("
            SELECT DISTINCT statut_validation 
            FROM Publication 
            WHERE statut_validation IS NOT NULL 
            ORDER BY statut_validation
        ");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Récupérer les projets disponibles
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
     * Mettre à jour le statut de validation
     */
    public function updateStatutValidation($publicationId, $statutValidation) {
        $stmt = $this->db->prepare("
            UPDATE Publication 
            SET statut_validation = ?
            WHERE id = ?
        ");
        return $stmt->execute([$statutValidation, $publicationId]);
    }
    
    /**
     * Statistiques des publications
     */
    public function getStats() {
        $stats = [];
        
        // Nombre total
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM Publication");
        $stats['total'] = $stmt->fetch()['total'];
        
        // Par type
        $stmt = $this->db->query("SELECT type_publication, COUNT(*) as count FROM Publication GROUP BY type_publication");
        $stats['par_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Par année
        $stmt = $this->db->query("
            SELECT YEAR(date_publication) as annee, COUNT(*) as count 
            FROM Publication 
            WHERE date_publication IS NOT NULL
            GROUP BY YEAR(date_publication)
            ORDER BY annee DESC
        ");
        $stats['par_annee'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Par domaine
        $stmt = $this->db->query("
            SELECT domaine, COUNT(*) as count 
            FROM Publication 
            WHERE domaine IS NOT NULL
            GROUP BY domaine
            ORDER BY count DESC
        ");
        $stats['par_domaine'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Par statut de validation
        $stmt = $this->db->query("
            SELECT statut_validation, COUNT(*) as count 
            FROM Publication 
            WHERE statut_validation IS NOT NULL
            GROUP BY statut_validation
            ORDER BY count DESC
        ");
        $stats['par_statut'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }

    /**
     * Compter les publications d'un membre
     */
    public function countByMembre($membreId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total
            FROM Publication_Auteur
            WHERE membre_id = ?
        ");
        $stmt->execute([$membreId]);
        return $stmt->fetch()['total'];
    }

    /**
     * Compter les publications d'un membre par statut
     */
    public function countByMembreAndStatus($membreId, $statut) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total
            FROM Publication_Auteur a
            JOIN Publication p ON a.publication_id = p.id
            WHERE a.membre_id = ? AND p.statut_validation = ?
        ");
        $stmt->execute([$membreId, $statut]);
        return $stmt->fetch()['total'];
    }

    /**
     * Récupérer toutes les publications publiques (validées)
     */
    public function getAllPublic() {
        $stmt = $this->db->query("
            SELECT p.*, 
                   GROUP_CONCAT(DISTINCT u.username SEPARATOR ', ') as auteurs
            FROM Publication p
            LEFT JOIN Publication_Auteur pa ON p.id = pa.publication_id
            LEFT JOIN Membre m ON pa.membre_id = m.id
            LEFT JOIN User u ON m.user_id = u.id
            WHERE p.statut_validation = 'en_attente'
            GROUP BY p.id
            ORDER BY p.date_publication DESC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Récupérer les publications récentes (validées)
     */
    public function getRecent($limit = 5) {
    $limit = (int) $limit;

    $stmt = $this->db->query("
        SELECT p.*,
               GROUP_CONCAT(DISTINCT u.username SEPARATOR ', ') as auteurs
        FROM Publication p
        LEFT JOIN Publication_Auteur pa ON p.id = pa.publication_id
        LEFT JOIN Membre m ON pa.membre_id = m.id
        LEFT JOIN User u ON m.user_id = u.id
        WHERE p.statut_validation = 'en_attente'
        GROUP BY p.id
        ORDER BY p.date_publication DESC
        LIMIT $limit
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    
    /**
     * Compter toutes les publications
     */
    public function count() {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM Publication");
        return $stmt->fetch()['total'];
    }

    /**
 * Récupérer les publications par auteur (user ou membre)
 * @param int $membreId
 */
public function getByAuteur($membreId) {
    $stmt = $this->db->prepare("
        SELECT p.*, 
               pa.ordre_auteur,
               GROUP_CONCAT(DISTINCT u.username SEPARATOR ', ') as auteurs,
               pr.titre as projet_titre,
               (SELECT COUNT(*) 
                FROM Publication_Auteur 
                WHERE publication_id = p.id) as nb_auteurs
        FROM Publication p
        JOIN Publication_Auteur pa ON p.id = pa.publication_id
        JOIN Membre m ON pa.membre_id = m.id
        JOIN User u ON m.user_id = u.id
        LEFT JOIN Projet pr ON p.projet_id = pr.id
        WHERE pa.membre_id = ?
        GROUP BY p.id
        ORDER BY p.date_publication DESC
    ");

    $stmt->execute([$membreId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

}
?>