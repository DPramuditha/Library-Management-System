<?php
session_start();

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
                    <a href="#" onclick="showSection('home')"
                       class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-500 shadow-md hover:transform duration-300 hover:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                             stroke="currentColor" class="size-6 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                        </svg>

                        <span class="text-base font-medium">Home</span>
                    </a>
                    <a href="#" onclick="showSection('profile')"
                       class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-500 shadow-md hover:transform duration-300 hover:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                             stroke="currentColor" class="size-6 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                        </svg>

                        <span class="text-md font-medium">Profile</span>
                    </a>
                    <a href="#" onclick="showSection('borrowed')"
                       class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-500 shadow-md hover:transform duration-300 hover:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                             stroke="currentColor" class="size-6 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>
                        </svg>

                        <span class="text-md font-medium">Borrowed Books</span>
                    </a>
                    <a href="#" onclick="showSection('search')"
                       class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-500 shadow-md hover:transform duration-300 hover:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                             class="size-6 mr-2">
                            <path fill-rule="evenodd"
                                  d="M10.5 3.75a6.75 6.75 0 1 0 0 13.5 6.75 6.75 0 0 0 0-13.5ZM2.25 10.5a8.25 8.25 0 1 1 14.59 5.28l4.69 4.69a.75.75 0 1 1-1.06 1.06l-4.69-4.69A8.25 8.25 0 0 1 2.25 10.5Z"
                                  clip-rule="evenodd"/>
                        </svg>

                        <span class="text-md font-medium">Search Books</span>
                    </a>
                    <a href="#"
                       class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-500 shadow-md hover:transform duration-300 hover:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12a7.5 7.5 0 0 0 15 0m-15 0a7.5 7.5 0 1 1 15 0m-15 0H3m16.5 0H21m-1.5 0H12m-8.457 3.077 1.41-.513m14.095-5.13 1.41-.513M5.106 17.785l1.15-.964m11.49-9.642 1.149-.964M7.501 19.795l.75-1.3m7.5-12.99.75-1.3m-6.063 16.658.26-1.477m2.605-14.772.26-1.477m0 17.726-.26-1.477M10.698 4.614l-.26-1.477M16.5 19.794l-.75-1.299M7.5 4.205 12 12m6.894 5.785-1.149-.964M6.256 7.178l-1.15-.964m15.352 8.864-1.41-.513M4.954 9.435l-1.41-.514M12.002 12l-3.75 6.495" />
                        </svg>
                        <span class="text-md font-medium">Settings</span>
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
                <a href="#" onclick="logout()"
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
                            <path d="M4.462 19.462c.42-.419.753-.89 1-1.395.453.214.902.435 1.347.662a6.742 6.742 0 0 1-1.286 1.794.75.75 0 0 1-1.06-1.06Z" />
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
                    <button class="flex items-center space-x-2 p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                        <img src="assets/profile-placeholder.png" alt="Profile"
                             class="h-10 w-10 rounded-full object-cover"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'"/>
                        <div class="h-8 w-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-medium"
                             style="display: none;">
                            D
                        </div>
                        <div class="hidden md:block text-sm font-medium">
                            <p>Dimuthu Pramuditha</p>
                            <p class="text-xs text-gray-500">Student Email: dimuthu01@gmail.com</p>
                        </div>
                    </button>
                    <button class=" hidden lg:flex items-center px-3 py-2 rounded-lg hover:bg-gray-300 hover:transform duration-300 hover:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                            <path fill-rule="evenodd" d="M7.5 3.75A1.5 1.5 0 0 0 6 5.25v13.5a1.5 1.5 0 0 0 1.5 1.5h6a1.5 1.5 0 0 0 1.5-1.5V15a.75.75 0 0 1 1.5 0v3.75a3 3 0 0 1-3 3h-6a3 3 0 0 1-3-3V5.25a3 3 0 0 1 3-3h6a3 3 0 0 1 3 3V9A.75.75 0 0 1 15 9V5.25a1.5 1.5 0 0 0-1.5-1.5h-6Zm10.72 4.72a.75.75 0 0 1 1.06 0l3 3a.75.75 0 0 1 0 1.06l-3 3a.75.75 0 1 1-1.06-1.06l1.72-1.72H9a.75.75 0 0 1 0-1.5h10.94l-1.72-1.72a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                        </svg>
                    </button>
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
                        <img src="pages/assets/S_dashboard.jpg" alt="Library Reading"
                             class="w-full h-64 object-cover transition-transform duration-700 hover:scale-110 animate-fade-in-left">
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-600/20 to-transparent opacity-0 hover:opacity-100 transition-opacity duration-300"></div>
                    </div>

                    <div class="relative overflow-hidden rounded-lg shadow-md">
                        <img src="pages/assets/S_dashboard02.jpg" alt="Digital Library"
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
                                    12</p>
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
                                    3</p>
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
                                    1,245</p>
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
                                    <?php if ($book['available_copies'] > 0): ?>
                                        <button onclick="borrowBook(<?php echo $book['id']; ?>)"
                                                class="w-full px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                            Borrow Book
                                        </button>
                                    <?php else: ?>
                                        <button class="w-full px-4 py-2 bg-gray-400 text-white text-sm font-medium rounded-lg cursor-not-allowed" disabled>
                                            Currently Borrowed
                                        </button>
                                    <?php endif; ?>
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
                                    class="mt-6 px-8 py-3 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 hover:scale-105 hover:shadow-lg transition-all duration-300">
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
                <h1 class="text-3xl font-bold text-gray-900 mb-6">Borrowed Books</h1>
                <div>
                    <img src="pages/assets/Borrowed Books.jpg" alt="Borrowed Books"
                         class="w-full h-100 object-cover rounded-lg shadow-md mb-6">
                </div>
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
                                    <?php if ($book['available_copies'] > 0): ?>
                                        <button onclick="borrowBook(<?php echo $book['id']; ?>)"
                                                class="w-full px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                            Borrow Book
                                        </button>
                                    <?php else: ?>
                                        <button class="w-full px-4 py-2 bg-gray-400 text-white text-sm font-medium rounded-lg cursor-not-allowed" disabled>
                                            Currently Borrowed
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>


            </div>

            <!-- Search Books Section -->
            <div id="search-section" class="content-section hidden">
                <h1 class="text-3xl font-bold text-gray-900 mb-6">Search Books</h1>

                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <form method="GET" action="">
                        <input type="hidden" name="section" value="search">
                        <div class="flex space-x-4">
                            <div class="relative flex-1">
                                <input type="text" name="search"
                                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                                       placeholder="Search by title, author, or ISBN..."
                                       class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-medium text-gray-700">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6 text-gray-400">
                                        <path fill-rule="evenodd" d="M10.5 3.75a6.75 6.75 0 1 0 0 13.5 6.75 6.75 0 0 0 0-13.5ZM2.25 10.5a8.25 8.25 0 1 1 14.59 5.28l4.69 4.69a.75.75 0 1 1-1.06 1.06l-4.69-4.69A8.25 8.25 0 0 1 2.25 10.5Z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                            <button type="submit" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors hover:scale-105 transition duration-300 ease-in-out">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6 inline-block ml-3 mr-3">
                                    <path fill-rule="evenodd" d="M10.5 3.75a6.75 6.75 0 1 0 0 13.5 6.75 6.75 0 0 0 0-13.5ZM2.25 10.5a8.25 8.25 0 1 1 14.59 5.28l4.69 4.69a.75.75 0 1 1-1.06 1.06l-4.69-4.69A8.25 8.25 0 0 1 2.25 10.5Z" clip-rule="evenodd" />
                                </svg>
                                Search
                            </button>
                        </div>
                    </form>
                </div>
                <div class="mt-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <?php
                        $booksToShow = !empty($searchResults) ? $searchResults : $availableBooks;
                        foreach ($booksToShow as $book):
                            ?>
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
                                    <?php if ($book['available_copies'] > 0): ?>
                                        <button onclick="borrowBook(<?php echo $book['id']; ?>)"
                                                class="w-full px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                            Borrow Book
                                        </button>
                                    <?php else: ?>
                                        <button class="w-full px-4 py-2 bg-gray-400 text-white text-sm font-medium rounded-lg cursor-not-allowed" disabled>
                                            Currently Borrowed
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
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
            window.location.href = 'login.html';
        }
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script src="main.js"></script>
</body>
</html>