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
            return $stmt->fetch(PDO::FETCH_ASSOC);
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
                    PasswordHash,
                    Role,
                    AccountStatus
                FROM User 
                WHERE UserID = ?
                AND IsDeleted = FALSE
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Database error in getUserProfile: " . $e->getMessage());
            return false;
        }
    }

    public function updateProfile($userId, $data) {
        try {
            $query = "UPDATE User SET 
                     FirstName = :first_name,
                     LastName = :last_name,
                     Email = :email,
                     DOB = :dob,
                     UpdatedAt = CURRENT_TIMESTAMP";
            
            $params = [
                ':first_name' => $data['first_name'],
                ':last_name' => $data['last_name'],
                ':email' => $data['email'],
                ':dob' => $data['dob'],
                ':user_id' => $userId
            ];

            if (isset($data['password'])) {
                $query .= ", PasswordHash = :password";
                $params[':password'] = $data['password'];
            }

            if (isset($data['profile_picture'])) {
                $query .= ", ProfilePicture = :profile_picture";
                $params[':profile_picture'] = $data['profile_picture'];
                error_log("Updating profile picture for user $userId");
            }

            $query .= " WHERE UserID = :user_id";

            $stmt = $this->db->prepare($query);
            $success = $stmt->execute($params);
            
            if (!$success) {
                error_log("Update failed: " . implode(", ", $stmt->errorInfo()));
            }
            return $success;

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

            $success = $stmt->execute([$imagePath, $userId]);
            
            if (!$success) {
                error_log("Profile picture update failed: " . implode(", ", $stmt->errorInfo()));
            }
            return $success;

        } catch (PDOException $e) {
            error_log("Database error in updateProfilePicture: " . $e->getMessage());
            return false;
        }
    }

    public function verifyCurrentPassword($userId, $password) {
        try {
            $user = $this->getUserProfile($userId);
            if (!$user || !isset($user['PasswordHash'])) {
                return false;
            }
            return password_verify($password, $user['PasswordHash']);
        } catch (PDOException $e) {
            error_log("Database error in verifyCurrentPassword: " . $e->getMessage());
            return false;
        }
    }

    // Additional methods for admin statistics can be included here
}
?>
