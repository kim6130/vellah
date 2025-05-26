<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../services/AdminDashboardService.php';

class AdminDashboardController {
    private $adminDashboardService;

    public function __construct() {
        $this->adminDashboardService = new AdminDashboardService();
    }

    public function getAdminDashboardData() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        
        try {
            error_log("ADMIN DASHBOARD: Starting data collection");

            $userStats = $this->adminDashboardService->getUserActivityStats();
            $avgSavings = $this->adminDashboardService->getAverageSavingsPerCategory();
            $activeUsersCount = $this->adminDashboardService->getActiveUsersThisMonth();
            $topCategories = $this->adminDashboardService->getMostUsedCategories();

            $response = [
                'success' => true,
                'userStats' => $userStats,
                'avgSavingsPerCategory' => $avgSavings,
                'activeUsersThisMonth' => $activeUsersCount,
                'topCategories' => $topCategories,
                'debug' => [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'server' => 'AdminDashboardController'
                ]
            ];

            error_log("ADMIN DASHBOARD: Data collected successfully");
            echo json_encode($response);
            
        } catch (Exception $e) {
            error_log("ADMIN DASHBOARD ERROR: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to load admin dashboard data', 
                'message' => $e->getMessage()
            ]);
        }
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'getAdminDashboardData') {
        error_log("ADMIN DASHBOARD: Request received for admin dashboard data");
        $controller = new AdminDashboardController();
        $controller->getAdminDashboardData();
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Invalid request'
        ]);
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
?>