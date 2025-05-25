<?php
declare(strict_types=1);

require_once __DIR__.'/../../3_Data/repositories/UserRepository.php';
require_once __DIR__.'/../../3_Data/Database.php';

class UploadController {
    private UserRepository $userRepository;
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png'];
    private const MAX_SIZE = 2 * 1024 * 1024; // 2MB

    public function __construct() {
        $this->initializeSession();
        $this->userRepository = new UserRepository();
    }

    private function initializeSession(): void {
        session_set_cookie_params([
            'lifetime' => 86400,
            'path' => '/AlkanSave/',
            'domain' => 'localhost',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function handleUpload(): void {
        try {
            $this->validateRequest();
            $file = $_FILES['avatar'];
            $this->validateFile($file);

            $relativePath = $this->processUpload($file);
            $this->updateUserAvatar($relativePath);

            $this->sendSuccessResponse($relativePath);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    private function validateRequest(): void {
        if (!isset($_FILES['avatar']) || !isset($_SESSION['user_id'])) {
            throw new RuntimeException("Invalid request", 400);
        }
    }

    private function validateFile(array $file): void {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if (!in_array($mime, self::ALLOWED_TYPES)) {
            throw new RuntimeException("Only JPEG/PNG allowed", 400);
        }

        if ($file['size'] > self::MAX_SIZE) {
            throw new RuntimeException("File too large (max 2MB)", 400);
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException("Upload error: {$file['error']}", 400);
        }
    }

    private function processUpload(array $file): string {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/AlkanSave/uploads/avatars/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = "user_{$_SESSION['user_id']}_" . time() . ".$extension";
        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException("Failed to save file", 500);
        }

        return "/AlkanSave/uploads/avatars/$filename";
    }

    private function updateUserAvatar(string $path): void {
        $success = $this->userRepository->updateProfilePicture(
            (int)$_SESSION['user_id'],
            $path
        );

        if (!$success) {
            throw new RuntimeException("Failed to update avatar in database", 500);
        }
    }

    private function sendSuccessResponse(string $avatarPath): void {
        echo json_encode([
            'status' => 'success',
            'avatar' => $avatarPath
        ]);
    }

    private function handleError(Exception $e): void {
        http_response_code($e->getCode() ?: 500);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
}

// Initialize and handle request
(new UploadController())->handleUpload();