<?php
require_once __DIR__ . '/Model.php';

class ActualiteModel extends Model {

    /* ==============================
       ACTUALITÉS SCIENTIFIQUES
    ============================== */

    public function getAllScientifiques($limit = null) {
        try {
            $sql = "
                SELECT 
                    a.id,
                    a.titre,
                    a.contenu AS description,
                    a.image,
                    a.date_publication,
                    'scientifique' AS source,
                    u.username AS auteur_nom
                FROM actualite_scientifique a
                LEFT JOIN membre m ON a.auteur_id = m.id
                LEFT JOIN user u ON m.user_id = u.id
                ORDER BY a.date_publication DESC
            ";
            
            if ($limit) {
                $sql .= " LIMIT " . intval($limit);
            }
            
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erreur getAllScientifiques(): " . $e->getMessage());
            return [];
        }
    }

    public function createScientifique($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO actualite_scientifique 
                (type_actualite, titre, contenu, image, auteur_id)
                VALUES (?, ?, ?, ?, ?)
            ");
            return $stmt->execute([
                $data['type_actualite'],
                $data['titre'],
                $data['contenu'],
                $data['image'] ?? null,
                $data['auteur_id'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Erreur createScientifique(): " . $e->getMessage());
            return false;
        }
    }

    /* ==============================
       ACTUALITÉS LABORATOIRE
    ============================== */

    public function getAllLaboratoire($limit = null) {
        try {
            $sql = "
                SELECT 
                    id,
                    titre,
                    descriptif AS description,
                    image,
                    date_publication,
                    'laboratoire' AS source
                FROM actualite_laboratoire
                ORDER BY date_publication DESC
            ";
            
            if ($limit) {
                $sql .= " LIMIT " . intval($limit);
            }
            
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erreur getAllLaboratoire(): " . $e->getMessage());
            return [];
        }
    }

    public function createLaboratoire($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO actualite_laboratoire
                (type_actualite, titre, descriptif, image, lien_detail)
                VALUES (?, ?, ?, ?, ?)
            ");
            return $stmt->execute([
                $data['type_actualite'],
                $data['titre'],
                $data['descriptif'],
                $data['image'] ?? null,
                $data['lien_detail'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Erreur createLaboratoire(): " . $e->getMessage());
            return false;
        }
    }

    /* ==============================
       FUSION DES DEUX TABLES
    ============================== */

    public function getRecent($limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT *
                FROM (
                    SELECT 
                        id,
                        titre,
                        contenu AS description,
                        image,
                        date_publication,
                        'scientifique' AS source
                    FROM actualite_scientifique

                    UNION ALL

                    SELECT 
                        id,
                        titre,
                        descriptif AS description,
                        image,
                        date_publication,
                        'laboratoire' AS source
                    FROM actualite_laboratoire
                ) AS actualites
                ORDER BY date_publication DESC
                LIMIT ?
            ");
            $stmt->execute([intval($limit)]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erreur getRecent(): " . $e->getMessage());
            return [];
        }
    }

    public function getAll() {
        try {
            $stmt = $this->db->query("
                SELECT *
                FROM (
                    SELECT 
                        id,
                        titre,
                        contenu AS description,
                        image,
                        date_publication,
                        'scientifique' AS source
                    FROM actualite_scientifique

                    UNION ALL

                    SELECT 
                        id,
                        titre,
                        descriptif AS description,
                        image,
                        date_publication,
                        'laboratoire' AS source
                    FROM actualite_laboratoire
                ) AS actualites
                ORDER BY date_publication DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erreur getAll(): " . $e->getMessage());
            return [];
        }
    }

    /* ==============================
       DIAPORAMA (ACCUEIL)
    ============================== */

    
    }
