<?php
require_once __DIR__ . '/Model.php';

/**
 * PartenaireModel.php - Modèle pour la gestion des partenaires
 * Version sécurisée avec gestion d'erreurs
 */
class PartenaireModel extends Model {
    protected $table = 'Partenaire';
    
    /**
     * Récupérer tous les partenaires
     */
    public function getAll() {
        try {
            error_log("PartenaireModel::getAll() - Début");
            
            $sql = "
                SELECT 
                    id,
                    nom,
                    type_partenaire,
                    description,
                    contact,
                    email,
                    telephone,
                    site_web,
                    adresse,
                    date_partenariat
                FROM Partenaire
                ORDER BY nom ASC
            ";
            
            error_log("SQL: " . $sql);
            
            $stmt = $this->db->query($sql);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Résultats: " . count($result) . " partenaires");
            
            return $result;
            
        } catch (Exception $e) {
            error_log("✗ ERREUR getAll(): " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [];
        }
    }
    
    /**
     * Récupérer les partenaires avec type formaté
     */
    public function getAllWithType() {
        try {
            error_log("PartenaireModel::getAllWithType() - Début");
            
            $sql = "
                SELECT 
                    id,
                    nom,
                    type_partenaire,
                    CASE 
                        WHEN type_partenaire = 'universite' THEN 'Université'
                        WHEN type_partenaire = 'entreprise' THEN 'Entreprise'
                        WHEN type_partenaire = 'organisme' THEN 'Organisme'
                        ELSE 'Partenaire'
                    END as type,
                    description,
                    contact,
                    email,
                    site_web
                FROM Partenaire
                ORDER BY nom ASC
            ";
            
            $stmt = $this->db->query($sql);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Résultats getAllWithType: " . count($result) . " partenaires");
            
            return $result;
            
        } catch (Exception $e) {
            error_log("✗ ERREUR getAllWithType(): " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer les partenaires récents (pour page d'accueil)
     * VERSION CORRIGÉE - gère les NULL et les logos manquants
     */
    public function getRecent($limit = 6) {
        try {
            error_log("PartenaireModel::getRecent($limit) - Début");
            
            $sql = "
                SELECT 
                    id,
                    nom,
                    type_partenaire,
                    CASE 
                        WHEN type_partenaire = 'universite' THEN 'Université'
                        WHEN type_partenaire = 'entreprise' THEN 'Entreprise'
                        WHEN type_partenaire = 'organisme' THEN 'Organisme'
                        ELSE 'Partenaire'
                    END as type,
                    description,
                    site_web,
                    NULL as logo
                FROM Partenaire
                ORDER BY nom ASC
                LIMIT ?
            ";
            
            error_log("SQL getRecent: " . $sql);
            error_log("Limit: " . intval($limit));
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([intval($limit)]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("✓ Résultats getRecent: " . count($result) . " partenaires");
            
            // Si aucun résultat, essayer sans LIMIT
            if (empty($result)) {
                error_log("⚠ Aucun résultat avec LIMIT, essai sans LIMIT...");
                $sql2 = "SELECT id, nom, type_partenaire FROM Partenaire";
                $stmt2 = $this->db->query($sql2);
                $result = $stmt2->fetchAll(PDO::FETCH_ASSOC);
                error_log("Sans LIMIT: " . count($result) . " partenaires");
                
                // Formater le type
                foreach ($result as &$p) {
                    $p['type'] = match($p['type_partenaire']) {
                        'universite' => 'Université',
                        'entreprise' => 'Entreprise',
                        'organisme' => 'Organisme',
                        default => 'Partenaire'
                    };
                    $p['logo'] = null;
                }
                
                // Limiter le résultat
                $result = array_slice($result, 0, intval($limit));
            }
            
            // Debug: afficher chaque partenaire
            foreach ($result as $idx => $p) {
                error_log("  Partenaire #$idx: " . ($p['nom'] ?? 'SANS NOM') . " - Type: " . ($p['type'] ?? 'N/A'));
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("✗ ERREUR CRITIQUE getRecent(): " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Tentative de récupération simple en cas d'erreur
            try {
                error_log("Tentative de récupération simple...");
                $stmt = $this->db->query("SELECT id, nom FROM Partenaire LIMIT " . intval($limit));
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Ajouter les champs manquants
                foreach ($result as &$p) {
                    $p['type'] = 'Partenaire';
                    $p['type_partenaire'] = 'autre';
                    $p['logo'] = null;
                }
                
                error_log("✓ Récupération simple réussie: " . count($result) . " partenaires");
                return $result;
            } catch (Exception $e2) {
                error_log("✗ Échec de la récupération simple: " . $e2->getMessage());
                return [];
            }
        }
    }
    
    /**
     * Récupérer par type
     */
    public function getByType($type) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM Partenaire 
                WHERE type_partenaire = ?
                ORDER BY nom ASC
            ");
            $stmt->execute([$type]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getByType(): " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer les partenaires institutionnels
     */
    public function getPartenairesInstitutionnels() {
        try {
            $stmt = $this->db->query("
                SELECT p.*, pi.type_institution, pi.pays
                FROM Partenaire p
                JOIN Partenaire_Institutionnel pi ON p.id = pi.partenaire_id
                ORDER BY p.nom ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getPartenairesInstitutionnels(): " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer les partenaires industriels
     */
    public function getPartenairesIndustriels() {
        try {
            $stmt = $this->db->query("
                SELECT p.*, pi.secteur_activite, pi.taille_entreprise
                FROM Partenaire p
                JOIN Partenaire_Industriel pi ON p.id = pi.partenaire_id
                ORDER BY p.nom ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getPartenairesIndustriels(): " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Compter les partenaires
     */
    public function count() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM Partenaire");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['total'] : 0;
        } catch (Exception $e) {
            error_log("Erreur count(): " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Statistiques des partenaires
     */
    public function getStats() {
        $stats = [];
        
        try {
            // Total
            $stats['total'] = $this->count();
            
            // Par type
            $stmt = $this->db->query("
                SELECT type_partenaire, COUNT(*) as count 
                FROM Partenaire 
                GROUP BY type_partenaire
            ");
            $stats['par_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erreur getStats(): " . $e->getMessage());
            $stats['total'] = 0;
            $stats['par_type'] = [];
        }
        
        return $stats;
    }
}