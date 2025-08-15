<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "libraryMS";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $role = $_POST['role'] ?? '';

    // Validation
    $errors = [];

    if (empty($name)) {
        $errors[] = "Name is required";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }

    if (empty($phoneNumber)) {
        $errors[] = "Phone number is required";
    }

    if (empty($address)) {
        $errors[] = "Address is required";
    }

    if (!in_array($role, ['student', 'librarian'])) {
        $errors[] = "Please select a valid role";
    }

    // Check if email already exists in users table (not students table)
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errors[] = "Email already exists";
        }
        $stmt->close();
    }

    // Insert user if no errors
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert into users table (not students table)
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, address, role, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssss", $name, $email, $hashedPassword, $phoneNumber, $address, $role);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Registration successful! Please login.";
            header("Location: login.php");
            exit();
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
        $stmt->close();
    }

    $_SESSION['errors'] = $errors;
    $_SESSION['form_data'] = $_POST;
    header("Location: register.php");
    exit();
}

// Get session data and clear it
$errors = $_SESSION['errors'] ?? [];
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['errors'], $_SESSION['form_data']);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <title>Register</title>
    <style>
        .topography-bg {
            background-image: url('pages/assets/topography.svg');
            background-repeat: repeat;
            background-size: 400px;
            opacity: 0.1;
        }
    </style>
</head>
<body>
<div class="flex flex-col lg:flex-row items-center justify-center min-h-screen bg-gray-100 gap-8 p-8 relative">
    <!-- Topography Background -->
    <div class="absolute inset-0 topography-bg"></div>

    <!-- Registration Form -->
    <div class="bg-white rounded-lg shadow-2xl p-6 sm:p-8 w-full max-w-md relative z-10">
        <div class="text-center mb-6">
            <img src="pages/assets/books01.gif" alt="Library Management" class="mx-auto h-25 w-auto" />
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900">Create a new account</h2>
            <p class="mt-1 text-sm text-gray-600">Please fill in the details below to register.</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" class="space-y-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-900">Full Name</label>
                <input id="name" type="text" name="name" required autocomplete="name"
                       value="<?php echo htmlspecialchars($formData['name'] ?? ''); ?>"
                       class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm" />
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-900">Email address</label>
                <input id="email" placeholder="example01@gmail.com" type="email" name="email" required autocomplete="email"
                       value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>"
                       class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm" />
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-900">Password</label>
                <input id="password" type="password" name="password" required autocomplete="new-password"
                       class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm" />
            </div>

            <div>
                <label for="phoneNumber" class="block text-sm font-medium text-gray-900">Phone Number</label>
                <input id="phoneNumber" placeholder="0771234567" type="number" name="phoneNumber" required autocomplete="phoneNumber"
                       value="<?php echo htmlspecialchars($formData['phone'] ?? ''); ?>"
                       class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm" />
            </div>

            <div>
                <label for="address" class="block text-sm font-medium text-gray-900">Address</label>
                <input id="address" type="text" name="address" required autocomplete="address"
                          value="<?php echo htmlspecialchars($formData['address'] ?? ''); ?>"
                       class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm" />
            </div>

            <div>
                <label for="role" class="block text-sm font-medium text-gray-900">Role</label>
                <select id="role" name="role" required class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm">
                    <option value="" disabled <?php echo empty($formData['role']) ? 'selected' : ''; ?>>Select your role</option>
                    <option value="student" <?php echo ($formData['role'] ?? '') === 'student' ? 'selected' : ''; ?>>Student</option>
                    <option value="librarian" <?php echo ($formData['role'] ?? '') === 'librarian' ? 'selected' : ''; ?>>Librarian</option>
                </select>
            </div>

            <div>
                <button type="submit" class="w-full flex justify-center rounded-md bg-indigo-600 p-4 text-sm font-semibold text-white shadow-xs hover:bg-blue-600 focus-visible:outline focus-visible:outline-indigo-600 hover:scale-95 transform duration-300">Register</button>
            </div>
        </form>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                Already have an account?
                <a href="login.php" class="text-indigo-600 hover:text-indigo-500 font-semibold">
                    Sign in
                </a>
            </p>
        </div>
    </div>

    <!-- Right Side with Topography Pattern -->
    <div class="hidden lg:block relative">
        <div class="absolute inset-0 topography-bg opacity-30 rounded-lg"></div>
        <img
                src="pages/assets/register_Image.jpg"
                alt="Register Illustration"
                class="w-xl h-auto object-contain relative z-10"
        />
    </div>
</div>

</body>
</html>

