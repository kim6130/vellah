<?php
require_once __DIR__ . '/../Database.php';

class PasswordResetRepository {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function createResetRequest($email) {
        // 1. Check if email exists
        $stmt = $this->db->prepare("SELECT UserID FROM User WHERE Email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) return false;
        
        // 2. Generate verification code (6 digits)
        $code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiration = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // 3. Store in database
        $stmt = $this->db->prepare(
            "INSERT INTO PasswordReset 
            (UserID, Email, VerificationCode, Expiration) 
            VALUES (?, ?, ?, ?)"
        );
        
        return $stmt->execute([
            $user['UserID'],
            $email,
            $code,
            $expiration
        ]) ? $code : false;
    }
    
    public function validateResetCode($email, $code) {
        $stmt = $this->db->prepare(
            "SELECT * FROM PasswordReset 
            WHERE Email = ? AND VerificationCode = ? 
            AND Used = FALSE AND Expiration > NOW()"
        );
        $stmt->execute([$email, $code]);
        return $stmt->fetch();
    }
    
    public function markCodeAsUsed($resetId) {
        $stmt = $this->db->prepare(
            "UPDATE PasswordReset SET Used = TRUE 
            WHERE ResetID = ?"
        );
        return $stmt->execute([$resetId]);
    }
    
    public function updatePassword($email, $newPassword) {
        $stmt = $this->db->prepare(
            "UPDATE User SET PasswordHash = ? 
            WHERE Email = ?"
        );
        return $stmt->execute([
            password_hash($newPassword, PASSWORD_BCRYPT),
            $email
        ]);
    }
    
    public function deleteResetRequest($email) {
        try {
            $stmt = $this->db->prepare(
                "DELETE FROM PasswordReset WHERE Email = ?"
            );
            return $stmt->execute([$email]);
        } catch (PDOException $e) {
            error_log("Failed to delete reset request: " . $e->getMessage());
            return false;
        }
    }
}
?>