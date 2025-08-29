<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: login.php');
    exit();
}

// Check if user has student role
if ($_SESSION['role'] !== 'student') {
    if ($_SESSION['role'] === 'librarian') {
        header('Location: librarian_dashboard.php');
    } else {
        header('Location: login.php');
    }
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "libraryMS";

$conn = new mysqli($servername, $username, $password, $dbname);
if($conn->connect_error){
    die("Connection failed: " . $conn->connect_error);
}

// Handle password change
$password_success = '';
$password_errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($current_password)) {
        $password_errors[] = "Current password is required";
    }

    if (empty($new_password)) {
        $password_errors[] = "New password is required";
    } elseif (strlen($new_password) < 6) {
        $password_errors[] = "New password must be at least 6 characters long";
    }

    if (empty($confirm_password)) {
        $password_errors[] = "Please confirm your new password";
    } elseif ($new_password !== $confirm_password) {
        $password_errors[] = "New passwords do not match";
    }

    if (empty($password_errors)) {
        // Get current user's password from database
        $userId = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Verify current password
            if (password_verify($current_password, $user['password'])) {
                // Update password
                $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updateStmt->bind_param("si", $hashed_new_password, $userId);

                if ($updateStmt->execute()) {
                    $password_success = "Password changed successfully!";
                } else {
                    $password_errors[] = "Failed to update password. Please try again.";
                }
                $updateStmt->close();
            } else {
                $password_errors[] = "Current password is incorrect";
            }
        } else {
            $password_errors[] = "User not found";
        }
        $stmt->close();
    }
}
?>