<?php
/**
 * Utility Model
 */

class Utility {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get all utilities
     */
    public function getAll() {
        $sql = "SELECT * FROM utilities ORDER BY name ASC";
        return $this->db->getRows($sql);
    }
    
    /**
     * Get utility by ID
     */
    public function getById($id) {
        $id = (int)$id;
        $sql = "SELECT * FROM utilities WHERE id = $id";
        return $this->db->getRow($sql);
    }
    
    /**
     * Create utility
     */
    public function create($name) {
        $name = $this->db->getConnection()->real_escape_string($name);
        $data = ['name' => $name];
        return $this->db->insert('utilities', $data);
    }
    
    /**
     * Update utility
     */
    public function update($id, $name) {
        $id = (int)$id;
        $name = $this->db->getConnection()->real_escape_string($name);
        $data = ['name' => $name];
        return $this->db->update('utilities', $data, "id = $id");
    }
    
    /**
     * Delete utility
     */
    public function delete($id) {
        $id = (int)$id;
        return $this->db->delete('utilities', "id = $id");
    }
}

?>
