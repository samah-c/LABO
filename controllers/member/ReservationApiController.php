<?php
/**
 * ReservationApiController.php - API pour les réservations membre
 * À créer dans : controllers/member/ReservationApiController.php
 */

require_once __DIR__ . '/../../models/EquipementModel.php';
require_once __DIR__ . '/../../models/MembreModel.php';
require_once __DIR__ . '/../../lib/helpers.php';

class ReservationApiController {
    private $equipementModel;
    private $membreModel;
    private $membreId;
    
    public function __construct() {
        $this->equipementModel = new EquipementModel();
        $this->membreModel = new MembreModel();
        
        // Récupérer l'ID du membre connecté
        $userId = session('user_id');
        $membre = $this->membreModel->getByUserId($userId);
        $this->membreId = $membre['id'] ?? null;
    }
    
    /**
     * Vérifier les conflits de réservation
     */
    public function checkConflicts() {
        header('Content-Type: application/json');
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $equipementId = $data['equipement_id'] ?? null;
            $dateDebut = $data['date_debut'] ?? null;
            $dateFin = $data['date_fin'] ?? null;
            
            if (!$equipementId || !$dateDebut || !$dateFin) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Données manquantes'
                ]);
                return;
            }
            
            // Récupérer les réservations en conflit
            $db = Database::getInstance()->getConnection();
            
            $stmt = $db->prepare("
                SELECT c.*, 
                       e.nom as equipement_nom,
                       u.username as membre_nom
                FROM Creneau c
                JOIN Equipement e ON c.equipement_id = e.id
                JOIN Membre m ON c.membre_id = m.id
                JOIN User u ON m.user_id = u.id
                WHERE c.equipement_id = ?
                AND c.statut IN ('confirme', 'en_attente')
                AND (
                    (c.date_debut BETWEEN ? AND ?)
                    OR (c.date_fin BETWEEN ? AND ?)
                    OR (? BETWEEN c.date_debut AND c.date_fin)
                    OR (? BETWEEN c.date_debut AND c.date_fin)
                )
                ORDER BY c.date_debut
            ");
            
            $stmt->execute([
                $equipementId,
                $dateDebut, $dateFin,
                $dateDebut, $dateFin,
                $dateDebut, $dateFin
            ]);
            
            $conflicts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'conflicts' => $conflicts,
                'has_conflicts' => count($conflicts) > 0
            ]);
            
        } catch (Exception $e) {
            error_log("Erreur checkConflicts: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors de la vérification'
            ]);
        }
    }
    
    /**
     * Statistiques d'un équipement
     */
    public function getEquipementStats($equipementId) {
        header('Content-Type: application/json');
        
        try {
            $db = Database::getInstance()->getConnection();
            
            // Réservations ce mois
            $stmt = $db->prepare("
                SELECT COUNT(*) as total
                FROM Creneau
                WHERE equipement_id = ?
                AND statut = 'confirme'
                AND MONTH(date_debut) = MONTH(CURRENT_DATE())
                AND YEAR(date_debut) = YEAR(CURRENT_DATE())
            ");
            $stmt->execute([$equipementId]);
            $reservationsMois = $stmt->fetch()['total'];
            
            // Taux d'occupation (heures réservées / heures totales du mois)
            $stmt = $db->prepare("
                SELECT SUM(TIMESTAMPDIFF(HOUR, date_debut, date_fin)) as heures
                FROM Creneau
                WHERE equipement_id = ?
                AND statut = 'confirme'
                AND MONTH(date_debut) = MONTH(CURRENT_DATE())
                AND YEAR(date_debut) = YEAR(CURRENT_DATE())
            ");
            $stmt->execute([$equipementId]);
            $heuresReservees = $stmt->fetch()['heures'] ?? 0;
            
            // Heures ouvrables dans le mois (environ 160h)
            $heuresMois = 160;
            $tauxOccupation = $heuresMois > 0 ? round(($heuresReservees / $heuresMois) * 100) : 0;
            
            // Durée moyenne des réservations
            $stmt = $db->prepare("
                SELECT AVG(TIMESTAMPDIFF(HOUR, date_debut, date_fin)) as moyenne
                FROM Creneau
                WHERE equipement_id = ?
                AND statut = 'confirme'
            ");
            $stmt->execute([$equipementId]);
            $dureeMoyenne = round($stmt->fetch()['moyenne'] ?? 0);
            
            // Prochaine disponibilité
            $stmt = $db->prepare("
                SELECT MIN(date_fin) as prochaine_dispo
                FROM Creneau
                WHERE equipement_id = ?
                AND statut = 'confirme'
                AND date_fin > NOW()
            ");
            $stmt->execute([$equipementId]);
            $prochaineDispo = $stmt->fetch()['prochaine_dispo'];
            
            if ($prochaineDispo) {
                $prochaineDispo = date('d/m/Y H:i', strtotime($prochaineDispo));
            } else {
                $prochaineDispo = 'Maintenant';
            }
            
            // Réservations à venir
            $stmt = $db->prepare("
                SELECT c.date_debut, c.date_fin, u.username as membre_nom
                FROM Creneau c
                JOIN Membre m ON c.membre_id = m.id
                JOIN User u ON m.user_id = u.id
                WHERE c.equipement_id = ?
                AND c.statut = 'confirme'
                AND c.date_debut > NOW()
                ORDER BY c.date_debut
                LIMIT 5
            ");
            $stmt->execute([$equipementId]);
            $reservationsRecentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'stats' => [
                    'reservations_mois' => $reservationsMois,
                    'taux_occupation' => $tauxOccupation,
                    'duree_moyenne' => $dureeMoyenne,
                    'prochaine_dispo' => $prochaineDispo,
                    'reservations_recentes' => $reservationsRecentes
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Erreur getEquipementStats: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors du chargement des statistiques'
            ]);
        }
    }
    
    /**
     * Statistiques globales du membre
     */
    public function getGlobalStats() {
        header('Content-Type: application/json');
        
        if (!$this->membreId) {
            echo json_encode(['success' => false, 'error' => 'Membre non trouvé']);
            return;
        }
        
        try {
            $db = Database::getInstance()->getConnection();
            
            // Total réservations
            $stmt = $db->prepare("
                SELECT COUNT(*) as total
                FROM Creneau
                WHERE membre_id = ?
            ");
            $stmt->execute([$this->membreId]);
            $totalReservations = $stmt->fetch()['total'];
            
            // Réservations ce mois
            $stmt = $db->prepare("
                SELECT COUNT(*) as total
                FROM Creneau
                WHERE membre_id = ?
                AND MONTH(date_debut) = MONTH(CURRENT_DATE())
                AND YEAR(date_debut) = YEAR(CURRENT_DATE())
            ");
            $stmt->execute([$this->membreId]);
            $reservationsMois = $stmt->fetch()['total'];
            
            // Heures utilisées
            $stmt = $db->prepare("
                SELECT SUM(TIMESTAMPDIFF(HOUR, date_debut, date_fin)) as heures
                FROM Creneau
                WHERE membre_id = ?
                AND statut = 'confirme'
            ");
            $stmt->execute([$this->membreId]);
            $heuresUtilisees = $stmt->fetch()['heures'] ?? 0;
            
            // Taux d'annulation
            $stmt = $db->prepare("
                SELECT 
                    COUNT(CASE WHEN statut = 'annule' THEN 1 END) as annulees,
                    COUNT(*) as total
                FROM Creneau
                WHERE membre_id = ?
            ");
            $stmt->execute([$this->membreId]);
            $result = $stmt->fetch();
            $tauxAnnulation = $result['total'] > 0 ? 
                round(($result['annulees'] / $result['total']) * 100) : 0;
            
            // Équipements favoris
            $stmt = $db->prepare("
                SELECT e.nom, COUNT(*) as count
                FROM Creneau c
                JOIN Equipement e ON c.equipement_id = e.id
                WHERE c.membre_id = ?
                GROUP BY c.equipement_id
                ORDER BY count DESC
                LIMIT 5
            ");
            $stmt->execute([$this->membreId]);
            $equipementsFavoris = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'stats' => [
                    'total_reservations' => $totalReservations,
                    'reservations_mois' => $reservationsMois,
                    'heures_utilisees' => $heuresUtilisees,
                    'taux_annulation' => $tauxAnnulation,
                    'equipements_favoris' => $equipementsFavoris
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Erreur getGlobalStats: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors du chargement des statistiques'
            ]);
        }
    }
    
    /**
     * Données pour le calendrier
     */
    public function getCalendarData() {
        header('Content-Type: application/json');
        
        if (!$this->membreId) {
            echo json_encode(['success' => false, 'error' => 'Membre non trouvé']);
            return;
        }
        
        try {
            $db = Database::getInstance()->getConnection();
            
            $stmt = $db->prepare("
                SELECT 
                    c.id,
                    c.date_debut as start,
                    c.date_fin as end,
                    e.nom as title,
                    c.statut,
                    e.type_equipement
                FROM Creneau c
                JOIN Equipement e ON c.equipement_id = e.id
                WHERE c.membre_id = ?
                AND c.date_debut >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
                AND c.date_debut <= DATE_ADD(NOW(), INTERVAL 3 MONTH)
                ORDER BY c.date_debut
            ");
            $stmt->execute([$this->membreId]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'events' => $events
            ]);
            
        } catch (Exception $e) {
            error_log("Erreur getCalendarData: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors du chargement du calendrier'
            ]);
        }
    }
    
    /**
     * Réservations à venir (notifications)
     */
    public function getUpcomingReservations() {
        header('Content-Type: application/json');
        
        if (!$this->membreId) {
            echo json_encode(['success' => false, 'error' => 'Membre non trouvé']);
            return;
        }
        
        try {
            $db = Database::getInstance()->getConnection();
            
            // Réservations dans les prochaines 24h
            $stmt = $db->prepare("
                SELECT 
                    c.*,
                    e.nom as equipement_nom,
                    e.type_equipement,
                    TIMESTAMPDIFF(HOUR, NOW(), c.date_debut) as heures_restantes
                FROM Creneau c
                JOIN Equipement e ON c.equipement_id = e.id
                WHERE c.membre_id = ?
                AND c.statut IN ('confirme', 'en_attente')
                AND c.date_debut > NOW()
                AND c.date_debut <= DATE_ADD(NOW(), INTERVAL 24 HOUR)
                ORDER BY c.date_debut
            ");
            $stmt->execute([$this->membreId]);
            $upcoming = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'reservations' => $upcoming,
                'count' => count($upcoming)
            ]);
            
        } catch (Exception $e) {
            error_log("Erreur getUpcomingReservations: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors du chargement'
            ]);
        }
    }
}
?>