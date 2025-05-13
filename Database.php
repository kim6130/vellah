<?php
class Database {
    private static $instance = null;

    private function __construct() {
        // Private constructor to prevent multiple instances
    }

    public static function getInstance() {
        if (!self::$instance) {
            try {
                self::$instance = new PDO(
                    "mysql:host=localhost;dbname=alkansave;charset=utf8mb4",
                    "root",
                    "", // Empty password for XAMPP default
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]
                );
                error_log("Database connected successfully!");
            } catch (PDOException $e) {
                error_log("Connection failed: " . $e->getMessage());
                die("Database connection failed. Please check your MySQL server.");
            }
        }
        return self::$instance;
    }

    public static function checkUserRole() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            header("Location: /1_Presentation/login.html?error=unauthorized");
            exit;
        }
        if ($_SESSION['role'] === 'admin') {
            header("Location: /1_Presentation/admin_dashboard.html");
            exit;
        }
    }
}
?>
