<?php
require_once 'ReportsRepository.php';

class ReportsController {
    private $repository;
    private $debug = true;

    public function __construct() {
        try {
            $this->repository = new ReportsRepository();
        } catch (PDOException $e) {
            error_log("Failed to initialize repository: " . $e->getMessage());
        }
    }

    public function handleRequest() {
        // Session should already be started in reports.php
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            error_log("No user_id in session: " . json_encode($_SESSION));
            $this->sendResponse(401, ['error' => 'Unauthorized', 'session' => $_SESSION]);
            return;
        }

        error_log("Handling request for user ID: " . $_SESSION['user_id']);

        try {
            switch ($_GET['action'] ?? '') {
                case 'savings_growth':
                    $this->handleSavingsGrowth($_SESSION['user_id']);
                    break;
                    
                case 'goal_completion':
                    $this->handleGoalCompletion($_SESSION['user_id']);
                    break;
                    
                case 'completed_goals':
                    $this->handleCompletedGoals($_SESSION['user_id']);
                    break;
                    
                default:
                    $this->sendResponse(400, [
                        'error' => 'Invalid action',
                        'valid_actions' => ['savings_growth', 'goal_completion', 'completed_goals']
                    ]);
            }
        } catch (Exception $e) {
            error_log("Exception in handleRequest: " . $e->getMessage());
            $this->sendResponse(500, [
                'error' => 'Server error',
                'message' => $e->getMessage()
            ]);
        }
    }

    private function handleSavingsGrowth($userId) {
        try {
            if (!isset($this->repository)) {
                throw new Exception("Repository not available");
            }
            
            $data = $this->repository->getSavingsGrowth($userId, $_GET['goal_id'] ?? null);
            
            // If empty data, return empty structure
            if (empty($data)) {
                $this->sendResponse(200, [
                    'labels' => [],
                    'datasets' => []
                ]);
                return;
            }
            
            $groupedData = [];
            foreach ($data as $entry) {
                $groupedData[$entry['GoalName']][] = $entry;
            }
    
            $datasets = [];
            $colors = ['#fa8fbc', '#24336e', '#ffd1dc', '#8fd3f4'];
            $colorIndex = 0;
    
            foreach ($groupedData as $goalName => $entries) {
                $datasets[] = [
                    'label' => $goalName,
                    'data' => array_column($entries, 'total_saved'),
                    'backgroundColor' => $colors[$colorIndex % count($colors)],
                    'borderColor' => $colors[$colorIndex % count($colors)],
                    'borderWidth' => 1
                ];
                $colorIndex++;
            }
    
            $this->sendResponse(200, [
                'labels' => array_values(array_unique(array_column($data, 'month'))),
                'datasets' => $datasets
            ]);
        } catch (Exception $e) {
            error_log("Error in handleSavingsGrowth: " . $e->getMessage());
            $this->sendResponse(200, [
                'labels' => [],
                'datasets' => [],
                '_error' => $e->getMessage()
            ]);
        }
    }

    private function handleGoalCompletion($userId) {
        try {
            if (!isset($this->repository)) {
                throw new Exception("Repository not available");
            }
            
            $data = $this->repository->getGoalCompletionStats($userId);
            
            // Handle null or empty data
            if (!$data) {
                $data = [
                    'completed' => 0,
                    'active' => 0,
                    'total' => 0
                ];
            }
            
            $this->sendResponse(200, [
                'labels' => ['Completed', 'Active'],
                'datasets' => [[
                    'label' => 'Goals',
                    'data' => [(int)$data['completed'], (int)$data['active']],
                    'backgroundColor' => ['#fa8fbc', '#ffd1dc'],
                    'borderColor' => '#fff3f8',
                    'borderWidth' => 2
                ]],
                'meta' => [
                    'completion_rate' => ($data['total'] > 0) ? round(($data['completed'] / $data['total']) * 100) : 0
                ]
            ]);
        } catch (Exception $e) {
            error_log("Error in handleGoalCompletion: " . $e->getMessage());
            $this->sendResponse(200, [
                'labels' => ['Completed', 'Active'],
                'datasets' => [[
                    'label' => 'Goals',
                    'data' => [0, 0],
                    'backgroundColor' => ['#fa8fbc', '#ffd1dc'],
                    'borderColor' => '#fff3f8',
                    'borderWidth' => 2
                ]],
                'meta' => [
                    'completion_rate' => 0
                ]
            ]);
        }
    }

    private function handleCompletedGoals($userId) {
        try {
            if (!isset($this->repository)) {
                throw new Exception("Repository not available");
            }
            
            $month = $_GET['month'] ?? date('Y-m');
            $data = $this->repository->getCompletedGoalsByMonth($userId, $month);
            
            if (empty($data)) {
                $this->sendResponse(200, []);
                return;
            }
            
            foreach ($data as &$goal) {
                $goal['SavedAmount'] = '₱' . number_format($goal['SavedAmount'], 2);
                $goal['TargetAmount'] = '₱' . number_format($goal['TargetAmount'], 2);
            }
            
            $this->sendResponse(200, $data);
        } catch (Exception $e) {
            error_log("Error in handleCompletedGoals: " . $e->getMessage());
            $this->sendResponse(200, []);
        }
    }

    private function sendResponse($code, $data) {
        http_response_code($code);
        header('Content-Type: application/json');
        if ($this->debug) {
            header('X-Debug: true');
            $data['_debug'] = [
                'timestamp' => date('Y-m-d H:i:s'),
                'user_id' => $_SESSION['user_id'] ?? 'none'
            ];
        }
        echo json_encode($data);
        exit;
    }
}