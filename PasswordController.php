<?php
session_start();

require_once __DIR__ . '/../../3_Data/repositories/PasswordResetRepository.php';

class PasswordController {
    private $resetRepo;
    
    public function __construct() {
        $this->resetRepo = new PasswordResetRepository();
    }
    
    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['email']) && !isset($_POST['code'])) {
                $this->handleResetRequest();
            } elseif (isset($_POST['code'])) {
                $this->handlePasswordUpdate();
            }
        }
    }
    
    private function handleResetRequest() {
        $email = $_POST['email'];
        $code = $this->resetRepo->createResetRequest($email);
        
        if ($code) {
            // Store code in session for test mode
            $_SESSION['reset_code'] = $code;
            $_SESSION['reset_email'] = $email;
            
            // For development - log the code instead of emailing
            error_log("Password reset code for $email: $code");
            
            // Redirect with test mode parameter
            header("Location: /AlkanSave/1_Presentation/forgotpass.html?email=".urlencode($email)."&test_mode=1");
        } else {
            header("Location: /AlkanSave/1_Presentation/forgotpass.html?error=invalid_email");
        }
        exit();
    }
    
    private function handlePasswordUpdate() {
        $email = $_POST['email'];
        $code = $_POST['code'];
        $newPassword = $_POST['password'];
        
        // Check session first for test mode codes
        if (isset($_SESSION['reset_code']) && 
            $_SESSION['reset_code'] === $code && 
            $_SESSION['reset_email'] === $email) {
            
            if ($this->resetRepo->updatePassword($email, $newPassword)) {
                unset($_SESSION['reset_code']);
                unset($_SESSION['reset_email']);
                header("Location: /AlkanSave/1_Presentation/login.html?password_reset=success");
                exit();
            }
        }
        
        // Normal code validation
        $resetRecord = $this->resetRepo->validateResetCode($email, $code);
        
        if ($resetRecord) {
            if ($this->resetRepo->updatePassword($email, $newPassword)) {
                $this->resetRepo->markCodeAsUsed($resetRecord['ResetID']);
                header("Location: /AlkanSave/1_Presentation/login.html?password_reset=success");
            } else {
                header("Location: /AlkanSave/1_Presentation/forgotpass.html?email=" . urlencode($email) . "&error=update_failed");
            }
        } else {
            header("Location: /AlkanSave/1_Presentation/forgotpass.html?email=" . urlencode($email) . "&error=invalid_code");
        }
        exit();
    }
}

(new PasswordController())->handleRequest();
?>