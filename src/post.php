<?php
// Enable error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// src/post.php
session_start();

require_once __DIR__ . '/../config/db.php';  // Ensure $pdo is defined correctly here
require_once __DIR__ . '/user.php';          // Optional: Only if used here

// Redirect helper
function redirectDashboard() {
    header('Location: ../public/dashboard.php');
    exit;
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    redirectDashboard();
}

$userId = (int) $_SESSION['user_id'];
$role   = $_SESSION['role'] ?? '';

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'create_question':
            // Only students may post questions
            if ($role !== 'student') break;

            $content = trim($_POST['content'] ?? '');
            if ($content === '') break;

            $stmt = $pdo->prepare("INSERT INTO questions (user_id, content) VALUES (?, ?)");
            $stmt->execute([$userId, $content]);
            break;

        case 'edit_question':
            // Only allow POST updates for editing
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') break;

            $qid = (int) ($_POST['id'] ?? 0);
            $newContent = trim($_POST['content'] ?? '');
            if (!$qid || $newContent === '') break;

            // Verify question ownership
            $chk = $pdo->prepare("SELECT user_id FROM questions WHERE id = ?");
            $chk->execute([$qid]);
            $orig = $chk->fetch();

            if (!$orig || $orig['user_id'] != $userId) break;

            $upd = $pdo->prepare("UPDATE questions SET content = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $upd->execute([$newContent, $qid]);
            break;

        case 'delete_question':
            // Only allow GET for deletion (or better: use POST for security)
            $qid = (int) ($_GET['id'] ?? 0);
            if (!$qid) break;

            // Verify question ownership
            $chk = $pdo->prepare("SELECT user_id FROM questions WHERE id = ?");
            $chk->execute([$qid]);
            $orig = $chk->fetch();

            if (!$orig || $orig['user_id'] != $userId) break;

            $del = $pdo->prepare("DELETE FROM questions WHERE id = ?");
            $del->execute([$qid]);
            break;

        default:
            // Unknown action: ignore silently
            break;
    }
} catch (Exception $e) {
    // Logging is important in production
    error_log('Post action error: ' . $e->getMessage());
}

redirectDashboard();

