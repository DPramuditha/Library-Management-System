<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    // User is not logged in, redirect to login page
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


$userId = $_SESSION['user_id'] ?? 1;
$userQuery = $conn->prepare("SELECT * FROM users WHERE id = ?");
$userQuery->bind_param("i", $userId);
$userQuery->execute();
$userResult = $userQuery->get_result();
$userData = $userResult->fetch_assoc();
$userQuery->close();

// Fetch borrowed books
$users = [];
$usersQuery = "SELECT id, name, email, phone, address, role, created_at FROM users ORDER BY created_at DESC";
$usersResult = $conn->query($usersQuery);

if ($usersResult && $usersResult->num_rows > 0) {
    while ($row = $usersResult->fetch_assoc()) {
        $users[] = $row;
    }
}

$books = [];
$booksQuery = "SELECT id, title, author, category, description, publication_year, publisher, total_copies, available_copies, status FROM books ORDER BY id DESC";
$booksResult = $conn->query($booksQuery);

if ($booksResult && $booksResult->num_rows > 0) {
    while ($row = $booksResult->fetch_assoc()) {
        $books[] = $row;
    }
}
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


// Total librarians
$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'librarian'");
$stats['total_librarians'] = $result->fetch_assoc()['total'];

// Total students
$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
$stats['total_students'] = $result->fetch_assoc()['total'];

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
                    <span class="text-2xl font-bold">Admin Portal</span>
                </div>
                <button class="lg:hidden p-2 hover:bg-white hover:bg-opacity-20 rounded-lg transition-colors">
                </button>
            </div>
            <div>
                <nav class="flex-1 px-4 py-6 space-y-2">
                    <a href="#" onclick="showSection('home')"
                       class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-500 shadow-md hover:transform duration-300 hover:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                             stroke="currentColor" class="size-6 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
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

                    <a href="#" onclick="showSection('maintain')"
                       class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-500 shadow-md hover:transform duration-300 hover:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75a4.5 4.5 0 0 1-4.884 4.484c-1.076-.091-2.264.071-2.95.904l-7.152 8.684a2.548 2.548 0 1 1-3.586-3.586l8.684-7.152c.833-.686.995-1.874.904-2.95a4.5 4.5 0 0 1 6.336-4.486l-3.276 3.276a3.004 3.004 0 0 0 2.25 2.25l3.276-3.276c.256.565.398 1.192.398 1.852Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.867 19.125h.008v.008h-.008v-.008Z" />
                        </svg>


                        <span class="text-md font-medium">Maintain User Account</span>
                    </a>
                </nav>
            </div>
            <div class="p-4">
                <div class="text-white rounded-lg shadow-md p-4">
                    <div>
                        <img src="pages/assets/A_dashboard02.jpg" alt="Reading Icon"
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
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                        </svg>

                        <p class="text-lg text-gray-500">Dashboard Overview</p>
                    </div>
                    <div class="hidden md:block relative">
                        <input type="text" placeholder="Search..."
                               class="w-80 pl-10 pr-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"/>
                        <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                <!-- Right side - Notifications and Profile -->
                <div class="flex items-center space-x-4">
                    <!-- Notification Button -->
                    <button class="relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                        <!--                        <img src="assets/bell-notification-social-media.svg" alt="Notification Icon" class="h-6 w-6">-->
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                            <path d="M5.85 3.5a.75.75 0 0 0-1.117-1 9.719 9.719 0 0 0-2.348 4.876.75.75 0 0 0 1.479.248A8.219 8.219 0 0 1 5.85 3.5ZM19.267 2.5a.75.75 0 1 0-1.118 1 8.22 8.22 0 0 1 1.987 4.124.75.75 0 0 0 1.48-.248A9.72 9.72 0 0 0 19.266 2.5Z" />
                            <path fill-rule="evenodd" d="M12 2.25A6.75 6.75 0 0 0 5.25 9v.75a8.217 8.217 0 0 1-2.119 5.52.75.75 0 0 0 .298 1.206c1.544.57 3.16.99 4.831 1.243a3.75 3.75 0 1 0 7.48 0 24.583 24.583 0 0 0 4.83-1.244.75.75 0 0 0 .298-1.205 8.217 8.217 0 0 1-2.118-5.52V9A6.75 6.75 0 0 0 12 2.25ZM9.75 18c0-.034 0-.067.002-.1a25.05 25.05 0 0 0 4.496 0l.002.1a2.25 2.25 0 1 1-4.5 0Z" clip-rule="evenodd" />
                        </svg>

                        <!-- Notification badge -->
                        <span class="absolute -top-1 -right-1 h-4 w-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">3</span>
                    </button>
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
                            <p class="text-xs text-gray-500">Admin Email: <?php echo htmlspecialchars($userData['email']); ?></p>
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
                        <img src="pages/assets/A_dashboard01.jpg" alt="Library Reading"
                             class="w-full h-64 object-cover transition-transform duration-700 hover:scale-110 animate-fade-in-left">
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-600/20 to-transparent opacity-0 hover:opacity-100 transition-opacity duration-300"></div>
                    </div>

                    <div class="relative overflow-hidden rounded-lg shadow-md">
                        <img src="pages/assets/A_dashboard.jpg" alt="Digital Library"
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
                <!-- Second Row of Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- Total Librarians Card - Indigo Theme -->
                    <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 p-6 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 border border-indigo-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 mb-2">Total Librarians</h3>
                                <p class="text-3xl font-bold text-indigo-600"><?php echo $stats['total_librarians']; ?></p>
                                <p class="text-sm text-indigo-500 mt-1">Library staff members</p>
                            </div>
                            <div class="bg-indigo-50 p-3 rounded-full">
                                <img src="pages/assets/total_librarians.png" alt="Librarians Icon" class="w-12 h-12">
                            </div>
                        </div>
                    </div>

                    <!-- Total Students Card - Teal Theme -->
                    <div class="bg-gradient-to-br from-teal-50 to-teal-100 p-6 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 border border-teal-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 mb-2">Total Students</h3>
                                <p class="text-3xl font-bold text-teal-600"><?php echo $stats['total_students']; ?></p>
                                <p class="text-sm text-teal-500 mt-1">Registered students</p>
                            </div>
                            <div class="bg-teal-500 p-3 rounded-full">
                                <img src="pages/assets/total_students.png" alt="Students Icon" class="w-12 h-12">
                            </div>
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
                                <button type="submit" class="w-full flex justify-center rounded-md bg-indigo-600 p-3 text-sm font-semibold text-white shadow-xs hover:bg-blue-600 focus-visible:outline focus-visible:outline-indigo-600 hover:scale-95 transition-all duration-300 cursor-pointer">
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

            <!-- Maintain User Section -->
            <div id="maintain-section" class="content-section hidden">
                <h1 class="text-3xl font-bold text-gray-900 mb-6">Manage User Account</h1>

                <!-- Success Message for User Update -->
                <?php if (isset($user_success)): ?>
                    <div class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200 text-green-800">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-sm font-mono font-bold"><?php echo htmlspecialchars($user_success); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Error Messages for User Update -->
                <?php if (isset($user_errors) && !empty($user_errors)): ?>
                    <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800">
                        <div class="flex items-center mb-2">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-sm font-mono font-bold">There were errors updating the user information:</p>
                        </div>
                        <ul class="list-disc list-inside">
                            <?php foreach ($user_errors as $error): ?>
                                <li class="text-sm font-mono font-bold"><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">User Management</h2>
                        <p class="text-sm text-gray-600 mt-1">Total Users: <?php echo count($users); ?></p>
                    </div>

                    <?php if (empty($users)): ?>
                        <div class="px-6 py-8 text-center">
                            <div class="text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
