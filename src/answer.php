<?php
// src/answer.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/user.php';

/**
 * Redirect helper
 */
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
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {

        case 'create_answer':
            // Both students and teachers can answer
            $questionId = (int) ($_POST['question_id'] ?? 0);
            $content    = trim($_POST['content'] ?? '');

            if ($questionId > 0 && $content !== '') {
                // Check if question exists
                $chk = $pdo->prepare("SELECT id FROM questions WHERE id = ?");
                $chk->execute([$questionId]);

                if ($chk->fetch()) {
                    $ins = $pdo->prepare(
                        "INSERT INTO answers (question_id, user_id, content)
                         VALUES (?, ?, ?)"
                    );
                    $ins->execute([$questionId, $userId, $content]);
                }
            }
            break;

        case 'delete_answer':
            // Only answer author may delete (via POST)
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                break;
            }
            $aid = (int) ($_POST['id'] ?? 0);
            if ($aid > 0) {
                // Verify ownership
                $chk = $pdo->prepare("SELECT user_id FROM answers WHERE id = ?");
                $chk->execute([$aid]);
                $orig = $chk->fetch();

                if ($orig && $orig['user_id'] == $userId) {
                    $del = $pdo->prepare("DELETE FROM answers WHERE id = ?");
                    $del->execute([$aid]);
                }
            }
            break;

        // Future: case 'edit_answer': ...

        default:
            // Unknown or no action
            break;
    }
} catch (Exception $e) {
    error_log("Error in answer.php: " . $e->getMessage());
}

redirectDashboard();
?>

