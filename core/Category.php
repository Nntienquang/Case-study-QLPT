<?php
/**
 * Category Model
 */

class Category {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get all categories
     */
    public function getAll() {
        $sql = "SELECT * FROM categories ORDER BY name ASC";
        return $this->db->getRows($sql);
    }
    
    /**
     * Get category by ID
     */
    public function getById($id) {
        $id = (int)$id;
        $sql = "SELECT * FROM categories WHERE id = $id";
        return $this->db->getRow($sql);
    }
    
    /**
     * Create category
     */
    public function create($name) {
        $name = $this->db->getConnection()->real_escape_string($name);
        $data = ['name' => $name];
        return $this->db->insert('categories', $data);
    }
    
    /**
     * Update category
     */
    public function update($id, $name) {
        $id = (int)$id;
        $name = $this->db->getConnection()->real_escape_string($name);
        $data = ['name' => $name];
        return $this->db->update('categories', $data, "id = $id");
    }
    
    /**
     * Delete category
     */
    public function delete($id) {
        $id = (int)$id;
        return $this->db->delete('categories', "id = $id");
    }
}

?>
