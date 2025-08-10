<?php
// src/auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

/**
 * Register a new user.
 *
 * @param array $data
 * @return array [success => bool, errors => array]
 */
function registerUser(array $data): array {
    global $pdo;

    $errors = [];

    // Trim and sanitize inputs
    $name     = trim($data['name'] ?? '');
    $mobile   = trim($data['mobile'] ?? '');
    $role     = trim($data['role'] ?? '');
    $username = trim($data['username'] ?? '');
    $subject  = trim($data['subject'] ?? '');
    $password = $data['password'] ?? '';
    $confirm  = $data['confirm_password'] ?? '';

    // Basic validation
    if (!$name || !$mobile || !$role || !$username || !$password || !$confirm) {
        $errors[] = 'All fields are required.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!in_array($role, ['student', 'teacher'])) {
        $errors[] = 'Invalid user role.';
    }

    // Optionally require subject for teachers
    if ($role === 'teacher' && !$subject) {
        $errors[] = 'Subject is required for teachers.';
    }

    // Check if username is already taken
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors[] = 'Username already exists.';
        }
    }

    // Create user
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("
            INSERT INTO users (name, mobile, role, username, subject, password_hash)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $mobile, $role, $username, $subject, $hash]);

        return ['success' => true, 'errors' => []];
    }

    return ['success' => false, 'errors' => $errors];
}


/**
 * Attempt login
 */
function loginUser(string $username, string $password): array {
    global $pdo;

    $errors = [];

    $username = trim($username);
    $password = trim($password);

    if (!$username || !$password) {
        $errors[] = 'Both username and password are required.';
        return ['success' => false, 'errors' => $errors];
    }

    $stmt = $pdo->prepare("SELECT id, password_hash, role FROM users WHERE username = ?");
    $stmt->execute([$username]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $username;
        $_SESSION['role']     = $user['role'];

        return ['success' => true, 'errors' => []];
    }

    $errors[] = 'Invalid username or password.';
    return ['success' => false, 'errors' => $errors];
}


/**
 * Require authentication
 */
function requireAuth(): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../public/index.php', true, 302);
        exit;
    }
}

/**
 * Logout and destroy session
 */
function logoutUser(): void {
    session_unset();
    session_destroy();
    header('Location: ../public/index.php', true, 302);
    exit;
}

/* Handle form actions below */

$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? '';

if ($method === 'POST' && $action === 'register') {
    $result = registerUser($_POST);

    if ($result['success']) {
        header('Location: ../public/index.php?registered=1', true, 302);
    } else {
        $_SESSION['errors'] = $result['errors'];
        header('Location: ../public/index.php', true, 302);
    }
    exit;
}

if ($method === 'POST' && $action === 'login') {
    $result = loginUser($_POST['username'] ?? '', $_POST['password'] ?? '');

    if ($result['success']) {
        header('Location: ../public/dashboard.php', true, 302);
    } else {
        $_SESSION['errors'] = $result['errors'];
        header('Location: ../public/index.php', true, 302);
    }
    exit;
}

if ($method === 'GET' && $action === 'logout') {
    logoutUser();
}

