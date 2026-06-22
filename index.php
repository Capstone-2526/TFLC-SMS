<?php
session_start(); // ❌ YOU WERE MISSING THIS!
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get role from session (NO DEFAULT!)
$role_path = $_SESSION['role'] ?? null;

// Validate role exists
if (!$role_path || !in_array($role_path, ['admin', 'teacher', 'parent'])) {
    session_destroy();
    header('Location: login.php?error=invalid_role');
    exit();
}

// Redirect to correct dashboard
header("Location: {$role_path}/dashboard.php");
exit();
?>