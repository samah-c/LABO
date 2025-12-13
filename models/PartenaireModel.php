<?php
require_once __DIR__ . '/Model.php';
// ========================================
// PartenaireModel.php
// ========================================
class PartenaireModel extends Model {
    protected $table = 'Partenaire';
    
    public function getByType($type) {
        $stmt = $this->db->prepare("SELECT * FROM Partenaire WHERE type_partenaire = ?");
        $stmt->execute([$type]);
        return $stmt->fetchAll();
    }
    
    public function getPartenairesInstitutionnels() {
        $stmt = $this->db->query("
            SELECT p.*, pi.type_institution, pi.pays
            FROM Partenaire p
            JOIN Partenaire_Institutionnel pi ON p.id = pi.partenaire_id
        ");
        return $stmt->fetchAll();
    }
    
    public function getPartenairesIndustriels() {
        $stmt = $this->db->query("
            SELECT p.*, pi.secteur_activite, pi.taille_entreprise
            FROM Partenaire p
            JOIN Partenaire_Industriel pi ON p.id = pi.partenaire_id
        ");
        return $stmt->fetchAll();
    }
}