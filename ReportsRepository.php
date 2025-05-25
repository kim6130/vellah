<?php
require_once 'Database.php';

class ReportsRepository {
    private $db;
    private $debug = true;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->verifyDatabaseConnection();
    }

    private function verifyDatabaseConnection() {
        try {
            $test = $this->db->query("SELECT 1");
            if ($this->debug) error_log("Database connection successful");
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw $e;
        }
    }

    public function getSavingsGrowth($userId, $goalId = null) {
        try {
            $query = "SELECT 
                DATE_FORMAT(st.DateSaved, '%Y-%m') AS month,
                SUM(st.Amount) AS total_saved,
                g.GoalName,
                c.CategoryName
              FROM savingstransaction st
              JOIN goal g ON st.GoalID = g.GoalID
              JOIN category c ON g.CategoryID = c.CategoryID
              WHERE g.UserID = :userId
              " . ($goalId ? "AND st.GoalID = :goalId" : "") . "
              GROUP BY month, g.GoalID
              ORDER BY month ASC";

            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
            if ($goalId) $stmt->bindValue(':goalId', $goalId, PDO::PARAM_INT);
            
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("getSavingsGrowth for user $userId: " . count($result) . " records found");
            return $result;
        } catch (PDOException $e) {
            error_log("Error in getSavingsGrowth: " . $e->getMessage());
            return [];
        }
    }

    public function getGoalCompletionStats($userId) {
        try {
            $query = "SELECT 
                SUM(Status = 'Completed') AS completed,
                SUM(Status = 'Active') AS active,
                COUNT(*) AS total
              FROM goal
              WHERE UserID = :userId AND IsDeleted = 0";

            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("getGoalCompletionStats for user $userId: " . json_encode($result));
            return $result;
        } catch (PDOException $e) {
            error_log("Error in getGoalCompletionStats: " . $e->getMessage());
            return [
                'completed' => 0,
                'active' => 0,
                'total' => 0
            ];
        }
    }

    public function getCompletedGoalsByMonth($userId, $yearMonth) {
        try {
            $query = "SELECT 
                g.GoalName,
                c.CategoryName,
                DATE_FORMAT(g.CompletionDate, '%Y-%m-%d') AS completion_date,
                g.SavedAmount,
                g.TargetAmount,
                ROUND((g.SavedAmount / g.TargetAmount) * 100) AS completion_percentage
              FROM goal g
              JOIN category c ON g.CategoryID = c.CategoryID
              WHERE g.UserID = :userId
                AND g.Status = 'Completed'
                AND DATE_FORMAT(g.CompletionDate, '%Y-%m') = :month
              ORDER BY g.CompletionDate DESC";

            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':month', $yearMonth, PDO::PARAM_STR);
            $stmt->execute();
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("getCompletedGoalsByMonth for user $userId, month $yearMonth: " . count($result) . " records found");
            return $result;
        } catch (PDOException $e) {
            error_log("Error in getCompletedGoalsByMonth: " . $e->getMessage());
            return [];
        }
    }
}