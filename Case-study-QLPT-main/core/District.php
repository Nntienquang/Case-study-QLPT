<?php
/**
 * District Model
 */

class District {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get all districts
     */
    public function getAll() {
        $sql = "SELECT * FROM districts ORDER BY name ASC";
        return $this->db->getRows($sql);
    }
    
    /**
     * Get district by ID
     */
    public function getById($id) {
        $id = (int)$id;
        $sql = "SELECT * FROM districts WHERE id = $id";
        return $this->db->getRow($sql);
    }
    
    /**
     * Create district
     */
    public function create($name) {
        $name = $this->db->getConnection()->real_escape_string($name);
        $data = ['name' => $name];
        return $this->db->insert('districts', $data);
    }
    
    /**
     * Update district
     */
    public function update($id, $name) {
        $id = (int)$id;
        $name = $this->db->getConnection()->real_escape_string($name);
        $data = ['name' => $name];
        return $this->db->update('districts', $data, "id = $id");
    }
    
    /**
     * Delete district
     */
    public function delete($id) {
        $id = (int)$id;
        return $this->db->delete('districts', "id = $id");
    }
}

?>
