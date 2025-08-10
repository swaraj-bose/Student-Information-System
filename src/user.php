<?php
// src/user.php

require_once __DIR__ . '/../config/db.php'; // Ensure this sets up $pdo as a global PDO instance

/**
 * Get a user record by its ID.
 *
 * @param int $id
 * @return array|null  Associative array of user data or null if not found.
 */
function getUserById(int $id): ?array {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT id, name, mobile, role, username, subject, created_at, updated_at
        FROM users
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}

/**
 * Get a user record by username.
 *
 * @param string $username
 * @return array|null
 */
function getUserByUsername(string $username): ?array {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT id, name, mobile, role, username, subject, created_at, updated_at
        FROM users
        WHERE username = ?
        LIMIT 1
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}

/**
 * Return the first letter (uppercase) of a username, for avatar use.
 *
 * @param string $username
 * @return string
 */
function getUserInitial(string $username): string {
    $initial = mb_substr(trim($username), 0, 1);
    return mb_strtoupper($initial);
}

/**
 * Fetch all users, useful for autocomplete or tagging.
 *
 * @return array  List of user associative arrays.
 */
function getAllUsers(): array {
    global $pdo;
    $stmt = $pdo->query("
        SELECT id, username, name, role
        FROM users
        ORDER BY username ASC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Check if a given user is a teacher.
 *
 * @param int $id
 * @return bool
 */
function isTeacher(int $id): bool {
    $user = getUserById($id);
    return !empty($user) && $user['role'] === 'teacher';
}

/**
 * Check if a given user is a student.
 *
 * @param int $id
 * @return bool
 */
function isStudent(int $id): bool {
    $user = getUserById($id);
    return !empty($user) && $user['role'] === 'student';
}

