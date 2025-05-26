<?php

require_once __DIR__ . '/../../3_Data/repositories/AdminDashboardRepository.php';

class AdminDashboardService {
    private $adminDashboardRepository;

    public function __construct() {
        $this->adminDashboardRepository = new AdminDashboardRepository();
    }

    public function getUserActivityStats() {
        try {
            $stats = $this->adminDashboardRepository->getUserActivityStats();
            error_log("ADMIN SERVICE: User stats - " . json_encode($stats));
            
            $totalUsers = $stats['totalUsers'];
            
            if ($totalUsers > 0) {
                // Use weekly active users for percentage calculation
                $activePercentage = round(($stats['activeUsers'] / $totalUsers) * 100, 1);
                $inactivePercentage = round(($stats['inactiveUsers'] / $totalUsers) * 100, 1);
            } else {
                $activePercentage = 0;
                $inactivePercentage = 0;
            }
            
            return [
                'activeUsers' => $stats['activeUsers'], // Active this week
                'inactiveUsers' => $stats['inactiveUsers'],
                'activePercentage' => $activePercentage,
                'inactivePercentage' => $inactivePercentage,
                'totalUsers' => $totalUsers,
                'activeUsersMonth' => $stats['activeUsersMonth'] // For separate month display
            ];
        } catch (Exception $e) {
            error_log("AdminDashboardService::getUserActivityStats Error: " . $e->getMessage());
            return [
                'activeUsers' => 0,
                'inactiveUsers' => 0,
                'activePercentage' => 0,
                'inactivePercentage' => 0,
                'totalUsers' => 0,
                'activeUsersMonth' => 0
            ];
        }
    }

    public function getAverageSavingsPerCategory() {
        try {
            $avgSavings = $this->adminDashboardRepository->getAverageSavingsPerCategory();
            error_log("ADMIN SERVICE: Average savings - " . $avgSavings);
            return max(0, floatval($avgSavings));
        } catch (Exception $e) {
            error_log("AdminDashboardService::getAverageSavingsPerCategory Error: " . $e->getMessage());
            return 0;
        }
    }

    public function getActiveUsersThisMonth() {
        try {
            $count = $this->adminDashboardRepository->getActiveUsersThisMonth();
            error_log("ADMIN SERVICE: Active users this month - " . $count);
            return max(0, intval($count));
        } catch (Exception $e) {
            error_log("AdminDashboardService::getActiveUsersThisMonth Error: " . $e->getMessage());
            return 0;
        }
    }

    public function getMostUsedCategories() {
        try {
            $categories = $this->adminDashboardRepository->getMostUsedCategories();
            error_log("ADMIN SERVICE: Top categories - " . json_encode($categories));
            return $categories;
        } catch (Exception $e) {
            error_log("AdminDashboardService::getMostUsedCategories Error: " . $e->getMessage());
            return [];
        }
    }
}
?>