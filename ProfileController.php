<?php
declare(strict_types=1);

/**
 * Enhanced Profile Controller with Security Best Practices
 */
class ProfileController 
{
    private const DEFAULT_AVATAR = 'images/profile.svg';
    private const SESSION_LIFETIME = 86400; // 24 hours

    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository = null) 
    {
        $this->initializeSession();
        $this->setSecurityHeaders();
        $this->userRepository = $userRepository ?? new UserRepository();
    }

    private function initializeSession(): void
    {
        session_set_cookie_params([
            'lifetime' => self::SESSION_LIFETIME,
            'path' => '/AlkanSave/',
            'domain' => 'localhost',
            'secure' => $this->isHttps(),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function setSecurityHeaders(): void
    {
        header("Content-Type: application/json");
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: DENY");
        header("X-XSS-Protection: 1; mode=block");
        header("Access-Control-Allow-Origin: http://localhost");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
    }

    public function getProfile(): void 
    {
        try {
            $this->validateSession();
            $profile = $this->getUserProfile();
            $this->sendJsonResponse($this->prepareProfileResponse($profile));
            
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    private function validateSession(): void
    {
        if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
            error_log("Security Alert: Unauthorized access attempt from IP: " 
                . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            throw new RuntimeException("Unauthorized access", 401);
        }
    }

    private function getUserProfile(): array
    {
        $userId = (int)$_SESSION['user_id'];
        $profile = $this->userRepository->getUserProfile($userId);

        if (empty($profile)) {
            throw new RuntimeException("Profile not found", 404);
        }

        return $profile;
    }

    private function prepareProfileResponse(array $profile): array
    {
        return [
            'status' => 'success',
            'data' => [
                'first_name' => $this->sanitizeString($profile['FirstName'] ?? ''),
                'last_name' => $this->sanitizeString($profile['LastName'] ?? ''),
                'dob' => $this->formatDate($profile['DOB'] ?? null),
                'email' => $this->sanitizeEmail($profile['Email'] ?? ''),
                'avatar' => $this->sanitizeUrl($profile['ProfilePicture'] ?? self::DEFAULT_AVATAR),
                'session_id' => session_id()
            ]
        ];
    }

    private function sanitizeString(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function sanitizeEmail(string $email): string
    {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }

    private function sanitizeUrl(string $url): string
    {
        return filter_var($url, FILTER_SANITIZE_URL);
    }

    private function formatDate(?string $date): string
    {
        if (empty($date)) {
            return '';
        }

        try {
            $dateObj = new DateTime($date);
            return $dateObj->format('Y-m-d');
        } catch (Exception $e) {
            error_log("Invalid date format: " . $date);
            return '';
        }
    }

    private function sendJsonResponse(array $data): void
    {
        echo json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }

    private function handleError(Exception $e): void
    {
        http_response_code($e->getCode() ?: 500);
        
        $errorResponse = [
            'status' => 'error',
            'message' => $e->getMessage(),
            'error_code' => $e->getCode()
        ];

        $this->sendJsonResponse($errorResponse);
        
        error_log(sprintf(
            "ProfileController Error [%s:%d]: %s\nStack Trace: %s",
            $e->getFile(),
            $e->getLine(),
            $e->getMessage(),
            $e->getTraceAsString()
        ));
    }

    private function isHttps(): bool
    {
        return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    }
}

// Initialize and run the controller
require_once __DIR__ . '/../../3_Data/repositories/UserRepository.php';
require_once __DIR__ . '/../../3_Data/Database.php';

(new ProfileController())->getProfile();
