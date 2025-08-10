<?php
// src/reaction.php

session_start();
require_once __DIR__ . '/../config/db.php'; // This must define $pdo (PDO connection instance)

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

$userId     = (int) $_SESSION['user_id'];
$type       = $_POST['type'] ?? '';
$targetType = $_POST['target_type'] ?? '';
$targetId   = (int) ($_POST['target_id'] ?? 0);

// Validate inputs
$validTypes       = ['like', 'dislike'];
$validTargetTypes = ['question', 'answer'];

if (!in_array($type, $validTypes, true) ||
    !in_array($targetType, $validTargetTypes, true) ||
    $targetId <= 0) {
    redirectDashboard();
}

try {
    // Ensure $pdo is defined
    if (!isset($pdo)) {
        throw new Exception('Database connection not available.');
    }

    // Check for existing reaction by this user on this target
    $chk = $pdo->prepare("
        SELECT id, type
        FROM reactions
        WHERE user_id = ? AND target_type = ? AND target_id = ?
        LIMIT 1
    ");
    $chk->execute([$userId, $targetType, $targetId]);
    $existing = $chk->fetch();

    if ($existing) {
        if ($existing['type'] === $type) {
            // Same reaction exists → remove it (toggle off)
            $del = $pdo->prepare("DELETE FROM reactions WHERE id = ?");
            $del->execute([$existing['id']]);
        } else {
            // Different reaction exists → update type
            $upd = $pdo->prepare("UPDATE reactions SET type = ?, created_at = CURRENT_TIMESTAMP WHERE id = ?");
            $upd->execute([$type, $existing['id']]);
        }
    } else {
        // No reaction yet → insert new
        $ins = $pdo->prepare("
            INSERT INTO reactions (user_id, type, target_type, target_id)
            VALUES (?, ?, ?, ?)
        ");
        $ins->execute([$userId, $type, $targetType, $targetId]);
    }
} catch (Exception $e) {
    // Log for debugging (optional)
    error_log('Reaction Error: ' . $e->getMessage());
}

// Redirect back
redirectDashboard();

