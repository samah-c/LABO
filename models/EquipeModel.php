<?php
require_once __DIR__ . '/Model.php';
// ========================================
// EquipeModel.php
// ========================================
class EquipeModel extends Model {
    protected $table = 'Equipe';
    
    public function getAllWithChefs() {
    $stmt = $this->db->query("
        SELECT e.*, 
               u.username as chef_nom,
               (SELECT COUNT(*) FROM Membre WHERE equipe_id = e.id) as nb_membres,
               (SELECT COUNT(*) FROM Membre WHERE equipe_id = e.id AND chef_equipe = 1) as nb_chefs
        FROM Equipe e
        LEFT JOIN Membre m_chef ON e.chef_id = m_chef.id
        LEFT JOIN User u ON m_chef.user_id = u.id
    ");
    return $stmt->fetchAll();
}
    
    public function getEquipeComplete($equipeId) {
        $equipe = $this->getById($equipeId);
        if ($equipe) {
            $stmt = $this->db->prepare("
                SELECT m.*, u.username
                FROM Membre m
                JOIN User u ON m.user_id = u.id
                WHERE m.equipe_id = ?
            ");
            $stmt->execute([$equipeId]);
            $equipe['membres'] = $stmt->fetchAll();
        }
        return $equipe;
    }

   public function getAllFiltered($filters = [])
{
    $sql = "SELECT e.*, 
                   u.username as chef_nom,
                   (SELECT COUNT(*) FROM Membre WHERE equipe_id = e.id) as nb_membres
            FROM Equipe e
            LEFT JOIN Membre m_chef ON e.chef_id = m_chef.id
            LEFT JOIN User u ON m_chef.user_id = u.id
            WHERE 1";
    $params = [];

    if (!empty($filters['domaine'])) {
        $sql .= " AND e.domaine = :domaine";
        $params['domaine'] = $filters['domaine'];
    }

    if (!empty($filters['search'])) {
    $sql .= " AND (e.nom LIKE :search 
                   OR u.username LIKE :search
                   OR e.domaine LIKE :search)";
    $params['search'] = '%' . $filters['search'] . '%';
}

    if (!empty($filters['search'])) {
        $sql .= " AND (e.nom LIKE :search OR u.username LIKE :search)";
        $params['search'] = '%' . $filters['search'] . '%';
    }

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}
