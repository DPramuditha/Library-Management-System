<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    // User is not logged in, redirect to login page
    header('Location: login.php');
    exit();
}

// Check if user has student role
if ($_SESSION['role'] !== 'student') {
    // User is not a student, redirect to appropriate dashboard or login
    if ($_SESSION['role'] === 'librarian') {
        header('Location: librarian_dashboard.php');
    } else {
        header('Location: login.php');
    }
    exit();
}

// Handle logout request
if (isset($_POST['logout']) && $_POST['logout'] == '1') {
    // Destroy session and redirect to login
    session_destroy();
    header('Location: login.php');
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

// Handle profile update
$updateMessage = '';
$updateSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $studentId = $_SESSION['user_id'] ?? 1; // Get from session or default to 1

    // Validation
    $errors = [];

    if (empty($fullName)) {
        $errors[] = "Full name is required";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }

    if (empty($phone)) {
        $errors[] = "Phone number is required";
    }
    if(!preg_match('/^[0-9]{10}$/', $phone)) {
        $errors[] = "Phone number must be 10 digits";
    }

    if (empty($errors)) {
        // Check if user exists in users table and update
        $checkUser = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $checkUser->bind_param("i", $studentId);
        $checkUser->execute();
        $userResult = $checkUser->get_result();

        if ($userResult->num_rows > 0) {
            // Update users table
            $updateUser = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
            $updateUser->bind_param("ssssi", $fullName, $email, $phone, $address, $studentId);

            if ($updateUser->execute()) {
                $updateSuccess = true;
                $updateMessage = "Profile updated successfully!";
                $_SESSION['user_name'] = $fullName;
                $_SESSION['user_email'] = $email;
            } else {
                $updateMessage = "Error updating profile: " . $conn->error;
            }
            $updateUser->close();
        } else {
            $updateMessage = "User not found";
        }
        $checkUser->close();
    } else {
        $updateMessage = implode(', ', $errors);
    }
}

// Get current user data
$userId = $_SESSION['user_id'] ?? 1;
$userQuery = $conn->prepare("SELECT * FROM users WHERE id = ?");
$userQuery->bind_param("i", $userId);
$userQuery->execute();
$userResult = $userQuery->get_result();
$userData = $userResult->fetch_assoc();
$userQuery->close();

// Set default values if no user data exists
if (!$userData) {
    $userData = [
        'name' => 'Dimuthu Pramuditha',
        'email' => 'dimuthu@example.com',
        'phone' => '071 123 4567',
        'address' => '123 Main St, Colombo'
    ];
}

// Your existing functions...
function getAllBooks($conn)
{
    $sql = "SELECT * FROM books ORDER BY created_at DESC";
    $result = $conn->query($sql);
    $books = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
    }
    return $books;
}

function getAvailableBooks($conn)
{
//    $sql = "SELECT * FROM books WHERE available_copies > 0 ORDER BY title";
    $sql = "SELECT * FROM books ORDER BY created_at DESC";
    $result = $conn->query($sql);
    $books = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
    }
    return $books;
}

function searchBooks($conn, $searchTerm)
{
    $searchTerm = $conn->real_escape_string($searchTerm);
    $sql = "SELECT * FROM books WHERE title LIKE '%$searchTerm%' OR author LIKE '%$searchTerm%' OR isbn LIKE '%$searchTerm%' ORDER BY title";
    $result = $conn->query($sql);
    $books = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
    }
    return $books;
}

// Fetch books for different sections
$topBooks = getAllBooks($conn);
$availableBooks = getAvailableBooks($conn);

// Handle search
$searchResults = [];
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchResults = searchBooks($conn, $_GET['search']);
}

// Handle Book Borrowing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrow_book'])) {
    $bookId = $_POST['book_id'] ?? '';
    $userId = $_SESSION['user_id'] ?? 1;
    $borrowDate = date('Y-m-d');
    $dueDate = date('Y-m-d', strtotime('+14 days'));

    $borrow_errors = [];

    // Check if book exists and is available
    $bookCheck = $conn->prepare("SELECT id, title, available_copies FROM books WHERE id = ? AND available_copies > 0");
    $bookCheck->bind_param("i", $bookId);
    $bookCheck->execute();
    $bookResult = $bookCheck->get_result();

    if ($bookResult->num_rows === 0) {
        $borrow_errors[] = "Book is not available for borrowing";
    }

    // Check if user already borrowed this book
    $alreadyBorrowed = $conn->prepare("SELECT id FROM borrowed_books WHERE user_id = ? AND book_id = ? AND status = 'borrowed'");
    $alreadyBorrowed->bind_param("ii", $userId, $bookId);
    $alreadyBorrowed->execute();
    $borrowedResult = $alreadyBorrowed->get_result();

    if ($borrowedResult->num_rows > 0) {
        $borrow_errors[] = "You have already borrowed this book";
    }

    if (empty($borrow_errors)) {
        $conn->begin_transaction();
        try {
            // Insert into borrowed_books table
            $borrowStmt = $conn->prepare("INSERT INTO borrowed_books (user_id, book_id, borrow_date, due_date, status) VALUES (?, ?, ?, ?, 'borrowed')");
            $borrowStmt->bind_param("iiss", $userId, $bookId, $borrowDate, $dueDate);
            $borrowStmt->execute();

            // Update available_copies
            $updateBook = $conn->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id = ?");
            $updateBook->bind_param("i", $bookId);
            $updateBook->execute();

            $conn->commit();
            $borrow_success = "Book borrowed successfully! Due date: " . date('F j, Y', strtotime($dueDate));

            // Refresh books data
            $topBooks = getAllBooks($conn);
            $availableBooks = getAvailableBooks($conn);

        } catch (Exception $e) {
            $conn->rollback();
            $borrow_errors[] = "Error borrowing book: " . $e->getMessage();
        }
    }
}

// Function to get borrowed books
function getBorrowedBooks($conn, $userId) {
    // Check if borrowed_books table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'borrowed_books'");
    if ($tableCheck->num_rows == 0) {
        return []; // Return empty array if table doesn't exist
    }

    $sql = "SELECT bb.*, b.title, b.author, b.isbn, b.category, bb.borrow_date, bb.due_date, bb.status
            FROM borrowed_books bb
            JOIN books b ON bb.book_id = b.id
            WHERE bb.user_id = ?
            ORDER BY bb.borrow_date DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $borrowedBooks = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $borrowedBooks[] = $row;
        }
    }
    return $borrowedBooks;
}

