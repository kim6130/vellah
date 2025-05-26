<?php

require_once __DIR__ . '/../Database.php';

class CategoryRepository {
    private $db;

    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (Exception $e) {
            error_log("CategoryRepository: Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }

    public function getMostUsedCategory() {
        try {
            $sql = "
                SELECT c.CategoryName, COUNT(g.GoalID) as usageCount
                FROM Category c
                INNER JOIN Goal g ON c.CategoryID = g.CategoryID
                WHERE c.IsDeleted = FALSE AND g.IsDeleted = FALSE
                AND c.UserID IS NULL
                GROUP BY c.CategoryID, c.CategoryName
                HAVING usageCount > 0
                ORDER BY usageCount DESC
                LIMIT 1
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
            
            return $result ? $result['CategoryName'] : 'No categories used';
        } catch (PDOException $e) {
            error_log("CategoryRepository::getMostUsedCategory PDO Error: " . $e->getMessage());
            throw new Exception("Failed to get most used category: " . $e->getMessage());
        }
    }

    public function getLeastUsedCategory() {
        try {
            // FIXED: Get categories with the LOWEST usage count (but still used)
            $sql = "
                SELECT c.CategoryName, COUNT(g.GoalID) as usageCount
                FROM Category c
                LEFT JOIN Goal g ON c.CategoryID = g.CategoryID AND g.IsDeleted = FALSE
                WHERE c.IsDeleted = FALSE AND c.UserID IS NULL
                GROUP BY c.CategoryID, c.CategoryName
                ORDER BY usageCount ASC, c.CategoryName ASC
                LIMIT 1
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
            
            return $result ? $result['CategoryName'] : 'No categories found';
        } catch (PDOException $e) {
            error_log("CategoryRepository::getLeastUsedCategory PDO Error: " . $e->getMessage());
            throw new Exception("Failed to get least used category: " . $e->getMessage());
        }
    }

    public function getAllSystemCategories() {
        try {
            // FIXED: Use DISTINCT to avoid duplicates
            $sql = "
                SELECT DISTINCT CategoryID, CategoryName, DateCreated
                FROM Category
                WHERE IsDeleted = FALSE AND UserID IS NULL
                ORDER BY CategoryName ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll();
            
            return $results;
        } catch (PDOException $e) {
            error_log("CategoryRepository::getAllSystemCategories PDO Error: " . $e->getMessage());
            throw new Exception("Failed to get all system categories: " . $e->getMessage());
        }
    }

    public function addSystemCategory($categoryName) {
        try {
            // Check if category already exists
            $checkSql = "SELECT CategoryID FROM Category WHERE CategoryName = ? AND UserID IS NULL AND IsDeleted = FALSE";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$categoryName]);
            
            if ($checkStmt->fetch()) {
                throw new Exception("Category already exists");
            }

            $sql = "
                INSERT INTO Category (CategoryName, UserID, DateCreated, IsDeleted)
                VALUES (?, NULL, NOW(), FALSE)
            ";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$categoryName]);
            
            if ($result) {
                return $this->db->lastInsertId();
            } else {
                throw new Exception("Failed to insert category");
            }
        } catch (PDOException $e) {
            error_log("CategoryRepository::addSystemCategory PDO Error: " . $e->getMessage());
            throw new Exception("Failed to add new category: " . $e->getMessage());
        }
    }

    public function getCategoryUsageStats() {
        try {
            $sql = "
                SELECT c.CategoryName, COUNT(g.GoalID) as usageCount,
                       ROUND((COUNT(g.GoalID) * 100.0 / (SELECT COUNT(*) FROM Goal WHERE IsDeleted = FALSE)), 1) as percentage
                FROM Category c
                LEFT JOIN Goal g ON c.CategoryID = g.CategoryID AND g.IsDeleted = FALSE
                WHERE c.IsDeleted = FALSE AND c.UserID IS NULL
                GROUP BY c.CategoryID, c.CategoryName
                ORDER BY usageCount DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll();
            
            return $results;
        } catch (PDOException $e) {
            error_log("CategoryRepository::getCategoryUsageStats PDO Error: " . $e->getMessage());
            throw new Exception("Failed to get category usage stats: " . $e->getMessage());
        }
    }
}
?>