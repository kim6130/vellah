<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../services/CategoryService.php';

class CategoryController {
    private $categoryService;

    public function __construct() {
        $this->categoryService = new CategoryService();
    }

    public function getCategoryData() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        
        try {
            error_log("CATEGORY CONTROLLER: Starting data collection");

            $mostUsed = $this->categoryService->getMostUsedCategory();
            $leastUsed = $this->categoryService->getLeastUsedCategory();
            $allCategories = $this->categoryService->getAllSystemCategories();
            $usageStats = $this->categoryService->getCategoryUsageStats();

            $response = [
                'success' => true,
                'mostUsedCategory' => $mostUsed,
                'leastUsedCategory' => $leastUsed,
                'allCategories' => $allCategories,
                'usageStats' => $usageStats,
                'debug' => [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'server' => 'CategoryController'
                ]
            ];

            error_log("CATEGORY CONTROLLER: Data collected successfully");
            echo json_encode($response);
            
        } catch (Exception $e) {
            error_log("CATEGORY CONTROLLER ERROR: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to load category data', 
                'message' => $e->getMessage()
            ]);
        }
    }

    public function addCategory() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $categoryName = $input['categoryName'] ?? '';

            error_log("CATEGORY CONTROLLER: Adding new category - " . $categoryName);

            $categoryId = $this->categoryService->addNewCategory($categoryName);

            $response = [
                'success' => true,
                'categoryId' => $categoryId,
                'message' => 'Category added successfully',
                'debug' => [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'server' => 'CategoryController'
                ]
            ];

            error_log("CATEGORY CONTROLLER: Category added successfully with ID " . $categoryId);
            echo json_encode($response);
            
        } catch (Exception $e) {
            error_log("CATEGORY CONTROLLER ADD ERROR: " . $e->getMessage());
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'getCategoryData') {
        error_log("CATEGORY CONTROLLER: Request received for category data");
        $controller = new CategoryController();
        $controller->getCategoryData();
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'addCategory') {
        error_log("CATEGORY CONTROLLER: Request received to add category");
        $controller = new CategoryController();
        $controller->addCategory();
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