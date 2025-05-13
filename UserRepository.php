<?php
require_once __DIR__ . '/../Database.php';

class UserRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findByEmail($email) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM User WHERE Email = ?");
            $stmt->execute([$email]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Database error in findByEmail: " . $e->getMessage());
            return false;
        }
    }

    public function createUser($data) {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO User 
                (FirstName, LastName, Email, DOB, PasswordHash, Role, AccountStatus) 
                VALUES (?, ?, ?, ?, ?, 'user', 'Active')"
            );
            $result = $stmt->execute([
                $data['first_name'],
                $data['last_name'],
                $data['email'],
                $data['dob'],
                password_hash($data['password'], PASSWORD_BCRYPT)
            ]);

            if (!$result) {
                error_log("User creation failed: " . implode(", ", $stmt->errorInfo()));
            }
            return $result;

        } catch (PDOException $e) {
            error_log("Database error in createUser: " . $e->getMessage());
            return false;
        }
    }

    public function getUserProfile($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    UserID,
                    FirstName, 
                    LastName, 
                    DOB, 
                    Email, 
                    ProfilePicture,
                    Role,
                    AccountStatus
                FROM User 
                WHERE UserID = ?
                AND IsDeleted = FALSE
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$profile) {
                error_log("Profile not found for UserID: " . $userId);
                return false;
            }

            return $profile;

        } catch (PDOException $e) {
            error_log("Database error in getUserProfile: " . $e->getMessage());
            return false;
        }
    }

    public function updateProfile($userId, $data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE User SET
                    FirstName = ?,
                    LastName = ?,
                    DOB = ?,
                    UpdatedAt = CURRENT_TIMESTAMP
                WHERE UserID = ?
            ");

            return $stmt->execute([
                $data['first_name'],
                $data['last_name'],
                $data['dob'],
                $userId
            ]);

        } catch (PDOException $e) {
            error_log("Database error in updateProfile: " . $e->getMessage());
            return false;
        }
    }

    public function updateProfilePicture($userId, $imagePath) {
        try {
            $stmt = $this->db->prepare("
                UPDATE User SET
                    ProfilePicture = ?,
                    UpdatedAt = CURRENT_TIMESTAMP
                WHERE UserID = ?
            ");

            return $stmt->execute([$imagePath, $userId]);

        } catch (PDOException $e) {
            error_log("Database error in updateProfilePicture: " . $e->getMessage());
            return false;
        }
    }

    // Additional methods for admin statistics can be included here
}
?>