// Get borrowed books for current user
$borrowedBooks = getBorrowedBooks($conn, $userId);

// Handle return book action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_book'])) {
    $book_id = (int)$_POST['book_id'];
    $user_id = $_SESSION['user_id'];

    try {
        // Start transaction
        $conn->begin_transaction();

        // Update the borrowing record to returned status
        $stmt = $conn->prepare("
            UPDATE borrowed_books
            SET status = 'returned', return_date = CURRENT_DATE
            WHERE id = ? AND user_id = ? AND status = 'borrowed'
        ");
        $stmt->bind_param("ii", $book_id, $user_id);
        $stmt->execute();

        // Get the book_id from the borrowed_books record to update available copies
        $getBookStmt = $conn->prepare("SELECT book_id FROM borrowed_books WHERE id = ?");
        $getBookStmt->bind_param("i", $book_id);
        $getBookStmt->execute();
        $bookResult = $getBookStmt->get_result();
        $bookData = $bookResult->fetch_assoc();

        if ($bookData) {
            // Increase available copies
            $updateBookStmt = $conn->prepare("
                UPDATE books
                SET available_copies = available_copies + 1
                WHERE id = ?
            ");
            $updateBookStmt->bind_param("i", $bookData['book_id']);
            $updateBookStmt->execute();
        }

        // Commit transaction
        $conn->commit();

        $return_success = "Book returned successfully!";

        // Refresh the borrowed books data
        $borrowedBooks = getBorrowedBooks($conn, $user_id);

    } catch (Exception $e) {
        $conn->rollback();
        $return_errors[] = "Error returning book: " . $e->getMessage();
    }
}
// Fetch all books for the dashboard
$books = [];
$booksQuery = "SELECT id, title, author, category, description, publication_year, publisher, total_copies, available_copies, status FROM books ORDER BY id DESC";
$booksResult = $conn->query($booksQuery);

if ($booksResult && $booksResult->num_rows > 0) {
    while ($row = $booksResult->fetch_assoc()) {
        $books[] = $row;
    }
}

// Handle Password Change form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $password_errors = [];

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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="pages/index.css" rel="stylesheet">
    <title>Student Dashboard</title>
</head>
<body>
<div class="flex h-screen bg-gradient-to-br from-gray-50 to-gray-100">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 z-50 w-72 bg-white shadow-2xl transition-all duration-300 ease-in-out lg:block lg:static lg:inset-0">
        <div class="flex flex-col h-full">
            <div class="flex items-center justify-between h-20 px-6 bg-gradient-to-r from-blue-600 to-purple-600 text-white">
                <div class="flex items-center">
                    <img src="assets/books02.gif" alt="Logo" class="h-15 w-15 rounded-lg">
                </div>
                <div class="text-xl font-bold">
                    <span class="text-2xl font-bold">Student Portal</span>
                </div>
                <button class="lg:hidden p-2 hover:bg-white hover:bg-opacity-20 rounded-lg transition-colors">
                </button>
            </div>
            <div>
                <nav class="flex-1 px-4 py-6 space-y-2">
                    <a href="?section=home" onclick="showSection('home')"
                       class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-500 shadow-md hover:transform duration-300 hover:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                             stroke="currentColor" class="size-6 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                        </svg>

                        <span class="text-base font-medium">Home</span>
                    </a>
                    <a href="?section=profile" onclick="showSection('profile')"
                       class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-500 shadow-md hover:transform duration-300 hover:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                             stroke="currentColor" class="size-6 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                        </svg>

                        <span class="text-md font-medium">Profile</span>
                    </a>
                    <a href="?section=borrowed" onclick="showSection('borrowed')"
                       class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-500 shadow-md hover:transform duration-300 hover:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                             stroke="currentColor" class="size-6 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>
                        </svg>

                        <span class="text-md font-medium">Borrowed Books</span>
                    </a>
                    <a href="?section=search" onclick="showSection('search')"
                       class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-500 shadow-md hover:transform duration-300 hover:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                             class="size-6 mr-2">
                            <path fill-rule="evenodd"
                                  d="M10.5 3.75a6.75 6.75 0 1 0 0 13.5 6.75 6.75 0 0 0 0-13.5ZM2.25 10.5a8.25 8.25 0 1 1 14.59 5.28l4.69 4.69a.75.75 0 1 1-1.06 1.06l-4.69-4.69A8.25 8.25 0 0 1 2.25 10.5Z"
                                  clip-rule="evenodd"/>
                        </svg>

                        <span class="text-md font-medium">Search Books</span>
                    </a>
                    <a href="?section=change-password" onclick="showSection('change-password')"
                       class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-500 shadow-md hover:transform duration-300 hover:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6 mr-2">
                            <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z" clip-rule="evenodd" />
                        </svg>

                        <span class="text-md font-medium">Change Password</span>
                    </a>
                </nav>
            </div>
            <div class="p-4">
                <div class="text-white rounded-lg shadow-md p-4">
                    <div>
                        <img src="pages/assets/38091993_8598876.jpg" alt="Reading Icon"
                             class="h-55 w-100 mx-auto">
                    </div>
                </div>
            </div>
            <nav class="flex-1 px-4 py-6 space-y-2">
                <a href="logout.php" onclick="return confirm('Are you sure you want to logout?')"
                   class="flex items-center px-3 py-2 rounded-lg hover:bg-red-500 shadow-md hover:transform duration-300 hover:scale-95">
                    <img src="assets/user-logout.svg" alt="Logout Icon" class="h-6 w-6 mr-2">
                    <span class="text-base font-medium">Logout</span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="currentColor" class="size-6 ml-30">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5"/>
                    </svg>
                </a>
            </nav>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col lg:ml-0">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 backdrop-blur-lg bg-opacity-90">
            <div class="flex items-center justify-between h-18 px-6">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-7">
                            <path d="M11.7 2.805a.75.75 0 0 1 .6 0A60.65 60.65 0 0 1 22.83 8.72a.75.75 0 0 1-.231 1.337 49.948 49.948 0 0 0-9.902 3.912l-.003.002c-.114.06-.227.119-.34.18a.75.75 0 0 1-.707 0A50.88 50.88 0 0 0 7.5 12.173v-.224c0-.131.067-.248.172-.311a54.615 54.615 0 0 1 4.653-2.52.75.75 0 0 0-.65-1.352 56.123 56.123 0 0 0-4.78 2.589 1.858 1.858 0 0 0-.859 1.228 49.803 49.803 0 0 0-4.634-1.527.75.75 0 0 1-.231-1.337A60.653 60.653 0 0 1 11.7 2.805Z" />
                            <path d="M13.06 15.473a48.45 48.45 0 0 1 7.666-3.282c.134 1.414.22 2.843.255 4.284a.75.75 0 0 1-.46.711 47.87 47.87 0 0 0-8.105 4.342.75.75 0 0 1-.832 0 47.87 47.87 0 0 0-8.104-4.342.75.75 0 0 1-.461-.71c.035-1.442.121-2.87.255-4.286.921.304 1.83.634 2.726.99v1.27a1.5 1.5 0 0 0-.14 2.508c-.09.38-.222.753-.397 1.11.452.213.901.434 1.346.66a6.727 6.727 0 0 0 .551-1.607 1.5 1.5 0 0 0 .14-2.67v-.645a48.549 48.549 0 0 1 3.44 1.667 2.25 2.25 0 0 0 2.12 0Z" />
                            <path d="M4.462 19.462c.42-.419.753-.89 1-1.395.453  .214.902.435 1.347.662a6.742 6.742 0 0 1-1.286 1.794.75.75 0 0 1-1.06-1.06Z" />
                        </svg>

                        <p class="text-lg text-gray-500">Dashboard Overview</p>
                    </div>
                </div>

                <!-- Right side - Notifications and Profile -->
                <div class="flex items-center space-x-4">
                    <!-- Profile Icon -->
                    <button class="flex items-center space-x-2 p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors cursor-pointer">
                        <img src="assets/profile-placeholder.png" alt="Profile"
                             class="h-10 w-10 rounded-full object-cover"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'"/>
                        <div class="h-8 w-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-medium"
                             style="display: none;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                        </div>
                        <div class="hidden md:block text-sm font-medium">
                            <p><?php echo htmlspecialchars($userData['name']); ?></p>
                            <p class="text-xs text-gray-500">Student Email: <?php echo htmlspecialchars($userData['email']); ?></p>
                        </div>
                    </button>
                    <a href="logout.php" onclick="return confirm('Are you sure you want to logout?')"
                       class="hidden lg:flex items-center px-3 py-2 rounded-lg hover:bg-red-400 hover:transform duration-300 hover:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                            <path fill-rule="evenodd" d="M7.5 3.75A1.5 1.5 0 0 0 6 5.25v13.5a1.5 1.5 0 0 0 1.5 1.5h6a1.5 1.5 0 0 0 1.5-1.5V15a.75.75 0 0 1 1.5 0v3.75a3 3 0 0 1-3 3h-6a3 3 0 0 1-3-3V5.25a3 3 0 0 1 3-3h6a3 3 0 0 1 3 3V9A.75.75 0 0 1 15 9V5.25a1.5 1.5 0 0 0-1.5-1.5h-6Zm10.72 4.72a.75.75 0 0 1 1.06 0l3 3a.75.75 0 0 1 0 1.06l-3 3a.75.75 0 1 1-1.06-1.06l1.72-1.72H9a.75.75 0 0 1 0-1.5h10.94l-1.72-1.72a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 overflow-auto p-6">
            <!-- Home Section -->
            <div id="home-section" class="content-section">
                <!--                <h1 class="text-3xl font-bold text-gray-900 mb-6">Dashboard Overview</h1>-->
                <!--                <div>-->
                <!--                    <img src="assets/S_dashboard.jpg" alt="Dashboard Image"-->
                <!--                         class="w-full h-auto rounded-lg shadow-md mb-6">-->
                <!--                </div>-->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="relative overflow-hidden rounded-lg shadow-md">
                        <img src="pages/assets/S_dashboard03.jpg" alt="Library Reading"
                             class="w-full h-64 object-cover transition-transform duration-700 hover:scale-110 animate-fade-in-left">
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-600/20 to-transparent opacity-0 hover:opacity-100 transition-opacity duration-300"></div>
                    </div>

                    <div class="relative overflow-hidden rounded-lg shadow-md">
                        <img src="pages/assets/S_dashboard.jpg" alt="Digital Library"
                             class="w-full h-64 object-cover transition-transform duration-700 hover:scale-110 animate-fade-in-right">
                        <div class="absolute inset-0 bg-gradient-to-l from-purple-600/20 to-transparent opacity-0 hover:opacity-100 transition-opacity duration-300"></div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Total Books Borrowed Card -->
                    <div class="bg-blue-50 p-6 rounded-lg shadow-md hover:shadow-xl transform hover:scale-105 transition-all duration-300 ease-in-out hover:bg-blue-50 group cursor-pointer">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2 group-hover:text-blue-700 transition-colors duration-300">
                                    Total Books Borrowed</h3>
                                <p class="text-3xl font-bold text-blue-600 group-hover:text-blue-700 transition-colors duration-300">
                                    <?php echo count($borrowedBooks); ?>
                                    </p>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-full group-hover:bg-blue-200 transition-colors duration-300">
                                <img src="assets/icons8-books-96.png" alt="Books Icon"
                                     class="h-15 w-15 text-blue-600 group-hover:text-blue-700 transition-colors duration-300">
                            </div>
                        </div>
                        <div class="mt-4 flex items-center text-sm text-gray-500">
                            <svg class="h-4 w-4 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                      d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z"
                                      clip-rule="evenodd"/>
                        </svg>
                            <span>+2 from last month</span>
                        </div>
                    </div>

                    <!-- Due Soon Card -->
                    <div class="bg-orange-50 p-6 rounded-lg shadow-md hover:shadow-xl transform hover:scale-105 transition-all duration-300 ease-in-out hover:bg-orange-50 group cursor-pointer">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2 group-hover:text-orange-700 transition-colors duration-300">
                                    Due Soon</h3>
                                <p class="text-3xl font-bold text-orange-600 group-hover:text-orange-700 transition-colors duration-300">
                                    <?php
                                    $dueSoon = array_filter($borrowedBooks, function($book) {
                                        return $book['status'] === 'borrowed' && strtotime($book['due_date']) <= strtotime('+3 days');
                                    });
                                    echo count($dueSoon);
                                    ?></p>
                            </div>
                            <div class="bg-orange-100 p-3 rounded-full group-hover:bg-orange-200 transition-colors duration-300">
                                <img src="assets/icons8-future-100.png" alt="Due Soon Icon"
                                     class="h-15 w-15 text-orange-600 group-hover:text-orange-700 transition-colors duration-300">
                            </div>
                        </div>
                        <div class="mt-4 flex items-center text-sm text-gray-500">
                            <svg class="h-4 w-4 mr-1 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                      d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z"
                                      clip-rule="evenodd"/>
                        </svg>
                            <span>Return by this week</span>
                        </div>
                    </div>

                    <!-- Available Books Card -->
                    <div class="bg-green-50 p-6 rounded-lg shadow-md hover:shadow-xl transform hover:scale-105 transition-all duration-300 ease-in-out hover:bg-green-50 group cursor-pointer">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2 group-hover:text-green-700 transition-colors duration-300">
                                    Available Books</h3>
                                <p class="text-3xl font-bold text-green-600 group-hover:text-green-700 transition-colors duration-300">
                                    <?php echo count($books); ?></p>
                            </div>
                            <div class="bg-green-100 p-3 rounded-full group-hover:bg-green-200 transition-colors duration-300">
                                <img src="assets/books03.svg" alt="Available Books Icon"
                                     class="h-15 w-15 text-green-600 group-hover:text-green-700 transition-colors duration-300">
                            </div>
                        </div>
                        <div class="mt-4 flex items-center text-sm text-gray-500">
                            <svg class="h-4 w-4 mr-1 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                      clip-rule="evenodd"/>
                        </svg>
                            <span>Ready to borrow</span>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                    <!-- Chart Card -->
                    <div class="bg-white rounded-lg shadow-md hover:shadow-xl transform hover: transition-all duration-300 ease-in-out p-6">
                        <h3 class="text-xl font-semibold text-gray-900 mb-4">Borrowing Trends</h3>
                        <canvas id="indigoLineChart" width="400" height="200"></canvas>
                    </div>

                    <!-- Replace the calendar card section with this pie chart -->
                    <div class="bg-white rounded-lg shadow-md hover:shadow-xl transform hover: transition-all duration-300 ease-in-out p-6">
                        <h3 class="text-xl font-semibold text-gray-900 mb-4">Book Categories</h3>
                        <div class="relative">
                            <canvas id="bookCategoriesPieChart" width="400" height="300"></canvas>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-blue-500 rounded-full mr-2"></div>
                                <span class="text-gray-600">Fiction (35%)</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                                <span class="text-gray-600">Science (25%)</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></div>
                                <span class="text-gray-600">History (20%)</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-purple-500 rounded-full mr-2"></div>
                                <span class="text-gray-600">Biography (20%)</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Add this after the existing dashboard cards and before the charts section -->
                <div class="mt-8">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">Top Books</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <?php foreach (array_slice($topBooks, 0, 4) as $book): ?>
                            <div class="bg-white rounded-lg shadow-md hover:shadow-xl transform hover:scale-105 transition-all duration-300 ease-in-out overflow-hidden">
                                <div class="h-48 bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center">
                                    <?php if (!empty($book['cover_image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($book['cover_image_url']); ?>"
                                             alt="<?php echo htmlspecialchars($book['title']); ?>"
                                             class="h-40 w-28 object-cover rounded shadow-lg"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                                    <?php endif; ?>
                                    <div class="h-40 w-28 bg-blue-500 text-white rounded shadow-lg flex items-center justify-center text-xs font-medium text-center p-2"
                                         style="<?php echo !empty($book['cover_image_url']) ? 'display: none;' : ''; ?>">
                                        <?php echo htmlspecialchars($book['title']); ?>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <div class="flex items-center justify-between mb-1">
                                        <h3 class="text-lg font-semibold text-gray-900 truncate"><?php echo htmlspecialchars($book['title']); ?></h3>
                                        <span class="relative flex size-3">
                                <?php if ($book['available_copies'] > 0): ?>
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-75"></span>
                                    <span class="relative inline-flex size-3 rounded-full bg-green-500"></span>
                                <?php else: ?>
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex size-3 rounded-full bg-red-500"></span>
                                <?php endif; ?>
                            </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($book['author']); ?></p>
                                    <p class="text-xs <?php echo $book['available_copies'] > 0 ? 'text-gray-500' : 'text-red-500'; ?> mb-3">
                                        <?php echo htmlspecialchars($book['category']); ?> •
                                        <?php echo $book['available_copies'] > 0 ? 'Available (' . $book['available_copies'] . ')' : 'Borrowed'; ?>
                                    </p>
                                    <!-- In your book display sections, replace the borrow button with: -->
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                        <button type="submit" name="borrow_book"
                                                class="w-full px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors duration-200 cursor-pointer"
                                                <?php echo ($book['available_copies'] <= 0) ? 'disabled' : ''; ?>>
                                            <?php echo ($book['available_copies'] <= 0) ? 'Not Available' : 'Borrow Book'; ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>


            <!-- Profile Section - Updated -->
            <div id="profile-section" class="content-section hidden">
                <h1 class="text-3xl font-bold text-gray-900 mb-6">My Profile</h1>

                <!-- Success/Error Message -->
                <?php if (!empty($updateMessage)): ?>
                    <div class="mb-6 p-4 rounded-lg <?php echo $updateSuccess ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800'; ?>">
                        <div class="flex items-center">
                            <?php if ($updateSuccess): ?>
                                <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            <p class="font-medium">Success!</p>
                            <?php else: ?>
                                <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($updateMessage); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="relative overflow-hidden rounded-lg shadow-md">
                        <img src="pages/assets/profile02.jpg" alt="Library Reading"
                             class="w-full h-80 object-cover transition-transform duration-700 hover:scale-110">
                    </div>
                    <div class="relative overflow-hidden rounded-lg shadow-md">
                        <img src="pages/assets/profile01.jpg" alt="Digital Library"
                             class="w-full h-80 object-cover transition-transform duration-700 hover:scale-110">
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex items-center space-x-4 mb-6">
<!--                        <img src="assets/profile-placeholder.png" alt="Profile"-->
<!--                             class="h-20 w-20 rounded-full object-cover border-2 border-gray-300">-->
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-15 w-15 p-3 rounded-full object-cover border-2 border-gray-300 bg-sky-500 text-white">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>

                        <div>
                            <h2 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($userData['name']); ?></h2>
                            <p class="text-gray-600">Email: <?php echo htmlspecialchars($userData['email']); ?></p>
                        </div>
                    </div>

                    <form method="POST" action="">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <div class="flex items-center space-x-2 mb-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                                        <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd" />
                                </svg>
                                    <label class="block text-sm font-medium text-gray-700">Full Name</label>
                                </div>
                                <input type="text" name="full_name" value="<?php echo htmlspecialchars($userData['name']); ?>" required
                                       class="p-4 block w-full rounded-md border-2 border-gray-300 focus:border-blue-600 shadow-sm hover:bg-indigo-50 transition-colors duration-400">
                            </div>

                            <div>
                                <div class="flex items-center space-x-2 mb-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                                        <path d="M1.5 8.67v8.58a3 3 0 0 0 3 3h15a3 3 0 0 0 3-3V8.67l-8.928 5.493a3 3 0 0 1-3.144 0L1.5 8.67Z" />
                                        <path d="M22.5 6.908V6.75a3 3 0 0 0-3-3h-15a3 3 0 0 0-3 3v.158l9.714 5.978a1.5 1.5 0 0 0 1.572 0L22.5 6.908Z" />
                                    </svg>
                                    <label class="block text-sm font-medium text-gray-700">Email</label>
                                </div>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required
                                       class="p-4 block w-full rounded-md border-2 border-gray-300 focus:border-blue-600 shadow-sm hover:bg-indigo-50 transition-colors duration-400">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <div class="flex items-center space-x-2 mb-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                                        <path fill-rule="evenodd" d="M1.5 4.5a3 3 0 0 1 3-3h1.372c.86 0 1.61.586 1.819 1.42l1.105 4.423a1.875 1.875 0 0 1-.694 1.955l-1.293.97c-.135.101-.164.249-.126.352a11.285 11.285 0 0 0 6.697 6.697c.103.038.25.009.352-.126l.97-1.293a1.875 1.875 0 0 1 1.955-.694l4.423 1.105c.834.209 1.42.959 1.42 1.82V19.5a3 3 0 0 1-3 3h-2.25C8.552 22.5 1.5 15.448 1.5 6.75V4.5Z" clip-rule="evenodd" />
                                    </svg>
                                    <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                                </div>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>" required
                                       class="p-4 block w-full rounded-md border-2 border-gray-300 focus:border-blue-600 shadow-sm hover:bg-indigo-50 transition-colors duration-400">
                            </div>

                            <div>
                                <div class="flex items-center space-x-2 mb-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                                        <path fill-rule="evenodd" d="m11.54 22.351.07.04.028.016a.76.76 0 0 0 .723 0l.028-.015.071-.041a16.975 16.975 0 0 0 1.144-.742 19.58 19.58 0 0 0 2.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 0 0-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 0 0 2.682 2.282 16.975 16.975 0 0 0 1.145.742ZM12 13.5a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" clip-rule="evenodd" />
                                    </svg>
                                    <label class="block text-sm font-medium text-gray-700">Address</label>
                                </div>
                                <input type="text" name="address" value="<?php echo htmlspecialchars($userData['address'] ?? ''); ?>"
                                       class="p-4 block w-full rounded-md border-2 border-gray-300 focus:border-blue-600 shadow-sm hover:bg-indigo-50 transition-colors duration-400">
                            </div>
                        </div>

                        <div>
                            <button type="submit" name="update_profile"
                                    class="mt-6 px-8 py-3 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 hover:scale-105 hover:shadow-lg transition-all duration-300 cursor-pointer">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5 inline mr-2">
                                    <path d="M21.731 2.269a2.625 2.625 0 0 0-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 0 0 0-3.712ZM19.513 8.199l-3.712-3.712-8.4 8.4a5.25 5.25 0 0 0-1.32 2.214l-.8 2.685a.75.75 0 0 0 .933.933l2.685-.8a5.25 5.25 0 0 0 2.214-1.32l8.4-8.4Z" />
                            </svg>
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Borrowed Books Section -->
            <div id="borrowed-section" class="content-section hidden">
                <h1 class="text-3xl font-bold text-gray-900 mb-6">My Borrowed Books</h1>

                <div class="mb-6">
                    <img src="pages/assets/Borrowed Books.jpg" alt="Borrowed Books"
                         class="w-full h-64 object-cover rounded-lg shadow-md">
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-blue-50 p-6 rounded-lg shadow-md hover:shadow-xl transform hover:scale-105 transition-all duration-300 ease-in-out">
                        <div class="flex items-center justify-between">
                            <div >
                                <p class="text-lg font-medium text-blue-600">Currently Borrowed</p>
                                <p class="text-2xl font-bold text-blue-900">
                                    <?php echo count(array_filter($borrowedBooks, function($book) { return $book['status'] === 'borrowed'; })); ?>
                                </p>
                            </div>
                            <div class="p-3 bg-blue-100 rounded-full">
<!--                                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">-->
<!--                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>-->
<!--                                </svg>-->
                                <img src="assets/icons8-books-96.png" alt="Books Icon"
                                     class="w-10 h-10 text-blue-600">
                            </div>
                        </div>
                    </div>

                    <div class="bg-orange-50 p-6 rounded-lg shadow-md hover:shadow-xl transform hover:scale-105 transition-all duration-300 ease-in-out">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-lg font-medium text-orange-600">Due Soon</p>
                                <p class="text-2xl font-bold text-orange-900">
                                    <?php
                                    $dueSoon = array_filter($borrowedBooks, function($book) {
                                        return $book['status'] === 'borrowed' && strtotime($book['due_date']) <= strtotime('+3 days');
                                    });
                                    echo count($dueSoon);
                                    ?>
                                </p>
                            </div>
                            <div class="p-3 bg-orange-100 rounded-full">
<!--                                <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">-->
<!--                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>-->
<!--                                </svg>-->
                                <img src="assets/icons8-future-100.png" alt="Due Soon Icon"
                                     class="w-10 h-10 text-orange-600">
                            </div>
                        </div>
                    </div>

                    <div class="bg-green-50 p-6 rounded-lg shadow-md hover:shadow-xl transform hover:scale-105 transition-all duration-300 ease-in-out">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-lg font-medium text-green-600">Total Borrowed</p>
                                <p class="text-2xl font-bold text-green-900"><?php echo count($borrowedBooks); ?></p>
                            </div>
                            <div class="p-3 bg-green-100 rounded-full">
<!--                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">-->
<!--                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 00 2 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>-->
<!--                                </svg>-->
                                <img src="pages/assets/icons8-increase-100.png" alt="Total Borrowed Icon"
                                     class="w-10 h-10 text-green-600">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Borrowed Books Table -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">My Borrowed Books</h2>
                        <p class="text-sm text-gray-600 mt-1">Track your borrowed books and due dates</p>
                    </div>

                    <?php if (empty($borrowedBooks)): ?>
                        <div class="px-6 py-8 text-center">
                            <div class="text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No borrowed books</h3>
                                <p class="mt-1 text-sm text-gray-500">Start by borrowing some books from our collection.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book Details</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Borrow Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days Left</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($borrowedBooks as $borrowedBook): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($borrowedBook['title']); ?></div>
                                                    <div class="text-sm text-gray-500">by <?php echo htmlspecialchars($borrowedBook['author']); ?></div>
                                                    <div class="text-xs text-gray-400"><?php echo htmlspecialchars($borrowedBook['category']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo date('M j, Y', strtotime($borrowedBook['borrow_date'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo date('M j, Y', strtotime($borrowedBook['due_date'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $today = date('Y-m-d');
                                            $dueDate = $borrowedBook['due_date'];
                                            $daysLeft = (strtotime($dueDate) - strtotime($today)) / (60 * 60 * 24);

                                            // Determine status and animation colors based on exact status values
                                            if ($borrowedBook['status'] === 'returned') {
                                                $statusClass = 'bg-gray-100 text-gray-800';
                                                $statusText = 'Returned';
                                                $animationClass = 'bg-gray-400 opacity-75';
                                                $dotClass = 'bg-gray-500';
                                            } elseif ($borrowedBook['status'] === 'overdue') {
                                                $statusClass = 'bg-red-100 text-red-800';
                                                $statusText = 'Overdue';
                                                $animationClass = 'bg-red-400 opacity-75';
                                                $dotClass = 'bg-red-500';
                                            } elseif ($borrowedBook['status'] === 'borrowed') {
                                                if( $daysLeft < 0) {
                                                    $statusText = 'Overdue';
                                                    $statusClass = 'bg-red-100 text-red-800';
                                                    $animationClass = 'bg-red-400 opacity-75';
                                                    $dotClass = 'bg-red-500';
                                                }
                                                elseif ($daysLeft <= 3) {
                                                    $statusClass = 'bg-yellow-100 text-yellow-800';
                                                    $statusText = 'Due Soon';
                                                    $animationClass = 'bg-yellow-400 opacity-75';
                                                    $dotClass = 'bg-yellow-500';
                                                }
                                                elseif ($daysLeft == 0) {
                                                    $statusClass = 'bg-orange-100 text-orange-800';
                                                    $statusText = 'Due Today';
                                                    $animationClass = 'bg-orange-400 opacity-75';
                                                    $dotClass = 'bg-orange-500';
                                                }
                                                else {
                                                    $statusClass = 'bg-green-100 text-green-800';
                                                    $statusText = 'Borrowed';
                                                    $animationClass = 'bg-green-400 opacity-75';
                                                    $dotClass = 'bg-green-500';
                                                }
                                            } else {
                                                // Fallback for any unexpected status
                                                $statusClass = 'bg-gray-100 text-gray-800';
                                                $statusText = 'Unknown';
                                                $animationClass = 'bg-gray-400 opacity-75';
                                                $dotClass = 'bg-gray-500';
                                            }
                                            ?>
                                            <span class="px-3 py-1 inline-flex items-center text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                                <span class="relative flex size-3 mr-2">
                                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full <?php echo $animationClass; ?>"></span>
                                                     <span class="relative inline-flex size-3 rounded-full <?php echo $dotClass; ?>"></span>
                                                    </span>
                                                    <?php echo $statusText; ?>
                                                </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <?php
                                            $daysLeft = ceil((strtotime($borrowedBook['due_date']) - time()) / (60 * 60 * 24));
                                            if ($daysLeft > 0) {
                                                echo "<span class='text-green-600'>$daysLeft days left</span>";
                                            } elseif ($daysLeft == 0) {
                                                echo "<span class='text-orange-600'>Due today</span>";
                                            } else {
                                                echo "<span class='text-red-600'>" . abs($daysLeft) . " days overdue</span>";
                                            }
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <?php if ($borrowedBook['status'] === 'borrowed'): ?>
                                                <form method="POST" action="" class="inline-block">
                                                    <input type="hidden" name="book_id" value="<?php echo $borrowedBook['id']; ?>">
                                                    <input type="hidden" name="return_book" value="1">
                                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 shadow-md bg-green-500 text-white text-xs font-medium rounded-md hover:bg-lime-600 transition-all duration-200 hover:scale-105 cursor-pointer">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 mr-2">
                                                            <path fill-rule="evenodd" d="M12 5.25c1.213 0 2.415.046 3.605.135a3.256 3.256 0 0 1 3.01 3.01c.044.583.077 1.17.1 1.759L17.03 8.47a.75.75 0 1 0-1.06 1.06l3 3a.75.75 0 0 0 1.06 0l3-3a.75.75 0 0 0-1.06-1.06l-1.752 1.751c-.023-.65-.06-1.296-.108-1.939a4.756 4.756 0 0 0-4.392-4.392 49.422 49.422 0 0 0-7.436 0A4.756 4.756 0 0 0 3.89 8.282c-.017.224-.033.447-.046.672a.75.75 0 1 0 1.497.092c.013-.217.028-.434.044-.651a3.256 3.256 0 0 1 3.01-3.01c1.19-.09 2.392-.135 3.605-.135Zm-6.97 6.22a.75.75 0 0 0-1.06 0l-3 3a.75.75 0 1 0 1.06 1.06l1.752-1.751c.023.65.06 1.296.108 1.939a4.756 4.756 0 0 0 4.392 4.392 49.413 49.413 0 0 0 7.436 0 4.756 4.756 0 0 0 4.392-4.392c.017-.223.032-.447.046-.672a.75.75 0 0 0-1.497-.092c-.013.217-.028.434-.044.651a3.256 3.256 0 0 1-3.01 3.01 47.953 47.953 0 0 1-7.21 0 3.256 3.256 0 0 1-3.01-3.01 47.759 47.759 0 0 1-.1-1.759L6.97 15.53a.75.75 0 0 0 1.06-1.06l-3-3Z" clip-rule="evenodd" />
                                                        </svg>
                                                        Return Book
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-7">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z" />
                                                </svg>

                                            <?php endif; ?>
                                        </td>

                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Search Books Section -->
            <div id="search-section" class="content-section hidden">
                <h1 class="text-3xl font-bold text-gray-900 mb-6">Search Books</h1>

                <!-- Success/Error Messages -->
                <?php if (isset($borrow_success)): ?>
                    <div class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200 text-green-800">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <?php echo htmlspecialchars($borrow_success); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($borrow_errors) && !empty($borrow_errors)): ?>
                    <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800">
                        <div class="flex items-center mb-2">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            Please fix the following errors:
                        </div>
                        <ul class="list-disc list-inside">
                            <?php foreach ($borrow_errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Search Form -->
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <form method="GET" action="">
                        <input type="hidden" name="section" value="search">
                        <div class="flex space-x-4">
                            <div class="relative flex-1">
                                <input type="text" name="search" placeholder="Search by title, author, or ISBN..."
                                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                                       class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <svg class="absolute left-3 top-3.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <button type="submit" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 hover:scale-105 transition-all duration-300 ease-in-out cursor-pointer">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                Search
                            </button>
                            <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                                <a href="?section=search" class="px-6 py-3 bg-gray-500 text-white font-semibold rounded-lg hover:bg-gray-600 transition-colors">
                                    Clear
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <?php
                // Get unique categories for filtering
                $categories = [];
                foreach ($availableBooks as $book) {
                    if (!empty($book['category']) && !in_array($book['category'], $categories)) {
                        $categories[] = $book['category'];
                    }
                }
                sort($categories);

                // Determine what to display
                $booksToShow = [];
                $searchPerformed = isset($_GET['search']) && !empty($_GET['search']);

                if ($searchPerformed) {
                    $booksToShow = $searchResults;
                    $sectionTitle = "Search Results for: \"" . htmlspecialchars($_GET['search']) . "\"";
                } else {
                    $booksToShow = $availableBooks;
                    $sectionTitle = "All Available Books";
                }
                ?>

                <!-- Results Section -->
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4"><?php echo $sectionTitle; ?></h2>

                    <?php if ($searchPerformed): ?>
                        <p class="text-gray-600 mb-4">
                            Found <?php echo count($booksToShow); ?> book(s) matching your search criteria.
                        </p>
                    <?php else: ?>
                        <!-- Category Filter Buttons -->
                        <div class="flex flex-wrap gap-2 mb-6">
                            <button onclick="filterByCategory('all')" class="category-filter px-4 py-2 bg-blue-100 text-blue-800 rounded-full hover:bg-blue-200 transition-colors active" data-category="all">
                                All Categories (<?php echo count($availableBooks); ?>)
                            </button>
                            <?php foreach ($categories as $category): ?>
                                <?php
                                $categoryCount = count(array_filter($availableBooks, function($book) use ($category) {
                                    return $book['category'] === $category;
                                }));
                                ?>
                                <button onclick="filterByCategory('<?php echo htmlspecialchars($category); ?>')"
                                        class="category-filter px-4 py-2 bg-gray-100 text-gray-800 rounded-full hover:bg-gray-200 transition-colors"
                                        data-category="<?php echo htmlspecialchars($category); ?>">
                                    <?php echo htmlspecialchars($category); ?> (<?php echo $categoryCount; ?>)
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Books Display -->
                <?php if (empty($booksToShow)): ?>
                    <!-- No Books Found Message -->
                    <div class="bg-white rounded-lg shadow-md p-12 text-center">
                        <div class="max-w-md mx-auto">
                            <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.034 0-3.92.785-5.165 2.083m13.83-8.646l1.414-1.414a2 2 0 000-2.828L19.414 2.83a2 2 0 00-2.828 0L15.172 4.244M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">No Books Found</h3>
                            <?php if ($searchPerformed): ?>
                                <p class="text-gray-600 mb-4">
                                    We couldn't find any books matching "<strong><?php echo htmlspecialchars($_GET['search']); ?></strong>".
                                </p>
                                <div class="space-y-2 text-sm text-gray-500 mb-6">
                                    <p>Try searching with different keywords such as:</p>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Book title or partial title</li>
                                        <li>Author's name</li>
                                        <li>ISBN number</li>
                                        <li>Different spelling variations</li>
                                    </ul>
                                </div>
                                <div class="space-x-3">
                                    <a href="?section=search" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        Browse All Books
                                    </a>
                                    <button onclick="document.querySelector('input[name=search]').focus()" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                        Try Another Search
                                    </button>
                                </div>
                            <?php else: ?>
                                <p class="text-gray-600 mb-4">
                                    It looks like there are no books available in the library at the moment.
                                </p>
                                <p class="text-sm text-gray-500">Please check back later or contact the librarian for assistance.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Books Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6" id="books-container">
                        <?php foreach ($booksToShow as $book): ?>
                            <div class="book-card bg-white rounded-lg shadow-md hover:shadow-xl transform hover:scale-105 transition-all duration-300 ease-in-out overflow-hidden" data-category="<?php echo htmlspecialchars($book['category']); ?>">
                                <div class="h-48 bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center">
                                    <?php if (!empty($book['cover_image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($book['cover_image_url']); ?>"
                                             alt="<?php echo htmlspecialchars($book['title']); ?>"
                                             class="h-40 w-28 object-cover rounded shadow-lg"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                                    <?php endif; ?>
                                    <div class="h-40 w-28 bg-blue-500 text-white rounded shadow-lg flex items-center justify-center text-xs font-medium text-center p-2"
                                         style="<?php echo !empty($book['cover_image_url']) ? 'display: none;' : ''; ?>">
                                        <?php echo htmlspecialchars($book['title']); ?>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <div class="flex items-center justify-between mb-1">
                                        <h3 class="text-lg font-semibold text-gray-900 truncate"><?php echo htmlspecialchars($book['title']); ?></h3>
                                        <span class="relative flex size-3">
                                <?php if ($book['available_copies'] > 0): ?>
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-75"></span>
                                    <span class="relative inline-flex size-3 rounded-full bg-green-500"></span>
                                <?php else: ?>
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex size-3 rounded-full bg-red-500"></span>
                                <?php endif; ?>
                            </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($book['author']); ?></p>
                                    <p class="text-xs <?php echo $book['available_copies'] > 0 ? 'text-gray-500' : 'text-red-500'; ?> mb-3">
                                        <?php echo htmlspecialchars($book['category']); ?> •
                                        <?php echo $book['available_copies'] > 0 ? 'Available (' . $book['available_copies'] . ')' : 'Borrowed'; ?>
                                    </p>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                        <button type="submit" name="borrow_book"
                                                class="w-full px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors duration-200 cursor-pointer"
                                                <?php echo ($book['available_copies'] <= 0) ? 'disabled' : ''; ?>>
                                            <?php echo ($book['available_copies'] <= 0) ? 'Not Available' : 'Borrow Book'; ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <!-- Password Change Section -->
            <div id="change-password-section" class="content-section hidden ">
                <h1 class="text-3xl font-bold text-gray-900 mb-6">Change Password</h1>

                <!-- Success Message -->
                <?php if (isset($password_success)): ?>
                    <div class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200 text-green-800">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-sm font-medium"><?php echo htmlspecialchars($password_success); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Error Messages -->
                <?php if (isset($password_errors) && !empty($password_errors)): ?>
                    <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800">
                        <div class="flex items-center mb-2">
                            <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-sm font-medium">Please fix the following errors:</p>
                        </div>
                        <ul class="list-disc list-inside">
                            <?php foreach ($password_errors as $error): ?>
                                <li class="text-sm"><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="max-w-md mx-auto bg-white rounded-xl shadow-xl p-6">
                    <div class="text-center mb-6">
                        <img src="pages/assets/password.gif" alt="Change Password"
                             class="mx-auto size-20 mb-2">
                        <h2 class="text-xl font-bold text-gray-900">Change Password</h2>
                        <p class="text-gray-600 text-sm mt-2">Enter your current password and choose a new one</p>
                    </div>

                    <form method="POST" action="" class="space-y-4">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                            <input type="password"
                                   id="current_password"
                                   name="current_password"
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Enter your current password">
                        </div>

                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                            <input type="password"
                                   id="new_password"
                                   name="new_password"
                                   required
                                   minlength="6"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Enter new password (min 6 characters)">
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                            <input type="password"
                                   id="confirm_password"
                                   name="confirm_password"
                                   required
                                   minlength="6"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Confirm your new password">
                        </div>

                        <div class="pt-4">
                            <button type="submit"
                                    name="change_password"
                                    class="w-full bg-blue-600 text-white font-semibold py-2 px-4 rounded-md hover:bg-blue-700 transition-all duration-300 hover:scale-95 cursor-pointer">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 inline mr-2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                                </svg>
                                Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
<script>
    // This function now handles showing sections and updating nav links without relying on a click event
    function showSection(sectionName) {
        // Hide all content sections
        const sections = document.querySelectorAll('.content-section');
        sections.forEach(section => {
            section.classList.add('hidden');
        });

        // Show the selected section
        const sectionToShow = document.getElementById(sectionName + '-section');
        if (sectionToShow) {
            sectionToShow.classList.remove('hidden');
        }

        // Update active state in navigation
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.classList.remove('bg-blue-500', 'text-white');
        });

        // Find the corresponding nav link and make it active
        // Note: This selector finds the link whose onclick attribute calls showSection with the correct name.
        const activeNavLink = document.querySelector(`.nav-link[onclick*="showSection('${sectionName}')"]`);
        if (activeNavLink) {
            activeNavLink.classList.add('bg-blue-500', 'text-white');
        }
    }

    // This code runs when the page loads to show the correct section
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const section = urlParams.get('section');

        if (section) {
            // If a section is specified in the URL (e.g., after a search), show it
            showSection(section);
        } else {
            // Otherwise, default to the home section
            showSection('home');
        }
    });

    // Your existing filterByCategory and logout functions remain the same
    function filterByCategory(category) {
        const bookCards = document.querySelectorAll('.book-card');
        const filterButtons = document.querySelectorAll('.category-filter');

        // Update button states
        filterButtons.forEach(button => {
            button.classList.remove('active', 'bg-blue-100', 'text-blue-800');
            button.classList.add('bg-gray-100', 'text-gray-800');
        });

        const activeButton = document.querySelector(`[data-category="${category}"]`);
        activeButton.classList.add('active', 'bg-blue-100', 'text-blue-800');
        activeButton.classList.remove('bg-gray-100', 'text-gray-800');

        // Filter books
        bookCards.forEach(card => {
            if (category === 'all' || card.getAttribute('data-category') === category) {
                card.style.display = 'block';
                card.classList.remove('hidden');
            } else {
                card.style.display = 'none';
                card.classList.add('hidden');
            }
        });
    }

    function logout() {
        if (confirm('Are you sure you want to logout?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'logout';
            input.value = '1';

            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
    function filterByCategory(category) {
        const bookCards = document.querySelectorAll('.book-card');
        const filterButtons = document.querySelectorAll('.category-filter');

        // Update button states
        filterButtons.forEach(button => {
            button.classList.remove('active', 'bg-blue-100', 'text-blue-800');
            button.classList.add('bg-gray-100', 'text-gray-800');
        });

        const activeButton = document.querySelector(`[data-category="${category}"]`);
        if (activeButton) {
            activeButton.classList.add('active', 'bg-blue-100', 'text-blue-800');
            activeButton.classList.remove('bg-gray-100', 'text-gray-800');
        }

        // Filter books
        bookCards.forEach(card => {
            if (category === 'all' || card.getAttribute('data-category') === category) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }
</script>

<script src="main.js"></script>
</body>
</html>

