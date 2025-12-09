<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use App\Auth\GoogleAuth;

$db = Database::getInstance()->getConnection();
$googleAuth = new GoogleAuth();

// Check if we have an authorization code
if (!isset($_GET['code'])) {
    header('Location: login.php?error=no_code');
    exit;
}

// Get user info from Google
$userInfo = $googleAuth->handleCallback($_GET['code']);

if (!$userInfo) {
    header('Location: login.php?error=google_auth_failed');
    exit;
}

try {
    // Check if user already exists
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$userInfo['email']]);
    $user = $stmt->fetch();

    if ($user) {
        // User exists - just log in
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
    } else {
        // New user - create account
        $stmt = $db->prepare("INSERT INTO users (name, email, password, phone, created_at) VALUES (?, ?, ?, '', NOW())");

        // Use a random password since they're using Google OAuth
        $randomPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

        $stmt->execute([
            $userInfo['name'],
            $userInfo['email'],
            $randomPassword
        ]);

        $userId = $db->lastInsertId();

        // Log in the new user
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $userInfo['name'];
        $_SESSION['user_email'] = $userInfo['email'];
    }

    // Success! Redirect to appropriate page
    if (isset($_GET['redirect'])) {
        header('Location: ' . $_GET['redirect']);
    } else {
        header('Location: menu.php');
    }
    exit;

} catch (Exception $e) {
    error_log("Database error during Google login: " . $e->getMessage());
    header('Location: login.php?error=database_error');
    exit;
}
