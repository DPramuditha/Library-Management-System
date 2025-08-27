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

// Check if the form is submitted
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validate inputs
    $errors = [];
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $errors[] = "Valid email is required";
    }
    if(strlen($password) < 6){
        $errors[] = "Password must be at least 6 characters";
    }

    if(empty($errors)){
        $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows === 1){
            $user = $result->fetch_assoc();

            if(password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;

                // Redirect based on role
                switch($user['role']) {
                    case 'admin':
                        header("Location: admin_dashboard.php");
                        break;
                    case 'librarian':
                        header("Location: librarian_dashboard.php");
                        break;
                    case 'student':
                        header("Location: student_dashboard.php");
                        break;
                    default:
                        $errors[] = "Invalid user role";
                        break;
                }

                if($user['role'] === 'admin' || $user['role'] === 'librarian' || $user['role'] === 'student') {
                    exit();
                }
            } else {
                $errors[] = "Invalid email or password";
            }
        } else {
            $errors[] = "No user found with this email or account is inactive";
        }
        $stmt->close();
    }
    $_SESSION['login_errors'] = $errors;
    $_SESSION['login_email'] = $email;
}

$errors = $_SESSION['login_errors'] ?? [];
$email = $_SESSION['login_email'] ?? '';
unset($_SESSION['login_errors'], $_SESSION['login_email']);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <title>Login - Library Management System</title>
</head>
<body>
<div class="flex min-h-screen flex-col lg:flex-row">
    <!-- Left side - Image -->
    <div class="w-full lg:w-1/2 flex justify-center items-center p-4 lg:p-0">
        <img src="assets/main.svg" alt="Login Background" class="max-w-full h-auto"/>
    </div>

    <!-- Right side - Login Form -->
    <div class="flex flex-col justify-center px-4 py-8 sm:px-6 lg:px-8 w-full lg:w-1/2 bg-amber-300">
        <div class="bg-white rounded-lg shadow-2xl p-4 sm:p-6 lg:p-8 hover:shadow-lg transition-shadow duration-300 max-w-md w-full mx-auto">
            <div class="sm:mx-auto sm:w-full sm:max-w-sm">
                <img src="assets/books.gif" alt="Library Logo" class="mx-auto h-20 w-auto"/>
                <h2 class="text-center text-xl sm:text-2xl/9 font-bold tracking-tight text-gray-900">Sign in to your account</h2>
                <p class="text-center text-sm text-gray-600 mt-2">Admin, Librarian, or Student Access</p>
            </div>

            <div class="mt-6 lg:mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
                <?php if(!empty($errors)): ?>
                    <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                        <ul class="list-disc list-inside">
                            <?php foreach($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if(isset($_SESSION['success'])): ?>
                    <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                        <?php echo htmlspecialchars($_SESSION['success']); ?>
                        <?php unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm/6 font-medium text-gray-900">Email address</label>
                        <div class="mt-2">
                            <input id="email" type="email" name="email" required autocomplete="email"
                                   value="<?php echo htmlspecialchars($email); ?>"
                                   class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6"/>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <label for="password" class="block text-sm/6 font-medium text-gray-900">Password</label>
                            <div class="text-sm">
                                <a href="#" class="font-semibold text-indigo-600 hover:text-indigo-500">Forgot password?</a>
                            </div>
                        </div>
                        <div class="mt-2">
                            <input id="password" type="password" name="password" required
                                   autocomplete="current-password"
                                   class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6"/>
                        </div>
                    </div>

                    <div>
                        <button type="submit"
                                class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm/6 font-semibold text-white shadow-xs hover:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 hover:scale-95 transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 mr-2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15M12 9l3 3m0 0-3 3m3-3H2.25" />
                            </svg>

                            Sign in
                        </button>
                    </div>
                </form>
                <!-- Divider -->
                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="bg-white px-2 text-gray-500">Or continue with</span>
                        </div>
                    </div>
                </div>
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">
                        Don't have an account?
                        <a href="register.php" class="text-indigo-600 hover:text-indigo-500 font-semibold">
                            Sign up
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>