<!--                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>-->
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($users as $user): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($user['phone']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($user['address']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $user['role'] === 'librarian' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                                <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                            </span>
                                    </td>
<!--                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">--><?php //echo date('M j, Y', strtotime($user['created_at'])); ?><!--</td>-->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="fillUpdateForm(<?php echo $user['id']; ?>, '<?php echo addslashes($user['name']); ?>', '<?php echo addslashes($user['email']); ?>', '<?php echo addslashes($user['phone']); ?>', '<?php echo addslashes($user['address']); ?>', '<?php echo $user['role']; ?>')"
                                                <?php echo $user['role'] === 'admin' ? 'disabled' : ''; ?>

                                                class=" <?php echo $user['role'] ==='admin' ? 'inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md  cursor-not-allowed opacity-50' : 'inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-300 hover:scale-95'?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 mr-1">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                            </svg>
                                            Update
                                        </button>
                                        <button
                                                class="<?php echo $user['role'] ==='admin'? 'inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md  cursor-not-allowed opacity-50' : 'inline-flex items-center px-3 py-1.5 shadow-md bg-red-50 text-black text-xs font-medium rounded-md hover:bg-red-700 transition-all duration-200 hover:scale-105 hover:text-white cursor-pointer'?>">
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
                    <?php endif; ?>
                </div>

                <div class="mt-6 p-4 rounded-lg bg-white border border-gray-200 shadow-md">
                    <h1 class="text-2xl font-bold text-gray-900 mb-4">Update User Information</h1>
                    <p class="text-gray-700 mb-4">Fill in the form below to update user information. Click on a user's "Update" button to auto-fill the form.</p>
                    <form method="POST" action="">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">User ID</label>
                                <input type="number" name="user_id" id="update_user_id" required
                                       class="p-2 block w-full rounded-md border-2 border-gray-300 focus:border-blue-600 shadow-sm"
                                       placeholder="Enter User ID">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Name</label>
                                <input type="text" name="name" id="update_name" required
                                       class="p-2 block w-full rounded-md border-2 border-gray-300 focus:border-blue-600 shadow-sm"
                                       placeholder="Enter Name">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" name="email" id="update_email" required
                                       class="p-2 block w-full rounded-md border-2 border-gray-300 focus:border-blue-600 shadow-sm"
                                       placeholder="Enter Email">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Phone</label>
                                <input type="text" name="phone" id="update_phone" required
                                       class="p-2 block w-full rounded-md border-2 border-gray-300 focus:border-blue-600 shadow-sm"
                                       placeholder="Enter Phone Number">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Address</label>
                                <input type="text" name="address" id="update_address" required
                                       class="p-2 block w-full rounded-md border-2 border-gray-300 focus:border-blue-600 shadow-sm"
                                       placeholder="Enter Address">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Role</label>
                                <select name="role" id="update_role" required class="p-2 block w-full rounded-md border-2 border-gray-300 focus:border-blue-600 shadow-sm">
                                    <option value="">Select Role</option>
                                    <option value="student">Student</option>
                                    <option value="librarian">Librarian</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" name="update_user" class="bg-blue-600 text-white font-semibold py-2 px-4 rounded-md hover:bg-indigo-700 transition-all duration-300 hover:scale-95 cursor-pointer">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 inline mr-2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                </svg>
                                Update User Information
                            </button>
                            <button type="reset" onclick="clearUpdateForm()" class="ml-4 bg-red-200 text-black font-semibold py-2 px-4 rounded-md hover:bg-red-600 transition-all duration-300 hover:scale-95 hover:text-white cursor-pointer">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 inline mr-2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                Clear Form
                            </button>
                        </div>
                    </form>
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
                        <img src="assets/profile-placeholder.png" alt="Profile"
                             class="h-20 w-20 rounded-full object-cover border-2 border-gray-300">
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

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script src="main.js"></script>
<script src="admin.js"></script>
</body>
</html>

