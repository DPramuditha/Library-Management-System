<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    // User is not logged in, redirect to login page
    header('Location: login.php');
    exit();
}

// Check if user has librarian role
if ($_SESSION['role'] !== 'librarian') {
    // User is not a librarian, redirect to appropriate dashboard or login
    if ($_SESSION['role'] === 'student') {
        header('Location: student_dashboard.php');
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
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$errors = [];
$success = '';
$form_data = [];

// Handle Add User form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role'])) {
    // Your existing add user code here...
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $role = $_POST['role'] ?? '';

    // Validation and insertion code remains the same...
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errors[] = "Email already exists";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, address, role, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssssss", $name, $email, $hashedPassword, $phoneNumber, $address, $role);

            if ($stmt->execute()) {
                $success = "User account created successfully!";
                $form_data = []; // Clear form data
            } else {
                $errors[] = "Failed to create user account. Please try again.";
            }
        }
        $stmt->close();
    } else {
        $form_data = $_POST;
    }
}

// Fetch all users for display
$users = [];
$usersQuery = "SELECT id, name, email, phone, address, role, created_at FROM users ORDER BY created_at DESC";
$usersResult = $conn->query($usersQuery);

if ($usersResult && $usersResult->num_rows > 0) {
    while ($row = $usersResult->fetch_assoc()) {
        $users[] = $row;
    }
}

// Update the books query to match your actual columns
$books = [];
$booksQuery = "SELECT id, title, author, category, description, publication_year, publisher, total_copies, available_copies, status FROM books ORDER BY id DESC";
$booksResult = $conn->query($booksQuery);

if ($booksResult && $booksResult->num_rows > 0) {
    while ($row = $booksResult->fetch_assoc()) {
        $books[] = $row;
    }
}

// Handle Update Book form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_book'])) {
    $book_id = trim($_POST['book_id'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $publication_year = trim($_POST['publication_year'] ?? '');
    $publisher = trim($_POST['publisher'] ?? '');
    $total_copies = trim($_POST['total_copies'] ?? '');
    $available_copies = trim($_POST['available_copies'] ?? '');
    $status = trim($_POST['status'] ?? '');

    $book_errors = [];

    // Validation
    if (empty($book_id)) {
        $book_errors[] = "Book ID is required";
    }
    if (empty($title)) {
        $book_errors[] = "Title is required";
    }
    if (empty($author)) {
        $book_errors[] = "Author is required";
    }
    if (empty($category)) {
        $book_errors[] = "Category is required";
    }
    if (empty($publication_year) || !is_numeric($publication_year)) {
        $book_errors[] = "Valid publication year is required";
    }
    if (empty($total_copies) || !is_numeric($total_copies) || $total_copies < 0) {
        $book_errors[] = "Valid total copies number is required";
    }
    if ($available_copies ==='' || !is_numeric($available_copies) || $available_copies < 0) {
        $book_errors[] = "Valid available copies number is required";
    }
    if ($available_copies > $total_copies) {
        $book_errors[] = "Available copies cannot exceed total copies";
    }
    if (empty($status) || !in_array($status, ['available', 'borrowed', 'unavailable'])) {
        $book_errors[] = "Please select a valid status (available, borrowed, or unavailable)";
    }

    if (empty($book_errors)) {
        // Check if book exists
        $stmt = $conn->prepare("SELECT id FROM books WHERE id = ?");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Update book information
            $updateStmt = $conn->prepare("UPDATE books SET title = ?, author = ?, category = ?, description = ?, publication_year = ?, publisher = ?, total_copies = ?, available_copies = ?, status = ? WHERE id = ?");
            $updateStmt->bind_param("ssssisiisi", $title, $author, $category, $description, $publication_year, $publisher, $total_copies, $available_copies, $status, $book_id);

            if ($updateStmt->execute()) {
                $book_success = "Book updated successfully!";
                // Refresh books data
                $booksQuery = "SELECT id, title, author, category, description, publication_year, publisher, total_copies, available_copies, status FROM books ORDER BY id DESC";
                $booksResult = $conn->query($booksQuery);
                $books = [];
                if ($booksResult && $booksResult->num_rows > 0) {
                    while ($row = $booksResult->fetch_assoc()) {
                        $books[] = $row;
                    }
                }
            } else {
                $book_errors[] = "Error updating book: " . $conn->error;
            }
            $updateStmt->close();
        } else {
            $book_errors[] = "Book not found";
        }
        $stmt->close();
    }
}

$userId = $_SESSION['user_id'] ?? 1;
$userQuery = $conn->prepare("SELECT * FROM users WHERE id = ?");
$userQuery->bind_param("i", $userId);
$userQuery->execute();
$userResult = $userQuery->get_result();
$userData = $userResult->fetch_assoc();
$userQuery->close();

// Fetch borrowing details with user and book information
$borrowings = [];
$borrowingQuery = "
    SELECT 
        bb.id as borrow_id,
        bb.user_id,
        bb.book_id,
        bb.borrow_date,
        bb.due_date,
        bb.return_date,
        bb.status,
        u.name as user_name,
        u.email as user_email,
        b.title as book_title,
        b.author as book_author
    FROM borrowed_books bb
    JOIN users u ON bb.user_id = u.id
    JOIN books b ON bb.book_id = b.id
    ORDER BY bb.borrow_date DESC
";

$borrowingResult = $conn->query($borrowingQuery);
if ($borrowingResult && $borrowingResult->num_rows > 0) {
    while ($row = $borrowingResult->fetch_assoc()) {
        $borrowings[] = $row;
    }
}

