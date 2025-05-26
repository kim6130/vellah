<?php

require_once __DIR__ . '/../Database.php';

class AdminDashboardRepository {
    private $db;

    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (Exception $e) {
            error_log("AdminDashboardRepository: Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }

    public function getUserActivityStats() {
        try {
            // FIXED: Proper logic for active users this week/month
            $sql = "
                SELECT 
                    COUNT(CASE WHEN LastLogin >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as activeUsersWeek,
                    COUNT(CASE WHEN LastLogin >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as activeUsersMonth,
                    COUNT(CASE WHEN LastLogin < DATE_SUB(CURDATE(), INTERVAL 7 DAY) OR LastLogin IS NULL THEN 1 END) as inactiveUsers,
                    COUNT(*) as totalUsers
                FROM User 
                WHERE IsDeleted = FALSE AND Role = 'user'
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
            
            error_log("ADMIN REPO: User activity query result - " . json_encode($result));
            
            return [
                'activeUsers' => intval($result['activeUsersWeek']), // Active this week for progress ring
                'inactiveUsers' => intval($result['inactiveUsers']),
                'activeUsersMonth' => intval($result['activeUsersMonth']), // Active this month for separate display
                'totalUsers' => intval($result['totalUsers'])
            ];
        } catch (PDOException $e) {
            error_log("AdminDashboardRepository::getUserActivityStats PDO Error: " . $e->getMessage());
            throw new Exception("Failed to get user activity stats: " . $e->getMessage());
        }
    }

    public function getAverageSavingsPerCategory() {
        try {
            // FIXED: Calculate average across categories, not all goals
            $sql = "
                SELECT AVG(category_avg) as overall_avg
                FROM (
                    SELECT c.CategoryID, AVG(g.SavedAmount) as category_avg
                    FROM Category c
                    INNER JOIN Goal g ON c.CategoryID = g.CategoryID
                    WHERE c.IsDeleted = FALSE 
                    AND g.IsDeleted = FALSE 
                    AND g.SavedAmount > 0
                    GROUP BY c.CategoryID
                ) as category_averages
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
            
            $avgSavings = $result['overall_avg'] ? floatval($result['overall_avg']) : 0;
            error_log("ADMIN REPO: Average savings per category result - " . $avgSavings);
            
            return $avgSavings;
        } catch (PDOException $e) {
            error_log("AdminDashboardRepository::getAverageSavingsPerCategory PDO Error: " . $e->getMessage());
            throw new Exception("Failed to get average savings: " . $e->getMessage());
        }
    }

    public function getActiveUsersThisMonth() {
        try {
            // FIXED: Clear definition - users who logged in this month OR were created this month
            $sql = "
                SELECT COUNT(DISTINCT UserID) as activeUsers
                FROM User 
                WHERE IsDeleted = FALSE 
                AND Role = 'user'
                AND LastLogin >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
            
            $activeUsers = intval($result['activeUsers']);
            error_log("ADMIN REPO: Active users this month - " . $activeUsers);
            
            return $activeUsers;
        } catch (PDOException $e) {
            error_log("AdminDashboardRepository::getActiveUsersThisMonth PDO Error: " . $e->getMessage());
            throw new Exception("Failed to get active users count: " . $e->getMessage());
        }
    }

    public function getMostUsedCategories() {
        try {
            $sql = "
                SELECT c.CategoryName, COUNT(g.GoalID) as usageCount
                FROM Category c
                INNER JOIN Goal g ON c.CategoryID = g.CategoryID
                WHERE c.IsDeleted = FALSE AND g.IsDeleted = FALSE
                GROUP BY c.CategoryID, c.CategoryName
                ORDER BY usageCount DESC
                LIMIT 5
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll();
            
            $categories = [];
            foreach ($results as $row) {
                $categories[] = [
                    'name' => $row['CategoryName'],
                    'count' => intval($row['usageCount'])
                ];
            }
            
            error_log("ADMIN REPO: Top categories - " . json_encode($categories));
            
            return $categories;
        } catch (PDOException $e) {
            error_log("AdminDashboardRepository::getMostUsedCategories PDO Error: " . $e->getMessage());
            throw new Exception("Failed to get top categories: " . $e->getMessage());
        }
    }
}
?>