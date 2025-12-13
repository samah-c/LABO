<?php
// ========================================
// Model.php - CLASSE DE BASE
// ========================================
require_once __DIR__ . '/../config/database.php';

abstract class Model {
    protected $db;
    protected $table;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function create($data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        return $this->db->lastInsertId();
    }
    
    public function update($id, $data) {
        $sets = [];
        foreach (array_keys($data) as $key) {
            $sets[] = "$key = ?";
        }
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $values = array_values($data);
        $values[] = $id;
        return $stmt->execute($values);
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