// Handle Return Book form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_book'])) {
    $borrow_id = trim($_POST['borrow_id'] ?? '');
    $return_errors = [];

    // Validation
    if (empty($borrow_id) || !is_numeric($borrow_id)) {
        $return_errors[] = "Invalid borrow ID";
    }

    if (empty($return_errors)) {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Check if the borrowing record exists and is currently borrowed
            $stmt = $conn->prepare("SELECT bb.*, b.id as book_id FROM borrowed_books bb JOIN books b ON bb.book_id = b.id WHERE bb.id = ? AND bb.status = 'borrowed'");
            $stmt->bind_param("i", $borrow_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $borrowing = $result->fetch_assoc();

                // Update borrowing record to returned
                $updateBorrowStmt = $conn->prepare("UPDATE borrowed_books SET status = 'returned', return_date = NOW() WHERE id = ?");
                $updateBorrowStmt->bind_param("i", $borrow_id);

                // Update book available copies
                $updateBookStmt = $conn->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?");
                $updateBookStmt->bind_param("i", $borrowing['book_id']);

                if ($updateBorrowStmt->execute() && $updateBookStmt->execute()) {
                    $conn->commit();
                    $return_success = "Book returned successfully!";

                    // Refresh borrowings data
                    $borrowings = [];
                    $borrowingQuery = "
                        SELECT
                            bb.id as borrow_id,
                            bb.user_id,
                            bb.book_id,
                            bb.borrow_date,
                            bb.due_date,
                            bb.return_date,
                            bb.status,
                            u.name as user_name,
                            u.email as user_email,
                            b.title as book_title,
                            b.author as book_author
                        FROM borrowed_books bb
                        JOIN users u ON bb.user_id = u.id
                        JOIN books b ON bb.book_id = b.id
                        ORDER BY bb.borrow_date DESC
                    ";

                    $borrowingResult = $conn->query($borrowingQuery);
                    if ($borrowingResult && $borrowingResult->num_rows > 0) {
                        while ($row = $borrowingResult->fetch_assoc()) {
                            $borrowings[] = $row;
                        }
                    }
                } else {
                    $conn->rollback();
                    $return_errors[] = "Error processing return: " . $conn->error;
                }

                $updateBorrowStmt->close();
                $updateBookStmt->close();
            } else {
                $return_errors[] = "Borrowing record not found or already returned";
            }
            $stmt->close();
        } catch (Exception $e) {
            $conn->rollback();
            $return_errors[] = "Error processing return: " . $e->getMessage();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="pages/index.css" rel="stylesheet">
    <title>Librarian Dashboard</title>
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
                    <span class="text-2xl font-bold">Librarian Portal</span>
                </div>
                <button class="lg:hidden p-2 hover:bg-white hover:bg-opacity-20 rounded-lg transition-colors">
                </button>
            </div>
            <div>
                <nav class="flex-1 px-4 py-6 space-y-2">
                    <a href="#" onclick="showSection('home')" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-500 shadow-md hover:transform duration-300 hover:scale-95">
                        <!--                        <img src="../assets/home.svg" alt="Home Icon" class="h-7 w-7 mr-2">-->
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                        </svg>

                        <span class="text-base font-medium">Home</span>
                    </a>
                    <a href="#" onclick="showSection('adduser')" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-500 shadow-md hover:transform duration-300 hover:scale-95">
                        <!--                        <img src="../assets/circle-user.svg" alt="Profile Icon" class="h-7 w-7 mr-2">-->
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>

                        <span class="text-md font-medium">Add User Account</span>
                    </a>
                    <a href="#" onclick="showSection('manageuser')" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-500 shadow-md hover:transform duration-300 hover:scale-95">
                        <!--                        <img src="../assets/books.svg" alt="Borrowed Books Icon" class="h-7 w-7 mr-2">-->
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75a4.5 4.5 0 0 1-4.884 4.484c-1.076-.091-2.264.071-2.95.904l-7.152 8.684a2.548 2.548 0 1 1-3.586-3.586l8.684-7.152c.833-.686.995-1.874.904-2.95a4.5 4.5 0 0 1 6.336-4.486l-3.276 3.276a3.004 3.004 0 0 0 2.25 2.25l3.276-3.276c.256.565.398 1.192.398 1.852Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.867 19.125h.008v.008h-.008v-.008Z" />
                        </svg>

                        <span class="text-md font-medium">Manage User Account</span>
                    </a>
                    <a href="#" onclick="showSection('addbooks')" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-500 shadow-md hover:transform duration-300 hover:scale-95">
                        <!--                        <img src="../assets/assessment.svg" alt="Search Icon" class="h-7 w-7 mr-2">-->
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <span class="text-md font-medium">Add Book</span>
                    </a>
                    <a href="#" onclick="showSection('maintainbooks')" class="flex items>-center px-3 py-2 rounded-lg hover:bg-blue-500 shadow-md hover:transform duration-300 hover:scale-95">
                        <!--                        <img src="assets/feedback.svg" alt="Feedback Icon" class="h-7 w-7 mr-2">-->
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                        </svg>
                        <span class="text-md font-medium">Maintaining Books Details</span>
                    </a>
                    <a href="#" onclick="showSection('borrowing')" class="flex items>-center px-3 py-2 rounded-lg hover:bg-blue-500 shadow-md hover:transform duration-300 hover:scale-95">
                        <!--                        <img src="assets/feedback.svg" alt="Feedback Icon" class="h-7 w-7 mr-2">-->
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                        </svg>
                        <span class="text-md font-medium">Borrowing Details</span>
                    </a>
                    <div class="mb-6 mt-4">
                        <img src="pages/assets/L_dashborad.jpg" class="w-full h-60 object-cover rounded-lg shadow-md" alt="Dashboard Image">
                    </div>
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
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col lg:ml-0">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 backdrop-blur-lg bg-opacity-90">
            <div class="flex items-center justify-between h-16 px-6">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                            <path fill-rule="evenodd" d="M12 6.75a5.25 5.25 0 0 1 6.775-5.025.75.75 0 0 1 .313 1.248l-3.32 3.319c.063.475.276.934.641 1.299.365.365.824.578 1.3.64l3.318-3.319a.75.75 0 0 1 1.248.313 5.25 5.25 0 0 1-5.472 6.756c-1.018-.086-1.87.1-2.309.634L7.344 21.3A3.298 3.298 0 1 1 2.7 16.657l8.684-7.151c.533-.44.72-1.291.634-2.309A5.342 5.342 0 0 1 12 6.75ZM4.117 19.125a.75.75 0 0 1 .75-.75h.008a.75.75 0 0 1 .75.75v.008a.75.75 0 0 1-.75.75h-.008a.75.75 0 0 1-.75-.75v-.008Z" clip-rule="evenodd" />
                            <path d="m10.076 8.64-2.201-2.2V4.874a.75.75 0 0 0-.364-.643l-3.75-2.25a.75.75 0 0 0-.916.113l-.75.75a.75.75 0 0 0-.113.916l2.25 3.75a.75.75 0 0 0 .643.364h1.564l2.062 2.062 1.575-1.297Z" />
                            <path fill-rule="evenodd" d="m12.556 17.329 4.183 4.182a3.375 3.375 0 0 0 4.773-4.773l-3.306-3.305a6.803 6.803 0 0 1-1.53.043c-.394-.034-.682-.006-.867.042a.589.589 0 0 0-.167.063l-3.086 3.748Zm3.414-1.36a.75.75 0 0 1 1.06 0l1.875 1.876a.75.75 0 1 1-1.06 1.06L15.97 17.03a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                        </svg>

                        <p class="text-lg text-gray-500">Librarian Dashboard</p>
                    </div>
                    <div class="hidden md:block relative">
                        <input type="text" placeholder="Search..." class="w-80 pl-10 pr-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"/>
                        <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Right side - Notifications and Profile -->
                <div class="flex items-center space-x-4">
                    <!-- Notification Button -->
                    <button class="relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                        <!--                        <img src="../assets/bell-notification-social-media.svg" alt="Notification Icon" class="h-6 w-6">-->
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                            <path d="M5.85 3.5a.75.75 0 0 0-1.117-1 9.719 9.719 0 0 0-2.348 4.876.75.75 0 0 0 1.479.248A8.219 8.219 0 0 1 5.85 3.5ZM19.267 2.5a.75.75 0 1 0-1.118 1 8.22 8.22 0 0 1 1.987 4.124.75.75 0 0 0 1.48-.248A9.72 9.72 0 0 0 19.266 2.5Z" />
                            <path fill-rule="evenodd" d="M12 2.25A6.75 6.75 0 0 0 5.25 9v.75a8.217 8.217 0 0 1-2.119 5.52.75.75 0 0 0 .298 1.206c1.544.57 3.16.99 4.831 1.243a3.75 3.75 0 1 0 7.48 0 24.583 24.583 0 0 0 4.83-1.244.75.75 0 0 0 .298-1.205 8.217 8.217 0 0 1-2.118-5.52V9A6.75 6.75 0 0 0 12 2.25ZM9.75 18c0-.034 0-.067.002-.1a25.05 25.05 0 0 0 4.496 0l.002.1a2.25 2.25 0 1 1-4.5 0Z" clip-rule="evenodd" />
                        </svg>

                        <!-- Notification badge -->
                        <span class="absolute -top-1 -right-1 h-4 w-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">3</span>
                    </button>
                    <!-- Profile Icon -->
                    <button class="flex items-center space-x-2 p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                        <img src="assets/profile-placeholder.png" alt="Profile" class="h-8 w-8 rounded-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'"/>
                        <div class="h-8 w-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-medium" style="display: none;">
                            D
                        </div>
                        <div class="hidden md:block text-sm font-medium">
                            <p><?php echo htmlspecialchars($userData['name']); ?></p>
                            <p class="text-xs text-gray-500">Librarian Email:  <?php echo htmlspecialchars($userData['email']); ?></p>
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
                <!-- Hero Images -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="relative overflow-hidden rounded-lg shadow-md">
                        <img src="pages/assets/L_dashboard01.jpg" alt="Library Reading"
                             class="w-full h-64 object-cover transition-transform duration-700 hover:scale-110 animate-fade-in-left">
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-600/20 to-transparent opacity-0 hover:opacity-100 transition-opacity duration-300"></div>
                    </div>
                    <div class="relative overflow-hidden rounded-lg shadow-md">
                        <img src="pages/assets/L_dashboard02.jpg" alt="Digital Library"
                             class="w-full h-64 object-cover transition-transform duration-700 hover:scale-110 animate-fade-in-right">
                        <div class="absolute inset-0 bg-gradient-to-l from-purple-600/20 to-transparent opacity-0 hover:opacity-100 transition-opacity duration-300"></div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <!-- Total Members Card -->
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 border border-blue-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 mb-2">Total Members</h3>
                                <p class="text-3xl font-bold text-blue-600"><?php echo count($users); ?></p>
                                <p class="text-sm text-blue-500 mt-1">Active library users</p>
                            </div>
                            <div class="bg-blue-300 p-3 rounded-full">
                                <img src="pages/assets/people.png" alt="Total Members Icon" class="w-12 h-12">
                            </div>
                        </div>
                    </div>

                    <!-- Available Books Card -->
                    <div class="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 border border-green-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 mb-2">Available Books</h3>
                                <p class="text-3xl font-bold text-green-600"><?php echo count($books); ?></p>
                                <p class="text-sm text-green-500 mt-1">Ready to borrow</p>
                            </div>
                            <div class="bg-green-500 p-3 rounded-full">
                                <img src="pages/assets/book.png" alt="Available Books Icon" class="w-12 h-12">
                            </div>
                        </div>
                    </div>

                    <!-- Books Due Soon Card -->
                    <div class="bg-gradient-to-br from-orange-50 to-orange-100 p-6 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 border border-orange-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 mb-2">Total Borrowing</h3>
                                <p class="text-3xl font-bold text-orange-600"><?php echo count($borrowings); ?></p>
                                <p class="text-sm text-orange-500 mt-1">Within 3 days</p>
                            </div>
                            <div class="bg-orange-500 p-3 rounded-full">
                                <img src="pages/assets/coming-soon.png" alt="Due Soon Icon" class="w-12 h-12">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Bar Chart -->
                    <div class="bg-white p-6 rounded-xl shadow-lg">
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Books Borrowed by Category</h3>
                        <div class="h-80">
                            <canvas id="booksBarChart"></canvas>
                        </div>
                    </div>

                    <!-- Area Chart -->
                    <div class="bg-white p-6 rounded-xl shadow-lg">
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Daily Library Activity</h3>
                        <div class="h-80">
                            <canvas id="activityAreaChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add User Section -->
            <div id="adduser-section" class="content-section hidden">
                <h1 class="text-3xl font-bold text-gray-900 mb-6">Add User Account</h1>

                <!-- Success Message -->
                <?php if (!empty($success)): ?>
                    <div class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200 text-green-800">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-sm font-mono font-bold"><?php echo htmlspecialchars($success); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Error Messages -->
                <?php if (!empty($errors)): ?>
                    <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800">
                        <div class="flex items-center mb-2">
                            <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-sm font-mono font-bold">Please fix the following errors:</p>
                        </div>
                        <ul class="list-disc list-inside">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="flex flex-col lg:flex-row items-center justify-center gap-8 p-8">
                    <!-- Left side - Form -->
                    <div class="bg-white rounded-lg shadow-2xl p-6 sm:p-8 w-full max-w-md">
                        <div class="text-center mb-6">
                            <img src="pages/assets/add.gif" alt="Add User" class="mx-auto h-25 w-auto">
                            <h2 class="text-xl sm:text-2xl font-bold text-gray-900">Create a new user account</h2>
                        </div>
                        <form method="POST" action="" class="space-y-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-900">Full Name</label>
                                <input id="name" type="text" name="name" value="<?php echo htmlspecialchars($form_data['name'] ?? ''); ?>" required autocomplete="name" class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm" />
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-900">Email address</label>
                                <input id="email" type="email" name="email" value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required autocomplete="email" class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm" />
                            </div>
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-900">Password</label>
                                <input id="password" type="password" name="password" required autocomplete="new-password" class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm" />
                            </div>
                            <div>
                                <label for="phoneNumber" class="block text-sm font-medium text-gray-900">Phone Number</label>
                                <input id="phoneNumber" type="tel" name="phoneNumber" value="<?php echo htmlspecialchars($form_data['phoneNumber'] ?? ''); ?>" required class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm" />
                            </div>
                            <div>
                                <label for="address" class="block text-sm font-medium text-gray-900">Address</label>
                                <textarea id="address" name="address" required class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm"><?php echo htmlspecialchars($form_data['address'] ?? ''); ?></textarea>
                            </div>
                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-900">Role</label>
                                <select id="role" name="role" required class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 outline-gray-300 focus:outline-2 focus:outline-indigo-600 sm:text-sm">
                                    <option value="">Select your role</option>
                                    <option value="student" <?php echo ($form_data['role'] ?? '') === 'student' ? 'selected' : ''; ?>>Student</option>
                                    <option value="librarian" <?php echo ($form_data['role'] ?? '') === 'librarian' ? 'selected' : ''; ?>>Librarian</option>
                                </select>
                            </div>
                            <div>
                                <button type="submit" class="w-full flex justify-center rounded-md bg-indigo-600 p-3 text-sm font-semibold text-white shadow-xs hover:bg-blue-600 focus-visible:outline focus-visible:outline-indigo-600 hover:scale-95 transition-all duration-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6 mr-2">
                                        <path d="M5.25 6.375a4.125 4.125 0 1 1 8.25 0 4.125 4.125 0 0 1-8.25 0ZM2.25 19.125a7.125 7.125 0 0 1 14.25 0v.003l-.001.119a.75.75 0 0 1-.363.63 13.067 13.067 0 0 1-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 0 1-.364-.63l-.001-.122ZM18.75 7.5a.75.75 0 0 0-1.5 0v2.25H15a.75.75 0 0 0 0 1.5h2.25v2.25a.75.75 0 0 0 1.5 0v-2.25H21a.75.75 0 0 0 0-1.5h-2.25V7.5Z" />
                                    </svg>
                                    Add New User
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Right side - Image (keep your existing image) -->
                    <div class="hidden lg:block w-full max-w-md">
                        <div class="relative">
                            <img src="pages/assets/38091993_8598876.jpg" alt="Team Collaboration" class="w-full h-auto rounded-2xl shadow-lg transform hover:scale-105 transition-transform duration-300">
                            <div class="absolute inset-0 bg-gradient-to-tr from-blue-600/10 to-purple-600/10 rounded-2xl"></div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Manage User Section -->
            <div id="manageuser-section" class="content-section hidden">
                <h1 class="text-3xl font-bold text-gray-900 mb-6">Manage User</h1>
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">User Management</h2>
                        <p class="text-sm text-gray-600 mt-1">Total Users: <?php echo count($users); ?></p>
                    </div>

                    <?php if (empty($users)): ?>
                        <div class="px-6 py-8 text-center">
                            <div class="text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No users found</h3>
                                <p class="mt-1 text-sm text-gray-500">Get started by adding a new user account.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($users as $user): ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        #<?php echo str_pad($user['id'], 3, '0', STR_PAD_LEFT); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($user['name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $user['role'] === 'librarian' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                                    <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                                </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex items-center text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    <span class="relative flex size-3 mr-2">
                                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-75"></span>
                                        <span class="relative inline-flex size-3 rounded-full bg-green-500"></span>
                                    </span>
                                    Active
                                </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                        <button  class="inline-flex items-center px-3 py-1.5 shadow-md bg-blue-600 text-white text-xs font-medium rounded-md hover:bg-blue-700 transition-all duration-200 hover:scale-105">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 mr-1">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                            </svg>
                                            Update
                                        </button>
                                        <button  class="inline-flex items-center px-3 py-1.5 shadow-md bg-red-50 text-black text-xs font-medium rounded-md hover:bg-red-700 transition-all duration-200 hover:scale-105 hover:text-white">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 mr-1">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                <div class="mt-6 p-4 rounded-lg bg-white border border-gray-200 shadow-md">
                <h1 class="text-2xl font-bold text-gray-900 mb-4">Managing User Information</h1>
                <p class="text-gray-700 mb-2">You can update user information by clicking the "Update" button next to each user. This will allow you to edit their details such as name, email, phone number, and address.</p>
                    <form action="#" method="POST" >
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">User ID</label>
                                <input type="text" name="user_id" required class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Full Name</label>
                                <input type="text" name="user_name" required class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" name="user_email" required class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Password</label>
                                <input type="password" name="user_password" required class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                                <input type="tel" name="user_phone" required class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Address</label>
                                <textarea name="user_address" required class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Role</label>
                                <select name="user_role" required class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 outline-gray-300 focus:outline-2 focus:outline-indigo-600 sm:text-sm">
                                    <option value="" disabled selected>Select Role</option>
                                    <option value="student">Student</option>
                                    <option value="librarian">Librarian</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="col-span-1 md:col-span-3 bg-blue-600 text-white font-semibold py-2 px-4 rounded-md hover:bg-indigo-700 transition-all duration-300 hover:scale-95">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 inline mr-2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                </svg>
                                Update User
                            </button>
                            <button type="reset" class="ml-4 bg-red-200 text-black font-semibold py-2 px-4 rounded-md hover:bg-red-600 transition-all duration-300 hover:scale-95 hover:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 inline mr-2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                                </svg>
                                Reset Form
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Add Books Section -->
            <div id="addbooks-section" class="content-section hidden">
                <h1 class="text-3xl font-bold text-gray-900 mb-6">Add New Books</h1>

                <div class="flex flex-col lg:flex-row items-center justify-center gap-8 p-8">
                    <!-- Left side - Form -->
                    <div class="bg-white rounded-lg shadow-2xl p-6 sm:p-8 w-full max-w-md">
                        <div class="text-center mb-6">
                            <h2 class="text-xl sm:text-2xl font-bold text-gray-900">Add New Books</h2>
                            <p class="text-gray-600 text-sm">Fill in the details below to add a new book to the library.</p>
                        </div>
                        <form action="#" method="POST" class="space-y-6">
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-900">Book Title</label>
                                <input id="title" type="text"  required autocomplete="title" class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm" />
                            </div>
                            <div>
                                <label for="author" class="block text-sm font-medium text-gray-900">Book Author</label>
                                <input id="author" type="text"  required autocomplete="author" class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm" />
                            </div>
                            <div>
                                <label for="category" class="block text-sm font-medium text-gray-900">Book Category</label>
                                <input id="category" type="text"  required autocomplete="category" class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm" />
                            </div>
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-900">Book Description</label>
                                <input id="description" type="text" name="descripton" required autocomplete="description" class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm" />
                            </div>
                            <div>
                                <label for="publicationyear" class="block text-sm font-medium text-gray-900">Book Publication Year</label>
                                <input id="publicationyear" type="number" name="pulicationyear" required autocomplete="pulicationyear" class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm" />
                            </div>
                            <div>
                                <label for="publisher" class="block text-sm font-medium text-gray-900">Book Publisher</label>
                                <input id="publisher" type="text"  required autocomplete="publisher" class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm" />
                            </div>
                            <div>
                                <label for="total_copies" class="block text-sm font-medium text-gray-900">Book Total Copies</label>
                                <input id="total_copies" type="number"  required autocomplete="total_copies" class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm" />
                            </div>
                            <div>
                                <label for="available_copies" class="block text-sm font-medium text-gray-900">Book Available Copies</label>
                                <input id="available_copies" type="number"  required autocomplete="available_copies" class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm" />
                            </div>
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-900">Books Status</label>
                                <select id="status" name="status" required class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm">
                                    <option value="" disabled selected>Select your Status</option>
                                    <option value="available">Available</option>
                                    <option value="unavailable">Unavailable</option>
                                </select>
                            </div>
                            <div>
                                <button type="submit" class="w-full flex justify-center rounded-md bg-indigo-600 p-3 text-sm font-semibold text-white shadow-xs hover:bg-blue-600 focus-visible:outline focus-visible:outline-indigo-600 hover:scale-95 transition-all duration-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6 mr-2">
                                        <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 9a.75.75 0 0 0-1.5 0v2.25H9a.75.75 0 0 0 0 1.5h2.25v2.25a.75.75 0 0 0 1.5 0v-2.25H15a.75.75 0 0 0 0-1.5h-2.25V9Z" clip-rule="evenodd" />
                                    </svg>
                                    Add New Book
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="hidden lg:block w-full max-w-md">
                        <div class="relative">
                            <img src="pages/assets/add_new_book.jpg" alt="Team Collaboration" class="w-full h-auto rounded-2xl shadow-lg transform hover:scale-105 transition-transform duration-300">
                            <div class="absolute inset-0 bg-gradient-to-tr from-blue-600/10 to-purple-600/10 rounded-2xl object-cover transition-transform duration-700 hover:scale-110"></div>
                        </div>
                    </div>
                </div>
            </div>



            <!-- Maintain Books Section -->
            <div id="maintainbooks-section" class="content-section hidden">
                <h1 class="text-3xl font-bold text-gray-900 mb-6">Maintain Books</h1>

                <!-- Delete Success/Error Messages -->
                <?php if (isset($_SESSION['delete_message'])): ?>
                    <div class="mb-6 p-4 rounded-lg <?php echo $_SESSION['delete_success'] ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800'; ?>">
                        <div class="flex items-center">
                            <?php if ($_SESSION['delete_success']): ?>
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            <?php else: ?>
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            <?php endif; ?>
                            <p class="text-sm font-mono font-bold"><?php echo htmlspecialchars($_SESSION['delete_message']); ?></p>
                        </div>
                    </div>
                    <?php
                    // Clear the session messages after displaying
                    unset($_SESSION['delete_message']);
                    unset($_SESSION['delete_success']);
                    ?>
                <?php endif; ?>

                <!-- Success Message for Book Update -->
                <?php if (isset($book_success)): ?>
                    <div class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200 text-green-800">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-sm font-mono font-bold">Book information updated successfully!</p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Error Messages for Book Update -->
                <?php if (isset($book_errors) && !empty($book_errors)): ?>
                    <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800">
                        <div class="flex items-center mb-2">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-sm font-mono font-bold">There were errors updating the book information:</p>
                        </div>
                        <ul class="list-disc list-inside">
                            <?php foreach ($book_errors as $error): ?>
                                <li class="text-sm font-mono font-bold"><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="mb-6 p-4 rounded-lg bg-white border border-gray-200 shadow-sm">
                    <h1 class="text-2xl font-semibold text-gray-900">Update Book Information</h1>
                    <p class="text-gray-600 mb-4">Use the form below to update book details. Ensure all fields are filled out correctly.</p>
                    <form method="POST" action="">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Book ID</label>
                                <input type="number" name="book_id" required
                                       value="<?php echo htmlspecialchars($_POST['book_id'] ?? ''); ?>"
                                       class="p-2 block w-full rounded-md border-2 border-gray-300 focus:border-blue-600 shadow-sm"
                                       placeholder="Enter Book ID">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Book Title</label>
                                <input type="text" name="title" required
                                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                                       class="p-2 block w-full rounded-md border-2 border-gray-300 focus:border-blue-600 shadow-sm"
                                       placeholder="Enter book title">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Author</label>
                                <input type="text" name="author" required
                                       value="<?php echo htmlspecialchars($_POST['author'] ?? ''); ?>"
                                       class="p-2 block w-full rounded-md border-2 border-gray-300 focus:border-blue-600 shadow-sm"
                                       placeholder="Enter author name">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Category</label>
                                <input type="text" name="category" required
                                       value="<?php echo htmlspecialchars($_POST['category'] ?? ''); ?>"
                                       class="p-2 block w-full rounded-md border-2 border-gray-300 focus:border-blue-600 shadow-sm"
                                       placeholder="Enter category">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Description</label>
                                <input type="text" name="description"
                                       value="<?php echo htmlspecialchars($_POST['description'] ?? ''); ?>"
                                       class="p-2 block w-full rounded-md border-2 border-gray-300 focus:border-blue-600 shadow-sm"
                                       placeholder="Enter description">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Publication Year</label>
                                <input type="number" name="publication_year" required
                                       value="<?php echo htmlspecialchars($_POST['publication_year'] ?? ''); ?>"
                                       class="p-2 block w-full rounded-md border-2 border-gray-300 focus:border-blue-600 shadow-sm"
                                       placeholder="Enter year">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Publisher</label>
                                <input type="text" name="publisher"
                                       value="<?php echo htmlspecialchars($_POST['publisher'] ?? ''); ?>"
                                       class="p-2 block w-full rounded-md border-2 border-gray-300 focus:border-blue-600 shadow-sm"
                                       placeholder="Enter publisher">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Total Copies</label>
                                <input type="number" name="total_copies" required min="0"
                                       value="<?php echo htmlspecialchars($_POST['total_copies'] ?? ''); ?>"
                                       class="p-2 block w-full rounded-md border-2 border-gray-300 focus:border-blue-600 shadow-sm"
                                       placeholder="Enter total copies">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Available Copies</label>
                                <input type="number" name="available_copies" required min="0"
                                       value="<?php echo htmlspecialchars($_POST['available_copies'] ?? ''); ?>"
                                       class="p-2 block w-full rounded-md border-2 border-gray-300 focus:border-blue-600 shadow-sm"
                                       placeholder="Enter available copies">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Status</label>
                                <select name="status" required class="p-2 block w-full rounded-md border-2 border-gray-300 focus:border-blue-600 shadow-sm">
                                    <option value="">Select Status</option>
                                    <option value="available" <?php echo (isset($_POST['status']) && $_POST['status'] === 'available') ? 'selected' : ''; ?>>Available</option>
                                    <option value="borrowed" <?php echo (isset($_POST['status']) && $_POST['status'] === 'borrowed') ? 'selected' : ''; ?>>Borrowed</option>
                                    <option value="unavailable" <?php echo (isset($_POST['status']) && $_POST['status'] === 'unavailable') ? 'selected' : ''; ?>>Unavailable</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="update_book" class="col-span-1 md:col-span-3 bg-blue-600 text-white font-semibold py-2 px-4 rounded-md hover:bg-indigo-700 transition-all duration-300 hover:scale-95">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 inline mr-2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                            </svg>
                            Update Book Information
                        </button>
                    </form>
                </div>

                <!-- Books Display Table -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Book Maintenance</h2>
                        <p class="text-sm text-gray-600 mt-1">Total Books: <?php echo count($books); ?></p>
                    </div>

                    <?php if (empty($books)): ?>
                        <div class="px-6 py-8 text-center">
                            <div class="text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No books found</h3>
                                <p class="mt-1 text-sm text-gray-500">Get started by adding a new book to the library.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Copies</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($books as $book): ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?php echo str_pad($book['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($book['title']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($book['author']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($book['category']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $book['available_copies'] . '/' . $book['total_copies']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $statusClass = '';
                                            $statusText = '';
                                            $animationClass = '';

                                            switch($book['status']) {
                                                case 'available':
                                                    $statusClass = 'bg-green-100 text-green-800';
                                                    $statusText = 'Available';
                                                    $animationClass = 'bg-green-400 opacity-75';
                                                    $dotClass = 'bg-green-500';
                                                    break;
                                                case 'borrowed':
                                                    $statusClass = 'bg-yellow-100 text-yellow-800';
                                                    $statusText = 'Borrowed';
                                                    $animationClass = 'bg-yellow-400 opacity-75';
                                                    $dotClass = 'bg-yellow-500';
                                                    break;
                                                case 'unavailable':
                                                    $statusClass = 'bg-red-100 text-red-800';
                                                    $statusText = 'Unavailable';
                                                    $animationClass = 'bg-red-400 opacity-75';
                                                    $dotClass = 'bg-red-500';
                                                    break;
                                                default:
                                                    $statusClass = 'bg-gray-100 text-gray-800';
                                                    $statusText = 'Unknown';
                                                    $animationClass = 'bg-gray-400 opacity-75';
                                                    $dotClass = 'bg-gray-500';
                                            }
                                            ?>
                                            <span class="px-2 inline-flex items-center text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                                <span class="relative flex size-3 mr-2">
                                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full <?php echo $animationClass; ?>"></span>
                                                <span class="relative inline-flex size-3 rounded-full <?php echo $dotClass; ?>"></span>
                                            </span>
                                            <?php echo $statusText; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            <button onclick="fillUpdateForm(<?php echo htmlspecialchars(json_encode($book)); ?>)" class="inline-flex items-center px-3 py-1.5 shadow-md bg-blue-600 text-white text-xs font-medium rounded-md hover:bg-blue-700 transition-all duration-200 hover:scale-105">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                </svg>
                                                Update
                                            </button>
                                            <button onclick="deleteBook(<?php echo $book['id']; ?>)"
                                                    class="inline-flex items-center px-3 py-1.5 shadow-md bg-red-50 text-black text-xs font-medium rounded-md hover:bg-red-700 transition-all duration-200 hover:scale-105 hover:text-white">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 mr-1">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>


            <!-- Borrowing Section -->
            <div id="borrowing-section" class="content-section hidden">
                <h1 class="text-3xl font-bold text-gray-900 mb-6">Books Borrowing Details</h1>

                <!-- Add this right after the borrowing section title -->
                <!-- Success Message for Book Return -->
                <?php if (isset($return_success)): ?>
                    <div class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200 text-green-800">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-sm font-mono font-bold"><?php echo htmlspecialchars($return_success); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Error Messages for Book Return -->
                <?php if (isset($return_errors) && !empty($return_errors)): ?>
                    <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800">
                        <div class="flex items-center mb-2">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-sm font-mono font-bold">Error processing return:</p>
                        </div>
                        <ul class="list-disc list-inside">
                            <?php foreach ($return_errors as $error): ?>
                                <li class="text-sm font-mono font-bold"><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Borrowing Details</h2>
                        <p class="text-sm text-gray-600 mt-1">Total Borrowing Records: <?php echo count($borrowings); ?></p>
                    </div>

                    <?php if (empty($borrowings)): ?>
                        <div class="px-6 py-8 text-center">
                            <div class="text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No borrowing records</h3>
                                <p class="mt-1 text-sm text-gray-500">No books have been borrowed yet.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Borrow ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User Details</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book Details</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Borrow Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Return Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($borrowings as $borrowing): ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            #<?php echo str_pad($borrowing['borrow_id'], 3, '0', STR_PAD_LEFT); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($borrowing['user_name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($borrowing['user_email']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($borrowing['book_title']); ?></div>
                                            <div class="text-sm text-gray-500">by <?php echo htmlspecialchars($borrowing['book_author']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M d, Y', strtotime($borrowing['borrow_date'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php
                                            $due_date = date('M d, Y', strtotime($borrowing['due_date']));
                                            $is_overdue = strtotime($borrowing['due_date']) < time() && $borrowing['status'] == 'borrowed';
                                            ?>
                                            <span class="<?php echo $is_overdue ? 'text-red-600 font-semibold' : ''; ?>">
                                        <?php echo $due_date; ?>
                                    </span>
                                            <?php if ($is_overdue): ?>
                                                <div class="text-xs text-red-500">OVERDUE</div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo $borrowing['return_date'] ? date('M d, Y', strtotime($borrowing['return_date'])) : '-'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $statusClass = '';
                                            $statusText = '';
                                            $animationClass = '';
                                            $dotClass = '';

                                            switch($borrowing['status']) {
                                                case 'borrowed':
                                                    if (strtotime($borrowing['due_date']) < time()) {
                                                        $statusClass = 'bg-red-100 text-red-800';
                                                        $statusText = 'Overdue';
                                                        $animationClass = 'bg-red-400 opacity-75';
                                                        $dotClass = 'bg-red-500';
                                                    } else {
                                                        $statusClass = 'bg-yellow-100 text-yellow-800';
                                                        $statusText = 'Borrowed';
                                                        $animationClass = 'bg-yellow-400 opacity-75';
                                                        $dotClass = 'bg-yellow-500';
                                                    }
                                                    break;
                                                case 'returned':
                                                    $statusClass = 'bg-green-100 text-green-800';
                                                    $statusText = 'Returned';
                                                    $animationClass = 'bg-green-400 opacity-75';
                                                    $dotClass = 'bg-green-500';
                                                    break;
                                                default:
                                                    $statusClass = 'bg-gray-100 text-gray-800';
                                                    $statusText = 'Unknown';
                                                    $animationClass = 'bg-gray-400 opacity-75';
                                                    $dotClass = 'bg-gray-500';
                                            }
                                            ?>
                                            <span class="px-2 inline-flex items-center text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                        <span class="relative flex size-3 mr-2">
                                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full <?php echo $animationClass; ?>"></span>
                                            <span class="relative inline-flex size-3 rounded-full <?php echo $dotClass; ?>"></span>
                                        </span>
                                        <?php echo $statusText; ?>
                                    </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            <!-- In the borrowing section table, replace the Mark Returned button with this: -->
                                            <?php if ($borrowing['status'] == 'borrowed'): ?>
                                                <form method="POST" action="" style="display: inline;">
                                                    <input type="hidden" name="borrow_id" value="<?php echo $borrowing['borrow_id']; ?>">
                                                    <button type="submit" name="return_book"
                                                            onclick="return confirm('Are you sure you want to mark this book as returned?')"
                                                            class="inline-flex items-center px-3 py-1.5 shadow-md bg-green-500 text-white text-xs font-medium rounded-md hover:bg-lime-600 transition-all duration-200 hover:scale-105">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 mr-1">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                                                        </svg>
                                                        Mark Returned
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-500">
                                                Already Returned
                                            </span>
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
        </main>
    </div>
</div>
<script>
    function showSection(sectionName) {
        // Hide all content sections
        const sections = document.querySelectorAll('.content-section');
        sections.forEach(section => {
            section.classList.add('hidden');
        });

        // Show selected section
        document.getElementById(sectionName + '-section').classList.remove('hidden');

        // Update active navigation item
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.classList.remove('bg-blue-500', 'text-white');
        });

        // Add active class to clicked nav item
        event.target.closest('.nav-link').classList.add('bg-blue-500', 'text-white');
    }

    function logout() {
        if (confirm('Are you sure you want to logout?')) {
            // Create a form to handle logout properly
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
<script>
    // Initialize charts when the page loads
    window.addEventListener('DOMContentLoaded', function() {
        initializeCharts();
    });

    function initializeCharts() {
        // Bar Chart - Books Borrowed by Category
        const barCtx = document.getElementById('booksBarChart').getContext('2d');
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: ['Fiction', 'Science', 'History', 'Technology', 'Biography', 'Arts'],
                datasets: [{
                    label: 'Books Borrowed',
                    data: [45, 32, 28, 38, 22, 15],
                    backgroundColor: [
                        '#4F46E5',
                        '#4F46E5',
                        '#4F46E5',
                        '#4F46E5',
                        '#4F46E5',
                        '#4F46E5'
                    ],
                    borderColor: [
                        '#4F46E5',
                        '#4F46E5',
                        '#4F46E5',
                        '#4F46E5',
                        '#4F46E5',
                        '#4F46E5'
                    ],
                    borderWidth: 1,
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#F3F4F6'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Area Chart - Daily Library Activity
        const areaCtx = document.getElementById('activityAreaChart').getContext('2d');
        new Chart(areaCtx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Books Borrowed',
                    data: [12, 19, 15, 25, 22, 18, 8],
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#3B82F6',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6
                }, {
                    label: 'Books Returned',
                    data: [8, 15, 12, 20, 18, 15, 6],
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#10B981',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#F3F4F6'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
</script>
<script src="main.js"></script>
</body>
</html>

