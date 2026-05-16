<?php
/**
 * Admin Revenue Controller
 * 
 * Quản lý doanh thu (commission) của admin
 */

class AdminRevenueController {
    private $revenue;
    private $db;
    
    public function __construct($db) {
        $this->revenue = new AdminRevenue($db);
        $this->db = $db;
    }
    
    /**
     * List admin revenue/commission
     */
    public function listRevenue() {
        $admin_id = $_SESSION['user_id'];
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        
        $revenue = $this->revenue->getRevenue($admin_id, $page, ITEMS_PER_PAGE);
        $total = $this->revenue->getRevenueCount($admin_id);
        $total_pages = ceil($total / ITEMS_PER_PAGE);
        $stats = $this->revenue->getStats($admin_id);
        
        return [
            'revenue' => $revenue,
            'total' => $total,
            'page' => $page,
            'total_pages' => $total_pages,
            'stats' => $stats
        ];
    }
    
    /**
     * Get admin revenue stats
     */
    public function getStats() {
        $admin_id = $_SESSION['user_id'];
        return $this->revenue->getStats($admin_id);
    }
    
    /**
     * Get revenue chart data (last 30 days)
     */
    public function getChartData() {
        $admin_id = $_SESSION['user_id'];
        $data = $this->revenue->getRevenueByDay($admin_id, 30);
        
        $chart_data = [];
        foreach ($data as $item) {
            $chart_data[] = [
                'date' => $item['date'],
                'amount' => $item['total']
            ];
        }
        
        return $chart_data;
    }
}
?>
