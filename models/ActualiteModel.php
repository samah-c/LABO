<?php
require_once __DIR__ . '/Model.php';

class ActualiteModel extends Model {

    /* ==============================
       ACTUALITÉS SCIENTIFIQUES
    ============================== */

    public function getAllScientifiques($limit = null) {
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
        if ($limit) $sql .= " LIMIT " . intval($limit);
        return $this->db->query($sql)->fetchAll();
    }

    public function createScientifique($data) {
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
    }

    /* ==============================
       ACTUALITÉS LABORATOIRE
    ============================== */

    public function getAllLaboratoire($limit = null) {
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
        if ($limit) $sql .= " LIMIT " . intval($limit);
        return $this->db->query($sql)->fetchAll();
    }

    public function createLaboratoire($data) {
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
    }

    /* ==============================
       FUSION DES DEUX TABLES
    ============================== */

    public function getRecent($limit = 10) {
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
        return $stmt->fetchAll();
    }

 
    public function getAll() {
        return $this->db->query("
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
        ")->fetchAll();
    }

    /* ==============================
       DIAPORAMA (ACCUEIL)
    ============================== */

    public function getDiaporama($limit = 5) {
        $stmt = $this->db->prepare("
            SELECT *
            FROM (
                SELECT 
                    id,
                    titre,
                    LEFT(contenu, 200) AS description,
                    image,
                    date_publication,
                    'scientifique' AS source
                FROM actualite_scientifique

                UNION ALL

                SELECT 
                    id,
                    titre,
                    LEFT(descriptif, 200) AS description,
                    image,
                    date_publication,
                    'laboratoire' AS source
                FROM actualite_laboratoire
            ) AS actualites
            ORDER BY date_publication DESC
            LIMIT ?
        ");
        $stmt->execute([intval($limit)]);
        return $stmt->fetchAll();
    }
}
