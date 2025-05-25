<?php
declare(strict_types=1);

require_once __DIR__ . '/../../3_Data/repositories/UserRepository.php';
require_once __DIR__ . '/../../3_Data/Database.php';

// Start output buffering and set headers
ob_start();
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

try {
    // Initialize session with secure settings
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/AlkanSave/',
        'domain' => 'localhost',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();

    // Verify authentication
    if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
        throw new RuntimeException("Unauthorized access", 401);
    }

    $userId = (int)$_SESSION['user_id'];
    $userRepo = new UserRepository();

    // Process and sanitize input data
    $inputData = [
        'first_name' => filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING) ?? null,
        'last_name' => filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING) ?? null,
        'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? null,
        'dob' => filter_input(INPUT_POST, 'dob', FILTER_SANITIZE_STRING) ?? null,
        'password' => $_POST['password'] ?? null, // Don't sanitize passwords
        'new_password' => $_POST['new_password'] ?? null
    ];

    // Handle file upload with enhanced validation
    $avatarPath = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar'];
        
        // Validate file type and size
        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        
        if (!array_key_exists($mime, $allowedTypes)) {
            throw new RuntimeException("Only JPG, PNG, and GIF images are allowed", 400);
        }
        
        if ($file['size'] > 2000000) {
            throw new RuntimeException("Maximum file size exceeded (2MB limit)", 400);
        }

        // Secure file upload handling
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/AlkanSave/uploads/avatars/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new RuntimeException("Failed to create upload directory", 500);
            }
        }

        $extension = $allowedTypes[$mime];
        $filename = sprintf("user_%d_%s.%s", 
            $userId, 
            bin2hex(random_bytes(8)), // Random filename component
            $extension
        );
        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException("Failed to save uploaded file", 500);
        }

        $avatarPath = "/AlkanSave/uploads/avatars/$filename";
    }

    // Prepare update data
    $updateData = [
        'first_name' => $inputData['first_name'],
        'last_name' => $inputData['last_name'],
        'email' => $inputData['email'],
        'dob' => $inputData['dob']
    ];

    // Handle password change if requested
    if (!empty($inputData['new_password'])) {
        if (empty($inputData['password'])) {
            throw new RuntimeException("Current password is required for changes", 400);
        }

        if (!$userRepo->verifyCurrentPassword($userId, $inputData['password'])) {
            throw new RuntimeException("Current password is incorrect", 401);
        }

        if (strlen($inputData['new_password']) < 8) {
            throw new RuntimeException("New password must be at least 8 characters", 400);
        }

        $updateData['password'] = password_hash($inputData['new_password'], PASSWORD_BCRYPT);
    }

    // Handle profile picture update
    if ($avatarPath) {
        $updateData['profile_picture'] = $avatarPath;
    }

    // Perform the update
    if (!$userRepo->updateProfile($userId, $updateData)) {
        throw new RuntimeException("Failed to update profile in database", 500);
    }

    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Profile updated successfully',
        'data' => [
            'avatar' => $avatarPath,
            'first_name' => $inputData['first_name'],
            'last_name' => $inputData['last_name']
        ]
    ]);

} catch (RuntimeException $e) {
    // Clean any output and return error
    ob_end_clean();
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
    exit;
} catch (Exception $e) {
    // Catch-all for other exceptions
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'An unexpected error occurred',
        'error_code' => 500
    ]);
    error_log("Unexpected error in EditProfileController: " . $e->getMessage());
    exit;
}

// Flush output buffer if no exceptions occurred
ob_end_flush();