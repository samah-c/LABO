<?php
/**
 * CreneauModel.php - Modèle pour la gestion des créneaux/réservations
 * Basé sur la table Creneau de votre base de données
 */

require_once __DIR__ . '/Model.php';

class CreneauModel extends Model {
    protected $table = 'Creneau';
    
    /**
     * Récupérer tous les créneaux avec détails
     */
    public function getAll() {
        $sql = "SELECT c.*, 
                e.nom as equipement_nom,
                u.username as membre_nom,
                m.poste as membre_poste
                FROM Creneau c
                JOIN Equipement e ON c.equipement_id = e.id
                JOIN Membre m ON c.membre_id = m.id
                JOIN User u ON m.user_id = u.id
                ORDER BY c.date_debut DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer les créneaux d'un équipement
     */
    public function getByEquipement($equipementId) {
        $sql = "SELECT c.*, 
                u.username as membre_nom,
                m.poste as membre_poste
                FROM Creneau c
                JOIN Membre m ON c.membre_id = m.id
                JOIN User u ON m.user_id = u.id
                WHERE c.equipement_id = :equipement_id
                ORDER BY c.date_debut DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':equipement_id' => $equipementId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer les créneaux d'un membre
     */
    public function getByMembre($membreId) {
        $sql = "SELECT c.*, 
                e.nom as equipement_nom,
                e.type_equipement
                FROM Creneau c
                JOIN Equipement e ON c.equipement_id = e.id
                WHERE c.membre_id = :membre_id
                ORDER BY c.date_debut DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':membre_id' => $membreId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Vérifier les créneaux futurs pour un équipement
     */
    public function hasFutureReservations($equipementId) {
        $sql = "SELECT COUNT(*) as total 
                FROM Creneau 
                WHERE equipement_id = :equipement_id 
                AND date_fin > NOW()
                AND statut IN ('confirme', 'en_attente')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':equipement_id' => $equipementId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] > 0;
    }
    
    /**
     * Détecter les conflits de réservation
     */
    public function getConflits() {
        $sql = "SELECT c1.*, c2.id as conflit_id,
                e.nom as equipement_nom,
                u1.username as membre1,
                u2.username as membre2
                FROM Creneau c1
                JOIN Creneau c2 ON c1.equipement_id = c2.equipement_id
                    AND c1.id < c2.id
                    AND (
                        (c1.date_debut BETWEEN c2.date_debut AND c2.date_fin)
                        OR (c1.date_fin BETWEEN c2.date_debut AND c2.date_fin)
                        OR (c2.date_debut BETWEEN c1.date_debut AND c1.date_fin)
                    )
                JOIN Equipement e ON c1.equipement_id = e.id
                JOIN Membre m1 ON c1.membre_id = m1.id
                JOIN Membre m2 ON c2.membre_id = m2.id
                JOIN User u1 ON m1.user_id = u1.id
                JOIN User u2 ON m2.user_id = u2.id
                WHERE c1.statut IN ('confirme', 'en_attente')
                AND c2.statut IN ('confirme', 'en_attente')
                ORDER BY c1.date_debut DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Compter les créneaux entre deux dates
     */
    public function countBetween($dateDebut, $dateFin) {
        $sql = "SELECT COUNT(*) as total 
                FROM Creneau 
                WHERE DATE(date_debut) BETWEEN :date_debut AND :date_fin";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':date_debut' => $dateDebut,
            ':date_fin' => $dateFin
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
    
    /**
     * Statistiques par membre
     */
    public function getStatsParMembre($dateDebut, $dateFin) {
        $sql = "SELECT 
                m.id, u.username,
                COUNT(c.id) as nb_reservations,
                SUM(TIMESTAMPDIFF(HOUR, c.date_debut, c.date_fin)) as heures_totales
                FROM Membre m
                JOIN User u ON m.user_id = u.id
                JOIN Creneau c ON m.id = c.membre_id
                WHERE DATE(c.date_debut) BETWEEN :date_debut AND :date_fin
                GROUP BY m.id
                ORDER BY nb_reservations DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':date_debut' => $dateDebut,
            ':date_fin' => $dateFin
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Créer un créneau
     */
    public function create($data) {
        $sql = "INSERT INTO Creneau (
            equipement_id, membre_id, date_debut, date_fin, 
            motif, statut
        ) VALUES (
            :equipement_id, :membre_id, :date_debut, :date_fin,
            :motif, :statut
        )";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':equipement_id' => $data['equipement_id'],
            ':membre_id' => $data['membre_id'],
            ':date_debut' => $data['date_debut'],
            ':date_fin' => $data['date_fin'],
            ':motif' => $data['motif'] ?? null,
            ':statut' => $data['statut'] ?? 'en_attente'
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Mettre à jour le statut d'un créneau
     */
    public function updateStatut($id, $statut) {
        $sql = "UPDATE Creneau SET statut = :statut WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':statut' => $statut
        ]);
    }
    
    /**
     * Supprimer un créneau
     */
    public function delete($id) {
        $sql = "DELETE FROM Creneau WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Vérifier la disponibilité d'un équipement
     */
    public function checkDisponibilite($equipementId, $dateDebut, $dateFin) {
        $sql = "SELECT COUNT(*) as count
                FROM Creneau c
                WHERE c.equipement_id = :equipement_id
                AND c.statut = 'confirme'
                AND (
                    (c.date_debut BETWEEN :date_debut1 AND :date_fin1)
                    OR (c.date_fin BETWEEN :date_debut2 AND :date_fin2)
                    OR (:date_debut3 BETWEEN c.date_debut AND c.date_fin)
                    OR (:date_fin3 BETWEEN c.date_debut AND c.date_fin)
                )";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':equipement_id' => $equipementId, 
            ':date_debut1' => $dateDebut, 
            ':date_fin1' => $dateFin, 
            ':date_debut2' => $dateDebut, 
            ':date_fin2' => $dateFin, 
            ':date_debut3' => $dateDebut, 
            ':date_fin3' => $dateFin
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] == 0;
    }
}
?>