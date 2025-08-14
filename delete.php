<?php
session_start();

// Check if user is logged in and is a librarian
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'librarian') {
    header('Location: login.php');
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "libraryMS";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize response variables
$success = false;
$message = '';

// Handle DELETE request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_id'])) {
    $book_id = intval($_POST['book_id']);

    if ($book_id > 0) {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Check if book exists and get its details
            $checkStmt = $conn->prepare("SELECT id, title, author FROM books WHERE id = ?");
            $checkStmt->bind_param("i", $book_id);
            $checkStmt->execute();
            $result = $checkStmt->get_result();

            if ($result->num_rows > 0) {
                $book = $result->fetch_assoc();

                // Check if book is currently borrowed
                $borrowCheckStmt = $conn->prepare("SELECT COUNT(*) as borrowed_count FROM borrowed_books WHERE book_id = ? AND status = 'borrowed'");
                $borrowCheckStmt->bind_param("i", $book_id);
                $borrowCheckStmt->execute();
                $borrowResult = $borrowCheckStmt->get_result();
                $borrowData = $borrowResult->fetch_assoc();

                if ($borrowData['borrowed_count'] > 0) {
                    // Book is currently borrowed, cannot delete
                    $message = "Cannot delete book '{$book['title']}' by {$book['author']}. This book is currently borrowed by users.";
                    $success = false;
                } else {
                    // Delete related borrowing history first (if any)
                    $deleteHistoryStmt = $conn->prepare("DELETE FROM borrowed_books WHERE book_id = ?");
                    $deleteHistoryStmt->bind_param("i", $book_id);
                    $deleteHistoryStmt->execute();

                    // Delete the book
                    $deleteBookStmt = $conn->prepare("DELETE FROM books WHERE id = ?");
                    $deleteBookStmt->bind_param("i", $book_id);

                    if ($deleteBookStmt->execute()) {
                        $conn->commit();
                        $message = "Book '{$book['title']}' by {$book['author']} has been successfully deleted.";
                        $success = true;
                    } else {
                        throw new Exception("Failed to delete book: " . $conn->error);
                    }

                    $deleteHistoryStmt->close();
                    $deleteBookStmt->close();
                }

                $borrowCheckStmt->close();
            } else {
                $message = "Book not found.";
                $success = false;
            }

            $checkStmt->close();

        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error deleting book: " . $e->getMessage();
            $success = false;
        }
    } else {
        $message = "Invalid book ID.";
        $success = false;
    }
} else {
    $message = "Invalid request.";
    $success = false;
}

$conn->close();

// Store message in session for display
$_SESSION['delete_message'] = $message;
$_SESSION['delete_success'] = $success;

// Redirect back to librarian dashboard
header('Location: librarian_dashboard.php#maintainbooks');
exit();
?>