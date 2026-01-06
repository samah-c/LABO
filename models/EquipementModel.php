<?php
require_once __DIR__ . '/Model.php';
// ========================================
// EquipementModel.php - VERSION CORRIGÉE
// ========================================
class EquipementModel extends Model {
    protected $table = 'Equipement';
    
    /**
     * Récupérer les équipements par type
     */
    public function getByType($type) {
        $stmt = $this->db->prepare("
            SELECT e.*, eq.nom as equipe_nom 
            FROM Equipement e
            LEFT JOIN Equipe eq ON e.equipe_id = eq.id
            WHERE e.type_equipement = ?
            ORDER BY e.nom
        ");
        $stmt->execute([$type]);
        return $stmt->fetchAll();
    }
    
    /**
     * Récupérer les équipements par état
     */
    public function getByEtat($etat) {
        $stmt = $this->db->prepare("
            SELECT e.*, eq.nom as equipe_nom 
            FROM Equipement e
            LEFT JOIN Equipe eq ON e.equipe_id = eq.id
            WHERE e.etat = ?
            ORDER BY e.nom
        ");
        $stmt->execute([$etat]);
        return $stmt->fetchAll();
    }
    
    /**
     * Récupérer les équipements disponibles
     */
    public function getDisponibles() {
        return $this->getByEtat('libre');
    }
    
    /**
     * Récupérer les équipements en maintenance
     */
    public function getEnMaintenance() {
        return $this->getByEtat('en_maintenance');
    }
    
    /**
     * Récupérer les équipements réservés
     */
    public function getReserves() {
        return $this->getByEtat('reserve');
    }
    
    /**
     * Récupérer les équipements filtrés
     */
    public function getAllFiltered($filters = []) {
        $sql = "
            SELECT e.*, 
                   eq.nom as equipe_nom,
                   eq.id as equipe_id,
                   (SELECT COUNT(*) FROM Creneau c WHERE c.equipement_id = e.id AND c.statut = 'confirme') as nb_reservations
            FROM Equipement e
            LEFT JOIN Equipe eq ON e.equipe_id = eq.id
            WHERE 1
        ";
        
        $params = [];

        if (!empty($filters['type_equipement'])) {
            $sql .= " AND e.type_equipement = :type_equipement";
            $params['type_equipement'] = $filters['type_equipement'];
        }

        if (!empty($filters['etat'])) {
            $sql .= " AND e.etat = :etat";
            $params['etat'] = $filters['etat'];
        }

        if (!empty($filters['localisation'])) {
            $sql .= " AND e.localisation LIKE :localisation";
            $params['localisation'] = '%' . $filters['localisation'] . '%';
        }

        if (!empty($filters['equipe_id'])) {
            $sql .= " AND e.equipe_id = :equipe_id";
            $params['equipe_id'] = $filters['equipe_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (e.nom LIKE :search OR e.numero_serie LIKE :search OR e.description LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY e.nom";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer les équipements par localisation
     */
    public function getByLocalisation($localisation) {
        $stmt = $this->db->prepare("
            SELECT e.*, eq.nom as equipe_nom 
            FROM Equipement e
            LEFT JOIN Equipe eq ON e.equipe_id = eq.id
            WHERE e.localisation LIKE ? 
            ORDER BY e.nom
        ");
        $stmt->execute(['%' . $localisation . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer les équipements par équipe
     */
    public function getByEquipe($equipeId) {
        $stmt = $this->db->prepare("
            SELECT e.*, 
                   eq.nom as equipe_nom,
                   (SELECT COUNT(*) FROM Creneau c WHERE c.equipement_id = e.id AND c.statut = 'confirme') as nb_reservations
            FROM Equipement e
            LEFT JOIN Equipe eq ON e.equipe_id = eq.id
            WHERE e.equipe_id = ?
            ORDER BY e.nom
        ");
        $stmt->execute([$equipeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer les équipements avec leurs créneaux de réservation
     */
    public function getWithReservations() {
        $stmt = $this->db->query("
            SELECT e.*, 
                   eq.nom as equipe_nom,
                   COUNT(c.id) as nb_reservations,
                   MAX(c.date_fin) as derniere_reservation
            FROM Equipement e
            LEFT JOIN Equipe eq ON e.equipe_id = eq.id
            LEFT JOIN Creneau c ON e.id = c.equipement_id
            GROUP BY e.id
            ORDER BY e.nom
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer les équipements réservés par un membre
     */
    public function getByMembre($membreId) {
        $stmt = $this->db->prepare("
            SELECT DISTINCT e.*, eq.nom as equipe_nom
            FROM Equipement e
            LEFT JOIN Equipe eq ON e.equipe_id = eq.id
            JOIN Creneau c ON e.id = c.equipement_id
            WHERE c.membre_id = ?
            ORDER BY e.nom
        ");
        $stmt->execute([$membreId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer les créneaux d'un équipement
     */
    public function getCreneaux($equipementId) {
        $stmt = $this->db->prepare("
            SELECT c.*, 
                   u.username,
                   m.poste,
                   u.username as membre_nom,
                   e.nom as equipement_nom
            FROM Creneau c
            JOIN Membre m ON c.membre_id = m.id
            JOIN User u ON m.user_id = u.id
            JOIN Equipement e ON c.equipement_id = e.id
            WHERE c.equipement_id = ?
            ORDER BY c.date_debut DESC
        ");
        $stmt->execute([$equipementId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Vérifier la disponibilité d'un équipement
     */
    public function checkDisponibilite($equipementId, $dateDebut, $dateFin) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM Creneau c
            WHERE c.equipement_id = ?
            AND c.statut = 'confirme'
            AND (
                (c.date_debut BETWEEN ? AND ?)
                OR (c.date_fin BETWEEN ? AND ?)
                OR (? BETWEEN c.date_debut AND c.date_fin)
                OR (? BETWEEN c.date_debut AND c.date_fin)
            )
        ");
        $stmt->execute([$equipementId, $dateDebut, $dateFin, $dateDebut, $dateFin, $dateDebut, $dateFin]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] == 0;
    }
    
    /**
     * Récupérer les types d'équipements disponibles
     */
    public function getTypes() {
        $stmt = $this->db->query("SELECT DISTINCT type_equipement FROM Equipement ORDER BY type_equipement");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Récupérer les états disponibles
     */
    public function getEtats() {
        $stmt = $this->db->query("SELECT DISTINCT etat FROM Equipement ORDER BY etat");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Récupérer les localisations disponibles
     */
    public function getLocalisations() {
        $stmt = $this->db->query("SELECT DISTINCT localisation FROM Equipement WHERE localisation IS NOT NULL ORDER BY localisation");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Récupérer les équipes disponibles
     */
    public function getEquipes() {
        $stmt = $this->db->query("
            SELECT DISTINCT eq.id, eq.nom 
            FROM Equipement e
            JOIN Equipe eq ON e.equipe_id = eq.id
            WHERE e.equipe_id IS NOT NULL
            ORDER BY eq.nom
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Statistiques des équipements
     */
    public function getStats() {
        $stats = [];
        
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM Equipement");
        $stats['total'] = $stmt->fetch()['total'];
        
        $stmt = $this->db->query("SELECT type_equipement, COUNT(*) as count FROM Equipement GROUP BY type_equipement");
        $stats['par_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $this->db->query("SELECT etat, COUNT(*) as count FROM Equipement GROUP BY etat");
        $stats['par_etat'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $this->db->query("
            SELECT eq.nom, COUNT(e.id) as count 
            FROM Equipement e
            LEFT JOIN Equipe eq ON e.equipe_id = eq.id
            GROUP BY e.equipe_id
        ");
        $stats['par_equipe'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $this->db->query("SELECT localisation, COUNT(*) as count FROM Equipement WHERE localisation IS NOT NULL GROUP BY localisation");
        $stats['par_localisation'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
    
    /**
     * Mettre à jour l'état d'un équipement
     */
    public function updateEtat($equipementId, $etat) {
        $stmt = $this->db->prepare("UPDATE Equipement SET etat = ? WHERE id = ?");
        return $stmt->execute([$etat, $equipementId]);
    }
    
    /**
     * Mettre à jour l'équipe d'un équipement
     */
    public function updateEquipe($equipementId, $equipeId) {
        $stmt = $this->db->prepare("UPDATE Equipement SET equipe_id = ? WHERE id = ?");
        return $stmt->execute([$equipeId, $equipementId]);
    }
    
    /**
     * Récupérer un équipement avec toutes ses informations
     */
    public function getWithDetails($equipementId) {
        $stmt = $this->db->prepare("
            SELECT e.*, 
                   eq.nom as equipe_nom,
                   eq.domaine as equipe_domaine,
                   u.username as chef_nom
            FROM Equipement e
            LEFT JOIN Equipe eq ON e.equipe_id = eq.id
            LEFT JOIN Membre m_chef ON eq.chef_id = m_chef.id
            LEFT JOIN User u ON m_chef.user_id = u.id
            WHERE e.id = ?
        ");
        $stmt->execute([$equipementId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer les équipements non attribués à une équipe
     */
    public function getSansEquipe() {
        $stmt = $this->db->prepare("
            SELECT e.* 
            FROM Equipement e
            WHERE e.equipe_id IS NULL
            ORDER BY e.nom
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Compter les réservations d'un membre
     */
    public function countReservationsByMembre($membreId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total
            FROM Creneau
            WHERE membre_id = ? AND statut IN ('confirme', 'en_attente')
        ");
        $stmt->execute([$membreId]);
        return $stmt->fetch()['total'];
    }

    /**
     * Récupérer les réservations d'un membre
     */
    public function getReservationsByMembre($membreId) {
        $stmt = $this->db->prepare("
            SELECT c.*, e.nom as equipement_nom, e.type_equipement
            FROM Creneau c
            JOIN Equipement e ON c.equipement_id = e.id
            WHERE c.membre_id = ?
            ORDER BY c.date_debut DESC
        ");
        $stmt->execute([$membreId]);
        return $stmt->fetchAll();
    }

    /**
     * Récupérer une réservation par ID
     */
    public function getReservationById($id) {
        $stmt = $this->db->prepare("
            SELECT c.*, e.nom as equipement_nom
            FROM Creneau c
            JOIN Equipement e ON c.equipement_id = e.id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Vérifier la disponibilité (alias)
     */
    public function isAvailable($equipementId, $dateDebut, $dateFin) {
        return $this->checkDisponibilite($equipementId, $dateDebut, $dateFin);
    }

    /**
     * Créer une réservation
     */
    public function createReservation($data) {
        $stmt = $this->db->prepare("
            INSERT INTO Creneau 
            (equipement_id, membre_id, date_debut, date_fin, motif, statut)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['equipement_id'],
            $data['membre_id'],
            $data['date_debut'],
            $data['date_fin'],
            $data['motif'] ?? null,
            $data['statut'] ?? 'en_attente'
        ]);
    }

    /**
     * Mettre à jour une réservation
     */
    public function updateReservation($id, $data) {
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
        
        $values[] = $id;
        
        $sql = "UPDATE Creneau SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($values);
    }

    /**
     * Récupérer par statut (etat pour Equipement)
     */
    public function getByStatus($status) {
        $stmt = $this->db->prepare("
            SELECT *
            FROM Equipement
            WHERE etat = ?
            ORDER BY nom
        ");
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    }

    /**
     * Statistiques par type
     */
    public function getStatsByType() {
        $stmt = $this->db->query("
            SELECT 
                type_equipement,
                COUNT(*) as nombre,
                SUM(CASE WHEN etat = 'libre' THEN 1 ELSE 0 END) as disponibles,
                SUM(CASE WHEN etat = 'reserve' THEN 1 ELSE 0 END) as reserves,
                SUM(CASE WHEN etat = 'en_maintenance' THEN 1 ELSE 0 END) as en_maintenance
            FROM Equipement
            GROUP BY type_equipement
            ORDER BY nombre DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtenir les équipements les plus réservés sur une période
     * FIXED: Cast $limit to int and use it directly in SQL instead of prepared statement
     */
    public function getTopEquipements($dateDebut, $dateFin, $limit = 10) {
        // Ensure $limit is an integer for security
        $limit = (int) $limit;
        
        // Use the limit directly in the SQL query, not as a prepared statement parameter
        $sql = "
            SELECT 
                e.id,
                e.nom,
                e.type_equipement,
                COUNT(c.id) as nb_reservations,
                COALESCE(SUM(TIMESTAMPDIFF(HOUR, c.date_debut, c.date_fin)), 0) as heures_totales
            FROM Equipement e
            LEFT JOIN Creneau c ON e.id = c.equipement_id
                AND c.statut = 'confirme'
                AND c.date_debut >= ?
                AND c.date_fin <= ?
            GROUP BY e.id, e.nom, e.type_equipement
            ORDER BY nb_reservations DESC, heures_totales DESC
            LIMIT " . $limit;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dateDebut, $dateFin]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>